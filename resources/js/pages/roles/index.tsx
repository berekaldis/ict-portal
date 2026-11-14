import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    Edit2,
    Lock,
    PlusCircle,
    Search,
    Shield,
    ShieldCheck,
    Trash2,
    Users,
} from 'lucide-react';
import React, { useMemo, useState } from 'react';

interface RoleItem {
    id: number;
    name: string;
    users_count: number;
    permissions_count: number;
    permissions: string[];
    grouped_permissions: Record<string, string[]>;
}

interface Props {
    roles: RoleItem[];
}

const ROLE_DESCRIPTIONS: Record<string, string> = {
    'Super Admin': 'Unrestricted master access across all ICT modules and system settings.',
    'superadmin': 'Unrestricted master access across all ICT modules and system settings.',
    'IT Admin': 'Full administrative control over Case management, voucher edits, and user configuration.',
    'IT Staff': 'Operational authority to resolve cases, execute requests, and log progress.',
    'Finance Manager': 'Review, approve, or reject Finance voucher edit requests.',
    'F&B Manager': 'Review, approve, or reject F&B store and On Hand Delete inventory requests.',
    'Coordinator': 'Departmental coordination and cross-departmental reporting view.',
    'Branch User': 'Branch staff member authorized to submit and track own branch requests.',
    'Admin': 'General administrator access with department management rights.',
};

export default function RolesIndex({ roles }: Props) {
    const [search, setSearch] = useState('');

    const filteredRoles = useMemo(() => {
        if (!search.trim()) return roles;
        const q = search.toLowerCase();
        return roles.filter(
            (r) =>
                r.name.toLowerCase().includes(q) ||
                (ROLE_DESCRIPTIONS[r.name] && ROLE_DESCRIPTIONS[r.name].toLowerCase().includes(q))
        );
    }, [roles, search]);

    const handleDelete = (id: number, name: string) => {
        if (confirm(`Are you sure you want to delete role '${name}'?`)) {
            router.delete(route('roles.destroy', id));
        }
    };

    const isSystemRole = (name: string) =>
        ['Super Admin', 'superadmin', 'IT Admin', 'IT Staff', 'Finance Manager', 'F&B Manager', 'Branch User', 'Admin'].includes(name);

    return (
        <AppLayout breadcrumbs={[{ title: 'Access Control', href: '/roles' }, { title: 'Roles' }]}>
            <Head title="Role Management" />

            <div className="space-y-4 max-w-5xl mx-auto">
                {/* Header & Search */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-border/40">
                    <div>
                        <div className="flex items-center gap-2">
                            <ShieldCheck className="h-5 w-5 text-primary" />
                            <h1 className="text-xl font-bold tracking-tight text-foreground">Roles & Access Control</h1>
                            <Badge variant="secondary" className="bg-primary/10 text-primary text-xs font-semibold">
                                {roles.length} Roles
                            </Badge>
                        </div>
                        <p className="text-xs text-muted-foreground mt-0.5">
                            Manage system user roles, permission policies, and department access boundaries
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <div className="relative w-48 sm:w-64">
                            <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-muted-foreground" />
                            <Input
                                placeholder="Search roles..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-8 h-9 text-xs"
                            />
                        </div>
                        <Button asChild size="sm" className="h-9 font-medium shrink-0">
                            <Link href={route('roles.create')}>
                                <PlusCircle className="h-4 w-4 mr-1.5" /> New Role
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Simplified Role List Table */}
                <Card className="border-border/60 shadow-xs overflow-hidden">
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/40 hover:bg-muted/40">
                                    <TableHead className="w-[200px] text-xs font-semibold">Role Name</TableHead>
                                    <TableHead className="text-xs font-semibold">Scope & Description</TableHead>
                                    <TableHead className="text-xs font-semibold text-center w-[120px]">Users</TableHead>
                                    <TableHead className="text-xs font-semibold text-center w-[130px]">Permissions</TableHead>
                                    <TableHead className="text-xs font-semibold text-right w-[150px]">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredRoles.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="text-center py-8 text-xs text-muted-foreground">
                                            No roles found matching "{search}"
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    filteredRoles.map((r) => {
                                        const protectedRole = isSystemRole(r.name);
                                        const desc = ROLE_DESCRIPTIONS[r.name] || 'Custom organization role.';

                                        return (
                                            <TableRow key={r.id} className="hover:bg-muted/20 transition-colors">
                                                <TableCell className="font-medium">
                                                    <div className="flex items-center gap-2">
                                                        <Shield className={`h-4 w-4 ${protectedRole ? 'text-primary' : 'text-muted-foreground'}`} />
                                                        <span className="text-sm font-semibold text-foreground">{r.name}</span>
                                                        {protectedRole && (
                                                            <Badge variant="outline" className="text-[10px] px-1 py-0 text-muted-foreground border-border/60">
                                                                System
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-xs text-muted-foreground line-clamp-1">{desc}</span>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Button asChild variant="ghost" size="sm" className="h-7 text-xs px-2 text-muted-foreground hover:text-foreground">
                                                        <Link href={`/users?role=${encodeURIComponent(r.name)}`}>
                                                            <Users className="h-3 w-3 mr-1 text-muted-foreground" />
                                                            {r.users_count}
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <Badge variant="secondary" className="text-xs font-mono bg-muted/60 text-foreground">
                                                        {r.permissions_count} perms
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1.5">
                                                        <Button asChild variant="outline" size="sm" className="h-7 text-xs px-2.5">
                                                            <Link href={route('roles.edit', r.id)}>
                                                                <Edit2 className="h-3 w-3 mr-1" /> Edit
                                                            </Link>
                                                        </Button>
                                                        {!protectedRole ? (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleDelete(r.id, r.name)}
                                                                className="h-7 text-xs px-2 text-destructive hover:text-destructive hover:bg-destructive/10"
                                                            >
                                                                <Trash2 className="h-3 w-3" />
                                                            </Button>
                                                        ) : (
                                                            <div className="w-7 h-7 flex items-center justify-center text-muted-foreground/40" title="System role cannot be deleted">
                                                                <Lock className="h-3 w-3" />
                                                            </div>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
