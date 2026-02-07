<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlogRequest;
use App\Http\Resources\BlogResponse;
use App\Models\Blog;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all blogs.
     */
    public function index()
    {
        $blogs = Blog::where('user_id', auth()->id())->latest()->get();

        return $this->success(BlogResponse::collection($blogs), 'Blogs retrieved successfully.');
    }

    /**
     * Store a new blog post.
     */
    public function store(BlogRequest $request)
    {
        $data = $request->only(['title', 'content', 'status']);
        $data['user_id'] = Auth::id();

        // Handle attachment (either image or video)
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $mime = $file->getMimeType();

            if (str_starts_with($mime, 'image/')) {
                $data['attachment_type'] = 'image';
                $data['attachment'] = $file->store('blog_images', 'public');
            } elseif (str_starts_with($mime, 'video/')) {
                $data['attachment_type'] = 'video';
                $data['attachment'] = $file->store('blog_videos', 'public');
            } else {
                return $this->error([], 'Invalid file type. Only images and videos are allowed.', 422);
            }
        }

        $blog = Blog::create($data);

        return $this->success(new BlogResponse($blog), 'Blog created successfully.', 201);
    }

    /**
     * Show a single blog post.
     */
    public function show($id)
    {
        $blog = Blog::find($id);
        if (! $blog) {
            return $this->error([], 'Blog not found', 404);
        }

        return $this->success(new BlogResponse($blog), 'Blog retrieved successfully.');
    }

    /**
     * Update a blog post.
     */
    public function update(BlogRequest $request, $id)
    {
        $blog = Blog::find($id);
        if (! $blog) {
            return $this->error([], 'Blog not found', 404);
        }

        $data = $request->only(['title', 'content', 'status']);

        // Handle attachment (either image or video)
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $mime = $file->getMimeType();

            // Delete existing file before updating
            if ($blog->attachment) {
                Storage::disk('public')->delete($blog->attachment);
            }

            if (str_starts_with($mime, 'image/')) {
                $data['attachment_type'] = 'image';
                $data['attachment'] = $file->store('blog_images', 'public');
            } elseif (str_starts_with($mime, 'video/')) {
                $data['attachment_type'] = 'video';
                $data['attachment'] = $file->store('blog_videos', 'public');
            } else {
                return $this->error([], 'Invalid file type. Only images and videos are allowed.', 422);
            }
        }

        $blog->update($data);

        return $this->success(new BlogResponse($blog), 'Blog updated successfully.');
    }

    /**
     * Delete a blog post.
     */
    public function destroy($id)
    {
        $blog = Blog::find($id);
        if (! $blog) {
            return $this->error([], 'Blog not found', 404);
        }

        // Delete associated file if exists
        if ($blog->attachment) {
            Storage::disk('public')->delete($blog->attachment);
        }

        $blog->delete();

        return $this->success([], 'Blog deleted successfully.');
    }
}
