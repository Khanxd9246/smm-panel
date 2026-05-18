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
     * Redirect to Google OAuth.
     * Works for both login and register — Google handles both.
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
            return redirect()->route('login')
                ->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        // Find existing user by google_id first, then fall back to email
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Existing user — update google_id if not set, then log in
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }

            // If account is banned
            if ($user->status === 'banned') {
                return redirect()->route('login')
                    ->withErrors(['email' => 'This account has been suspended. Contact support.']);
            }

            Auth::login($user, true);
            return redirect()->intended(route('dashboard'));
        }

        // New user — create account
        // Google already verified the email so mark it verified immediately
        $user = User::create([
            'name'              => $googleUser->getName(),
            'email'             => $googleUser->getEmail(),
            'google_id'         => $googleUser->getId(),
            'password'          => bcrypt(bin2hex(random_bytes(16))), // random, unusable password
            'email_verified_at' => now(), // Google email is already verified
            'referral_code'     => strtoupper(bin2hex(random_bytes(6))),
        ]);

        event(new Registered($user));

        Auth::login($user, true);

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to ' . config('app.name') . '! Your account has been created.');
    }
}
