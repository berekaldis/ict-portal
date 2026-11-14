import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { formatPermissionLabel } from '@/lib/permission-categories';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import {
    Briefcase,
    FileEdit,
    Lock,
    Settings,
    Shield,
    Users,
} from 'lucide-react';
import React from 'react';

interface Props {
    groupedPermissions: Record<string, string[]>;
    total: number;
}

const CATEGORY_ICONS: Record<string, React.ReactNode> = {
    'Case Management (IT Only)': <Briefcase className="h-4 w-4 text-amber-600" />,
    'Edit & Void Requests': <FileEdit className="h-4 w-4 text-blue-600" />,
    'User & Organization': <Users className="h-4 w-4 text-emerald-600" />,
    'Roles & Access Control': <Shield className="h-4 w-4 text-purple-600" />,
    'System & General': <Settings className="h-4 w-4 text-zinc-600" />,
};

export default function PermissionsIndex({ groupedPermissions, total }: Props) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Access Control', href: '/roles' }, { title: 'Permissions' }]}>
            <Head title="System Permissions" />

            <div className="space-y-6 max-w-5xl mx-auto">
                <div>
                    <div className="flex items-center gap-2">
                        <Lock className="h-5 w-5 text-primary" />
                        <h2 className="text-xl font-bold tracking-tight text-foreground">System Permissions Registry</h2>
                        <Badge variant="secondary" className="bg-primary/10 text-primary text-xs">
                            {total} Defined
                        </Badge>
                    </div>
                    <p className="text-xs text-muted-foreground mt-0.5">
                        Overview of all granular capabilities available across the Kaldis ICT Portal
                    </p>
                </div>

                <div className="space-y-4">
                    {Object.keys(groupedPermissions).map((cat) => {
                        const perms = groupedPermissions[cat];
                        return (
                            <Card key={cat} className="border-border/60 shadow-xs">
                                <CardHeader className="py-3 px-5 border-b border-border/40 bg-muted/15 flex flex-row items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        {CATEGORY_ICONS[cat]}
                                        <CardTitle className="text-sm font-semibold">{cat}</CardTitle>
                                    </div>
                                    <Badge variant="outline" className="text-xs font-mono">
                                        {perms.length} Permissions
                                    </Badge>
                                </CardHeader>
                                <CardContent className="p-4">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                                        {perms.map((p) => (
                                            <div
                                                key={p}
                                                className="p-2.5 rounded-lg border border-border/40 bg-card hover:bg-muted/20 transition-colors space-y-0.5"
                                            >
                                                <div className="text-xs font-semibold text-foreground">
                                                    {formatPermissionLabel(p)}
                                                </div>
                                                <div className="font-mono text-[10px] text-muted-foreground">
                                                    {p}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
