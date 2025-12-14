<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class CleanupCriteriaService
{
    protected array $config;

    public function __construct()
    {
        $this->loadConfiguration();
    }

    /**
     * Load configuration from config file.
     */
    public function loadConfiguration(): void
    {
        $this->config = Config::get('appointment_cleanup', []);
    }

    /**
     * Get all cleanup criteria.
     */
    public function getCleanupCriteria(): array
    {
        return [
            'draft_appointments' => [
                'enabled' => $this->config['actions']['cleanup_draft_appointments'] ?? true,
                'timeout_minutes' => $this->config['criteria']['draft_timeout_minutes'] ?? 30,
                'description' => 'Draft appointments that exceed timeout period'
            ],
            'payment_pending_appointments' => [
                'enabled' => $this->config['actions']['cleanup_payment_pending'] ?? true,
                'timeout_minutes' => $this->config['criteria']['payment_pending_timeout_minutes'] ?? 15,
                'description' => 'Payment pending appointments that exceed timeout period'
            ],
            'failed_payment_appointments' => [
                'enabled' => $this->config['actions']['cleanup_failed_payments'] ?? true,
                'timeout_hours' => $this->config['criteria']['failed_payment_cleanup_hours'] ?? 24,
                'description' => 'Appointments with failed payments older than timeout'
            ],
            'old_cancelled_appointments' => [
                'enabled' => $this->config['actions']['cleanup_old_cancelled'] ?? true,
                'timeout_days' => $this->config['criteria']['cancelled_cleanup_days'] ?? 7,
                'description' => 'Old cancelled appointments beyond retention period'
            ],
            'expired_time_slots' => [
                'enabled' => $this->config['actions']['cleanup_expired_timeslots'] ?? true,
                'timeout_minutes' => $this->config['criteria']['expired_timeslot_cleanup_minutes'] ?? 5,
                'description' => 'Expired temporarily blocked time slots'
            ]
        ];
    }

    /**
     * Check if an appointment should be deleted based on all criteria.
     */
    public function shouldDeleteAppointment(Appointment $appointment): array
    {
        $criteria = $this->getCleanupCriteria();
        $now = now();

        // Check draft appointments
        if ($criteria['draft_appointments']['enabled'] && 
            $appointment->status === Appointment::STATUS_DRAFT) {
            $timeoutMinutes = $criteria['draft_appointments']['timeout_minutes'];
            $cutoffTime = $now->copy()->subMinutes($timeoutMinutes);
            
            if ($appointment->created_at <= $cutoffTime) {
                return [
                    'should_delete' => true,
                    'reason' => 'draft_timeout',
                    'description' => "Draft appointment exceeded {$timeoutMinutes} minute timeout",
                    'timeout_minutes' => $timeoutMinutes,
                    'age_minutes' => $now->diffInMinutes($appointment->created_at)
                ];
            }
        }

        // Check payment pending appointments
        if ($criteria['payment_pending_appointments']['enabled'] && 
            $appointment->status === Appointment::STATUS_PAYMENT_PENDING) {
            $timeoutMinutes = $criteria['payment_pending_appointments']['timeout_minutes'];
            $cutoffTime = $now->copy()->subMinutes($timeoutMinutes);
            
            if ($appointment->created_at <= $cutoffTime) {
                return [
                    'should_delete' => true,
                    'reason' => 'payment_pending_timeout',
                    'description' => "Payment pending appointment exceeded {$timeoutMinutes} minute timeout",
                    'timeout_minutes' => $timeoutMinutes,
                    'age_minutes' => $now->diffInMinutes($appointment->created_at)
                ];
            }
        }

        // Check failed payment appointments
        if ($criteria['failed_payment_appointments']['enabled'] && 
            $appointment->payment_status === Appointment::PAYMENT_STATUS_FAILED) {
            $timeoutHours = $criteria['failed_payment_appointments']['timeout_hours'];
            $cutoffTime = $now->copy()->subHours($timeoutHours);
            
            if ($appointment->updated_at <= $cutoffTime) {
                return [
                    'should_delete' => true,
                    'reason' => 'failed_payment_cleanup',
                    'description' => "Failed payment appointment older than {$timeoutHours} hours",
                    'timeout_hours' => $timeoutHours,
                    'age_hours' => $now->diffInHours($appointment->updated_at)
                ];
            }
        }

        // Check old cancelled appointments
        if ($criteria['old_cancelled_appointments']['enabled'] && 
            $appointment->status === Appointment::STATUS_CANCELLED) {
            $timeoutDays = $criteria['old_cancelled_appointments']['timeout_days'];
            $cutoffTime = $now->copy()->subDays($timeoutDays);
            
            if ($appointment->updated_at <= $cutoffTime) {
                return [
                    'should_delete' => true,
                    'reason' => 'old_cancelled_cleanup',
                    'description' => "Cancelled appointment older than {$timeoutDays} days",
                    'timeout_days' => $timeoutDays,
                    'age_days' => $now->diffInDays($appointment->updated_at)
                ];
            }
        }

        return [
            'should_delete' => false,
            'reason' => 'no_criteria_met',
            'description' => 'Appointment does not meet any deletion criteria'
        ];
    }

    /**
     * Get appointments that should be deleted based on criteria.
     */
    public function getAppointmentsForDeletion(int $limit = null): Collection
    {
        $criteria = $this->getCleanupCriteria();
        $now = now();
        $appointments = collect();

        $limit = $limit ?? ($this->config['limits']['max_deletions_per_run'] ?? 100);

        // Get draft appointments
        if ($criteria['draft_appointments']['enabled']) {
            $timeoutMinutes = $criteria['draft_appointments']['timeout_minutes'];
            $cutoffTime = $now->copy()->subMinutes($timeoutMinutes);
            
            $draftAppointments = Appointment::where('status', Appointment::STATUS_DRAFT)
                ->where('created_at', '<=', $cutoffTime)
                ->limit($limit - $appointments->count())
                ->get()
                ->map(function ($appointment) use ($timeoutMinutes, $now) {
                    $appointment->deletion_reason = [
                        'reason' => 'draft_timeout',
                        'description' => "Draft appointment exceeded {$timeoutMinutes} minute timeout",
                        'timeout_minutes' => $timeoutMinutes,
                        'age_minutes' => $now->diffInMinutes($appointment->created_at)
                    ];
                    return $appointment;
                });
            
            $appointments = $appointments->concat($draftAppointments);
        }

        // Get payment pending appointments
        if ($criteria['payment_pending_appointments']['enabled'] && $appointments->count() < $limit) {
            $timeoutMinutes = $criteria['payment_pending_appointments']['timeout_minutes'];
            $cutoffTime = $now->copy()->subMinutes($timeoutMinutes);
            
            $paymentPendingAppointments = Appointment::where('status', Appointment::STATUS_PAYMENT_PENDING)
                ->where('created_at', '<=', $cutoffTime)
                ->limit($limit - $appointments->count())
                ->get()
                ->map(function ($appointment) use ($timeoutMinutes, $now) {
                    $appointment->deletion_reason = [
                        'reason' => 'payment_pending_timeout',
                        'description' => "Payment pending appointment exceeded {$timeoutMinutes} minute timeout",
                        'timeout_minutes' => $timeoutMinutes,
                        'age_minutes' => $now->diffInMinutes($appointment->created_at)
                    ];
                    return $appointment;
                });
            
            $appointments = $appointments->concat($paymentPendingAppointments);
        }

        // Get failed payment appointments
        if ($criteria['failed_payment_appointments']['enabled'] && $appointments->count() < $limit) {
            $timeoutHours = $criteria['failed_payment_appointments']['timeout_hours'];
            $cutoffTime = $now->copy()->subHours($timeoutHours);
            
            $failedPaymentAppointments = Appointment::where('payment_status', Appointment::PAYMENT_STATUS_FAILED)
                ->where('updated_at', '<=', $cutoffTime)
                ->limit($limit - $appointments->count())
                ->get()
                ->map(function ($appointment) use ($timeoutHours, $now) {
                    $appointment->deletion_reason = [
                        'reason' => 'failed_payment_cleanup',
                        'description' => "Failed payment appointment older than {$timeoutHours} hours",
                        'timeout_hours' => $timeoutHours,
                        'age_hours' => $now->diffInHours($appointment->updated_at)
                    ];
                    return $appointment;
                });
            
            $appointments = $appointments->concat($failedPaymentAppointments);
        }

        // Get old cancelled appointments
        if ($criteria['old_cancelled_appointments']['enabled'] && $appointments->count() < $limit) {
            $timeoutDays = $criteria['old_cancelled_appointments']['timeout_days'];
            $cutoffTime = $now->copy()->subDays($timeoutDays);
            
            $oldCancelledAppointments = Appointment::where('status', Appointment::STATUS_CANCELLED)
                ->where('updated_at', '<=', $cutoffTime)
                ->limit($limit - $appointments->count())
                ->get()
                ->map(function ($appointment) use ($timeoutDays, $now) {
                    $appointment->deletion_reason = [
                        'reason' => 'old_cancelled_cleanup',
                        'description' => "Cancelled appointment older than {$timeoutDays} days",
                        'timeout_days' => $timeoutDays,
                        'age_days' => $now->diffInDays($appointment->updated_at)
                    ];
                    return $appointment;
                });
            
            $appointments = $appointments->concat($oldCancelledAppointments);
        }

        return $appointments->take($limit);
    }

    /**
     * Get expired time slots that should be cleaned up.
     */
    public function getExpiredTimeSlots(int $limit = null): Collection
    {
        $criteria = $this->getCleanupCriteria();
        
        if (!$criteria['expired_time_slots']['enabled']) {
            return collect();
        }

        $timeoutMinutes = $criteria['expired_time_slots']['timeout_minutes'];
        $cutoffTime = now()->subMinutes($timeoutMinutes);
        $limit = $limit ?? ($this->config['limits']['max_deletions_per_run'] ?? 100);

        return TimeSlot::where('status', TimeSlot::STATUS_TEMPORARILY_BLOCKED)
            ->where('blocked_until', '<', $cutoffTime)
            ->limit($limit)
            ->get()
            ->map(function ($timeSlot) use ($timeoutMinutes) {
                $timeSlot->cleanup_reason = [
                    'reason' => 'expired_time_slot',
                    'description' => "Time slot block expired more than {$timeoutMinutes} minutes ago",
                    'timeout_minutes' => $timeoutMinutes,
                    'blocked_until' => $timeSlot->blocked_until,
                    'minutes_expired' => now()->diffInMinutes($timeSlot->blocked_until)
                ];
                return $timeSlot;
            });
    }

    /**
     * Get timeout for a specific appointment status.
     */
    public function getTimeoutForStatus(string $status): int
    {
        switch ($status) {
            case Appointment::STATUS_DRAFT:
                return $this->config['criteria']['draft_timeout_minutes'] ?? 30;
            case Appointment::STATUS_PAYMENT_PENDING:
                return $this->config['criteria']['payment_pending_timeout_minutes'] ?? 15;
            case Appointment::STATUS_CANCELLED:
                return ($this->config['criteria']['cancelled_cleanup_days'] ?? 7) * 24 * 60; // Convert to minutes
            default:
                return 0;
        }
    }

    /**
     * Get cleanup statistics.
     */
    public function getCleanupStatistics(): array
    {
        $criteria = $this->getCleanupCriteria();
        $now = now();
        $stats = [];

        foreach ($criteria as $key => $criterion) {
            if (!$criterion['enabled']) {
                continue;
            }

            switch ($key) {
                case 'draft_appointments':
                    $cutoffTime = $now->copy()->subMinutes($criterion['timeout_minutes']);
                    $count = Appointment::where('status', Appointment::STATUS_DRAFT)
                        ->where('created_at', '<=', $cutoffTime)
                        ->count();
                    $stats[$key] = [
                        'count' => $count,
                        'description' => $criterion['description'],
                        'cutoff_time' => $cutoffTime->toDateTimeString()
                    ];
                    break;

                case 'payment_pending_appointments':
                    $cutoffTime = $now->copy()->subMinutes($criterion['timeout_minutes']);
                    $count = Appointment::where('status', Appointment::STATUS_PAYMENT_PENDING)
                        ->where('created_at', '<=', $cutoffTime)
                        ->count();
                    $stats[$key] = [
                        'count' => $count,
                        'description' => $criterion['description'],
                        'cutoff_time' => $cutoffTime->toDateTimeString()
                    ];
                    break;

                case 'failed_payment_appointments':
                    $cutoffTime = $now->copy()->subHours($criterion['timeout_hours']);
                    $count = Appointment::where('payment_status', Appointment::PAYMENT_STATUS_FAILED)
                        ->where('updated_at', '<=', $cutoffTime)
                        ->count();
                    $stats[$key] = [
                        'count' => $count,
                        'description' => $criterion['description'],
                        'cutoff_time' => $cutoffTime->toDateTimeString()
                    ];
                    break;

                case 'old_cancelled_appointments':
                    $cutoffTime = $now->copy()->subDays($criterion['timeout_days']);
                    $count = Appointment::where('status', Appointment::STATUS_CANCELLED)
                        ->where('updated_at', '<=', $cutoffTime)
                        ->count();
                    $stats[$key] = [
                        'count' => $count,
                        'description' => $criterion['description'],
                        'cutoff_time' => $cutoffTime->toDateTimeString()
                    ];
                    break;

                case 'expired_time_slots':
                    $cutoffTime = $now->copy()->subMinutes($criterion['timeout_minutes']);
                    $count = TimeSlot::where('status', TimeSlot::STATUS_TEMPORARILY_BLOCKED)
                        ->where('blocked_until', '<', $cutoffTime)
                        ->count();
                    $stats[$key] = [
                        'count' => $count,
                        'description' => $criterion['description'],
                        'cutoff_time' => $cutoffTime->toDateTimeString()
                    ];
                    break;
            }
        }

        return $stats;
    }

    /**
     * Validate configuration.
     */
    public function validateConfiguration(): array
    {
        $errors = [];

        // Check required configuration keys
        $requiredKeys = [
            'criteria.draft_timeout_minutes',
            'criteria.payment_pending_timeout_minutes',
            'criteria.failed_payment_cleanup_hours',
            'criteria.cancelled_cleanup_days',
            'limits.max_deletions_per_run',
            'limits.max_execution_time_seconds'
        ];

        foreach ($requiredKeys as $key) {
            if (!data_get($this->config, $key)) {
                $errors[] = "Missing required configuration key: {$key}";
            }
        }

        // Validate timeout values
        if (($this->config['criteria']['draft_timeout_minutes'] ?? 0) <= 0) {
            $errors[] = 'Draft timeout minutes must be greater than 0';
        }

        if (($this->config['criteria']['payment_pending_timeout_minutes'] ?? 0) <= 0) {
            $errors[] = 'Payment pending timeout minutes must be greater than 0';
        }

        if (($this->config['limits']['max_deletions_per_run'] ?? 0) <= 0) {
            $errors[] = 'Max deletions per run must be greater than 0';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}