<?php

namespace App\Services;

class CleanupResult
{
    public function __construct(
        public readonly int $appointmentsDeleted,
        public readonly int $timeSlotsReleased,
        public readonly array $deletionReasons,
        public readonly array $errors,
        public readonly float $executionTimeSeconds,
        public readonly bool $dryRun,
        public readonly array $statistics = []
    ) {}

    /**
     * Create a successful cleanup result.
     */
    public static function success(
        int $appointmentsDeleted,
        int $timeSlotsReleased,
        array $deletionReasons,
        float $executionTimeSeconds,
        bool $dryRun = false,
        array $statistics = []
    ): self {
        return new self(
            appointmentsDeleted: $appointmentsDeleted,
            timeSlotsReleased: $timeSlotsReleased,
            deletionReasons: $deletionReasons,
            errors: [],
            executionTimeSeconds: $executionTimeSeconds,
            dryRun: $dryRun,
            statistics: $statistics
        );
    }

    /**
     * Create a cleanup result with errors.
     */
    public static function withErrors(
        int $appointmentsDeleted,
        int $timeSlotsReleased,
        array $deletionReasons,
        array $errors,
        float $executionTimeSeconds,
        bool $dryRun = false,
        array $statistics = []
    ): self {
        return new self(
            appointmentsDeleted: $appointmentsDeleted,
            timeSlotsReleased: $timeSlotsReleased,
            deletionReasons: $deletionReasons,
            errors: $errors,
            executionTimeSeconds: $executionTimeSeconds,
            dryRun: $dryRun,
            statistics: $statistics
        );
    }

    /**
     * Create an empty result (no cleanup performed).
     */
    public static function empty(float $executionTimeSeconds, bool $dryRun = false): self
    {
        return new self(
            appointmentsDeleted: 0,
            timeSlotsReleased: 0,
            deletionReasons: [],
            errors: [],
            executionTimeSeconds: $executionTimeSeconds,
            dryRun: $dryRun
        );
    }

    /**
     * Check if the cleanup was successful (no errors).
     */
    public function isSuccessful(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if any cleanup was performed.
     */
    public function hasCleanedUp(): bool
    {
        return $this->appointmentsDeleted > 0 || $this->timeSlotsReleased > 0;
    }

    /**
     * Get total items processed.
     */
    public function getTotalProcessed(): int
    {
        return $this->appointmentsDeleted + $this->timeSlotsReleased;
    }

    /**
     * Get formatted execution time.
     */
    public function getFormattedExecutionTime(): string
    {
        if ($this->executionTimeSeconds < 1) {
            return number_format($this->executionTimeSeconds * 1000, 2) . 'ms';
        }
        return number_format($this->executionTimeSeconds, 2) . 's';
    }

    /**
     * Get summary of deletion reasons.
     */
    public function getDeletionSummary(): array
    {
        $summary = [];
        foreach ($this->deletionReasons as $reason) {
            $key = $reason['reason'] ?? 'unknown';
            if (!isset($summary[$key])) {
                $summary[$key] = 0;
            }
            $summary[$key]++;
        }
        return $summary;
    }

    /**
     * Get error summary.
     */
    public function getErrorSummary(): array
    {
        $summary = [];
        foreach ($this->errors as $error) {
            $type = $error['type'] ?? 'general';
            if (!isset($summary[$type])) {
                $summary[$type] = 0;
            }
            $summary[$type]++;
        }
        return $summary;
    }

    /**
     * Convert to array for logging or API responses.
     */
    public function toArray(): array
    {
        return [
            'appointments_deleted' => $this->appointmentsDeleted,
            'time_slots_released' => $this->timeSlotsReleased,
            'total_processed' => $this->getTotalProcessed(),
            'deletion_reasons' => $this->deletionReasons,
            'deletion_summary' => $this->getDeletionSummary(),
            'errors' => $this->errors,
            'error_summary' => $this->getErrorSummary(),
            'execution_time_seconds' => $this->executionTimeSeconds,
            'formatted_execution_time' => $this->getFormattedExecutionTime(),
            'dry_run' => $this->dryRun,
            'successful' => $this->isSuccessful(),
            'has_cleaned_up' => $this->hasCleanedUp(),
            'statistics' => $this->statistics,
        ];
    }

    /**
     * Get a human-readable summary message.
     */
    public function getSummaryMessage(): string
    {
        $mode = $this->dryRun ? '[DRY RUN] ' : '';
        
        if (!$this->hasCleanedUp()) {
            return $mode . 'No cleanup required. All appointments are valid.';
        }

        $message = $mode . "Cleanup completed: {$this->appointmentsDeleted} appointments deleted, {$this->timeSlotsReleased} time slots released";
        
        if (!empty($this->errors)) {
            $errorCount = count($this->errors);
            $message .= " with {$errorCount} error(s)";
        }
        
        $message .= " in {$this->getFormattedExecutionTime()}.";
        
        return $message;
    }

    /**
     * Merge with another cleanup result.
     */
    public function merge(CleanupResult $other): self
    {
        return new self(
            appointmentsDeleted: $this->appointmentsDeleted + $other->appointmentsDeleted,
            timeSlotsReleased: $this->timeSlotsReleased + $other->timeSlotsReleased,
            deletionReasons: array_merge($this->deletionReasons, $other->deletionReasons),
            errors: array_merge($this->errors, $other->errors),
            executionTimeSeconds: $this->executionTimeSeconds + $other->executionTimeSeconds,
            dryRun: $this->dryRun || $other->dryRun,
            statistics: array_merge_recursive($this->statistics, $other->statistics)
        );
    }
}