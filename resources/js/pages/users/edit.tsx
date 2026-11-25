import CategorizedPermissionSelector from '@/components/CategorizedPermissionSelector';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Check, KeyRound, Shield, UserCheck } from 'lucide-react';
import React from 'react';

interface Props {
    editUser: {
        id: number;
        username: string;
        name: string;
        email?: string;
        department?: string;
        branch?: string;
        chat_id?: string;
        is_active: boolean;
        roles: string[];
        permissions?: string[];
    };
    roles: string[];
    branches: string[];
    allPermissions?: string[];
}

export default function UserEdit({ editUser, roles, branches, allPermissions = [] }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: editUser.name || '',
        email: editUser.email || '',
        password: '',
        department: editUser.department || '',
        branch: editUser.branch || '',
        chat_id: editUser.chat_id || '',
        is_active: editUser.is_active,
        roles: editUser.roles || [],
        permissions: editUser.permissions || [],
    });

    const toggleRole = (roleName: string) => {
        const isSelected = data.roles.includes(roleName);
        const nextRoles = isSelected
            ? data.roles.filter((r) => r !== roleName)
            : [...data.roles, roleName];
        setData('roles', nextRoles);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('users.update', editUser.id));
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Users', href: '/users' }, { title: `Edit ${editUser.username}` }]}>
            <Head title={`Edit User ${editUser.username}`} />

            <div className="max-w-3xl mx-auto space-y-4">
                <div className="flex items-center gap-3">
                    <Button asChild variant="outline" size="icon" className="h-9 w-9">
                        <Link href={route('users.index')}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <UserCheck className="h-5 w-5 text-primary" /> Edit User: {editUser.username}
                        </h1>
                        <p className="text-xs text-muted-foreground">
                            Update profile, departmental role assignments, and individual direct permissions
                        </p>
                    </div>
                </div>

                <Card className="border-border/60 shadow-xs">
                    <CardHeader className="py-4 px-6 border-b border-border/40">
                        <CardTitle className="text-sm font-semibold">User Details & Access Configuration</CardTitle>
                    </CardHeader>
                    <CardContent className="p-6">
                        <form onSubmit={submit} className="space-y-5">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label>Username</Label>
                                    <Input value={editUser.username} disabled className="bg-muted/40 font-mono" />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="name">Full Name <span className="text-destructive">*</span></Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                    />
                                    {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="email">Email Address</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                    />
                                    {errors.email && <p className="text-xs text-destructive">{errors.email}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="password">Change Password (Leave blank to keep)</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        placeholder="New password..."
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="branch">Branch</Label>
                                    <Select
                                        value={data.branch}
                                        onValueChange={(val) => setData('branch', val)}
                                    >
                                        <SelectTrigger id="branch">
                                            <SelectValue placeholder="Select Branch" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Head Office">Head Office</SelectItem>
                                            {branches.map((b) => (
                                                <SelectItem key={b} value={b}>{b}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="department">Department</Label>
                                    <Input
                                        id="department"
                                        value={data.department}
                                        onChange={(e) => setData('department', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="chat_id">Telegram Chat ID</Label>
                                <Input
                                    id="chat_id"
                                    value={data.chat_id}
                                    onChange={(e) => setData('chat_id', e.target.value)}
                                    className="font-mono text-xs"
                                />
                            </div>

                            <div className="flex items-center justify-between p-3 rounded-lg border border-border/60 bg-muted/20">
                                <div>
                                    <div className="text-xs font-semibold text-foreground">Active Account</div>
                                    <div className="text-[11px] text-muted-foreground">Allows user to sign in to the portal</div>
                                </div>
                                <Switch
                                    checked={data.is_active}
                                    onCheckedChange={(checked) => setData('is_active', checked)}
                                />
                            </div>

                            {/* Roles */}
                            <div className="space-y-2 pt-3 border-t border-border/40">
                                <div className="flex items-center justify-between">
                                    <Label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                        Assigned Roles <span className="text-destructive">*</span>
                                    </Label>
                                    <span className="text-xs text-muted-foreground font-mono">
                                        {data.roles.length} selected
                                    </span>
                                </div>
                                <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    {roles.map((r) => {
                                        const isChecked = data.roles.includes(r);
                                        return (
                                            <div
                                                key={r}
                                                role="button"
                                                tabIndex={0}
                                                onClick={() => toggleRole(r)}
                                                onKeyDown={(e) => {
                                                    if (e.key === ' ' || e.key === 'Enter') {
                                                        e.preventDefault();
                                                        toggleRole(r);
                                                    }
                                                }}
                                                className={`flex items-center gap-2.5 p-2.5 rounded-lg border text-xs cursor-pointer select-none transition-all ${
                                                    isChecked
                                                        ? 'bg-amber-500/10 border-amber-500/50 font-semibold text-foreground shadow-2xs'
                                                        : 'bg-background hover:bg-muted/30 border-border/60 text-muted-foreground'
                                                }`}
                                            >
                                                <div
                                                    className={`size-4 shrink-0 rounded-[4px] border flex items-center justify-center transition-all ${
                                                        isChecked
                                                            ? 'bg-amber-600 border-amber-600 text-white'
                                                            : 'border-input bg-background/50 hover:border-amber-500/60'
                                                    }`}
                                                >
                                                    {isChecked && <Check className="size-3 stroke-[3]" />}
                                                </div>
                                                <span>{r}</span>
                                            </div>
                                        );
                                    })}
                                </div>
                                {errors.roles && <p className="text-xs text-destructive">{errors.roles}</p>}
                            </div>

                            {/* Individual Direct User Permissions */}
                            {allPermissions.length > 0 && (
                                <div className="space-y-2.5 pt-4 border-t border-border/40">
                                    <div className="space-y-0.5">
                                        <Label className="text-sm font-semibold text-foreground flex items-center gap-1.5">
                                            <KeyRound className="h-4 w-4 text-primary" /> Individual Direct Permissions
                                        </Label>
                                        <p className="text-xs text-muted-foreground">
                                            Set explicit permissions directly for this single user (effective alongside their role permissions).
                                        </p>
                                    </div>

                                    <CategorizedPermissionSelector
                                        allPermissions={allPermissions}
                                        selectedPermissions={data.permissions}
                                        onChange={(perms) => setData('permissions', perms)}
                                    />
                                </div>
                            )}

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-border/50">
                                <Button asChild variant="outline" type="button">
                                    <Link href={route('users.index')}>Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing} className="font-medium">
                                    {processing ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
