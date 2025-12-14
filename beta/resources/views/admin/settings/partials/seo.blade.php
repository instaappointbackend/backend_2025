<!-- resources/views/admin/settings/partials/seo.blade.php -->
<div class="tab-pane fade" id="seo-settings" role="tabpanel">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">SEO Settings</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="meta_title" class="form-label">Meta Title</label>
                <input type="text" class="form-control" id="meta_title" name="meta_title" value="{{ $seoSettings['meta_title'] }}">
            </div>

            <div class="mb-3">
                <label for="meta_description" class="form-label">Meta Description</label>
                <textarea class="form-control" id="meta_description" name="meta_description" rows="3">{{ $seoSettings['meta_description'] }}</textarea>
            </div>

            <div class="mb-3">
                <label for="meta_keywords" class="form-label">Meta Keywords</label>
                <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="{{ $seoSettings['meta_keywords'] }}">
                <div class="form-text">Separate keywords with commas.</div>
            </div>

            <h6 class="mt-4 mb-3">Analytics</h6>

            <div class="mb-3">
                <label for="google_analytics_id" class="form-label">Google Analytics ID</label>
                <input type="text" class="form-control" id="google_analytics_id" name="google_analytics_id" value="{{ $seoSettings['google_analytics_id'] }}">
                <div class="form-text">Format: UA-XXXXXXXXX-X or G-XXXXXXXXXX</div>
            </div>

            <div class="mb-3">
                <label for="facebook_pixel_id" class="form-label">Facebook Pixel ID</label>
                <input type="text" class="form-control" id="facebook_pixel_id" name="facebook_pixel_id" value="{{ $seoSettings['facebook_pixel_id'] }}">
            </div>

            <h6 class="mt-4 mb-3">Social Media</h6>

            <div class="mb-3">
                <label for="social_facebook" class="form-label">Facebook URL</label>
                <input type="url" class="form-control" id="social_facebook" name="social_facebook" value="{{ isset($seoSettings['social_facebook']) ? $seoSettings['social_facebook'] : '' }}">
            </div>

            <div class="mb-3">
                <label for="social_twitter" class="form-label">Twitter URL</label>
                <input type="url" class="form-control" id="social_twitter" name="social_twitter" value="{{ isset($seoSettings['social_twitter']) ? $seoSettings['social_twitter'] : '' }}">
            </div>

            <div class="mb-3">
                <label for="social_instagram" class="form-label">Instagram URL</label>
                <input type="url" class="form-control" id="social_instagram" name="social_instagram" value="{{ isset($seoSettings['social_instagram']) ? $seoSettings['social_instagram'] : '' }}">
            </div>

            <div class="mb-3">
                <label for="social_linkedin" class="form-label">LinkedIn URL</label>
                <input type="url" class="form-control" id="social_linkedin" name="social_linkedin" value="{{ isset($seoSettings['social_linkedin']) ? $seoSettings['social_linkedin'] : '' }}">
            </div>
        </div>
    </div>
</div>
