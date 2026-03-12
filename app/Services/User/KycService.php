<?php

namespace App\Services\User;

use App\Models\KycDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class KycService
{
    public function upload(array $data, array $files, $user_id = null): KycDocument
    {
        try {

            return DB::transaction(function () use ($data, $files, $user_id) {

                // Resolve user
                $userId = $user_id ?? Auth::id();
                $user = User::find($userId);

                if (! $user) {
                    throw new \Exception('User not found');
                }

                $kyc = KycDocument::firstOrNew([
                    'user_id' => $user->id,
                ]);

                $fileFields = [
                    'aadhar_attachment',
                    'pan_attachment',
                    'bank_attachment',
                    'business_logo',
                    'identity_document',
                ];

                foreach ($fileFields as $field) {

                    // Ensure this is a real uploaded file
                    if (isset($files[$field]) && $files[$field] instanceof UploadedFile) {

                        // Delete old file if exists
                        if (! empty($kyc->$field)) {
                            Storage::disk('public')->delete($kyc->$field);
                        }

                        // Store new file and save relative path
                        $path = $files[$field]->store('kyc_documents', 'public');

                        $kyc->$field = $path;
                    }
                }

                // Fill non-file data
                $kyc->fill($data);

                // Reset verification flags
                $kyc->is_aadhar_verified = false;
                $kyc->is_pan_verified = false;
                $kyc->is_bank_verified = false;
                $kyc->is_business_verified = false;

                $kyc->save();

                $user->update([
                    'is_kyc_uploaded' => true,
                ]);

                return $kyc;
            });
        } catch (Throwable $e) {

            // Optional: log error
            // logger()->error('KYC upload failed', [
            //     'error' => $e->getMessage(),
            //     'user_id' => optional($user)->id,
            // ]);

            // Re-throw so controller can decide response type
            throw $e;
        }
    }
}
