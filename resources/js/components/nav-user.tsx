import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { PasswordChangeDialog } from '@/components/PasswordChangeDialog';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    ChevronsUpDown,
    KeyRound,
    LogOut,
    Mail,
    Settings,
    Shield,
} from 'lucide-react';
import React, { useState } from 'react';

export function NavUser() {
    const { isMobile, state } = useSidebar();
    const { props } = usePage<any>();
    const user = props.auth?.user;
    const roles: string[] = props.auth?.roles || [];
    const permissions: string[] = props.auth?.permissions || [];
    const [passwordModalOpen, setPasswordModalOpen] = useState(false);

    if (!user) return null;

    const initials = (user.name || user.username || 'U')
        .split(' ')
        .map((n: string) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    const primaryRole = roles[0] || user.role || 'Staff';
    const canManageSettings = permissions.includes('case.settings.manage') || roles.includes('Super Admin');

    return (
        <>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <SidebarMenuButton
                                size="lg"
                                className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground border border-sidebar-border/40 hover:border-sidebar-border"
                            >
                                <Avatar className="h-8 w-8 rounded-lg border border-amber-600/30">
                                    <AvatarFallback className="rounded-lg bg-amber-500/15 text-amber-800 dark:text-amber-300 font-bold text-xs">
                                        {initials}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden">
                                    <span className="truncate font-semibold text-xs text-foreground">
                                        {user.name || user.username}
                                    </span>
                                    <span className="truncate text-[10px] text-muted-foreground font-medium">
                                        {primaryRole}
                                    </span>
                                </div>
                                <ChevronsUpDown className="ml-auto size-4 text-muted-foreground group-data-[collapsible=icon]:hidden" />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            className="w-64 rounded-xl p-1.5 shadow-lg border-border/70"
                            side={isMobile ? 'bottom' : (state === 'collapsed' ? 'right' : 'top')}
                            align={state === 'collapsed' ? 'end' : 'start'}
                            sideOffset={8}
                        >
                            <DropdownMenuLabel className="p-2 font-normal">
                                <div className="flex items-center gap-2.5">
                                    <Avatar className="h-10 w-10 rounded-lg border border-amber-500/30">
                                        <AvatarFallback className="rounded-lg bg-amber-500/15 text-amber-800 dark:text-amber-300 font-bold text-sm">
                                            {initials}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className="grid flex-1 text-left leading-tight min-w-0">
                                        <span className="truncate font-semibold text-sm text-foreground">
                                            {user.name || user.username}
                                        </span>
                                        <span className="truncate text-xs text-muted-foreground flex items-center gap-1">
                                            <Mail className="h-2.5 w-2.5 shrink-0" />
                                            {user.email || user.username}
                                        </span>
                                    </div>
                                </div>
                            </DropdownMenuLabel>

                            <DropdownMenuSeparator />

                            {/* Role, Department, Branch Info */}
                            <div className="px-2.5 py-1.5 space-y-1 bg-muted/40 rounded-lg mx-1 my-1 text-[11px]">
                                <div className="flex items-center justify-between text-muted-foreground">
                                    <span className="flex items-center gap-1.5">
                                        <Shield className="h-3 w-3 text-amber-600 dark:text-amber-400" />
                                        <span>Role:</span>
                                    </span>
                                    <span className="font-semibold text-foreground truncate max-w-[120px]">
                                        {roles.join(', ') || primaryRole}
                                    </span>
                                </div>
                                {user.department && (
                                    <div className="flex items-center justify-between text-muted-foreground">
                                        <span className="flex items-center gap-1.5">
                                            <Building2 className="h-3 w-3 text-primary" />
                                            <span>Dept:</span>
                                        </span>
                                        <span className="font-medium text-foreground">{user.department}</span>
                                    </div>
                                )}
                                {user.branch && (
                                    <div className="flex items-center justify-between text-muted-foreground">
                                        <span className="flex items-center gap-1.5">
                                            <Building2 className="h-3 w-3 text-primary" />
                                            <span>Branch:</span>
                                        </span>
                                        <span className="font-medium text-foreground">{user.branch}</span>
                                    </div>
                                )}
                            </div>

                            <DropdownMenuSeparator />

                            {/* Settings & Password links */}
                            {canManageSettings && (
                                <DropdownMenuItem asChild>
                                    <Link
                                        href={route('settings.index')}
                                        className="w-full flex items-center gap-2 px-2.5 py-2 text-xs rounded-md cursor-pointer hover:bg-muted"
                                    >
                                        <Settings className="h-3.5 w-3.5 text-muted-foreground" />
                                        <span>Portal Settings</span>
                                    </Link>
                                </DropdownMenuItem>
                            )}

                            <DropdownMenuItem
                                onClick={() => setPasswordModalOpen(true)}
                                className="w-full flex items-center gap-2 px-2.5 py-2 text-xs rounded-md cursor-pointer hover:bg-muted"
                            >
                                <KeyRound className="h-3.5 w-3.5 text-muted-foreground" />
                                <span>Change Password</span>
                            </DropdownMenuItem>

                            <DropdownMenuSeparator />

                            {/* Log out */}
                            <DropdownMenuItem asChild>
                                <Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                    className="w-full flex items-center gap-2 px-2.5 py-2 text-xs rounded-md cursor-pointer text-destructive focus:text-destructive focus:bg-destructive/10 hover:bg-destructive/10 transition-colors"
                                >
                                    <LogOut className="h-3.5 w-3.5" />
                                    <span className="font-semibold">Log out</span>
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>

            {passwordModalOpen && (
                <PasswordChangeDialog
                    open={passwordModalOpen}
                    onClose={() => setPasswordModalOpen(false)}
                />
            )}
        </>
    );
}
