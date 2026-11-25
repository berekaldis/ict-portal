import CategorizedPermissionSelector from '@/components/CategorizedPermissionSelector';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Check, KeyRound, UserPlus } from 'lucide-react';
import React from 'react';

interface Props {
    roles: string[];
    branches: string[];
    allPermissions?: string[];
}

export default function UserCreate({ roles, branches, allPermissions = [] }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        username: '',
        name: '',
        email: '',
        password: '',
        department: '',
        branch: '',
        chat_id: '',
        roles: [] as string[],
        permissions: [] as string[],
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
        post(route('users.store'));
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Users', href: '/users' }, { title: 'Create User' }]}>
            <Head title="Create New User" />

            <div className="max-w-xl mx-auto space-y-4">
                <div className="flex items-center gap-3">
                    <Button asChild variant="outline" size="icon" className="h-9 w-9">
                        <Link href={route('users.index')}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <UserPlus className="h-5 w-5 text-primary" /> Create New User
                        </h1>
                        <p className="text-xs text-muted-foreground">
                            Register a new account and assign roles & branch access
                        </p>
                    </div>
                </div>

                <Card className="border-border/60 shadow-xs">
                    <CardHeader className="py-4 px-6 border-b border-border/40">
                        <CardTitle className="text-sm font-semibold">Account Information</CardTitle>
                    </CardHeader>
                    <CardContent className="p-6">
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="username">Username <span className="text-destructive">*</span></Label>
                                    <Input
                                        id="username"
                                        placeholder="e.g. jdoe"
                                        value={data.username}
                                        onChange={(e) => setData('username', e.target.value)}
                                    />
                                    {errors.username && <p className="text-xs text-destructive">{errors.username}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="name">Full Name <span className="text-destructive">*</span></Label>
                                    <Input
                                        id="name"
                                        placeholder="John Doe"
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
                                        placeholder="jdoe@kaldisbunna.et"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                    />
                                    {errors.email && <p className="text-xs text-destructive">{errors.email}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="password">Initial Password <span className="text-destructive">*</span></Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        placeholder="••••••••"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                    />
                                    {errors.password && <p className="text-xs text-destructive">{errors.password}</p>}
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
                                        placeholder="e.g. IT, Finance, Operations..."
                                        value={data.department}
                                        onChange={(e) => setData('department', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="chat_id">Telegram Chat ID (Optional)</Label>
                                <Input
                                    id="chat_id"
                                    placeholder="e.g. 814523272"
                                    value={data.chat_id}
                                    onChange={(e) => setData('chat_id', e.target.value)}
                                />
                            </div>

                            {/* Roles Selection */}
                            <div className="space-y-2 pt-2 border-t border-border/40">
                                <div className="flex items-center justify-between">
                                    <Label className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                        Assign System Roles <span className="text-destructive">*</span>
                                    </Label>
                                    <span className="text-xs text-muted-foreground font-mono">
                                        {data.roles.length} selected
                                    </span>
                                </div>
                                <div className="grid grid-cols-2 gap-2">
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
                                            Optionally set explicit direct permissions for this user (effective alongside role permissions).
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
                                    {processing ? 'Creating User...' : 'Create User'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
