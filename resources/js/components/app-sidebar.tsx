import { AppLogo } from '@/components/app-logo';
import { NavMain, NavSection } from '@/components/nav-main';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { Link } from '@inertiajs/react';
import {
    BarChart3,
    Briefcase,
    CheckCircle2,
    ClipboardCheck,
    Clock,
    DollarSign,
    FileText,
    GraduationCap,
    HardDrive,
    LayoutDashboard,
    LayoutGrid,
    Lock,
    MessageSquare,
    Phone,
    PlusCircle,
    Receipt,
    Send,
    Server,
    Settings,
    Settings2,
    ShieldCheck,
    Signal,
    TrendingUp,
    UserCheck,
    Users,
    Wifi,
} from 'lucide-react';

const sections: NavSection[] = [
    // =========================================================
    // DASHBOARD
    // =========================================================
    {
        label: 'Dashboard',
        icon: LayoutGrid,
        permission: 'view dashboard',
        items: [
            {
                title: 'Overview',
                href: '/dashboard',
                icon: LayoutDashboard,
                permission: 'view dashboard',
            },
            {
                title: 'Reports & Analytics',
                href: '/edit-requests/reports',
                icon: BarChart3,
                permission: 'edit_request.reports.view',
            },
        ],
    },
    // =========================================================
    // VOUCHER EDIT & ON HAND DELETE REQUESTS
    // =========================================================
    {
        label: 'Voucher Requests',
        icon: FileText,
        permission: 'edit_request.view|edit_request.create|edit_request.reports.view',
        items: [
            {
                title: 'All Requests',
                href: '/edit-requests',
                icon: FileText,
                permission: 'edit_request.view',
            },
            {
                title: 'Submit Request',
                href: '/edit-requests/create',
                icon: PlusCircle,
                permission: 'edit_request.create',
            },
        ],
    },
    // =========================================================
    // CASE MANAGEMENT (IT ONLY)
    // =========================================================
    {
        label: 'Case Management',
        icon: Briefcase,
        itOnly: true,
        permission: 'case.view|case.create|case.sla.view',
        items: [
            {
                title: 'All Cases',
                href: '/cases',
                icon: Briefcase,
                permission: 'case.view',
            },
            {
                title: 'New Case',
                href: '/cases/create',
                icon: PlusCircle,
                permission: 'case.create',
            },
            {
                title: 'SLA & Escalations',
                href: '/cases/sla',
                icon: Clock,
                permission: 'case.sla.view',
            },
        ],
    },
    // =========================================================
    // TELECOM MANAGEMENT MODULE
    // =========================================================
    {
        label: 'Telecom Management',
        icon: Signal,
        permission: 'telecom.view|view telecom management|manage telecom connections',
        items: [
            {
                title: 'Telecom Dashboard',
                href: '/telecom/dashboard',
                icon: LayoutDashboard,
                permission: 'telecom.view|view telecom management',
            },
            {
                title: 'Phone Lines & SIMs',
                href: '/telecom/phone-numbers',
                icon: Phone,
                permission: 'telecom.phone.view|telecom.view|view telecom management',
            },
            {
                title: 'Broadband & WTTx',
                href: '/telecom/broadbands',
                icon: Wifi,
                permission: 'telecom.broadband.view|telecom.view|view telecom management',
            },
            {
                title: 'Service Providers',
                href: '/telecom/providers',
                icon: Server,
                permission: 'telecom.providers.manage|manage telecom connections',
            },
        ],
    },
    // =========================================================
    // KALDIS BRANCH COMMUNICATION PLATFORM
    // =========================================================
    {
        label: 'Kaldis Communication',
        icon: Send,
        permission: 'communication.view|view kaldis communication|view telegram config|manage telegram config',
        items: [
            {
                title: 'Communication Hub',
                href: '/kaldis-communication',
                icon: MessageSquare,
                permission: 'communication.view|view kaldis communication|view telegram config|manage telegram config',
            },
        ],
    },
    // =========================================================
    // IT BRANCH INFRASTRUCTURE & HARDWARE AUDIT (SERVEY MODULE)
    // =========================================================
    {
        label: 'IT Branch Survey & Audit',
        icon: ClipboardCheck,
        permission: 'it_survey.view|it_survey.create|it_survey.manage',
        items: [
            {
                title: 'Audit Reports & Health',
                href: '/it-surveys',
                icon: BarChart3,
                permission: 'it_survey.view',
            },
            {
                title: 'New Branch Inspection',
                href: '/it-surveys/create',
                icon: PlusCircle,
                permission: 'it_survey.create',
            },
            {
                title: 'Hardware Asset Inventory',
                href: '/it-surveys/hardware',
                icon: HardDrive,
                permission: 'it_survey.view',
            },
        ],
    },
    // =========================================================
    // SALES DISCREPANCY & Z-REPORT RECONCILIATION (SALES MODULE)
    // =========================================================
    {
        label: 'Sales Reconciliation',
        icon: Receipt,
        permission: 'sales_case.view|sales_case.create|sales_case.manage',
        items: [
            {
                title: 'Sales Dashboard',
                href: '/sales/dashboard',
                icon: TrendingUp,
                permission: 'sales_case.view',
            },
            {
                title: 'Reconciliation Cases',
                href: '/sales/cases',
                icon: FileText,
                permission: 'sales_case.view',
            },
            {
                title: 'Add Sales Case',
                href: '/sales/create',
                icon: PlusCircle,
                permission: 'sales_case.create',
            },
        ],
    },
    // =========================================================
    // HR MANAGEMENT (HR MODULE)
    // =========================================================
    {
        label: 'HR Management',
        icon: Users,
        permission: 'hr.view|hr.create|hr.manage',
        items: [
            {
                title: 'HR Dashboard',
                href: '/hr',
                icon: LayoutDashboard,
                permission: 'hr.view',
            },
            {
                title: 'Internship Requests',
                href: '/hr/internships',
                icon: GraduationCap,
                permission: 'hr.view',
            },
            {
                title: 'Qualifications',
                href: '/hr/qualifications',
                icon: UserCheck,
                permission: 'hr.view',
            },
            {
                title: 'Recruitments',
                href: '/hr/recruitments',
                icon: Briefcase,
                permission: 'hr.view',
            },
            {
                title: 'HR Settings',
                href: '/hr/settings',
                icon: Settings2,
                permission: 'hr.manage',
            },
        ],
    },
    // =========================================================
    // SYSTEM ADMINISTRATION
    // =========================================================
    {
        label: 'System Administration',
        icon: Settings,
        permission: 'view users|view roles|view permissions|case.settings.manage',
        items: [
            {
                title: 'Users',
                href: '/users',
                icon: Users,
                permission: 'view users',
            },
            {
                title: 'Roles',
                href: '/roles',
                icon: ShieldCheck,
                permission: 'view roles',
            },
            {
                title: 'Permissions',
                href: '/permissions',
                icon: Lock,
                permission: 'view permissions',
            },
            {
                title: 'Settings',
                href: '/settings',
                icon: Settings2,
                permission: 'case.settings.manage',
            },
        ],
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <Link href="/dashboard" prefetch>
                            <AppLogo />
                        </Link>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="overflow-y-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                <NavMain sections={sections} />
            </SidebarContent>
        </Sidebar>
    );
}
