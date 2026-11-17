import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { usePermission } from '@/hooks/user-permissions';
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import React, { useEffect, useRef, useState } from 'react';

export interface NavItem {
    title: string;
    href: string;
    icon: React.ComponentType<{ className?: string }>;
    permission?: string;
    badge?: string;
    itOnly?: boolean;
}

export interface NavSection {
    label: string;
    icon?: React.ComponentType<{ className?: string }>;
    permission?: string;
    itOnly?: boolean;
    items: NavItem[];
}

interface Props {
    sections: NavSection[];
}

/**
 * Checks whether an item's href matches the current URL accurately.
 * Prevents prefix collision (e.g. /edit-requests falsely matching /edit-requests/reports).
 */
export function isRouteActive(itemHref: string, currentUrl: string): boolean {
    const currentPath = currentUrl.split('?')[0].replace(/\/+$/, '') || '/';
    const targetPath = itemHref.split('?')[0].replace(/\/+$/, '') || '/';

    // 1. Exact match
    if (currentPath === targetPath) return true;

    // 2. Both represent dashboard / root home
    if ((currentPath === '/' || currentPath === '/dashboard') && (targetPath === '/' || targetPath === '/dashboard')) {
        return true;
    }

    // 3. Prevent dashboard or home from matching other routes
    if (targetPath === '/' || targetPath === '/dashboard') return false;

    // 4. Disallow /edit-requests from matching /edit-requests/reports or /edit-requests/create
    if (targetPath === '/edit-requests') {
        if (currentPath.startsWith('/edit-requests/reports') || currentPath.startsWith('/edit-requests/create')) {
            return false;
        }
    }

    // 5. Disallow /cases from matching /cases/create or /cases/sla or /cases/settings
    if (targetPath === '/cases') {
        if (
            currentPath.startsWith('/cases/create') ||
            currentPath.startsWith('/cases/sla') ||
            currentPath.startsWith('/cases/settings')
        ) {
            return false;
        }
    }

    // 6. Disallow /hr (HR Dashboard) from matching /hr/internships, /hr/qualifications, /hr/recruitments, /hr/settings
    if (targetPath === '/hr') {
        if (
            currentPath.startsWith('/hr/internships') ||
            currentPath.startsWith('/hr/qualifications') ||
            currentPath.startsWith('/hr/recruitments') ||
            currentPath.startsWith('/hr/settings')
        ) {
            return false;
        }
    }

    // 7. Match sub-resource detail or edit routes (e.g. /edit-requests/41 or /cases/12)
    return currentPath.startsWith(targetPath + '/');
}

function NavSectionItem({
    section,
    visibleItems,
    currentUrl,
    isOpen,
    isCollapsed,
    onToggle,
    onLinkClick,
}: {
    section: NavSection;
    visibleItems: NavItem[];
    currentUrl: string;
    isOpen: boolean;
    isCollapsed: boolean;
    onToggle: (open: boolean) => void;
    onLinkClick: () => void;
}) {
    const isSectionActive = visibleItems.some((item) => isRouteActive(item.href, currentUrl));
    const SectionIcon = section.icon;

    // In Collapsed (icon-only) mode, show a clean flyout dropdown menu on hover/click
    if (isCollapsed) {
        return (
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            tooltip={section.label}
                            isActive={isSectionActive}
                            className={`w-full justify-center transition-colors p-2 rounded-lg cursor-pointer ${
                                isSectionActive
                                    ? 'text-primary font-semibold bg-primary/10'
                                    : 'text-foreground/80 hover:bg-muted/70 hover:text-foreground'
                            }`}
                        >
                            {SectionIcon && (
                                <SectionIcon
                                    className={`h-4.5 w-4.5 shrink-0 transition-colors ${
                                        isSectionActive ? 'text-primary' : 'text-muted-foreground'
                                    }`}
                                />
                            )}
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        side="right"
                        align="start"
                        sideOffset={8}
                        className="min-w-52 rounded-xl p-1.5 shadow-xl border border-border/70 bg-popover text-popover-foreground z-50"
                    >
                        <DropdownMenuLabel className="px-2.5 py-1.5 text-xs font-bold text-foreground flex items-center gap-2">
                            {SectionIcon && <SectionIcon className="h-4 w-4 text-primary shrink-0" />}
                            <span>{section.label}</span>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator className="my-1 border-border/50" />
                        {visibleItems.map((item) => {
                            const isActive = isRouteActive(item.href, currentUrl);
                            const ItemIcon = item.icon;

                            return (
                                <DropdownMenuItem key={item.title} asChild className="cursor-pointer">
                                    <Link
                                        href={item.href}
                                        prefetch
                                        onClick={onLinkClick}
                                        className={`flex items-center justify-between w-full px-2.5 py-2 text-xs rounded-lg transition-colors ${
                                            isActive
                                                ? 'bg-primary/15 text-primary font-semibold'
                                                : 'text-muted-foreground hover:text-foreground hover:bg-muted/60'
                                        }`}
                                    >
                                        <div className="flex items-center gap-2.5 min-w-0">
                                            <ItemIcon
                                                className={`h-4 w-4 shrink-0 ${
                                                    isActive ? 'text-primary' : 'text-muted-foreground'
                                                }`}
                                            />
                                            <span className="truncate">{item.title}</span>
                                        </div>
                                        {item.badge && (
                                            <span className="ml-auto text-[10px] font-mono px-1.5 py-0.2 rounded-full bg-primary/15 text-primary shrink-0">
                                                {item.badge}
                                            </span>
                                        )}
                                    </Link>
                                </DropdownMenuItem>
                            );
                        })}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        );
    }

    // In Expanded mode, standard collapsible accordion with sub-menu items
    return (
        <Collapsible
            open={isOpen}
            onOpenChange={onToggle}
            className="group/collapsible w-full"
        >
            <SidebarMenuItem>
                <CollapsibleTrigger asChild>
                    <SidebarMenuButton
                        tooltip={section.label}
                        className={`w-full justify-between transition-colors py-2 px-3 rounded-lg cursor-pointer select-none ${
                            isSectionActive
                                ? 'text-foreground font-semibold bg-muted/60'
                                : 'text-foreground/80 hover:bg-muted/70 hover:text-foreground'
                        }`}
                    >
                        <div className="flex items-center gap-3 min-w-0">
                            {SectionIcon && (
                                <SectionIcon
                                    className={`h-4.5 w-4.5 shrink-0 transition-colors ${
                                        isSectionActive
                                            ? 'text-primary'
                                            : 'text-muted-foreground group-hover/collapsible:text-foreground'
                                    }`}
                                />
                            )}
                            <span className="truncate text-sm font-medium group-data-[collapsible=icon]:hidden">
                                {section.label}
                            </span>
                        </div>
                        <ChevronRight
                            className={`h-4 w-4 shrink-0 text-muted-foreground transition-transform duration-200 group-data-[collapsible=icon]:hidden ${
                                isOpen ? 'rotate-90 text-foreground' : ''
                            }`}
                        />
                    </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent className="group-data-[collapsible=icon]:hidden">
                    <SidebarMenuSub className="ml-4 my-1 pl-3.5 border-l border-border/60 space-y-1">
                        {visibleItems.map((item) => {
                            const isActive = isRouteActive(item.href, currentUrl);
                            const ItemIcon = item.icon;

                            return (
                                <SidebarMenuSubItem key={item.title}>
                                    <SidebarMenuSubButton
                                        asChild
                                        isActive={isActive}
                                        className={`transition-colors py-1.5 px-2 rounded-md ${
                                            isActive
                                                ? 'bg-primary/10 text-primary font-semibold'
                                                : 'text-muted-foreground hover:text-foreground hover:bg-muted/50'
                                        }`}
                                    >
                                        <Link
                                            href={item.href}
                                            prefetch
                                            onClick={onLinkClick}
                                            className="flex items-center justify-between w-full"
                                        >
                                            <div className="flex items-center gap-2 min-w-0">
                                                <ItemIcon
                                                    className={`h-3.5 w-3.5 shrink-0 ${
                                                        isActive ? 'text-primary' : 'text-muted-foreground'
                                                    }`}
                                                />
                                                <span className="truncate text-xs">{item.title}</span>
                                            </div>
                                            {item.badge && (
                                                <span className="ml-auto text-[10px] font-mono px-1.5 py-0.2 rounded-full bg-primary/15 text-primary shrink-0">
                                                    {item.badge}
                                                </span>
                                            )}
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            );
                        })}
                    </SidebarMenuSub>
                </CollapsibleContent>
            </SidebarMenuItem>
        </Collapsible>
    );
}

