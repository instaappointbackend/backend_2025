<?php

namespace App\Services;

use App\Models\Refund;

class RefundCalculation
{
    public function __construct(
        public readonly float $originalAmount,
        public readonly float $customerRefund,
        public readonly float $vendorCompensation,
        public readonly float $adminRetention,
        public readonly string $policyTier,
        public readonly float $hoursBeforeBooking,
        public readonly array $breakdown,
        public readonly bool $eligible = true,
        public readonly ?string $reason = null
    ) {}

    /**
     * Create a RefundCalculation for full refund scenario (>24 hours).
     */
    public static function fullRefund(
        float $originalAmount,
        float $serviceCharges,
        float $platformFee,
        float $otherCharges,
        float $gstAmount,
        float $hoursBeforeBooking
    ): self {
        return new self(
            originalAmount: $originalAmount,
            customerRefund: $originalAmount,
            vendorCompensation: 0,
            adminRetention: 0,
            policyTier: Refund::TIER_FULL_REFUND,
            hoursBeforeBooking: $hoursBeforeBooking,
            breakdown: [
                'original_amount' => $originalAmount,
                'service_charges' => $serviceCharges,
                'platform_fee' => $platformFee,
                'other_charges' => $otherCharges,
                'gst_amount' => $gstAmount,
                'refund_percentage' => 100,
                'vendor_percentage' => 0,
                'admin_percentage' => 0,
            ]
        );
    }

    /**
     * Create a RefundCalculation for partial refund scenario (24h-2h).
     */
    public static function partialRefund(
        float $originalAmount,
        float $serviceCharges,
        float $platformFee,
        float $otherCharges,
        float $gstAmount,
        float $hoursBeforeBooking
    ): self {
        $customerRefund = $serviceCharges * 0.75;
        $vendorCompensation = $serviceCharges * 0.15;
        $adminRetention = $serviceCharges * 0.10;

        return new self(
            originalAmount: $originalAmount,
            customerRefund: $customerRefund,
            vendorCompensation: $vendorCompensation,
            adminRetention: $adminRetention,
            policyTier: Refund::TIER_PARTIAL_REFUND,
            hoursBeforeBooking: $hoursBeforeBooking,
            breakdown: [
                'original_amount' => $originalAmount,
                'service_charges' => $serviceCharges,
                'platform_fee' => $platformFee,
                'other_charges' => $otherCharges,
                'gst_amount' => $gstAmount,
                'refund_percentage' => 75,
                'vendor_percentage' => 15,
                'admin_percentage' => 10,
            ]
        );
    }

    /**
     * Create a RefundCalculation for no refund scenario (<2 hours).
     */
    public static function noRefund(
        float $originalAmount,
        float $serviceCharges,
        float $platformFee,
        float $otherCharges,
        float $gstAmount,
        float $hoursBeforeBooking,
        string $reason = 'Cancellation within 2 hours of booking time'
    ): self {
        $vendorCompensation = $serviceCharges * 0.50;
        $adminRetention = $serviceCharges * 0.50;

        return new self(
            originalAmount: $originalAmount,
            customerRefund: 0,
            vendorCompensation: $vendorCompensation,
            adminRetention: $adminRetention,
            policyTier: Refund::TIER_NO_REFUND,
            hoursBeforeBooking: $hoursBeforeBooking,
            breakdown: [
                'original_amount' => $originalAmount,
                'service_charges' => $serviceCharges,
                'platform_fee' => $platformFee,
                'other_charges' => $otherCharges,
                'gst_amount' => $gstAmount,
                'refund_percentage' => 0,
                'vendor_percentage' => 50,
                'admin_percentage' => 50,
            ],
            eligible: false,
            reason: $reason
        );
    }

    /**
     * Create an ineligible RefundCalculation.
     */
    public static function ineligible(string $reason): self
    {
        return new self(
            originalAmount: 0,
            customerRefund: 0,
            vendorCompensation: 0,
            adminRetention: 0,
            policyTier: '',
            hoursBeforeBooking: 0,
            breakdown: [],
            eligible: false,
            reason: $reason
        );
    }

    /**
     * Get formatted customer refund amount.
     */
    public function getFormattedCustomerRefund(): string
    {
        return '₹'.number_format($this->customerRefund, 2);
    }

    /**
     * Get formatted vendor compensation amount.
     */
    public function getFormattedVendorCompensation(): string
    {
        return '₹'.number_format($this->vendorCompensation, 2);
    }

    /**
     * Get formatted admin retention amount.
     */
    public function getFormattedAdminRetention(): string
    {
        return '₹'.number_format($this->adminRetention, 2);
    }

    /**
     * Get formatted original amount.
     */
    public function getFormattedOriginalAmount(): string
    {
        return '₹'.number_format($this->originalAmount, 2);
    }

    /**
     * Get human-readable policy tier.
     */
    public function getHumanPolicyTier(): string
    {
        switch ($this->policyTier) {
            case Refund::TIER_FULL_REFUND:
                return 'Full Refund (>24 hours)';
            case Refund::TIER_PARTIAL_REFUND:
                return 'Partial Refund (24h-2h)';
            case Refund::TIER_NO_REFUND:
                return 'No Refund (<2 hours)';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get the refund percentage for customer.
     */
    public function getRefundPercentage(): int
    {
        return $this->breakdown['refund_percentage'] ?? 0;
    }

    /**
     * Get the vendor compensation percentage.
     */
    public function getVendorPercentage(): int
    {
        return $this->breakdown['vendor_percentage'] ?? 0;
    }

    /**
     * Get the admin retention percentage.
     */
    public function getAdminPercentage(): int
    {
        return $this->breakdown['admin_percentage'] ?? 0;
    }

    /**
     * Convert to array for database storage.
     */
    public function toArray(): array
    {
        return [
            'refund_amount' => $this->customerRefund,
            'customer_refund' => $this->customerRefund,
            'vendor_compensation' => $this->vendorCompensation,
            'admin_retention' => $this->adminRetention,
            'policy_tier' => $this->policyTier,
            'hours_before_booking' => $this->hoursBeforeBooking,
            'refund_breakdown' => $this->breakdown,
            'eligible' => $this->eligible,
            'reason' => $this->reason,
        ];
    }

    /**
     * Validate that the calculation totals are correct.
     */
    public function isValid(): bool
    {
        if (! $this->eligible) {
            return true; // Ineligible calculations don't need amount validation
        }

        $serviceCharges = $this->breakdown['service_charges'] ?? 0;
        $expectedTotal = $this->customerRefund + $this->vendorCompensation + $this->adminRetention;

        // For full refund, total should equal original amount
        if ($this->policyTier === Refund::TIER_FULL_REFUND) {
            return abs($this->originalAmount - $this->customerRefund) < 0.01;
        }

        // For partial/no refund, total should equal service charges
        return abs($serviceCharges - $expectedTotal) < 0.01;
    }
}
