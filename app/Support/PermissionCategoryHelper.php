<?php

namespace App\Support;

class PermissionCategoryHelper
{
    public static function getCategory(string $permission): string
    {
        $perm = strtolower(trim($permission));

        if (str_starts_with($perm, 'case.')) {
            return 'Case Management (IT Only)';
        }

        if (str_starts_with($perm, 'edit_request.')) {
            return 'Edit & Void Requests';
        }

        if (self::containsAny($perm, ['role', 'roles', 'permission', 'permissions'])) {
            return 'Roles & Access Control';
        }

        if (self::containsAny($perm, ['user', 'users', 'branch', 'branches', 'department', 'departments'])) {
            return 'User & Organization';
        }

        return 'System & General';
    }

    public static function groupPermissions(array $permissions): array
    {
        $grouped = [
            'Case Management (IT Only)' => [],
            'Edit & Void Requests' => [],
            'User & Organization' => [],
            'Roles & Access Control' => [],
            'System & General' => [],
        ];

        foreach ($permissions as $permission) {
            $cat = self::getCategory($permission);
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $permission;
        }

        return array_filter($grouped, fn($items) => count($items) > 0);
    }

    private static function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }
}
