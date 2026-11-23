export interface GroupedPermissions {
    [category: string]: string[];
}

export const CATEGORY_ORDER = [
    'Kaldis Communication',
    'Telecom Management',
    'IT Survey & Audit',
    'Sales Reconciliation',
    'HR Management',
    'Case Management (IT Only)',
    'Edit & Void Requests',
    'User & Organization',
    'Roles & Access Control',
    'System & General',
];

export function getPermissionCategory(perm: string): string {
    const p = perm.toLowerCase();

    if (p.includes('communication')) {
        return 'Kaldis Communication';
    }

    if (p.includes('telecom')) {
        return 'Telecom Management';
    }

    if (p.startsWith('it_survey.') || p.startsWith('cctv_audit.') || p.includes('survey') || p.includes('cctv')) {
        return 'IT Survey & Audit';
    }

    if (p.startsWith('sales_case.') || p.includes('sales')) {
        return 'Sales Reconciliation';
    }

    if (p.startsWith('hr.') || p.includes('internship') || p.includes('qualification') || p.includes('recruitment')) {
        return 'HR Management';
    }

    if (p.startsWith('case.')) {
        return 'Case Management (IT Only)';
    }

    if (p.startsWith('edit_request.')) {
        return 'Edit & Void Requests';
    }

    if (p.includes('role') || p.includes('permission')) {
        return 'Roles & Access Control';
    }

    if (p.includes('user') || p.includes('branch') || p.includes('department')) {
        return 'User & Organization';
    }

    if (p.startsWith('password_manager.') || p.includes('password_manager')) {
        return 'System & General';
    }

    return 'System & General';
}

export function groupPermissionsList(permissions: string[]): GroupedPermissions {
    const groups: GroupedPermissions = {
        'Kaldis Communication': [],
        'Telecom Management': [],
        'IT Survey & Audit': [],
        'Sales Reconciliation': [],
        'HR Management': [],
        'Case Management (IT Only)': [],
        'Edit & Void Requests': [],
        'User & Organization': [],
        'Roles & Access Control': [],
        'System & General': [],
    };

    permissions.forEach((perm) => {
        const cat = getPermissionCategory(perm);
        if (!groups[cat]) {
            groups[cat] = [];
        }
        groups[cat].push(perm);
    });

    // Remove empty groups
    Object.keys(groups).forEach((key) => {
        if (groups[key].length === 0) {
            delete groups[key];
        }
    });

    return groups;
}

export function formatPermissionLabel(perm: string): string {
    return perm
        .replace(/\./g, ' ')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}