export function NavMain({ sections }: Props) {
    const { can, isItTeam } = usePermission();
    const { url } = usePage();
    const { isMobile, setOpenMobile, state } = useSidebar();
    const isCollapsed = state === 'collapsed' && !isMobile;

    // Determine which section contains the currently active route
    const getActiveSectionLabel = React.useCallback((): string | null => {
        for (const section of sections) {
            if (section.itOnly && !isItTeam) continue;
            if (section.permission && !can(section.permission)) continue;

            const hasActiveItem = section.items.some((item) => {
                if (item.itOnly && !isItTeam) return false;
                if (item.permission && !can(item.permission)) return false;
                return isRouteActive(item.href, url);
            });

            if (hasActiveItem) {
                return section.label;
            }
        }
        return null;
    }, [sections, isItTeam, can, url]);

    // Track open state for each section independently
    const [openSections, setOpenSections] = useState<Record<string, boolean>>(() => {
        const active = getActiveSectionLabel();
        return active ? { [active]: true } : {};
    });

    // When the URL changes, automatically expand the newly active section
    const lastUrlRef = useRef(url);
    useEffect(() => {
        if (lastUrlRef.current !== url) {
            lastUrlRef.current = url;
            const active = getActiveSectionLabel();
            if (active) {
                setOpenSections((prev) => ({ ...prev, [active]: true }));
            }
        }
    }, [url, getActiveSectionLabel]);

    const handleToggleSection = (label: string, nextOpen?: boolean) => {
        setOpenSections((prev) => ({
            ...prev,
            [label]: nextOpen !== undefined ? nextOpen : !prev[label],
        }));
    };

    const handleLinkClick = () => {
        if (isMobile) {
            setOpenMobile(false);
        }
    };

    return (
        <SidebarGroup className="py-2">
            <SidebarMenu className="space-y-1">
                {sections.map((section) => {
                    if (section.itOnly && !isItTeam) {
                        return null;
                    }

                    if (section.permission && !can(section.permission)) {
                        return null;
                    }

                    const visibleItems = section.items.filter((item) => {
                        if (item.itOnly && !isItTeam) return false;
                        if (!item.permission) return true;
                        return can(item.permission);
                    });

                    if (visibleItems.length === 0) {
                        return null;
                    }

                    const isOpen = Boolean(openSections[section.label]);

                    return (
                        <NavSectionItem
                            key={section.label}
                            section={section}
                            visibleItems={visibleItems}
                            currentUrl={url}
                            isOpen={isOpen}
                            isCollapsed={isCollapsed}
                            onToggle={(open) => handleToggleSection(section.label, open)}
                            onLinkClick={handleLinkClick}
                        />
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
