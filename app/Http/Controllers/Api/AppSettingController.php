<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppSettingResource;
use App\Models\AppSetting;
use App\Models\Page;
use App\Traits\ApiResponseTrait;

class AppSettingController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        $settings = AppSetting::all();

        return $this->success(AppSettingResource::collection($settings), 'Settings retrieved successfully.');
    }

    /**
     * Get page content by slug
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPage($slug)
    {
        $page = Page::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $page) {
            return $this->error([], 'Page not found', 404);
        }

        return $this->success([
            'title' => $page->title,
            'content' => $page->content,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'last_updated_at' => $page->last_updated_at ? $page->last_updated_at->format('Y-m-d') : null,
        ], 'Page content retrieved successfully.');
    }
}
