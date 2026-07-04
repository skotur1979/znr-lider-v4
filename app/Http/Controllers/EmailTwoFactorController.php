<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Filament\Facades\Filament;

class EmailTwoFactorController extends Controller
{
    public function verify(Request $request)
    {
        $user = Filament::auth()->user() ?? auth()->user();

        abort_unless($user && $user->isSuperAdmin(), 403);

        if (! session()->get('superadmin_email_2fa_code_sent')) {
            $this->sendCode($user);

            session()->put('superadmin_email_2fa_code_sent', true);
        }

        return view('auth.email-2fa-verify');
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = Filament::auth()->user() ?? auth()->user();

        abort_unless($user && $user->isSuperAdmin(), 403);

        if (
            ! $user->email_2fa_code_hash ||
            ! $user->email_2fa_expires_at ||
            now()->greaterThan($user->email_2fa_expires_at)
        ) {
            return back()->withErrors([
                'code' => 'Kod je istekao. Zatražite novi kod.',
            ]);
        }

        if (! Hash::check($request->code, $user->email_2fa_code_hash)) {
            return back()->withErrors([
                'code' => 'Kod nije ispravan.',
            ]);
        }

        $user->forceFill([
            'email_2fa_code_hash' => null,
            'email_2fa_expires_at' => null,
            'email_2fa_verified_at' => now(),
        ])->save();

        session()->put('superadmin_email_2fa_passed', true);
        session()->forget('superadmin_email_2fa_code_sent');

        return redirect('/admin');
    }

    public function resend(Request $request)
    {
        $user = Filament::auth()->user() ?? auth()->user();

        abort_unless($user && $user->isSuperAdmin(), 403);

        $this->sendCode($user);

        session()->put('superadmin_email_2fa_code_sent', true);

        return back()->with('status', 'Novi sigurnosni kod je poslan na vaš e-mail.');
    }

    private function sendCode($user): void
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'email_2fa_code_hash' => Hash::make($code),
            'email_2fa_expires_at' => now()->addMinutes(10),
        ])->save();

        Mail::raw(
            "Vaš sigurnosni kod za prijavu u ZNR LIDER je: {$code}\n\nKod vrijedi 10 minuta.\n\nAko niste pokušali prijavu, odmah promijenite lozinku.",
            function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('ZNR LIDER - sigurnosni kod za prijavu');
            }
        );
    }
}
