<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\KycRequest;
use App\Models\BusinessCategory;
use App\Models\User;
use App\Services\User\KycService;
use Illuminate\Http\Request;

class KycController extends Controller
{
    public function showKycForm()
    {
        $businessCategory =  BusinessCategory::select(['name', 'id'])->get();
        $cards = collect(config('business_categories'))
            ->map(fn($c) => [
                ...$c,
                'src' => asset($c['src'])
            ]);
        return view('vendor.user.kyc', compact('cards', 'businessCategory'));
    }

    public function uploadKycDocuments(KycRequest $request, KycService $kycService)
    {

        try {
            if (session()->has('registration_token')) {
                $userId = session('registration_token');
            }

            // $kycService->upload(
            //     $request->validated(),
            //     $request->allFiles(),
            //     $userId
            // );

            $kycService->upload(
                $request->only([
                    'aadhar_number',
                    'pan_number',
                    'bank_name',
                    'bank_account',
                    'ifsc_code',
                    'business_name',
                    'business_category_id',
                    'business_established_date',
                    'description',
                    'address',
                    'full_address',
                    'street',
                    'city',
                    'state',
                    'country',
                    'postal_code',
                    'latitude',
                    'longitude'
                ]),
                $request->file(),   // ✅ IMPORTANT
                $userId
            );

            return redirect()->route('vendor.vendor.registration.success')
                ->with('success', 'KYC documents uploaded successfully. Pending verification.');
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->back()
                ->with('error', 'Something went wrong while uploading KYC documents.');
        }
    }
}
