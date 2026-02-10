<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FAQRequest;
use App\Http\Resources\FAQResource;
use App\Models\FAQ;
use App\Traits\ApiResponseTrait;

class FAQController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all FAQs.
     */
    public function index()
    {
        $faqs = FAQ::where('is_active', true)->get();

        return $this->success(FAQResource::collection($faqs), 'FAQs retrieved successfully.');
    }

    /**
     * Store a new FAQ.
     */
    public function store(FAQRequest $request)
    {
        $faq = FAQ::create($request->validated());

        return $this->success(new FAQResource($faq), 'FAQ created successfully.', 201);
    }

    /**
     * Get a single FAQ by ID.
     */
    public function show($id)
    {
        $faq = FAQ::find($id);
        if (! $faq) {
            return $this->error([], 'FAQ not found.', 404);
        }

        return $this->success(new FAQResource($faq), 'FAQ retrieved successfully.');
    }

    /**
     * Update an existing FAQ.
     */
    public function update(FAQRequest $request, $id)
    {
        $faq = FAQ::find($id);
        if (! $faq) {
            return $this->error([], 'FAQ not found.', 404);
        }

        $faq->update($request->validated());

        return $this->success(new FAQResource($faq), 'FAQ updated successfully.');
    }

    /**
     * Delete an FAQ.
     */
    public function destroy($id)
    {
        $faq = FAQ::find($id);
        if (! $faq) {
            return $this->error([], 'FAQ not found.', 404);
        }

        $faq->delete();

        return $this->success([], 'FAQ deleted successfully.');
    }
}
