<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // Show 2FA challenge form
    public function show()
    {

        if (!session('2fa:user:id')) {
            return redirect()->route('admin.login');
        }

        return view('auth.two-factor-challenge');
    }

    // Verify 2FA code
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required_without:recovery_code',
            'recovery_code' => 'required_without:code',
        ]);

        $userId = session('2fa:user:id');
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return redirect()->route('admin.login');
        }

        // Try authentication code
        if ($request->filled('code')) {
            $secret = Crypt::decryptString($user->two_factor_secret);
            $valid = $this->google2fa->verifyKey($secret, $request->code);

            if ($valid) {
                return $this->loginUser($user);
            }

            return back()->withErrors(['code' => 'Invalid authentication code.']);
        }

        // Try recovery code
        if ($request->filled('recovery_code')) {
            $codes = $user->getRecoveryCodes();

            if ($codes->contains($request->recovery_code)) {
                $user->replaceRecoveryCode($request->recovery_code);
                return $this->loginUser($user);
            }

            return back()->withErrors(['recovery_code' => 'Invalid recovery code.']);
        }

        return back()->withErrors(['code' => 'Please provide a code.']);
    }

    // Login user after successful 2FA
    protected function loginUser($user)
    {
        session()->forget('2fa:user:id');
        Auth::login($user, session('2fa:remember'));
        session()->forget('2fa:remember');
        session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function disabled2fa()
    {

        $userId = session('2fa:user:id');
        $user = \App\Models\User::find($userId);
        $user->two_factor_recovery_codes = null;
        $user->two_factor_secret = null;
        $user->save();

        return redirect()->intended(route('admin.password.login'));
    }
}
