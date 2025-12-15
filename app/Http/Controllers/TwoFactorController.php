<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // Show 2FA settings page
    public function index()
    {
        //$user = auth()->user();

        $id = session()->get('2fa:user:id');
        $user = \App\Models\User::find($id);

        return view('auth.two-factor', [
            'user' => $user,
            'qrCode' => $user->two_factor_secret && !$user->two_factor_confirmed_at
                ? $this->generateQrCode($user)
                : null,
            'recoveryCodes' => $user->two_factor_secret && !$user->two_factor_confirmed_at
                ? $user->getRecoveryCodes()
                : null,
        ]);
    }

    // Enable 2FA
    public function enable(Request $request)
    {
        $id = session()->get('2fa:user:id');
        $user = \App\Models\User::find($id);

        // Generate secret key
        $secret = $this->google2fa->generateSecretKey();

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        // Save to database (not confirmed yet)
        $user->update([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => null,
        ]);

        return redirect()->route('admin.two-factor.index')
            ->with('status', '2FA enabled. Please scan the QR code and confirm.');
    }

    // Confirm 2FA setup
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|numeric',
        ]);

        $id = session()->get('2fa:user:id');
        $user = \App\Models\User::find($id);
        $secret = Crypt::decryptString($user->two_factor_secret);

        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if ($valid) {
            $user->update([
                'two_factor_confirmed_at' => now(),
            ]);

            return redirect()->route('admin.two-factor.index')
                ->with('status', '2FA confirmed successfully!');
        }

        return back()->withErrors(['code' => 'Invalid authentication code.']);
    }

    // Disable 2FA
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);
        $id = session()->get('2fa:user:id');
        $user = \App\Models\User::find($id);
        $user()->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return redirect()->route('admin.two-factor.index')
            ->with('status', '2FA disabled successfully.');
    }

    // Regenerate recovery codes
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $recoveryCodes = $this->generateRecoveryCodes();

        auth()->user()->update([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes)),
        ]);

        return redirect()->route('admin.two-factor.index')
            ->with('recoveryCodes', $recoveryCodes)
            ->with('status', 'Recovery codes regenerated.');
    }

    // Generate QR code
    protected function generateQrCode($user): string
    {
        $secret = Crypt::decryptString($user->two_factor_secret);

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString($qrCodeUrl);
    }

    // Generate recovery codes
    protected function generateRecoveryCodes(): array
    {
        return Collection::times(8, function () {
            return strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10));
        })->toArray();
    }
}
