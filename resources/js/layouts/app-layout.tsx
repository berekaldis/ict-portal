import { AppFooter } from '@/components/app-footer';
import { AppHeader } from '@/components/app-header';
import { AppSidebar } from '@/components/app-sidebar';
import { BreadcrumbItemType } from '@/components/breadcrumbs';
import { PasswordChangeDialog } from '@/components/PasswordChangeDialog';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/react';
import React, { useEffect } from 'react';
import { Toaster, toast } from 'sonner';

interface Props {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItemType[];
}

export default function AppLayout({ children, breadcrumbs = [] }: Props) {
    const { props } = usePage<any>();
    const flash = props.flash;
    const mustChangePassword = props.auth?.mustChangePassword;

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
        if (flash?.message) {
            toast.info(flash.message);
        }
    }, [flash]);

    return (
        <SidebarProvider defaultOpen={props.sidebarOpen ?? true}>
            <AppSidebar />
            <SidebarInset className="flex flex-col flex-1 min-h-full min-h-screen bg-background">
                <AppHeader breadcrumbs={breadcrumbs} />
                <main className="flex-1 p-4 md:p-6 overflow-x-hidden">
                    {children}
                </main>
                <AppFooter />
                {mustChangePassword && <PasswordChangeDialog open={true} />}
                <Toaster position="top-right" richColors />
            </SidebarInset>
        </SidebarProvider>
    );
}
