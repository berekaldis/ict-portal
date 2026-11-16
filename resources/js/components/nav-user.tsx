import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/react';
import { ChevronsUpDown, LogOut, Shield, User } from 'lucide-react';

export function NavUser() {
    const { isMobile } = useSidebar();
    const { props } = usePage<any>();
    const user = props.auth?.user;
    const roles = props.auth?.roles || [];

    if (!user) return null;

    const initials = (user.name || user.username || 'U')
        .split(' ')
        .map((n: string) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        >
                            <Avatar className="h-8 w-8 rounded-lg border border-border/40">
                                <AvatarFallback className="rounded-lg bg-primary/10 text-primary font-bold text-xs">
                                    {initials}
                                </AvatarFallback>
                            </Avatar>
                            <div className="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden">
                                <span className="truncate font-semibold text-xs">{user.name || user.username}</span>
                                <span className="truncate text-[10px] text-muted-foreground">
                                    {roles[0] || user.role || 'User'}
                                </span>
                            </div>
                            <ChevronsUpDown className="ml-auto size-4 group-data-[collapsible=icon]:hidden" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        side={isMobile ? 'bottom' : 'right'}
                        align="end"
                        sideOffset={4}
                    >
                        <DropdownMenuLabel className="p-0 font-normal">
                            <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <Avatar className="h-8 w-8 rounded-lg">
                                    <AvatarFallback className="rounded-lg bg-primary/10 text-primary font-bold text-xs">
                                        {initials}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="grid flex-1 text-left text-sm leading-tight">
                                    <span className="truncate font-semibold">{user.name || user.username}</span>
                                    <span className="truncate text-xs text-muted-foreground">
                                        {user.email || user.username}
                                    </span>
                                </div>
                            </div>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <div className="px-2 py-1 text-[11px] text-muted-foreground flex items-center gap-1.5">
                            <Shield className="h-3 w-3 text-primary" />
                            <span>Role: <strong>{roles.join(', ') || 'None'}</strong></span>
                        </div>
                        {user.branch && (
                            <div className="px-2 py-0.5 text-[11px] text-muted-foreground">
                                Branch: <strong>{user.branch}</strong>
                            </div>
                        )}
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="w-full flex items-center gap-2 cursor-pointer text-destructive focus:text-destructive"
                            >
                                <LogOut className="h-4 w-4" />
                                <span>Log out</span>
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
