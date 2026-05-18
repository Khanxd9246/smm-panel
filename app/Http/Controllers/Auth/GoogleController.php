<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect to Google OAuth consent screen.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google callback — login existing user or create new account.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Google OAuth callback failed', ['error' => $e->getMessage()]);
            return redirect('/login')
                ->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        // Find by google_id first, then fall back to matching email
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Link google_id if this is the first Google login for an email-registered user
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            if ($user->status === 'banned') {
                return redirect('/login')
                    ->withErrors(['email' => 'This account has been suspended. Contact support.']);
            }

            Auth::login($user, remember: true);

            // Use explicit path — route('dashboard') is ambiguous (both user + admin
            // routes share the name; Laravel resolves to whichever was registered last)
            return redirect('/dashboard');
        }

        // New user — create and auto-verify (Google already verified the email)
        $user = User::create([
            'name'              => $googleUser->getName(),
            'email'             => $googleUser->getEmail(),
            'google_id'         => $googleUser->getId(),
            'password'          => bcrypt(bin2hex(random_bytes(16))),
            'email_verified_at' => now(),
            'referral_code'     => strtoupper(bin2hex(random_bytes(6))),
        ]);

        event(new Registered($user));

        Auth::login($user, remember: true);

        return redirect('/dashboard')
            ->with('success', 'Welcome to ' . config('app.name') . '! Your account has been created. 🎉');
    }
}
