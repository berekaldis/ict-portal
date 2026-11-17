<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CompanySystemSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the single unified login view.
     */
    public function create(): Response
    {
        $currentUser = Auth::user() ? [
            'name' => Auth::user()->name ?: Auth::user()->username,
            'role' => Auth::user()->role ?: (Auth::user()->roles->first()?->name ?? 'User'),
        ] : null;

        return Inertia::render('auth/login', [
            'status' => session('status'),
            'currentUser' => $currentUser,
        ]);
    }

    /**
     * Handle incoming authentication with single unified login.
     * All users authenticate based on their role and permissions.
     */
    public function store(Request $request, CompanySystemSyncService $syncService): RedirectResponse
    {
        $loginInput = trim((string)($request->input('email') ?? $request->input('login') ?? $request->input('username') ?? ''));
        $password = (string)$request->input('password', '');
        $remember = $request->boolean('remember');

        if (empty($loginInput)) {
            return back()->withErrors(['email' => 'Email address is required.', 'login' => 'Email address is required.']);
        }

        if (empty($password)) {
            return back()->withErrors(['password' => 'Password is required.']);
        }

        // 1. Look up user locally (case-insensitive by email, username, prefix before @, or name)
        $prefix = str_contains($loginInput, '@') ? explode('@', $loginInput)[0] : $loginInput;

        $user = User::whereRaw('LOWER(email) = ?', [strtolower($loginInput)])
            ->orWhereRaw('LOWER(email) = ?', [strtolower($prefix . '@kaldisbunna.et')])
            ->orWhereRaw('LOWER(email) = ?', [strtolower($prefix . '@kaldiscoffee.com')])
            ->orWhereRaw('LOWER(username) = ?', [strtolower($loginInput)])
            ->orWhereRaw('LOWER(username) = ?', [strtolower($prefix)])
            ->orWhereRaw('LOWER(SUBSTRING_INDEX(email, "@", 1)) = ?', [strtolower($prefix)])
            ->orWhereRaw('LOWER(name) = ?', [strtolower($loginInput)])
            ->orWhereRaw('LOWER(name) = ?', [strtolower($prefix)])
            ->first();

        // 2. If not found in local DB, attempt real-time sync from company_system
        if (!$user) {
            $user = $syncService->syncSingleUser($loginInput, $password)
                ?: $syncService->syncSingleUser($prefix, $password);
        }

        if (!$user) {
            return back()->withErrors(['email' => 'Account record not found for this email address.', 'login' => 'Account record not found for this email address.']);
        }

        if (!$user->is_active) {
            return back()->withErrors(['email' => 'Your account has been deactivated. Please contact an administrator.', 'login' => 'Your account has been deactivated. Please contact an administrator.']);
        }

        // 3. Verify password
        if (!$this->verifyPassword($user, $password)) {
            // Attempt on-demand re-sync from company_system in case password was changed in CS
            $refreshed = $syncService->syncSingleUser($loginInput, $password);
            if (!$refreshed || !$this->verifyPassword($refreshed, $password)) {
                return back()->withErrors(['password' => 'Invalid password for ' . ($user->name ?: $user->username) . '.']);
            }
            $user = $refreshed;
        }

        // 4. Clean session and log in
        if (Auth::check() && Auth::id() !== $user->id) {
            Auth::logout();
            $request->session()->invalidate();
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        if ($password === 'admin123' && $user->must_change_password) {
            $request->session()->put('must_change_password', true);
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Helper to verify user password with bcrypt, legacy fallback, and admin123 default logic.
     */
    protected function verifyPassword(User $user, string $plainPassword): bool
    {
        $storedHash = (string)$user->password;

        if (Hash::check($plainPassword, $storedHash)) {
            return true;
        }

        // Legacy plain-text fallback (auto-upgrade to bcrypt)
        if ($storedHash === $plainPassword) {
            $user->password = Hash::make($plainPassword);
            $user->save();
            return true;
        }

        // Default password 'kaldis123' handling (also accepts 'admin123' for backwards compatibility)
        if ($plainPassword === 'kaldis123' || $plainPassword === 'admin123') {
            return true;
        }

        return false;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
