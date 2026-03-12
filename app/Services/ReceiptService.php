<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PDF; // Using laravel-dompdf

class ReceiptService
{
    /**
     * Generate a PDF receipt for an appointment or payment
     *
     * @param  mixed  $appointment  Appointment object or ID
     * @param  mixed  $payment  Payment object or null
     * @param  array  $businessInfo  Business information
     * @return string Path to the generated PDF file
     */
    public function generatePDF($appointment, $payment = null, $businessInfo = null)
    {
        // Make sure we have an appointment object
        if (! ($appointment instanceof Appointment)) {
            $appointment = Appointment::with(['service', 'comboService', 'client', 'user', 'payment'])
                ->findOrFail($appointment);
        }

        // Get payment information if not provided
        if (! $payment && $appointment->payment) {
            $payment = $appointment->payment;
        }

        // If we still don't have a payment and have a payment_id, get it
        if (! $payment && $appointment->payment_id) {
            $payment = Payment::find($appointment->payment_id);
        }

        // If business info is not provided, create a default one
        if (! $businessInfo) {
            $provider = $appointment->user;
            $businessInfo = [
                'name' => $provider->business_name ?? $provider->name,
                'address' => $provider->business_address ?? '',
                'phone' => $provider->phone ?? '',
                'email' => $provider->email ?? '',
                'logo' => $provider->logo_url ?? null,
            ];
        }

        // Get service name
        $serviceName = '';
        if ($appointment->service) {
            $serviceName = $appointment->service->name;
        } elseif ($appointment->comboService) {
            $serviceName = $appointment->comboService->name;
        }

        // Format payment details
        $paymentDetails = [
            'status' => $payment ? $payment->status : $appointment->payment_status,
            'method' => $payment ? $payment->payment_method : $appointment->payment_method,
            'transaction_id' => $payment ? $payment->transaction_id : $appointment->payment_id,
            'original_price' => $payment ? $payment->original_price : $appointment->original_price,
            'booking_price' => $payment ? $payment->booking_price : $appointment->booking_price,
            'platform_fee' => $payment ? $payment->platform_fee : $appointment->platform_fees,
            'other_charges' => $payment ? $payment->other_charges : $appointment->other_charges,
            'gst_amount' => $payment ? $payment->gst_amount : $appointment->gst,
            'discount_amount' => $payment ? $payment->discount_amount : $appointment->discount_amount,
            'home_visit_fee' => $payment ? $payment->home_visit_fee : $appointment->home_visit_fee,
            'additional_services_fee' => $payment ? $payment->additional_services_fee : $appointment->additional_services_fee,
            'net_amount' => $payment ? $payment->net_amount : $appointment->final_price,
            'total_amount' => $payment ? $payment->amount : $appointment->payment_amount,
            'formatted_total' => $this->formatCurrency($payment ? $payment->amount : $appointment->payment_amount),
            'coupon_code' => $payment ? $payment->coupon_code : $appointment->coupon_code,
            'offer_title' => $payment ? $payment->offer_title : $appointment->offer_title,
        ];

        // Prepare data for the view
        $data = [
            'appointment' => $appointment,
            'provider' => $appointment->user,
            'client' => $appointment->client,
            'service' => [
                'name' => $serviceName,
                'duration' => $appointment->service ? $appointment->service->duration :
                    ($appointment->comboService ? $appointment->comboService->total_duration : null),
                'price' => $appointment->service ? $appointment->service->price :
                    ($appointment->comboService ? $appointment->comboService->discounted_price : null),
            ],
            'payment' => $paymentDetails,
            'business' => $businessInfo,
            'receipt_number' => 'RCT-'.$appointment->id.'-'.date('Ymd'),
            'receipt_date' => Carbon::now()->format('F d, Y'),
            'appointment_date' => Carbon::parse($appointment->date)->format('F d, Y'),
            'appointment_time' => $appointment->formatted_time,
        ];

        // Generate PDF
        $pdf = PDF::loadView('pdf.receipt', $data);

        // Generate a unique filename
        $filename = 'receipt_'.$appointment->id.'_'.time().'.pdf';
        $path = 'receipts/'.$filename;

        // Save PDF to storage
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Format currency with symbol
     *
     * @param  float  $amount
     * @param  string  $currency
     * @return string
     */
    private function formatCurrency($amount, $currency = 'INR')
    {
        $symbol = $currency === 'INR' ? '₹' : '$';

        return $symbol.number_format($amount, 2);
    }
}
