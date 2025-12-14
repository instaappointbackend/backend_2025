<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use App\Models\User;
use App\Models\BusinessCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KycController extends Controller
{
    /**
     * Display a listing of KYC documents.
     */
    public function index(Request $request)
    {
        $query = KycDocument::with('user', 'businessCategory');

        // Filter by verification status
        if ($request->has('status')) {
            if ($request->status == 'pending') {
                $query->where('is_business_verified', false);
            } elseif ($request->status == 'verified') {
                $query->where('is_business_verified', true);
            }
        }

        // Filter by business type
        if ($request->has('business_category_id') && $request->business_category_id) {
            $query->where('business_category_id', $request->business_category_id);
        }

        // Search by user name, email, mobile, or business name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                })
                ->orWhere('business_name', 'like', "%{$search}%")
                ->orWhere('aadhar_number', 'like', "%{$search}%")
                ->orWhere('pan_number', 'like', "%{$search}%");
            });
        }

        $kycDocuments = $query->latest()->paginate(15);
        $businessCategories = BusinessCategory::all();

        return view('admin.kyc.index', compact('kycDocuments', 'businessCategories'));
    }

    /**
     * Display the specified KYC document.
     */
    public function show($id)
    {
        // Find the KYC document with related models
        $kycDocument = KycDocument::with(['user', 'businessCategory'])->find($id);

        // Check if KYC document exists
        if (!$kycDocument) {
            return redirect()->route('admin.kyc.index')
                ->with('error', 'KYC document not found.');
        }

        return view('admin.kyc.show', compact('kycDocument'));
    }

    /**
     * Show the form for editing the specified KYC document.
     */
    public function edit(KycDocument $kycDocument)
    {
        $kycDocument->load('user', 'businessCategory');
        $businessCategories = BusinessCategory::all();
        return view('admin.kyc.edit', compact('kycDocument', 'businessCategories'));
    }

    /**
     * Update the specified KYC document in storage.
     */
    public function update(Request $request, $id)
    {

        $kycDocument=KycDocument::find($id);

        // Check if user's KYC is already completed
        if ($kycDocument->user && $kycDocument->user->is_kyc_completed) {

            return redirect()->route('admin.kyc.show', $kycDocument->id)
                ->with('warning', 'KYC is already verified. No changes allowed.');
        }

        $validated = $request->validate([
            'is_business_verified' => 'boolean',
            'is_aadhar_verified' => 'boolean',
            'is_pan_verified' => 'boolean',
            'is_bank_verified' => 'boolean',
            'feedback' => 'nullable|string',
            'business_name' => 'nullable|string|max:255',
            'business_address' => 'nullable|string',
            'business_category_id' => 'nullable|exists:business_categories,id',
            'business_established_date' => 'nullable|date',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'full_address' => 'nullable|string',
            'street' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'feedback' => 'nullable|string',
        ]);

        // Update the KYC document
        $kycDocument->update($validated);

        // Check if all verifications are true, then update user's kyc status
        $this->checkAndUpdateUserKycStatus($kycDocument);

        return redirect()->route('admin.kyc.show', $kycDocument->id)
            ->with('success', 'KYC document updated successfully.');
    }

    /**
     * Verify a specific field of the KYC document.
     */
    public function verify(Request $request, KycDocument $kycDocument)
    {
        // Check if user's KYC is already completed
        if ($kycDocument->user && $kycDocument->user->is_kyc_completed) {
            return redirect()->route('admin.kyc.show', $kycDocument->id)
            ->with('warning', 'KYC is already verified. No changes allowed.');
        }

        $validated = $request->validate([
            'field' => 'required|string|in:is_aadhar_verified,is_pan_verified,is_bank_verified,is_business_verified',
            'is_aadhar_verified' => 'nullable|boolean',
            'is_pan_verified' => 'nullable|boolean',
            'is_bank_verified' => 'nullable|boolean',
            'is_business_verified' => 'nullable|boolean',
            'feedback' => 'nullable|string',
        ]);

        $field = $request->field;
        $value = $request->has($field) ? (bool)$request->input($field) : false;

        // Update the specified field
        $kycDocument->$field = $value;

        // Add feedback if provided
        if ($request->has('feedback') && $request->feedback) {
            $kycDocument->feedback = $request->feedback;
        }
        dd($kycDocument->feedback);

        $kycDocument->save();

        // Check if all verifications are true, then update user's kyc status
        $this->checkAndUpdateUserKycStatus($kycDocument);

        $status = $value ? 'verified' : 'unverified';
        $fieldName = str_replace('is_', '', str_replace('_verified', '', $field));

        return redirect()->back()
            ->with('success', ucfirst($fieldName) . " document {$status} successfully.");
    }

    /**
     * Approve the KYC document.
     */
    public function approve(Request $request, $id)
    {
        $kycDocument = KycDocument::findOrFail($id);

        // Check if user's KYC is already completed
        if ($kycDocument->user && $kycDocument->user->is_kyc_completed) {
            return redirect()->route('admin.kyc.index')
                ->with('warning', 'KYC is already verified. No changes allowed.');
        }

        // Update KYC document verification status
        $kycDocument->is_aadhar_verified = true;
        $kycDocument->is_pan_verified = true;
        if ($kycDocument->bank_attachment) {
            $kycDocument->is_bank_verified = true;
        }
        $kycDocument->is_business_verified = true;

        // Add feedback if provided
        if ($request->has('feedback')) {
            $kycDocument->feedback = $request->feedback;
        }

        $kycDocument->save();

        // Check if all verifications are true, then update user's kyc status
        $this->checkAndUpdateUserKycStatus($kycDocument);

        return redirect()->route('admin.kyc.index')
            ->with('success', 'KYC document approved successfully.');
    }

    /**
     * Reject the KYC document.
     */
    public function reject(Request $request, $id)
    {
        $kycDocument = KycDocument::findOrFail($id);

        // Check if user's KYC is already completed
        if ($kycDocument->user && $kycDocument->user->is_kyc_completed) {
            return redirect()->route('admin.kyc.index')
                ->with('warning', 'KYC is already verified. No changes allowed.');
        }

        $validated = $request->validate([
            'feedback' => 'required|string',
        ]);

        // Update KYC document verification status
        $kycDocument->is_aadhar_verified = false;
        $kycDocument->is_pan_verified = false;
        $kycDocument->is_bank_verified = false;
        $kycDocument->is_business_verified = false;
        $kycDocument->feedback = $validated['feedback'];
        $kycDocument->save();

        // Update user's KYC status if user exists
        if ($kycDocument->user) {
            $user = $kycDocument->user;
            $user->is_kyc_completed = false;
            $user->save();
        }

        return redirect()->route('admin.kyc.index')
            ->with('success', 'KYC document rejected successfully.');
    }

    /**
     * Helper method to check and update user's KYC status
     */
    private function checkAndUpdateUserKycStatus(KycDocument $kycDocument)
    {
        // Check if user exists
        if (!$kycDocument->user) {
            return;
        }

        // Check if user's KYC is already completed
        if ($kycDocument->user->is_kyc_completed) {
            return;
        }

        // Check if all required verifications are true
        $allVerified =
            (isset($kycDocument->is_aadhar_verified) && $kycDocument->is_aadhar_verified) &&
            (isset($kycDocument->is_pan_verified) && $kycDocument->is_pan_verified) &&
            ($kycDocument->is_business_verified) &&
            (!$kycDocument->bank_attachment || (isset($kycDocument->is_bank_verified) && $kycDocument->is_bank_verified));

        if ($allVerified) {
            $user = $kycDocument->user;
            $user->is_kyc_completed = true;
            $user->save();
        }
    }

    /**
     * Download KYC attachment.
     */
    public function downloadAttachment(KycDocument $kycDocument, $type)
    {
        $fileName = null;

        switch ($type) {
            case 'aadhar':
                $fileName = $kycDocument->aadhar_attachment;
                break;
            case 'pan':
                $fileName = $kycDocument->pan_attachment;
                break;
            case 'bank':
                $fileName = $kycDocument->bank_attachment;
                break;
            case 'business_logo':
                $fileName = $kycDocument->business_logo;
                break;
            case 'identity_document':
                $fileName = $kycDocument->identity_document;
                break;
            default:
                abort(404);
        }

        if (!$fileName || !Storage::disk('public')->exists($fileName)) {
            abort(404);
        }

        return Storage::disk('public')->download($fileName);
    }

    /**
     * Show pending KYC verifications.
     */
    public function pending()
    {
        $kycDocuments = KycDocument::with('user', 'businessCategory')
            ->where('is_business_verified', false)
            ->latest()
            ->paginate(15);

        return view('admin.kyc.index', compact('kycDocuments'));
    }

    /**
     * Show verified KYC documents.
     */
    public function verified()
    {
        $kycDocuments = KycDocument::with('user', 'businessCategory')
            ->where('is_business_verified', true)
            ->latest()
            ->paginate(15);

        return view('admin.kyc.index', compact('kycDocuments'));
    }
}
