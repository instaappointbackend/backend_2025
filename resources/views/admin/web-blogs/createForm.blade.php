<form action="{{ route('admin.web-blogs.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <!-- Blog style -->
    <div class="mb-3">
        <label for="style" class="form-label">Blog Style</label>
        <textarea name="style" class="form-control  @error('style') is-invalid @enderror" rows="5">{{ old('style') }}</textarea>
        @error('style')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Blog Title -->
    <div class="mb-3">
        <label class="form-label">Banner Image <span class="text-danger">*</span></label>
        <div id="banner-image">
            <div class="image-row mb-4 d-flex align-items-center gap-3">
                <div>
                    <input type="file" name="banner-images" class="form-control" required>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-8">
            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title"
                name="title" value="{{ old('title') }}" required>
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label for="user_id" class="form-label">Author <span class="text-danger">*</span></label>
            <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                <option value="">Select Author</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
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
            <input type="text" value="{{ old('sub_title') }}"
                class="form-control @error('sub_title') is-invalid @enderror" name="sub_title" required>
            @error('sub_title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft
                </option>
                <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>
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
        <textarea required name="description" class="form-control  @error('description') is-invalid @enderror" rows="5">{{ old('description') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <!-- Blog Category -->
    <div class="mb-3">
        <label for="category_id" class="form-label ">Category <span class="text-danger">*</span></label>
        <select required name="category_id" class="form-select @error('category_id') is-invalid @enderror">
            @foreach ($blogCategory as $cat)
                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
    </div>

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
        <button type="submit" class="btn btn-primary">Create Blog Post</button>
    </div>
</form>
