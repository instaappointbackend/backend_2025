<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Plan::query();

        // Filter by status (0/1)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search (name)
        if ($request->filled('search')) {
            $searchTerm = trim($request->search);

            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%");
            });
        }

        $plans = $query->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.Plans.list', compact('plans'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.Plans.plan');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Add slug to the request
        $request->merge([
            'slug' => Str::slug($request->title)
        ]);

        $plan = Plan::create($request->only([
            'title',
            'slug',
            'original_price',
            'discounted_price',
            'discount',
            'duration',
            'badge',
            'tagline',
            'type'
        ]));

        foreach ($request->features as $feature) {
            $plan->features()->create($feature);
        }

        return redirect()->back()->with('success', 'Plan created');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $plan = Plan::with(['features'])->where('id', $id)->first();
        return view('admin.Plans.show', compact('plan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $plan = Plan::with(['features'])->where('id', $id)->first();
        return view('admin.Plans.plan', ['plan' => $plan]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $plan = Plan::findOrFail($id);

        // ✅ Validate request
        $request->validate([
            'type' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'original_price' => 'nullable|numeric',
            'discounted_price' => 'nullable|numeric',
            'discount' => 'nullable|string|max:50',
            'duration' => 'nullable|string|max:50',
            'badge' => 'nullable|string|max:50',
            'tagline' => 'nullable|string|max:255',
            'features.*.text' => 'nullable|string|max:255',
            'features.*.included' => 'nullable|boolean',
        ]);

        // ✅ Update plan
        $plan->update([
            'type' => $request->type,
            'slug' => Str::slug($request->title),
            'title' => $request->title,
            'original_price' => $request->original_price,
            'discounted_price' => $request->discounted_price,
            'discount' => $request->discount,
            'duration' => $request->duration,
            'badge' => $request->badge,
            'tagline' => $request->tagline,
        ]);

        // ✅ Delete old features
        $plan->features()->delete();

        // ✅ Insert new features
        if ($request->features) {
            foreach ($request->features as $feature) {
                if (!empty($feature['text'])) {
                    $plan->features()->create([
                        'text' => $feature['text'],
                        'included' => $feature['included'] ?? 0,
                    ]);
                }
            }
        }

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Plan updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
