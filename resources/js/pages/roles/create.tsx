import CategorizedPermissionSelector from '@/components/CategorizedPermissionSelector';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, PlusCircle, ShieldPlus } from 'lucide-react';
import React from 'react';

interface Props {
    permissions: string[];
    groupedPermissions: Record<string, string[]>;
}

export default function RoleCreate({ permissions, groupedPermissions }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        permissions: [] as string[],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('roles.store'));
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Roles', href: '/roles' }, { title: 'Create Role' }]}>
            <Head title="Create New Role" />

            <div className="max-w-4xl mx-auto space-y-4">
                <div className="flex items-center gap-3">
                    <Button asChild variant="outline" size="icon" className="h-9 w-9">
                        <Link href={route('roles.index')}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <ShieldPlus className="h-5 w-5 text-primary" /> Create New Role
                        </h1>
                        <p className="text-xs text-muted-foreground">
                            Define a custom role and configure its module permissions
                        </p>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <Card className="border-border/60 shadow-xs">
                        <CardHeader className="py-4 px-6 border-b border-border/40">
                            <CardTitle className="text-sm font-semibold">Role Name</CardTitle>
                        </CardHeader>
                        <CardContent className="p-6">
                            <div className="space-y-1.5 max-w-md">
                                <Label htmlFor="name">Role Title <span className="text-destructive">*</span></Label>
                                <Input
                                    id="name"
                                    placeholder="e.g. IT Network Specialist"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                />
                                {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 shadow-xs">
                        <CardHeader className="py-4 px-6 border-b border-border/40">
                            <CardTitle className="text-sm font-semibold">Permissions Matrix</CardTitle>
                            <CardDescription className="text-xs">
                                Check the permissions this role will possess. (Case module permissions are strictly IT Team only).
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-6">
                            <CategorizedPermissionSelector
                                allPermissions={permissions}
                                groupedPermissions={groupedPermissions}
                                selectedPermissions={data.permissions}
                                onChange={(perms) => setData('permissions', perms)}
                            />
                            {errors.permissions && <p className="text-xs text-destructive mt-2">{errors.permissions}</p>}
                        </CardContent>
                    </Card>

                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Button asChild variant="outline" type="button">
                            <Link href={route('roles.index')}>Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing} className="font-medium">
                            {processing ? 'Creating Role...' : 'Save Role'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
