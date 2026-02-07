<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\VendorRegisterRequest;
use App\Models\BusinessCategory;
use App\Models\User;
use App\Services\User\UserRegistrationService;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        $businessCategory = BusinessCategory::select(['name', 'id'])->get();
        $cards = collect(config('business_categories'))
            ->map(fn ($c) => [
                ...$c,
                'src' => asset($c['src']),
            ]);

        // dd($cards);
        return view('vendor.user.register', compact('cards', 'businessCategory'));
    }

    public function store(VendorRegisterRequest $request, UserRegistrationService $service)
    {

        try {
            $user = User::where('mobile', $request->get('mobile'))->first();

            if ($user) {

                if (! $user->is_kyc_uploaded && $user->role === 'vendor') {
                    session()->put('registration_token', $user->id);

                    return redirect()
                        ->route('vendor.kycForm')
                        ->with('success', 'Account is already exist. Submit business information');
                }

                return back()->withErrors(['mobile' => 'Already registered']);
            }

            $user = $service->register($request->all());

            session()->put('registration_token', $user->id);

            return redirect()
                ->route('vendor.kycForm')
                ->with('success', 'Registration successful');
        } catch (\Throwable $th) {
            dd($th);

            return back()->withErrors(['Error' => 'User not created']);
        }
    }

    public function registrationSuccess()
    {
        return view('vendor.user.registrationSuccess');
    }
}
