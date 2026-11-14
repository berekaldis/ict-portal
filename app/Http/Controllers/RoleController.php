<?php

namespace App\Http\Controllers;

use App\Support\PermissionCategoryHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $roles = Role::with('permissions')->withCount('users')->get()->map(function ($r) {
            $permNames = $r->permissions->pluck('name')->toArray();
            return [
                'id' => $r->id,
                'name' => $r->name,
                'users_count' => $r->users_count ?? 0,
                'permissions_count' => count($permNames),
                'permissions' => $permNames,
                'grouped_permissions' => PermissionCategoryHelper::groupPermissions($permNames),
            ];
        });

        return Inertia::render('roles/index', [
            'roles' => $roles,
        ]);
    }

    public function create(): Response
    {
        $allPermissions = Permission::pluck('name')->toArray();

        return Inertia::render('roles/create', [
            'permissions' => $allPermissions,
            'groupedPermissions' => PermissionCategoryHelper::groupPermissions($allPermissions),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        $role->syncPermissions($validated['permissions']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' created.");
    }

    public function edit(int $id): Response
    {
        $role = Role::with('permissions')->findOrFail($id);
        $allPermissions = Permission::pluck('name')->toArray();

        return Inertia::render('roles/edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ],
            'permissions' => $allPermissions,
            'groupedPermissions' => PermissionCategoryHelper::groupPermissions($allPermissions),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->syncPermissions($validated['permissions']);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' updated.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        if (in_array($role->name, ['Super Admin', 'IT Admin'])) {
            return back()->with('error', 'Cannot delete essential system roles.');
        }

        $role->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->route('roles.index')->with('success', "Role deleted.");
    }
}
