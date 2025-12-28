<h5 class="mb-4 fw-bold text-center">Select Business Category</h5>

<div class="row g-3">
    @foreach ($cards as $card)
        <div class="col-6 col-md-4">
            {{-- <input type="radio" class="btn-check" name="business_category_id"
                                id="category-{{ $card['id'] }}" value="{{ $card['id'] }}" form="vendorForm"
                                autocomplete="off" required
                                {{ old('business_category_id') == $card['id'] ? 'checked' : '' }}> --}}

            <label class="card h-100 text-center shadow-sm border-2 rounded-3 p-2" for="category-{{ $card['id'] }}">
                <img src="{{ $card['src'] }}" class="img-fluid mb-2" style="max-height:110px; object-fit:contain;"
                    alt="{{ $card['title'] }}">
                <div class="fw-semibold small">
                    {{ $card['title'] }}
                </div>
            </label>
        </div>
    @endforeach
</div>
