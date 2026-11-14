<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Spatie\Permission\Models\Permission;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        if ($user && $user->roles->isEmpty() && !empty($user->role)) {
            $mappedRole = match ($user->role) {
                'Branch Manager', 'Shift Leader', 'Supervisor', 'Branch User' => 'Branch User',
                'Super Admin', 'superadmin' => 'Super Admin',
                'IT Admin' => 'IT Admin',
                'Admin', 'admin' => 'Admin',
                'IT Staff', 'IT' => 'IT Staff',
                'Finance Manager' => 'Finance Manager',
                'F&B Manager' => 'F&B Manager',
                'Coordinator' => 'Coordinator',
                default => null,
            };
            if ($mappedRole && \Spatie\Permission\Models\Role::where('name', $mappedRole)->exists()) {
                $user->assignRole($mappedRole);
                $user->load('roles', 'permissions');
            }
        }

        $isSuperAdmin = $user ? ($user->hasRole('Super Admin') || $user->hasRole('superadmin')) : false;

        $userPermissions = $user ? $user->getAllPermissions()->pluck('name')->unique()->values() : collect();
        $permissions = $user
            ? ($isSuperAdmin ? \Illuminate\Support\Facades\Cache::remember('all_permissions_list', 3600, fn() => Permission::pluck('name')->values()) : $userPermissions)
            : collect();

        $roles = $user ? $user->roles->pluck('name')->toArray() : [];
        $dept = strtolower(trim((string)$user?->department));
        $isItDept = in_array($dept, ['it', 'ict']);
        $isItTeam = $isSuperAdmin || (
            $user &&
            ($user->can('case.view') || (
                $isItDept && (in_array('IT Admin', $roles) || in_array('IT Staff', $roles) || in_array('IT', $roles))
            ))
        );
        $hasSession = $request->hasSession();
        $mustChangePassword = $user && (($user->must_change_password || ($hasSession && $request->session()->get('must_change_password'))) && !($hasSession && $request->session()->get('dismiss_password_prompt')));

        return [
            ...parent::share($request),
            'name' => config('app.name', 'Kaldis ICT Portal'),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), ['roles' => $roles]) : null,
                'roles' => $roles,
                'permissions' => $permissions->toArray(),
                'isItTeam' => $isItTeam,
                'mustChangePassword' => (bool)$mustChangePassword,
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
