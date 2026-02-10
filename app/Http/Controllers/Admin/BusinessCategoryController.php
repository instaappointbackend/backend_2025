<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BusinessCategoryController extends Controller
{
    /**
     * Display a listing of business Categories.
     */
    public function index(Request $request)
    {
        $query = BusinessCategory::query();

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $businessCategories = $query->latest()->paginate(15);

        return view('admin.business-categories.index', compact('businessCategories'));
    }

    /**
     * Show the form for creating a new business Category.
     */
    public function create()
    {
        return view('admin.business-categories.create');
    }

    /**
     * Store a newly created business Category in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:business_categories',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('business-categories', 'public');
            $validated['image'] = $imagePath;
        }

        // Create the business Category
        BusinessCategory::create($validated);

        return redirect()->route('admin.business-categories.index')
            ->with('success', 'Business category created successfully.');
    }

    /**
     * Display the specified business Category.
     */
    public function show(BusinessCategory $businessCategory)
    {
        $businessCategory->load([
            'users' => function ($query) {
                $query->where('role', 'vendor')->latest()->limit(10);
            },
            'kycDocuments' => function ($query) {
                $query->latest()->limit(10);
            },
        ]);

        return view('admin.business-categories.show', compact('businessCategory'));
    }

    /**
     * Show the form for editing the specified business Category.
     */
    public function edit(BusinessCategory $businessCategory)
    {
        return view('admin.business-categories.edit', compact('businessCategory'));
    }

    /**
     * Update the specified business Category in storage.
     */
    public function update(Request $request, BusinessCategory $businessCategory)
    {
        $rules = [
            'name' => 'required|string|max:255|unique:business_categories,name,'.$businessCategory->id,
            'description' => 'nullable|string',
        ];

        // Only require image if there's no existing image or if remove_image is checked
        if (! $businessCategory->image || $request->has('remove_image')) {
            $rules['image'] = 'required|image|mimes:jpeg,png,jpg,gif|max:5120';
        } else {
            $rules['image'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120';
        }

        $validated = $request->validate($rules);

        // Remove current image if requested
        if ($request->has('remove_image') && $businessCategory->image) {
            Storage::disk('public')->delete($businessCategory->image);
            $validated['image'] = null;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($businessCategory->image) {
                Storage::disk('public')->delete($businessCategory->image);
            }

            $imagePath = $request->file('image')->store('business-categories', 'public');
            $validated['image'] = $imagePath;
        } elseif (! $request->hasFile('image') && ! $request->has('remove_image')) {
            // Keep the existing image if not being replaced or removed
            unset($validated['image']);
        }

        // Update the business Category
        $businessCategory->update($validated);

        return redirect()->route('admin.business-categories.index')
            ->with('success', 'Business category updated successfully.');
    }

    /**
     * Remove the specified business Category from storage.
     */
    public function destroy(BusinessCategory $businessCategory)
    {
        // Check if business Category is being used by users or KYC documents
        if ($businessCategory->users()->count() > 0 || $businessCategory->kycDocuments()->count() > 0) {
            return redirect()->route('admin.business-categories.index')
                ->with('error', 'Cannot delete business Category as it is being used by users or KYC documents.');
        }

        // Delete the image if it exists
        if ($businessCategory->image) {
            Storage::disk('public')->delete($businessCategory->image);
        }

        // Delete the business Category
        $businessCategory->delete();

        return redirect()->route('admin.business-categories.index')
            ->with('success', 'Business Category deleted successfully.');
    }
}
