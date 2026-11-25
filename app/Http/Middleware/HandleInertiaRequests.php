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
        if ($user) {
            $deptNorm = strtolower(trim((string)$user->department));
            $uname = strtolower(trim((string)$user->username));
            $isSuper = in_array($uname, ['superadmin', 'superadmin@kaldisbunna.et', 'berhanu@kaldisbunna.et', 'takelu@kaldisbunna.et'])
                || $user->role === 'Super Admin'
                || $user->hasRole(['Super Admin', 'superadmin']);

            if ($isSuper) {
                if (!$user->hasRole('Super Admin')) {
                    $user->syncRoles(['Super Admin']);
                    $user->role = 'Super Admin';
                    $user->save();
                    $user->load('roles', 'permissions');
                }
            } elseif ($user->roles->isEmpty()) {
                // Only assign default fallback role if user has no assigned roles at all
                if ($deptNorm === 'finance' || str_contains($deptNorm, 'finance')) {
                    $user->syncRoles(['Finance Manager']);
                    $user->role = 'Finance Manager';
                } elseif ($deptNorm === 'f&b' || $deptNorm === 'fb' || str_contains($deptNorm, 'food') || str_contains($deptNorm, 'beverage')) {
                    $user->syncRoles(['F&B Manager']);
                    $user->role = 'F&B Manager';
                } elseif (in_array($deptNorm, ['it', 'ict'])) {
                    $user->syncRoles(['IT Staff']);
                    $user->role = 'IT Staff';
                } else {
                    $user->syncRoles(['Branch User']);
                    $user->role = 'Branch User';
                }
                $user->save();
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

        $hasItRole = in_array('IT Admin', $roles) || in_array('IT Staff', $roles) || in_array('IT', $roles) || in_array('Admin', $roles) || in_array('admin', $roles);
        $hasItPermission = $user && (
            $user->can('case.view') ||
            $user->can('view users') ||
            $user->can('view roles') ||
            $user->can('view permissions') ||
            $user->can('password_manager.view') ||
            $user->can('it_survey.view') ||
            $user->can('cctv_audit.view') ||
            $user->can('view telecom connections') ||
            $user->can('telecom.phone.view') ||
            $user->can('telecom.broadband.view')
        );

        $isItTeam = $isSuperAdmin || $hasItRole || $hasItPermission || ($user && $isItDept && count($roles) > 0);

        return [
            ...parent::share($request),
            'name' => config('app.name', 'Kaldis ICT Portal'),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), ['roles' => $roles]) : null,
                'roles' => $roles,
                'permissions' => $permissions->toArray(),
                'isItTeam' => $isItTeam,
                'mustChangePassword' => false,
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
