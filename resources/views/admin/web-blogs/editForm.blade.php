<form action="{{ route('admin.web-blogs.update', $blog->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <!-- Blog style -->
    <div class="mb-3">
        <label for="style" class="form-label">Blog Style</label>
        <textarea name="style" class="form-control  @error('style') is-invalid @enderror" rows="5">{{ old('style', $blog->style ?? '') }}</textarea>
        @error('style')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Blog Title -->
    <div class="mb-3">
        <label class="form-label">Banner Image <span class="text-danger">*</span></label>
        <div>
            <div class="image-row mb-4 d-flex align-items-center gap-3">

                <div>
                    <input type="file" name="banner-images"
                        class="form-control @error('banner-images') is-invalid @enderror">
                    @error('banner-images')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Show existing image if available (edit mode) -->
                <div>
                    <img src="{{ asset('storage/' . $blog->image_path) }}" alt="Current Banner"
                        style="max-height: 80px; border: 1px solid #ccc; padding: 2px;">

                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-8">
            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title"
                name="title" value="{{ old('title', $blog->title ?? '') }}" required>
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label for="user_id" class="form-label">Author <span class="text-danger">*</span></label>
            <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                <option value="">Select Author</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}"
                        {{ old('user_id', $blog->user_id ?? '') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            @error('user_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Blog Sub Title -->
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="sub_title" class="form-label">Sub Title <span class="text-danger">*</span> </label>
            <input type="text" value="{{ old('sub_title', $blog->sub_title ?? '') }}"
                class="form-control  @error('sub_title') is-invalid @enderror" name="sub_title">
            @error('sub_title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                <option value="draft" {{ old('status', $blog->status ?? '') == 'draft' ? 'selected' : '' }}>Draft
                </option>
                <option value="published" {{ old('status', $blog->status ?? '') == 'published' ? 'selected' : '' }}>
                    Published
                </option>
            </select>
            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Blog Description -->
    <div class="mb-3">
        <label for="description" class="form-label">Blog Description <span class="text-danger">*</span></label>
        <textarea name="description" class="form-control @error('status') is-invalid @enderror" rows="5" required>{{ old('description', $blog->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Blog Category -->
    <div class="mb-3">
        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
            @foreach ($blogCategory as $cat)
                <option value="{{ $cat->id }}"
                    {{ old('category_id', $blog->category_id ?? '') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        @error('category_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Existing Multiple Images -->
    @if (count($blog->images))
        <div class="mb-3">
            <label class="form-label">Existing Images (Update or Delete)</label>
            <div class="row">
                @foreach ($blog->images as $index => $image)
                    <div class="col-md-6 mb-4">
                        <div class="border p-3 position-relative">
                            <!-- Existing Image Preview -->
                            <img src="{{ asset('storage/' . $image->image_path) }}" alt="Image"
                                style="max-width: 100%; height: auto; margin-bottom: 10px;" />

                            <!-- Upload New Image (Optional) -->
                            <label class="form-label">Replace Image (optional):</label>
                            <input type="file" name="existing_images[{{ $image->id }}][file]"
                                class="form-control mb-2">

                            <!-- Edit Description -->
                            <label class="form-label">Image Description:</label>
                            <textarea name="existing_images[{{ $image->id }}][description]" rows="3" class="form-control mb-2">{{ old("existing_images.{$image->id}.description", $image->image_description) }}</textarea>

                            <!-- Delete Checkbox -->
                            <div class="form-check mt-2">
                                <input type="checkbox" name="delete_images[]" value="{{ $image->id }}"
                                    class="form-check-input" id="delete_image_{{ $image->id }}">
                                <label class="form-check-label" for="delete_image_{{ $image->id }}">Delete this
                                    image</label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif



    <!-- Image Uploads -->
    <div class="mb-3">
        <label class="form-label">Images & Descriptions</label>
        <div id="image-upload-group">
            <div class="image-row mb-4">
                <div class="mb-2">
                    <input type="file" name="images[]" class="form-control">
                </div>
                <div>
                    <textarea name="image_descriptions[]" class="form-control" rows="3" placeholder="Image Description"></textarea>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addImageField()">+ Add More
            Image</button>
    </div>
    {{-- <!-- Submit Button -->
                <button type="submit" class="btn btn-success mt-3">Create Blog</button> --}}

    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
        <button type="reset" class="btn btn-secondary me-md-2">Reset</button>
        <button type="submit" class="btn btn-primary">Submit</button>
    </div>
</form>
