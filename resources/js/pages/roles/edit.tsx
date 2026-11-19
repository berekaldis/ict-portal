import CategorizedPermissionSelector from '@/components/CategorizedPermissionSelector';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckSquare, Save, ShieldCheck, Square } from 'lucide-react';
import React from 'react';

interface Props {
    role: {
        id: number;
        name: string;
        permissions: string[];
    };
    permissions: string[];
    groupedPermissions: Record<string, string[]>;
}

export default function RoleEdit({ role, permissions, groupedPermissions }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        permissions: role.permissions || [],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('roles.update', role.id));
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Roles', href: '/roles' }, { title: `Edit ${role.name}` }]}>
            <Head title={`Edit Role ${role.name}`} />

            <div className="max-w-4xl mx-auto space-y-4">
                <div className="flex items-center gap-3">
                    <Button asChild variant="outline" size="icon" className="h-9 w-9">
                        <Link href={route('roles.index')}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <ShieldCheck className="h-5 w-5 text-primary" /> Edit Role Permissions: {role.name}
                        </h1>
                        <p className="text-xs text-muted-foreground">
                            Update granted capabilities and module access rights for this role
                        </p>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-4">
                    <Card className="border-border/60 shadow-xs">
                        <CardHeader className="py-4 px-6 border-b border-border/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div>
                                <CardTitle className="text-sm font-semibold">Configured Permissions ({data.permissions.length} Selected)</CardTitle>
                                <CardDescription className="text-xs">
                                    Changes take effect immediately upon saving.
                                </CardDescription>
                            </div>
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setData('permissions', [...permissions])}
                                    className="text-xs h-8 font-medium"
                                >
                                    <CheckSquare className="h-3.5 w-3.5 mr-1 text-primary" /> Select All ({permissions.length})
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setData('permissions', [])}
                                    className="text-xs h-8 text-destructive hover:bg-destructive/10 border-destructive/30 font-medium"
                                >
                                    <Square className="h-3.5 w-3.5 mr-1" /> Deselect All
                                </Button>
                            </div>
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
                            <Save className="h-4 w-4 mr-1.5" />
                            {processing ? 'Saving...' : 'Update Permissions'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
