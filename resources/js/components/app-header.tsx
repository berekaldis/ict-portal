import { AppearanceDropdown } from '@/components/appearance-dropdown';
import { Breadcrumbs, BreadcrumbItemType } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { usePermission } from '@/hooks/user-permissions';
import { router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import React, { useState } from 'react';
import { toast } from 'sonner';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

export function AppHeader({ breadcrumbs = [] }: Props) {
    const { can, isSuperAdmin } = usePermission();
    const [syncing, setSyncing] = useState(false);

    const canSync = can('company_system.sync') || isSuperAdmin;

    const handleManualSync = () => {
        setSyncing(true);
        router.post(
            route('company-system.sync'),
            {},
            {
                onSuccess: (page: any) => {
                    const msg = page.props.flash?.success || 'Company System synchronized successfully!';
                    toast.success(msg);
                },
                onError: (err: any) => {
                    const msg = err?.message || 'Failed to sync with Company System.';
                    toast.error(msg);
                },
                onFinish: () => {
                    setSyncing(false);
                },
            }
        );
    };

    return (
        <header className="flex h-14 shrink-0 items-center gap-2 border-b border-border/50 bg-background/80 px-4 backdrop-blur-sm sticky top-0 z-10 justify-between">
            <div className="flex items-center gap-2 min-w-0">
                <SidebarTrigger className="-ml-1" />
                <div className="h-4 w-[1px] bg-border mx-1 shrink-0" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <div className="flex items-center gap-2.5 shrink-0">
                {canSync && (
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={handleManualSync}
                        disabled={syncing}
                        className="h-8 gap-1.5 text-xs font-semibold border-amber-500/30 hover:bg-amber-500/10 text-amber-900 dark:text-amber-200 transition-all shadow-2xs"
                        title="Manually sync departments, active branches, and users from Company System"
                    >
                        <RefreshCw className={`h-3.5 w-3.5 text-amber-600 dark:text-amber-400 ${syncing ? 'animate-spin' : ''}`} />
                        <span className="hidden sm:inline">
                            {syncing ? 'Syncing...' : 'Sync Company System'}
                        </span>
                    </Button>
                )}

                <AppearanceDropdown />
            </div>
        </header>
    );
}
