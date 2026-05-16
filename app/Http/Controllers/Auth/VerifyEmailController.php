<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class VerifyEmailController extends Controller
{
    /**
     * Handle email verification link clicks.
     *
     * FIX: The original route required 'auth' middleware, meaning users had to
     * log in before verifying. This caused a redirect loop:
     *   click link → sent to /login → login → sent to /email/verify page
     *   → page re-sends verification email instead of completing verification.
     *
     * This controller now handles both cases:
     *   - User is already logged in → verify and redirect to dashboard
     *   - User is not logged in → find by ID, verify signature, verify email,
     *     auto-login, redirect to dashboard
     *
     * The route must use 'signed' middleware only (no 'auth').
     * Update routes/web.php:
     *   Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
     *       ->middleware(['signed', 'throttle:6,1'])
     *       ->name('verification.verify');
     */
    public function __invoke(Request $request): RedirectResponse
    {
        // Find the user by ID from the URL parameter
        $user = User::findOrFail($request->route('id'));

        // Validate the hash matches this user's email
        if (! hash_equals(
            sha1($user->getEmailForVerification()),
            (string) $request->route('hash')
        )) {
            abort(403, 'Invalid verification link.');
        }

        // Validate the signed URL hasn't expired or been tampered with
        if (! URL::hasValidSignature($request)) {
            abort(403, 'Verification link has expired. Please request a new one.');
        }

        // If already verified just go to dashboard
        if ($user->hasVerifiedEmail()) {
            // Log them in if not already authenticated
            if (! Auth::check()) {
                Auth::login($user, true);
            }
            return redirect()->route('dashboard')
                ->with('info', 'Your email is already verified.');
        }

        // Mark as verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Auto-login the user so they don't have to log in manually
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Email verified! Welcome to ' . config('app.name') . ' 🎉');
    }
}
