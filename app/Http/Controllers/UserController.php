<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::with('roles');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            if ($role !== 'All') {
                $query->whereHas('roles', fn($q) => $q->where('name', $role));
            }
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $users = $query->orderByDesc('id')->paginate(12)->withQueryString();
        $roles = Role::pluck('name')->toArray();
        $branches = Branch::where('is_active', true)->pluck('branch_name')->toArray();

        return Inertia::render('users/index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
            'roles' => $roles,
            'branches' => $branches,
        ]);
    }

    public function create(): Response
    {
        $roles = Role::pluck('name')->toArray();
        $branches = Branch::where('is_active', true)->pluck('branch_name')->toArray();
        $allPermissions = Permission::orderBy('name')->pluck('name')->toArray();

        return Inertia::render('users/create', [
            'roles' => $roles,
            'branches' => $branches,
            'allPermissions' => $allPermissions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:100|unique:users,username',
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150|unique:users,email',
            'password' => 'required|string|min:6',
            'department' => 'nullable|string|max:100',
            'branch' => 'nullable|string|max:150',
            'chat_id' => 'nullable|string|max:50',
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'name' => $validated['name'],
            'full_name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
            'department' => $validated['department'] ?? null,
            'branch' => $validated['branch'] ?? null,
            'chat_id' => $validated['chat_id'] ?? null,
            'role' => $validated['roles'][0] ?? 'Branch User',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $user->syncRoles($validated['roles']);
        if (!empty($validated['permissions'])) {
            $user->syncPermissions($validated['permissions']);
        }

        return redirect()->route('users.index')->with('success', "User '{$user->username}' created successfully.");
    }

    public function edit(int $id): Response
    {
        $user = User::with(['roles', 'permissions'])->findOrFail($id);
        $roles = Role::pluck('name')->toArray();
        $branches = Branch::where('is_active', true)->pluck('branch_name')->toArray();
        $allPermissions = Permission::orderBy('name')->pluck('name')->toArray();

        return Inertia::render('users/edit', [
            'editUser' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department,
                'branch' => $user->branch,
                'chat_id' => $user->chat_id,
                'is_active' => (bool)$user->is_active,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $user->permissions->pluck('name')->toArray(),
            ],
            'roles' => $roles,
            'branches' => $branches,
            'allPermissions' => $allPermissions,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'department' => 'nullable|string|max:100',
            'branch' => 'nullable|string|max:150',
            'chat_id' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
            'password' => 'nullable|string|min:6',
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $user->name = $validated['name'];
        $user->full_name = $validated['name'];
        $user->email = $validated['email'] ?? null;
        $user->department = $validated['department'] ?? null;
        $user->branch = $validated['branch'] ?? null;
        $user->chat_id = $validated['chat_id'] ?? null;
        $user->is_active = $validated['is_active'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->role = $validated['roles'][0] ?? $user->role;
        $user->save();

        $user->syncRoles($validated['roles']);
        $user->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('users.index')->with('success', "User '{$user->username}' updated successfully.");
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        $statusStr = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "User '{$user->username}' {$statusStr}.");
    }

    public function resetPassword(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $newPass = $request->input('password', 'admin123');

        $user->password = Hash::make($newPass);
        $user->must_change_password = true;
        $user->save();

        return back()->with('success', "Password for '{$user->username}' reset to '{$newPass}'.");
    }
}
