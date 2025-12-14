<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use App\Models\AppSetting;

class FeeCalculator
{
    /**
     * Calculate fees and taxes for a booking
     *
     * @param float $bookingAmount
     * @param float $homeVisitFee
     * @param float $discountAmount
     * @return array
     */
    public static function calculateBookingFees($bookingAmount, $homeVisitFee = 0, $discountAmount = 0)
    {
        // Calculate booking price after discount
        $originalPrice = $bookingAmount;

        // Apply home visit fee if applicable
        $priceWithHomeVisit = $originalPrice + $homeVisitFee;

        // Apply discount
        $bookingPriceAfterDiscount = $priceWithHomeVisit - $discountAmount;

        // Ensure booking price is not negative
        $bookingPriceAfterDiscount = max(0, $bookingPriceAfterDiscount);

        // Get platform fee
        $platformFee = self::isFeatureEnabled('enable_platform_fee') ? self::getPlatformFee() : 0;

        // Calculate other charges (2% of booking price after discount)
        $otherChargesPercentage = self::getOtherChargesPercentage();
        $otherCharges = self::isFeatureEnabled('enable_other_charges')
            ? round($bookingPriceAfterDiscount * $otherChargesPercentage, 2)
            : 0;

        // Calculate GST (18% of platform fee + other charges)
        $gstPercentage = self::getGstPercentage();
        $gstAmount = self::isFeatureEnabled('enable_gst')
            ? round(($platformFee + $otherCharges) * $gstPercentage, 2)
            : 0;

        // Calculate total amount
        $totalAmount = $bookingPriceAfterDiscount + $platformFee + $otherCharges + $gstAmount;

        // Calculate vendor and admin earnings
        $vendorEarnings = $bookingPriceAfterDiscount;
        $adminEarnings = $platformFee + $otherCharges + $gstAmount;

        return [
            'base_amount' => $originalPrice,
            'original_price' => $originalPrice,
            'home_visit_fee' => $homeVisitFee,
            'discount_amount' => $discountAmount,
            'booking_price' => $bookingPriceAfterDiscount,
            'platform_fees' => $platformFee,
            'other_charges' => $otherCharges,
            'gst' => $gstAmount,
            'subtotal' => $bookingPriceAfterDiscount + $platformFee + $otherCharges,
            'total_amount' => $totalAmount,
            'final_price' => $totalAmount,
            'payment_amount' => $totalAmount,
            'amount' => $totalAmount,
            'vendor_earnings' => $vendorEarnings,
            'admin_earnings' => $adminEarnings,
            'fee_description' => self::getSetting('fee_description', 'Platform processing fee')
        ];
    }

    /**
     * Get platform fee
     *
     * @return float
     */
    public static function getPlatformFee()
    {
        return (float) self::getSetting('platform_fee', 8);
    }

    /**
     * Get other charges percentage
     *
     * @return float
     */
    public static function getOtherChargesPercentage()
    {
        return (float) self::getSetting('other_charges_percentage', 0.02);
    }

    /**
     * Get GST percentage
     *
     * @return float
     */
    public static function getGstPercentage()
    {
        return (float) self::getSetting('gst_percentage', 0.18);
    }

    /**
     * Check if a feature is enabled
     *
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public static function isFeatureEnabled($key, $default = true)
    {
        return (bool) self::getSetting($key, $default);
    }

    /**
     * Get a setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getSetting($key, $default = null)
    {
        $settings = Cache::remember('app_settings', 3600, function () {
            return AppSetting::pluck('value', 'key')->toArray();
        });

        return $settings[$key] ?? $default;
    }

    /**
     * Format amount as currency
     *
     * @param float $amount
     * @param string $currencySymbol
     * @return string
     */
    public static function formatCurrency($amount, $currencySymbol = '₹')
    {
        return $currencySymbol . number_format($amount, 2);
    }

    /**
     * Get a breakdown of fees as formatted strings
     *
     * @param float $bookingAmount
     * @param float $homeVisitFee
     * @param float $discountAmount
     * @param string $currencySymbol
     * @return array
     */
    public static function getFormattedBreakdown($bookingAmount, $homeVisitFee = 0, $discountAmount = 0, $currencySymbol = '₹')
    {
        $fees = self::calculateBookingFees($bookingAmount, $homeVisitFee, $discountAmount);

        return [
            'base_amount' => self::formatCurrency($fees['base_amount'], $currencySymbol),
            'home_visit_fee' => self::formatCurrency($fees['home_visit_fee'], $currencySymbol),
            'discount_amount' => self::formatCurrency($fees['discount_amount'], $currencySymbol),
            'booking_price' => self::formatCurrency($fees['booking_price'], $currencySymbol),
            'platform_fee' => self::formatCurrency($fees['platform_fees'], $currencySymbol),
            'other_charges' => self::formatCurrency($fees['other_charges'], $currencySymbol),
            'subtotal' => self::formatCurrency($fees['subtotal'], $currencySymbol),
            'gst' => self::formatCurrency($fees['gst'], $currencySymbol),
            'total_amount' => self::formatCurrency($fees['total_amount'], $currencySymbol),
            'fee_description' => $fees['fee_description'],
            'other_charges_percentage' => (self::getOtherChargesPercentage() * 100) . '%',
            'gst_percentage' => (self::getGstPercentage() * 100) . '%'
        ];
    }

    /**
     * Calculate platform commission from a booking amount
     *
     * @param float $bookingAmount
     * @return float
     */
    public static function calculateCommission($bookingAmount)
    {
        // Get commission percentage from settings
        $commissionPercentage = (float) self::getSetting('commission_percentage', 10) / 100;

        // Calculate commission
        return $bookingAmount * $commissionPercentage;
    }

    /**
     * Check if the amount meets minimum payout requirement
     *
     * @param float $amount
     * @return bool
     */
    public static function meetsMinimumPayout($amount)
    {
        $minimumPayout = (float) self::getSetting('minimum_payout_amount', 1000);
        return $amount >= $minimumPayout;
    }
}
