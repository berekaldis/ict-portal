<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);

        $user = $request->user();

        // If current password provided, verify it (unless user was on default admin123 or must_change_password is true)
        if (!empty($validated['current_password'])) {
            $currentMatched = Hash::check($validated['current_password'], (string)$user->password)
                || $validated['current_password'] === (string)$user->password
                || ($validated['current_password'] === 'admin123' && ($user->must_change_password || empty($user->password)));

            if (!$currentMatched) {
                return back()->withErrors(['current_password' => 'The provided current password does not match.']);
            }
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();

        // Clear session flags so the prompt immediately stops appearing
        if ($request->hasSession()) {
            $request->session()->forget(['must_change_password', 'dismiss_password_prompt']);
        }

        return back()->with('success', 'Your password has been successfully updated.');
    }

    /**
     * Dismiss password change prompt for current session.
     */
    public function dismiss(Request $request): RedirectResponse
    {
        $request->session()->put('dismiss_password_prompt', true);
        return back();
    }
}
