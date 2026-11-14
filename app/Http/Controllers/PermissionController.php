<?php

namespace App\Http\Controllers;

use App\Support\PermissionCategoryHelper;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(): Response
    {
        $allPermissions = Permission::orderBy('name')->pluck('name')->toArray();
        $grouped = PermissionCategoryHelper::groupPermissions($allPermissions);

        return Inertia::render('permissions/index', [
            'groupedPermissions' => $grouped,
            'total' => count($allPermissions),
        ]);
    }
}
