<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminOfferController extends Controller
{
    /**
     * Display a listing of the offers.
     */
    public function index(Request $request)
    {
        $query = Offer::where('offer_type', Offer::TYPE_ADMIN);

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by validity
        if ($request->has('validity')) {
            $today = now()->format('Y-m-d');
            if ($request->validity === 'current') {
                $query->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today);
            } elseif ($request->validity === 'expired') {
                $query->where('end_date', '<', $today);
            } elseif ($request->validity === 'upcoming') {
                $query->where('start_date', '>', $today);
            }
        }

        // Filter by search query
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhere('coupon_code', 'like', "%{$searchTerm}%");
            });
        }

        $adminOffers = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.offers.index', compact('adminOffers'));
    }

    /**
     * Show the form for creating a new offer.
     */
    public function create()
    {
        return view('admin.offers.create');
    }

    /**
     * Store a newly created offer in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'coupon_code' => 'required|string|max:50|unique:offers,coupon_code',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'usage_limit' => 'nullable|integer|min:1',
        ]);

        $validated['is_active'] = !empty($validated['is_active']) ? true : false;
        $validated['offer_type'] = Offer::TYPE_ADMIN; // Set offer type to admin
        $validated['used_count'] = 0; // Initialize used count to 0

        // If no coupon code is provided, generate one
        if (empty($validated['coupon_code'])) {
            $validated['coupon_code'] = Str::upper(Str::random(8));
        }

        $adminOffer = Offer::create($validated);

        return redirect()->route('admin.offers.index')
            ->with('success', 'Offer created successfully.');
    }

    /**
     * Display the specified offer.
     */
    public function show(Offer $offer)
    {
        // Check if it's an admin offer
        if ($offer->offer_type !== Offer::TYPE_ADMIN) {
            abort(404);
        }
        
        return view('admin.offers.show', compact('offer'));
    }

    /**
     * Show the form for editing the specified offer.
     */
    public function edit(Offer $offer)
    {
        // Check if it's an admin offer
        if ($offer->offer_type !== Offer::TYPE_ADMIN) {
            abort(404);
        }
        
        return view('admin.offers.edit', compact('offer'));
    }

    /**
     * Update the specified offer in storage.
     */
    public function update(Request $request, Offer $offer)
    {
        // Check if it's an admin offer
        if ($offer->offer_type !== Offer::TYPE_ADMIN) {
            abort(404);
        }
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'coupon_code' => 'required|string|max:50|unique:offers,coupon_code,' . $offer->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'usage_limit' => 'nullable|integer|min:1',
        ]);

        $validated['is_active'] = !empty($validated['is_active']) ? true : false;
        // Ensure offer_type remains as admin
        $validated['offer_type'] = Offer::TYPE_ADMIN;

        $offer->update($validated);

        return redirect()->route('admin.offers.index')
            ->with('success', 'Offer updated successfully.');
    }

    /**
     * Remove the specified offer from storage.
     */
    public function destroy(Offer $offer)
    {
        // Check if it's an admin offer
        if ($offer->offer_type !== Offer::TYPE_ADMIN) {
            abort(404);
        }
        
        $offer->delete();

        return redirect()->route('admin.offers.index')
            ->with('success', 'Offer deleted successfully.');
    }

    /**
     * Toggle the active status of the specified offer.
     */
    public function toggleStatus(Offer $offer)
    {
        // Check if it's an admin offer
        if ($offer->offer_type !== Offer::TYPE_ADMIN) {
            abort(404);
        }
        
        $offer->is_active = !$offer->is_active;
        $offer->save();

        return redirect()->route('admin.offers.index')
            ->with('success', 'Offer status updated successfully.');
    }
}