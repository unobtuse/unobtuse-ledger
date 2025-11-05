<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Socialite Controller
 * 
 * Handles OAuth authentication flows for third-party providers (Google).
 * Supports both new user registration and existing user login via OAuth.
 */
class SocialiteController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return RedirectResponse
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            // Get user info from Google
            $googleUser = Socialite::driver('google')->user();

            // Check if user already exists by Google ID
            $user = User::where('google_id', $googleUser->getId())->first();

            if ($user) {
                // Existing user - update avatar if changed
                if ($user->avatar_url !== $googleUser->getAvatar()) {
                    $user->avatar_url = $googleUser->getAvatar();
                    $user->save();
                }
            } else {
                // Check if user exists by email (linking existing account)
                $user = User::where('email', $googleUser->getEmail())->first();

                if ($user) {
                    // Link existing account to Google
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar_url' => $googleUser->getAvatar(),
                        'provider' => 'google',
                        'email_verified_at' => now(), // Google emails are pre-verified
                    ]);
                } else {
                    // Create new user
                    $user = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'avatar_url' => $googleUser->getAvatar(),
                        'provider' => 'google',
                        'email_verified_at' => now(), // Google emails are pre-verified
                        'password' => null, // OAuth users don't have passwords
                    ]);
                }
            }

            // Log the user in
            Auth::login($user, true); // Remember the user

            // Redirect to dashboard
            return redirect()->intended(config('fortify.home'));
        } catch (Throwable $e) {
            // Log the error
            logger()->error('Google OAuth failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Redirect back with error message
            return redirect('/login')->with('error', 'Unable to login with Google. Please try again.');
        }
    }
}

