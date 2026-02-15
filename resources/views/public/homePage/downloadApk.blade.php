@extends('layouts.app')

@section('styles')
    <style>
        .scanner-img {
            max-width: 220px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .scanner-img:hover {
            transform: scale(1.05);
        }

        .store-btn img {
            height: 55px;
        }
    </style>
@endsection

@section('content')
    <div class="container text-center py-5">
        <h2 class="mb-4">Scan or Download Our App</h2>

        <!-- Scanner Images -->
        <div class="row justify-content-center mb-4">
            <div class="col-md-3 col-6 mb-3">
                <!-- Google Play QR -->
                <a href="https://play.google.com/store/apps/details?id=in.instaappoint.app&pcampaignid=web_share"
                    target="_blank">
                    <img src="{{ asset('images/android.jpeg') }}" alt="Google Play Scanner" class="scanner-img img-fluid">
                </a>
                <p class="mt-2">Scan for Android</p>
            </div>

            <div class="col-md-3 col-6 mb-3">
                <!-- Apple App Store QR -->
                <a href="https://apps.apple.com/in/app/instaappoint/id6747049926" target="_blank">
                    <img src="{{ asset('images/iso.jpeg') }}" alt="Apple Store Scanner" class="scanner-img img-fluid">
                </a>
                <p class="mt-2">Scan for iOS</p>
            </div>
        </div>

        <!-- Download Buttons -->
        <div class="d-flex justify-content-center gap-3">
            <a href="https://play.google.com/store/apps/details?id=in.instaappoint.app&pcampaignid=web_share"
                target="_blank" class="d-inline-block mb-2">
                <img src="{{ asset('images/Google_Play_Store_badge_EN.svg') }}?v=1" alt="Get it on Google Play"
                    class="img-fluid download-badge" style="width: 150px; height: auto;">
            </a>

            <a href="https://apps.apple.com/in/app/instaappoint/id6747049926" target="_blank" class="d-inline-block">
                <img src="{{ asset('images/Download_on_the_App_Store_Badge.svg') }}?v=2" alt="Download on App Store"
                    class="img-fluid download-badge" style="width: 150px; height: auto;">
            </a>
        </div>
    </div>
@endsection
