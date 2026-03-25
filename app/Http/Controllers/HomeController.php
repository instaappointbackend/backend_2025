<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Page;
use App\Models\Plan;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Display the home page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get contact information from settings
        $contactInfo = [
            'support_email' => $this->getSetting('support_email', 'support@instaappoint.com'),
            'support_phone' => $this->getSetting('support_phone', '+1 (555) 123-4567'),
            'address' => $this->getSetting('address', '123 App Street, Tech City, CA 12345'),
            'social_facebook' => $this->getSetting('social_facebook', null),
            'social_twitter' => $this->getSetting('social_twitter', null),
            'social_instagram' => $this->getSetting('social_instagram', null),
            'social_linkedin' => $this->getSetting('social_linkedin', null),
        ];

        $plans = Plan::with('features')->where('type', 'normal')->get();

        return view('public.homePage.welcome', compact('contactInfo', 'plans'));
    }

    /**
     * Display a page by slug.
     *
     * @return \Illuminate\View\View
     */
    public function show(Page $page)
    {
        // Check if page is active
        if (! $page->is_active) {
            abort(404);
        }

        return view('pages.show', compact('page'));
    }

    /**
     * Get a setting value by key.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    private function getSetting($key, $default = null)
    {
        // if ($key === 'social_instagram') {
        // Try to get from cache first
        $settings = Cache::remember('app_settings', 3600, function () {
            return AppSetting::pluck('value', 'key')->toArray();
        });
        // $settings = AppSetting::pluck('value', 'key')->toArray();

        return $settings[$key] ?? $default;
        // }
    }

    public function downloadApk()
    {
        return view('downloadApk');
    }

    public function socialPlans()
    {
        $plans = Plan::with('features')->where('type', 'social')->get();

        return view('public.socialPage.socialPage', compact('plans'));
    }
}
