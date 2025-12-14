<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessCategoryRequest;
use App\Http\Resources\BusinessCategoryResource;
use App\Models\BusinessCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use App\Traits\ApiResponseTrait;
class BusinessCategoryController extends Controller
{
    use ApiResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $businessCategories = BusinessCategory::all();
        return $this->success(BusinessCategoryResource::collection($businessCategories), 'Category retrieved successfully.');
        
    }


}
