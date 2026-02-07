<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OfferRequest;
use App\Http\Resources\OfferResponse;
use App\Models\Offer;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfferController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of all provider offers for the authenticated user.
     */
    public function index()
    {
        $offers = Offer::forProvider(Auth::id())
            ->with('service')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(
            OfferResponse::collection($offers),
            'Offers retrieved successfully.'
        );
    }

    /**
     * Get active provider offers for the authenticated user.
     */
    public function active()
    {
        $offers = Offer::forProvider(Auth::id())
            ->with('service')
            ->active()
            ->current()
            ->get();

        return $this->success(
            OfferResponse::collection($offers),
            'Active offers retrieved successfully.'
        );
    }

    /**
     * Store a newly created offer (provider or admin).
     */
    public function store(OfferRequest $request)
    {
        $data = $request->validated();

        // Check if creating an admin offer (requires admin permission)
        if ($data['offer_type'] === Offer::TYPE_ADMIN) {
            // Verify the user has admin permissions
            if (! Auth::user()->isAdmin()) {
                return $this->error([], 'Unauthorized to create admin offers.', 403);
            }

            $data['user_id'] = null; // Admin offers don't have a specific user
        } else {
            // Provider offer
            $data['user_id'] = Auth::id();
            $data['offer_type'] = Offer::TYPE_PROVIDER; // Enforce correct type
        }

        // Initialize used_count to 0
        $data['used_count'] = 0;

        $offer = Offer::create($data);

        if ($data['offer_type'] === Offer::TYPE_PROVIDER) {
            $offer->load('service');
        }

        return $this->success(
            new OfferResponse($offer),
            'Offer created successfully.'
        );
    }

    /**
     * Display the specified offer.
     */
    public function show($id)
    {
        $offer = $this->getOfferOrFail($id);

        if ($offer->offer_type === Offer::TYPE_PROVIDER) {
            $offer->load('service');
        }

        return $this->success(
            new OfferResponse($offer),
            'Offer retrieved successfully.'
        );
    }

    /**
     * Update the specified offer.
     */
    public function update(OfferRequest $request, $id)
    {
        $offer = $this->getOfferOrFail($id);

        $data = $request->validated();

        // Ensure offer_type cannot be changed
        $data['offer_type'] = $offer->offer_type;

        // Admin offers can only be updated by admins
        if ($offer->offer_type === Offer::TYPE_ADMIN && ! Auth::user()->isAdmin()) {
            return $this->error([], 'Unauthorized to update admin offers.', 403);
        }

        $offer->update($data);

        if ($offer->offer_type === Offer::TYPE_PROVIDER) {
            $offer->load('service');
        }

        return $this->success(
            new OfferResponse($offer),
            'Offer updated successfully.'
        );
    }

    /**
     * Remove the specified offer.
     */
    public function destroy($id)
    {
        $offer = $this->getOfferOrFail($id);

        // Admin offers can only be deleted by admins
        if ($offer->offer_type === Offer::TYPE_ADMIN && ! Auth::user()->isAdmin()) {
            return $this->error([], 'Unauthorized to delete admin offers.', 403);
        }

        $offer->delete();

        return $this->success(
            null,
            'Offer deleted successfully.'
        );
    }

    /**
     * Toggle the active status of the specified offer.
     */
    public function toggleStatus($id)
    {
        $offer = $this->getOfferOrFail($id);

        // Admin offers can only be modified by admins
        if ($offer->offer_type === Offer::TYPE_ADMIN && ! Auth::user()->isAdmin()) {
            return $this->error([], 'Unauthorized to modify admin offers.', 403);
        }

        $offer->is_active = ! $offer->is_active;
        $offer->save();

        return $this->success(
            new OfferResponse($offer),
            'Offer status updated successfully.'
        );
    }

    /**
     * Get all active global admin offers.
     */
    public function globalOffers($providerId = null, $service_id = null)
    {
        // Get admin offers
        $adminOffersQuery = Offer::admin()
            ->active()
            ->current()
            ->available();
        $adminOffers = $adminOffersQuery->get();

        // Get provider-specific offers
        $providerOffers = collect();
        if ($providerId) {
            $providerOffersQuery = Offer::forProvider($providerId)
                ->active()
                ->current()
                ->available()
                ->with('service');

            // Filter provider offers by service if service_id is provided
            if ($service_id) {
                $providerOffersQuery->where(function ($query) use ($service_id) {
                    $query->where('service_id', $service_id)
                        ->orWhereNull('service_id'); // Include offers not tied to any specific service
                });
            }

            $providerOffers = $providerOffersQuery->get();
        }

        // Combine both sets of offers
        $allOffers = $adminOffers->concat($providerOffers);

        return $this->success(
            OfferResponse::collection($allOffers),
            'Service-specific offers retrieved successfully.'
        );
    }

    public function globalOffers1($providerId = null)
    {
        $adminOffers = Offer::admin()
            ->active()
            ->current()
            ->available()
            ->get();

        // Get provider-specific offers for the requested provider
        $providerOffers = [];
        if ($providerId) {
            $providerOffers = Offer::forProvider($providerId)
                ->active()
                ->current()
                ->available()
                ->with('service')
                ->get();
        }

        // Combine both sets of offers
        $allOffers = $adminOffers->concat($providerOffers);

        return $this->success(
            OfferResponse::collection($allOffers),
            'Global offers retrieved successfully.'
        );
    }

    /**
     * Validate a coupon code and return offer details if valid.
     */
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $couponCode = $request->coupon_code;

        $offer = Offer::admin()
            ->where('coupon_code', $couponCode)
            ->active()
            ->current()
            ->available()
            ->first();

        if (! $offer) {
            return $this->error([], 'Invalid or expired coupon code.', 404);
        }

        return $this->success(
            new OfferResponse($offer),
            'Coupon code is valid.'
        );
    }

    /**
     * Apply a coupon code (increment usage count).
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $couponCode = $request->coupon_code;

        $offer = Offer::admin()
            ->where('coupon_code', $couponCode)
            ->active()
            ->current()
            ->available()
            ->first();

        if (! $offer) {
            return $this->error([], 'Invalid or expired coupon code.', 404);
        }

        // Increment the used count
        $offer->incrementUsedCount();

        return $this->success(
            new OfferResponse($offer),
            'Coupon applied successfully.'
        );
    }

    /**
     * List admin offers (admin only).
     */
    public function adminOffers()
    {
        // Verify the user has admin permissions
        if (! Auth::user()->isAdmin()) {
            return $this->error([], 'Unauthorized to view admin offers.', 403);
        }

        $offers = Offer::admin()
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(
            OfferResponse::collection($offers),
            'Admin offers retrieved successfully.'
        );
    }

    /**
     * Get offer by ID or return 404 error.
     * For provider offers, ensures the offer belongs to the authenticated user.
     */
    protected function getOfferOrFail($id)
    {
        $offer = Offer::find($id);

        if (! $offer) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Offer not found');
        }

        // If it's a provider offer, ensure it belongs to current user
        if ($offer->offer_type === Offer::TYPE_PROVIDER && $offer->user_id !== Auth::id()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Unauthorized access to offer');
        }

        return $offer;
    }
}
