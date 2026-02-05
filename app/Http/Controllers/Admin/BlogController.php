<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    /**
     * Display a listing of the blogs.
     */
    public function index(Request $request)
    {
        $query = Blog::with('user');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by author
        if ($request->filled('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by search query
        if ($request->filled('search') && $request->search) {
            $searchTerm = trim($request->search);

            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('content', 'like', "%{$searchTerm}%");
            });
        }

        $blogs = $query->latest()
            ->paginate(10)
            ->withQueryString();

        $users = User::all();

        return view('admin.blogs.index', compact('blogs', 'users'));
    }

    /**
     * Show the form for creating a new blog.
     */
    public function create()
    {
        $users = User::all();

        return view('admin.blogs.create', compact('users'));
    }

    /**
     * Store a newly created blog in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,mp4,webm,mov|max:10240', // 10MB limit
            'attachment_type' => 'nullable|string',
            'status' => 'required|string|in:draft,published',
            'user_id' => 'required|exists:users,id',
        ]);

        // Handle attachment upload
        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('blog-attachments', 'public');

            // Set attachment type based on file extension
            $extension = $request->file('attachment')->getClientOriginalExtension();
            $mimeType = $request->file('attachment')->getMimeType();

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) || strpos($mimeType, 'image/') === 0) {
                $validated['attachment_type'] = 'image';
            } elseif (in_array($extension, ['mp4', 'webm', 'mov']) || strpos($mimeType, 'video/') === 0) {
                $validated['attachment_type'] = 'video';
            } else {
                $validated['attachment_type'] = 'document';
            }
        }

        $blog = Blog::create($validated);

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog post created successfully.');
    }

    /**
     * Display the specified blog.
     */
    public function show(Blog $blog)
    {
        $blog->load('user');

        return view('admin.blogs.show', compact('blog'));
    }

    /**
     * Show the form for editing the specified blog.
     */
    public function edit(Blog $blog)
    {
        $users = User::all();

        return view('admin.blogs.edit', compact('blog', 'users'));
    }

    /**
     * Update the specified blog in storage.
     */
    public function update(Request $request, Blog $blog)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,mp4,webm,mov|max:10240', // 10MB limit
            'remove_attachment' => 'nullable|string',
            'status' => 'required|string|in:draft,published',
            'user_id' => 'required|exists:users,id',
        ]);

        // Remove existing attachment if requested
        if ($request->has('remove_attachment') && $blog->attachment) {
            Storage::disk('public')->delete($blog->attachment);
            $validated['attachment'] = null;
            $validated['attachment_type'] = null;
        }

        // Handle attachment upload
        if ($request->hasFile('attachment')) {
            // Delete old attachment if exists
            if ($blog->attachment) {
                Storage::disk('public')->delete($blog->attachment);
            }

            $validated['attachment'] = $request->file('attachment')->store('blog-attachments', 'public');

            // Set attachment type based on file extension
            $extension = $request->file('attachment')->getClientOriginalExtension();
            $mimeType = $request->file('attachment')->getMimeType();

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) || strpos($mimeType, 'image/') === 0) {
                $validated['attachment_type'] = 'image';
            } elseif (in_array($extension, ['mp4', 'webm', 'mov']) || strpos($mimeType, 'video/') === 0) {
                $validated['attachment_type'] = 'video';
            } else {
                $validated['attachment_type'] = 'document';
            }
        }

        // Remove keys that shouldn't be in the update
        unset($validated['remove_attachment']);
        if (!$request->hasFile('attachment')) {
            unset($validated['attachment']);
        }

        $blog->update($validated);

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog post updated successfully.');
    }

    /**
     * Remove the specified blog from storage.
     */
    public function destroy(Blog $blog)
    {
        // Delete attachment if exists
        if ($blog->attachment) {
            Storage::disk('public')->delete($blog->attachment);
        }

        $blog->delete();

        return redirect()->route('admin.blogs.index')
            ->with('success', 'Blog post deleted successfully.');
    }
}
