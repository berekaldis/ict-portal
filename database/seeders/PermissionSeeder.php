<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    private array $permissions = [
        // Case Module (Strictly IT Team Only)
        'case.view',
        'case.create',
        'case.update',
        'case.delete',
        'case.status.update',
        'case.assign',
        'case.comment',
        'case.export',
        'case.sla.view',
        'case.sla.report',
        'case.settings.manage',
        'case.types.manage',
        'case.assignees.manage',

        // Edit Request & On Hand Delete Module
        'edit_request.view',
        'edit_request.view.all',
        'edit_request.view.own_branch',
        'edit_request.create',
        'edit_request.edit',
        'edit_request.delete',
        'edit_request.approve',
        'edit_request.reject',
        'edit_request.mark_done',
        'edit_request.bulk_action',
        'edit_request.settings.manage',
        'edit_request.reports.view',

        // User & Organization
        'view users',
        'create users',
        'update users',
        'delete users',
        'view branches',
        'create branches',
        'update branches',
        'delete branches',
        'view departments',
        'create departments',
        'update departments',
        'delete departments',

        // Roles & Permissions
        'view roles',
        'create roles',
        'update roles',
        'delete roles',
        'view permissions',

        // General & System Integrations
        'view dashboard',
        'manage telegram config',
        'view system logs',
        'company_system.sync',

        // Customer Survey Module
        'survey.view',
        'survey.manage',

        // HR Management Module
        'hr.view',
        'hr.create',
        'hr.manage',

        // Telecom Management Module
        'telecom.view',
        'view telecom management',
        'manage telecom connections',
        'export telecom data',
        'telecom.phone.view',
        'telecom.phone.create',
        'telecom.phone.edit',
        'telecom.phone.delete',
        'telecom.phone.transfer',
        'telecom.broadband.view',
        'telecom.broadband.create',
        'telecom.broadband.edit',
        'telecom.broadband.delete',
        'telecom.broadband.transfer',
        'telecom.providers.manage',
        'telecom.export',

        // IT Branch Survey & Infrastructure Audit Module
        'it_survey.view',
        'it_survey.create',
        'it_survey.manage',
        'cctv_audit.view',
        'cctv_audit.manage',

        // Sales Discrepancy & Z-Report Reconciliation Module
        'sales_case.view',
        'sales_case.create',
        'sales_case.manage',

        // Kaldis Branch Communication Module
        'communication.view',
        'communication.manage',
        'communication.moderation',
        'communication.broadcast',
        'communication.sync',
        'view kaldis communication',
        'manage kaldis communication',
        'manage communication moderation',
        'send communication broadcast',

        // Password & Credential Vault Module (System Admin & IT)
        'password_manager.view',
        'password_manager.manage',
        'password_manager.reveal',
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // Define Role Maps
        $roleMap = [
            'Super Admin' => $this->permissions,
            'superadmin' => $this->permissions,

            'Admin' => [
                'edit_request.view',
                'edit_request.approve',
                'edit_request.reject',
                'edit_request.reports.view',
                'view dashboard',
            ],
            'admin' => [
                'edit_request.view',
                'edit_request.approve',
                'edit_request.reject',
                'edit_request.reports.view',
                'view dashboard',
            ],

            'IT Admin' => [
                'case.view', 'case.create', 'case.update', 'case.delete',
                'case.status.update', 'case.assign', 'case.comment', 'case.export',
                'case.sla.view', 'case.sla.report', 'case.settings.manage',
                'case.types.manage', 'case.assignees.manage',
                'edit_request.view', 'edit_request.view.all', 'edit_request.create',
                'edit_request.edit', 'edit_request.delete', 'edit_request.approve',
                'edit_request.reject', 'edit_request.mark_done', 'edit_request.bulk_action',
                'edit_request.settings.manage', 'edit_request.reports.view',
                'view users', 'create users', 'update users', 'delete users',
                'view branches', 'create branches', 'update branches', 'delete branches',
                'view departments', 'create departments', 'update departments', 'delete departments',
                'view roles', 'create roles', 'update roles', 'delete roles', 'view permissions',
                'view dashboard', 'manage telegram config', 'view system logs',
                'survey.view', 'survey.manage',
                'hr.view', 'hr.create', 'hr.manage',
                'password_manager.view', 'password_manager.manage', 'password_manager.reveal',
            ],

            'IT Staff' => [
                'case.view', 'case.create', 'case.update', 'case.status.update',
                'case.assign', 'case.comment', 'case.export', 'case.sla.view',
                'case.sla.report',
                'edit_request.view', 'edit_request.view.all', 'edit_request.mark_done',
                'view dashboard',
                'survey.view', 'hr.view',
            ],

            'Finance Manager' => [
                'edit_request.view',
                'edit_request.approve',
                'edit_request.reject',
                'edit_request.reports.view',
                'view dashboard',
                'sales_case.view',
                'sales_case.create',
                'sales_case.manage',
            ],

            'F&B Manager' => [
                'edit_request.view',
                'edit_request.approve',
                'edit_request.reject',
                'edit_request.reports.view',
                'view dashboard',
            ],

            'Coordinator' => [
                'edit_request.view',
                'edit_request.view.all',
                'edit_request.reports.view',
                'view dashboard',
            ],

            'Branch User' => [
                'edit_request.view',
                'edit_request.view.own_branch',
                'edit_request.create',
                'view dashboard',
            ],
            'branch user' => [
                'edit_request.view',
                'edit_request.view.own_branch',
                'edit_request.create',
                'view dashboard',
            ],
        ];

        foreach ($roleMap as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
