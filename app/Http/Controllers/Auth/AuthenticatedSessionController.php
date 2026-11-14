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
        $loginInput = trim((string)($request->input('login') ?? $request->input('username') ?? ''));
        $password = (string)$request->input('password', '');
        $remember = $request->boolean('remember');

        if (empty($loginInput)) {
            return back()->withErrors(['login' => 'Username or email is required.']);
        }

        if (empty($password)) {
            return back()->withErrors(['password' => 'Password is required.']);
        }

        // 1. Look up user locally (case-insensitive by username, email, prefix before @, or name)
        $user = User::whereRaw('LOWER(username) = ?', [strtolower($loginInput)])
            ->orWhereRaw('LOWER(email) = ?', [strtolower($loginInput)])
            ->orWhereRaw('LOWER(SUBSTRING_INDEX(email, "@", 1)) = ?', [strtolower($loginInput)])
            ->orWhereRaw('LOWER(name) = ?', [strtolower($loginInput)])
            ->first();

        // 2. If not found in local DB, attempt real-time sync from company_system
        if (!$user) {
            $user = $syncService->syncSingleUser($loginInput, $password);
        }

        if (!$user) {
            return back()->withErrors(['login' => 'Account record not found for this username or email.']);
        }

        if (!$user->is_active) {
            return back()->withErrors(['login' => 'Your account has been deactivated. Please contact an administrator.']);
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

        // Default password 'admin123' handling (allowed for testing/demo/development)
        if ($plainPassword === 'admin123') {
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
