<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KycRequest;
use App\Http\Resources\KycResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\KycDocument;
use App\Services\User\KycService as UserKycService;
use App\Traits\ApiResponseTrait;
use App\User\Services\KycService;
use Illuminate\Support\Facades\Storage;

class KycController extends Controller
{
    use ApiResponseTrait;

    /**
     * Upload KYC Documents.
     */
    // public function uploadKycDocuments(KycRequest $request)
    // {
    //     $user = Auth::user();
    //     $kyc = KycDocument::firstOrNew(['user_id' => $user->id]);

    //     // Upload attachments if provided
    //     if ($request->hasFile('aadhar_attachment')) {
    //         if ($kyc->aadhar_attachment) {
    //             Storage::disk('public')->delete($kyc->aadhar_attachment);
    //         }
    //         $kyc->aadhar_attachment = $request->file('aadhar_attachment')->store('kyc_documents', 'public');
    //     }

    //     if ($request->hasFile('pan_attachment')) {
    //         if ($kyc->pan_attachment) {
    //             Storage::disk('public')->delete($kyc->pan_attachment);
    //         }
    //         $kyc->pan_attachment = $request->file('pan_attachment')->store('kyc_documents', 'public');
    //     }

    //     if ($request->hasFile('bank_attachment')) {
    //         if ($kyc->bank_attachment) {
    //             Storage::disk('public')->delete($kyc->bank_attachment);
    //         }
    //         $kyc->bank_attachment = $request->file('bank_attachment')->store('kyc_documents', 'public');
    //     }

    //     if ($request->hasFile('business_logo')) {
    //         if ($kyc->business_logo) {
    //             Storage::disk('public')->delete($kyc->business_logo);
    //         }
    //         $kyc->business_logo = $request->file('business_logo')->store('kyc_documents', 'public');
    //     }

    //     if ($request->hasFile('identity_document')) {
    //         if ($kyc->identity_document) {
    //             Storage::disk('public')->delete($kyc->identity_document);
    //         }
    //         $kyc->identity_document = $request->file('identity_document')->store('kyc_documents', 'public');
    //     }

    //     // Fill other details
    //     $kyc->fill($request->only([
    //         'aadhar_number', 'pan_number', 'bank_name', 'bank_account', 'ifsc_code',
    //         'business_name',  'business_category_id', 'business_established_date', 'description','address','full_address',
    //         'street','city','state','country','postal_code','latitude','longitude'
    //     ]));

    //     // Reset verification flags on update
    //     $kyc->is_aadhar_verified = false;
    //     $kyc->is_pan_verified = false;
    //     $kyc->is_bank_verified = false;
    //     $kyc->is_business_verified = false;

    //     $kyc->save();

    //     // Mark user as KYC completed
    //     $user->is_kyc_uploaded = true;
    //     $user->save();

    //     return $this->success(new KycResponse($kyc), 'KYC documents updated successfully. Pending verification.', 200);
    // }
    public function uploadKycDocuments(KycRequest $request, UserKycService $kycService)
    {
        try {

            // $kyc = $kycService->upload(
            //     $request->validated(),
            //     $request->allFiles()
            // );

            $kyc =  $kycService->upload(
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
            );

            return $this->success(
                new KycResponse($kyc),
                'KYC documents updated successfully. Pending verification.',
                200
            );
        } catch (\Throwable $e) {
            return $this->error(
                'Failed to upload KYC documents.',
                500
            );
        }
    }

    /**
     * Check KYC Status.
     */
    public function checkKycStatus()
    {
        $user = Auth::user();
        $kyc = KycDocument::where('user_id', $user->id)->first();


        if (!$kyc) {
            return $this->error([], 'KYC details not found.', 404);
        }

        return $this->success(new KycResponse($kyc), 'KYC verification status retrieved.', 200);
    }
}
