<?php

namespace Database\Factories;

use App\Models\Refund;
use App\Models\Payment;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Refund>
 */
class RefundFactory extends Factory
{
    protected $model = Refund::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $refundAmount = $this->faker->randomFloat(2, 100, 2000);
        $customerRefund = $refundAmount * 0.75; // Default to partial refund scenario
        $vendorCompensation = $refundAmount * 0.15;
        $adminRetention = $refundAmount * 0.10;

        $bookingTime = $this->faker->dateTimeBetween('+1 day', '+7 days');
        $cancellationTime = $this->faker->dateTimeBetween('now', $bookingTime);
        $hoursBeforeBooking = Carbon::parse($cancellationTime)->diffInHours(Carbon::parse($bookingTime), false);

        return [
            'payment_id' => Payment::factory(),
            'appointment_id' => Appointment::factory(),
            'refund_amount' => $refundAmount,
            'customer_refund' => $customerRefund,
            'vendor_compensation' => $vendorCompensation,
            'admin_retention' => $adminRetention,
            'policy_tier' => $this->faker->randomElement([
                Refund::TIER_FULL_REFUND,
                Refund::TIER_PARTIAL_REFUND,
                Refund::TIER_NO_REFUND
            ]),
            'cancellation_time' => $cancellationTime,
            'booking_time' => $bookingTime,
            'hours_before_booking' => $hoursBeforeBooking,
            'refund_reason' => $this->faker->randomElement([
                'Customer requested cancellation',
                'Emergency cancellation',
                'Service provider unavailable',
                'Weather conditions',
                'Personal reasons'
            ]),
            'status' => $this->faker->randomElement([
                Refund::STATUS_PENDING,
                Refund::STATUS_PROCESSING,
                Refund::STATUS_COMPLETED,
                Refund::STATUS_FAILED
            ]),
            'processed_at' => $this->faker->optional(0.7)->dateTimeBetween($cancellationTime, 'now'),
            'refund_breakdown' => [
                'original_amount' => $refundAmount,
                'service_charges' => $refundAmount * 0.8,
                'platform_fee' => $refundAmount * 0.05,
                'other_charges' => $refundAmount * 0.02,
                'gst_amount' => $refundAmount * 0.13,
                'refund_percentage' => 75,
                'vendor_percentage' => 15,
                'admin_percentage' => 10,
            ],
            'gateway_refund_id' => $this->faker->optional(0.6)->regexify('REF_[A-Z0-9]{10}'),
            'initiated_by' => User::factory(),
            'approved_by' => $this->faker->optional(0.5)->randomElement([User::factory()]),
            'approved_at' => $this->faker->optional(0.5)->dateTimeBetween($cancellationTime, 'now'),
            'gateway_response' => $this->faker->optional(0.6)->randomElement([
                ['status' => 'success', 'transaction_id' => $this->faker->uuid],
                ['status' => 'pending', 'message' => 'Processing refund'],
                ['status' => 'failed', 'error' => 'Insufficient funds']
            ])
        ];
    }

    /**
     * Indicate that the refund is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Refund::STATUS_PENDING,
            'processed_at' => null,
            'gateway_refund_id' => null,
            'gateway_response' => null
        ]);
    }

    /**
     * Indicate that the refund is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Refund::STATUS_COMPLETED,
            'processed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'gateway_refund_id' => 'REF_' . $this->faker->regexify('[A-Z0-9]{10}'),
            'gateway_response' => ['status' => 'success', 'transaction_id' => $this->faker->uuid]
        ]);
    }

    /**
     * Indicate that the refund failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Refund::STATUS_FAILED,
            'processed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'gateway_response' => ['status' => 'failed', 'error' => 'Payment gateway error']
        ]);
    }

    /**
     * Indicate that this is a full refund scenario.
     */
    public function fullRefund(): static
    {
        return $this->state(function (array $attributes) {
            $refundAmount = $attributes['refund_amount'];
            return [
                'policy_tier' => Refund::TIER_FULL_REFUND,
                'customer_refund' => $refundAmount,
                'vendor_compensation' => 0,
                'admin_retention' => 0,
                'hours_before_booking' => $this->faker->numberBetween(25, 72),
                'refund_breakdown' => [
                    'original_amount' => $refundAmount,
                    'service_charges' => $refundAmount * 0.8,
                    'platform_fee' => $refundAmount * 0.05,
                    'other_charges' => $refundAmount * 0.02,
                    'gst_amount' => $refundAmount * 0.13,
                    'refund_percentage' => 100,
                    'vendor_percentage' => 0,
                    'admin_percentage' => 0,
                ]
            ];
        });
    }

    /**
     * Indicate that this is a partial refund scenario.
     */
    public function partialRefund(): static
    {
        return $this->state(function (array $attributes) {
            $refundAmount = $attributes['refund_amount'];
            $serviceCharges = $refundAmount * 0.8;
            return [
                'policy_tier' => Refund::TIER_PARTIAL_REFUND,
                'customer_refund' => $serviceCharges * 0.75,
                'vendor_compensation' => $serviceCharges * 0.15,
                'admin_retention' => $serviceCharges * 0.10,
                'hours_before_booking' => $this->faker->numberBetween(3, 23),
                'refund_breakdown' => [
                    'original_amount' => $refundAmount,
                    'service_charges' => $serviceCharges,
                    'platform_fee' => $refundAmount * 0.05,
                    'other_charges' => $refundAmount * 0.02,
                    'gst_amount' => $refundAmount * 0.13,
                    'refund_percentage' => 75,
                    'vendor_percentage' => 15,
                    'admin_percentage' => 10,
                ]
            ];
        });
    }

    /**
     * Indicate that this is a no refund scenario.
     */
    public function noRefund(): static
    {
        return $this->state(function (array $attributes) {
            $refundAmount = $attributes['refund_amount'];
            $serviceCharges = $refundAmount * 0.8;
            return [
                'policy_tier' => Refund::TIER_NO_REFUND,
                'customer_refund' => 0,
                'vendor_compensation' => $serviceCharges * 0.50,
                'admin_retention' => $serviceCharges * 0.50,
                'hours_before_booking' => $this->faker->randomFloat(2, 0, 1.9),
                'refund_breakdown' => [
                    'original_amount' => $refundAmount,
                    'service_charges' => $serviceCharges,
                    'platform_fee' => $refundAmount * 0.05,
                    'other_charges' => $refundAmount * 0.02,
                    'gst_amount' => $refundAmount * 0.13,
                    'refund_percentage' => 0,
                    'vendor_percentage' => 50,
                    'admin_percentage' => 50,
                ]
            ];
        });
    }
}