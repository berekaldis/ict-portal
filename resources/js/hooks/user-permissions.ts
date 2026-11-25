import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';

type AuthProps = {
    auth?: {
        user?: {
            id?: number;
            name?: string;
            username?: string;
            email?: string;
            roles?: string[];
            department?: string;
            branch?: string;
        };
        roles?: string[];
        permissions?: string[];
        isItTeam?: boolean;
    };
};

export function usePermission() {
    const { props } = usePage<AuthProps>();
    const permissions = props.auth?.permissions || [];
    const roles = props.auth?.roles || props.auth?.user?.roles || [];
    const isSuperAdmin = Array.isArray(roles)
        ? roles.some((r: any) => {
            const name = (typeof r === 'string' ? r : r?.name || '').toLowerCase();
            return name === 'super admin' || name === 'superadmin';
        })
        : false;

    const isItTeam = Boolean(
        props.auth?.isItTeam ?? (
            isSuperAdmin || (
                Array.isArray(roles) && roles.some((r: any) => {
                    const name = (typeof r === 'string' ? r : r?.name || '').toLowerCase();
                    return name === 'it admin' || name === 'it staff' || name === 'it' || name === 'admin';
                })
            ) || (
                (props.auth?.user?.department?.toLowerCase() === 'it' || props.auth?.user?.department?.toLowerCase() === 'ict')
            ) || (
                permissions.some((p) => [
                    'case.view', 'view users', 'view roles', 'view permissions',
                    'password_manager.view', 'it_survey.view', 'cctv_audit.view',
                    'view telecom connections', 'telecom.phone.view', 'telecom.broadband.view'
                ].includes(p))
            )
        )
    );

    const can = useCallback((permission: string): boolean => {
        if (!permission) return true;

        if (isSuperAdmin) {
            return true;
        }

        const requestedPermissions = permission
            .split('|')
            .map((value) => value.trim())
            .filter(Boolean);

        return requestedPermissions.some((value) => {
            if (value.startsWith('role:')) {
                const roleName = value.substring(5).trim().toLowerCase();
                return Array.isArray(roles) && roles.some((r: any) => {
                    const rName = typeof r === 'string' ? r.toLowerCase() : r?.name?.toLowerCase();
                    return rName === roleName;
                });
            }

            return permissions.includes(value);
        });
    }, [isSuperAdmin, roles, permissions]);

    return { can, isSuperAdmin, isItTeam, roles, permissions };
}
