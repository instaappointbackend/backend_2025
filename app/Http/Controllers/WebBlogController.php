<?php

namespace App\Http\Controllers;

use App\Http\Requests\webBlog\EditWebBlogRequest;
use App\Http\Requests\webBlog\StoreWebBlogRequest;
use App\Models\AppSetting;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogImage;
use App\Models\User;
use App\Models\WebBlog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class WebBlogController extends Controller
{
    /**
     * Display a listing of the blogs.
     */
    public function index(Request $request)
    {
        $query = WebBlog::with(['user']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by author
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by search query
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('content', 'like', "%{$searchTerm}%");
            });
        }

        $blogs = $query->latest()
            ->paginate(10)
            ->withQueryString();

        $users = User::all();

        return view('admin.web-blogs.index', compact('blogs', 'users'));
    }

    /**
     * Show the form for creating a new blog.
     */
    public function create()
    {
        $users = User::all();
        $blogCategory = BlogCategory::all();
        return view('admin.web-blogs.create', compact('users', 'blogCategory'));
    }


    /**
     * Show the form for creating a new blog.
     */
    public function store(StoreWebBlogRequest $request)
    {
        try {
            $validatedData = $request->validated();

            //banner-images
            if ($request->hasFile('banner-images')) {
                $image_path = $request->file('banner-images')->store('web_blogs_images', 'public');
                $validatedData['image_path']   = $image_path;
            }
            if (($key = array_search('banner-images', $validatedData)) !== false) {
                unset($validatedData[$key]);
            }

            $blog = WebBlog::create($validatedData);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $i => $file) {
                    $path = $file->store('web_blogs_images', 'public');
                    $desc = $request->image_descriptions[$i] ?? null;

                    BlogImage::create([
                        'blog_id' => $blog->id,
                        'image_path' => $path,
                        'image_description' => $desc,
                    ]);
                }
            }

            return redirect()->route('admin.web-blogs.index')
                ->with('success', 'Blog post created successfully.');
        } catch (\Throwable $th) {

            return redirect()->route('admin.web-blogs.create')
                ->with('false', 'Something went wrong');
        }
    }

    /**
     * Display the specified blog.
     */
    public function show($id)
    {
        $blog = WebBlog::with(['user', 'images'])->where('id', $id)->first();

        //dd('here', $blog);
        return view('admin.web-blogs.show', compact('blog'));
    }

    /**
     * Show the form for editing the specified blog.
     */
    public function edit($id)
    {
        $blog = WebBlog::with(['user', 'images'])->where('id', $id)->first();
        $users = User::all();
        $create = false;
        $blogCategory = BlogCategory::all();
        return view('admin.web-blogs.edit', compact('blog', 'users', 'blogCategory', 'create'));
    }

    /**
     * Update the specified blog in storage.
     */
    // public function update(EditWebBlogRequest $request, $id)
    // {
    //     try {
    //         $blog = WebBlog::find($id);
    //         $validatedData = $request->validated();

    //         if ($request->hasFile('banner-images')) {
    //             if ($blog->image_path && Storage::disk('public')->exists($blog->image_path)) {
    //                 Storage::disk('public')->delete($blog->image_path);
    //             }

    //             $image_path = $request->file('banner-images')->store('web_blogs_images', 'public');
    //             $validatedData['image_path']   = $image_path;
    //         }

    //         // Clean up banner field
    //         unset($validatedData['banner-images']);

    //         // === Handle Deletion of Existing Images ===
    //         if ($request->has('delete_images')) {
    //             foreach ($request->delete_images as $imgId) {
    //                 $img = BlogImage::find($imgId);
    //                 if ($img && Storage::disk('public')->exists($img->image_path)) {
    //                     Storage::disk('public')->delete($img->image_path);
    //                 }
    //                 $img?->delete();
    //             }
    //         }

    //         if ($request->hasFile('images')) {
    //             foreach ($request->file('images') as $i => $file) {
    //                 $path = $file->store('web_blogs_images', 'public');
    //                 $desc = $request->image_descriptions[$i] ?? null;

    //                 BlogImage::create([
    //                     'blog_id' => $blog->id,
    //                     'image_path' => $path,
    //                     'image_description' => $desc,
    //                 ]);
    //             }
    //         }
    //         $blog->update($validatedData);
    //         return redirect()->route('admin.web-blogs.index')
    //             ->with('success', 'Blog post updated successfully.');
    //     } catch (\Throwable $th) {
    //         return redirect()->route('admin.web-blogs.create')
    //             ->with('false', 'Something went wrong');
    //     }
    // }

    public function update(EditWebBlogRequest $request, $id)
    {
        try {
            $blog = WebBlog::findOrFail($id);
            $validatedData = $request->validated();

            // === Update Banner Image ===
            if ($request->hasFile('banner-images')) {
                if ($blog->image_path && Storage::disk('public')->exists($blog->image_path)) {
                    Storage::disk('public')->delete($blog->image_path);
                }

                $image_path = $request->file('banner-images')->store('web_blogs_images', 'public');
                $validatedData['image_path'] = $image_path;
            }

            // Clean up form key
            unset($validatedData['banner-images']);

            // === Delete Selected Existing Images ===
            if ($request->has('delete_images')) {
                foreach ($request->delete_images as $imgId) {
                    $img = BlogImage::find($imgId);
                    if ($img && Storage::disk('public')->exists($img->image_path)) {
                        Storage::disk('public')->delete($img->image_path);
                    }
                    $img?->delete();
                }
            }

            // === Update Existing Images ===
            if ($request->has('existing_images')) {
                foreach ($request->existing_images as $imgId => $data) {
                    $img = BlogImage::find($imgId);
                    if (!$img) continue;

                    // Update description
                    $img->image_description = $data['description'] ?? $img->image_description;

                    // Replace file if new file is uploaded
                    if (isset($data['file']) && $data['file'] instanceof \Illuminate\Http\UploadedFile) {
                        // Delete old file
                        if ($img->image_path && Storage::disk('public')->exists($img->image_path)) {
                            Storage::disk('public')->delete($img->image_path);
                        }

                        // Store new file
                        $newPath = $data['file']->store('web_blogs_images', 'public');
                        $img->image_path = $newPath;
                    }

                    $img->save();
                }
            }

            // === Add New Images ===
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $i => $file) {
                    if ($file && $file->isValid()) {
                        $path = $file->store('web_blogs_images', 'public');
                        $desc = $request->image_descriptions[$i] ?? null;

                        BlogImage::create([
                            'blog_id' => $blog->id,
                            'image_path' => $path,
                            'image_description' => $desc,
                        ]);
                    }
                }
            }

            // === Update Blog Data ===
            $blog->update($validatedData);

            return redirect()->route('admin.web-blogs.index')
                ->with('success', 'Blog post updated successfully.');
        } catch (\Throwable $th) {
            // Log the error for debugging
            \Log::error('Blog Update Error: ', ['error' => $th]);

            return redirect()->route('admin.web-blogs.create')
                ->with('false', 'Something went wrong');
        }
    }


    /**
     * Remove the specified blog from storage.
     */
    public function destroy($id)
    {
        try {
            $blog = WebBlog::with('images')->findOrFail($id);

            // 1. Delete banner image file
            // if ($blog->image_path && Storage::disk('public')->exists($blog->image_path)) {
            //     Storage::disk('public')->delete($blog->image_path);
            // }

            // 2. Delete related images and their files
            // foreach ($blog->images as $image) {
            //     if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
            //         Storage::disk('public')->delete($image->image_path);
            //     }
            //     $image->delete(); // Delete record from DB
            // }

            // 3. Delete the blog record
            $blog->status = 'deleted';
            $blog->save();

            return redirect()->route('admin.web-blogs.index')
                ->with('success', 'Blog post deleted successfully.');
        } catch (\Throwable $th) {
            return redirect()->back()
                ->with('false', 'Something went wrong while deleting the blog post.');
        }
    }

    public function blogs()
    {
        // Get 6 posts per page
        $posts = WebBlog::where('status', 'published')->orderBy('id', 'desc')->paginate(9);

        $contactInfo = [
            'support_email' => $this->getSetting('support_email', 'support@instaappoint.com'),
            'support_phone' => $this->getSetting('support_phone', '+1 (555) 123-4567'),
            'address' => $this->getSetting('address', '123 App Street, Tech City, CA 12345'),
            'social_facebook' => $this->getSetting('social_facebook', 'https://www.facebook.com/people/Insta-Appoint/61580330438370/'),
            'social_twitter' => $this->getSetting('social_twitter', 'https://x.com/InstaAppoint'),
            'social_instagram' => $this->getSetting('social_instagram', 'https://www.instagram.com/insta_appoint/?igsh=MWo2eWljZWl1ZjRlYw%3D%3D#'),
            'social_linkedin' => $this->getSetting('social_linkedin', 'https://www.linkedin.com/company/instaappoint/about/?viewAsMember=true'),
        ];

        return view('admin.web-blogs.listing', compact('posts', 'contactInfo'));
    }


    public function blog($slug)
    {
        // Get 6 posts per page
        $post = WebBlog::with(['images', 'user'])->where('slug', $slug)->first();

        $post->increment('view_count');

        $topBlogs = WebBlog::where('read_count', '!=', 0)->orderBy('read_count', 'desc')->orderBy('view_count', 'desc')
            ->take(6)
            ->get();


        $contactInfo = [
            'support_email' => $this->getSetting('support_email', 'support@instaappoint.com'),
            'support_phone' => $this->getSetting('support_phone', '+1 (555) 123-4567'),
            'address' => $this->getSetting('address', '123 App Street, Tech City, CA 12345'),
            'social_facebook' => $this->getSetting('social_facebook', 'https://www.facebook.com/people/Insta-Appoint/61580330438370/'),
            'social_twitter' => $this->getSetting('social_twitter', 'https://x.com/InstaAppoint'),
            'social_instagram' => $this->getSetting('social_instagram', 'https://www.instagram.com/insta_appoint/?igsh=MWo2eWljZWl1ZjRlYw%3D%3D#'),
            'social_linkedin' => $this->getSetting('social_linkedin', 'https://www.linkedin.com/company/instaappoint/about/?viewAsMember=true'),
        ];

        //dd('here', $post);
        return view('admin.web-blogs.sinlgeBlog', compact('post', 'contactInfo', 'topBlogs'));
    }

    private function getSetting($key, $default = null)
    {
        // Try to get from cache first
        $settings = Cache::remember('app_settings', 3600, function () {
            return AppSetting::pluck('value', 'key')->toArray();
        });

        return $settings[$key] ?? $default;
    }

    public function incrementReadCount($id)
    {
        $key = 'blog_read_' . $id;
        if (session()->has($key)) {
            return response()->json(['success' => false, 'message' => 'Already counted']);
        }

        $blog = WebBlog::findOrFail($id);
        $blog->increment('read_count');

        session()->put($key, true);

        return response()->json(['success' => true, 'read_count' => $blog->read_count]);
    }
}
