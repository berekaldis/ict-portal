import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import React, { useState } from 'react';
import { usePermission } from '@/hooks/user-permissions';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    AlertTriangle,
    BarChart3,
    CheckCircle2,
    ClipboardCheck,
    Download,
    Edit2,
    Eye,
    HardDrive,
    Laptop,
    Plus,
    Printer,
    RotateCcw,
    Search,
    Send,
    Server,
    Smartphone,
    Check,
    Copy,
    ExternalLink,
    Trash2,
    XCircle,
    Video,
    ShieldAlert,
    AlertOctagon,
    Clock,
    PhoneCall,
    CheckSquare,
    FileText,
    RefreshCw,
    Radio,
} from 'lucide-react';

interface Report {
    id: number;
    report_uuid: string;
    branch_name: string;
    technician_name: string;
    branch_manager: string;
    type: 'hardware' | 'software';
    health_score: number;
    health_status: 'Good' | 'Warning' | 'Critical';
    critical_count: number;
    total_items: number;
    passed_items: number;
    failed_items: number;
    skipped_items: number;
    issues_summary: string | null;
    submitted_at: string;
}

interface Asset {
    id: number;
    branch_name: string;
    asset_tag: string | null;
    serial_number: string | null;
    asset_type: string;
    brand: string | null;
    model: string | null;
    specifications: string | null;
    status: 'operational' | 'maintenance' | 'repair' | 'faulty' | 'missing' | 'retired';
    assigned_to: string | null;
    location: string | null;
    ip_address: string | null;
    notes: string | null;
}

interface ChecklistItem {
    id: number;
    question: string;
    help_text: string | null;
}

interface Category {
    id: number;
    name: string;
    icon: string;
    type: 'hardware' | 'software';
    has_photo: boolean;
    description: string | null;
    items: ChecklistItem[];
}

interface Stats {
    total_reports: number;
    good_count: number;
    warning_count: number;
    critical_count: number;
    avg_score: number;
    total_assets: number;
    operational_assets: number;
    maintenance_assets: number;
    faulty_assets: number;
    missing_assets: number;
}

export interface CctvRegister {
    id: number;
    branch_no: number;
    branch_name: string;
    nvr_model_ip: string;
    camera_count: number;
    hdd_size_days: string;
    firmware: string;
    last_check: string | null;
    status: 'operational' | 'warning' | 'critical' | 'maintenance';
    issues: string | null;
    notes?: string | null;
}

export interface CctvIncident {
    id: number;
    incident_number: string;
    incident_date: string;
    branch_name: string;
    device_name: string;
    issue_type: string;
    severity: 'critical' | 'major' | 'minor';
    impact: string | null;
    first_check_done: string | null;
    reported_by: string;
    reported_at: string;
    fixed_at: string | null;
    status: 'open' | 'investigating' | 'in_progress' | 'resolved';
    action_taken: string | null;
    telegram_announced_at?: string | null;
}

export const CCTV_CHECKLIST_SECTIONS = [
    {
        key: 'one_time',
        title: '1. One-time setup (verify per branch)',
        badge: 'Setup / Hardening',
        description: 'Verify one-time security, networking and baseline storage configuration per branch.',
        items: [
            'Default passwords changed on NVR and all cameras (unique strong password per branch)',
            'Admin accounts limited; one account per person; ex-staff removed',
            'P2P/Cloud and UPnP off unless needed; remote access via VPN only (no port-forwarding)',
            'CCTV on separate VLAN/network from office PCs and guest Wi-Fi',
            'NTP set, time zone GMT+3 (Addis Ababa), correct date/time',
            'Surveillance-grade HDDs installed (WD Purple / Seagate SkyHawk)',
            'Recording mode set (continuous or motion) and retention target set (e.g. 30 days)',
            'Auto-maintenance/reboot time set (off-hours)',
            'UPS connected to NVR, PoE switch and router',
            'Branch register filled: NVR model/IP, camera count, HDD size, firmware, serial numbers',
            'NVR config backup exported and stored',
        ],
    },
    {
        key: 'daily',
        title: '2. Daily (5 min – Helpdesk, remote)',
        badge: 'Helpdesk Remote',
        description: 'Quick 5-minute morning verification via SmartPSS / DMSS / NVR web portal.',
        items: [
            'All cameras online (SmartPSS / DMSS / NVR web)',
            'NVR recording on all channels',
            'NVR date/time correct',
            'No HDD error or disk-full alert',
        ],
    },
    {
        key: 'weekly',
        title: '3. Weekly (15 min)',
        badge: 'Weekly Audit',
        description: '15-minute operational check of recording coverage, storage retention and logs.',
        items: [
            'Playback test: 1 random camera per branch from yesterday (day and night)',
            'Recording days available meet the target',
            'System log reviewed (reboots, disconnects, failed logins)',
            'UPS on; no unexpected NVR reboot from power cuts',
        ],
    },
    {
        key: 'monthly',
        title: '4. Monthly (Hardware Technician, on-site)',
        badge: 'On-Site Inspection',
        description: 'Physical maintenance, optics cleaning, UPS testing and technician review.',
        items: [
            'HDD health (SMART), model, power-on hours',
            'Clean lenses/domes; check IR at night',
            'Camera angle, mounting, cables and connectors',
            'PoE switch and NVR ventilation (dust, heat)',
            'UPS battery and runtime test',
            'User accounts reviewed',
        ],
    },
    {
        key: 'quarterly',
        title: '5. Quarterly',
        badge: 'Quarterly Lifecycle',
        description: 'Firmware upgrades, backup drills, and proactive drive lifecycle maintenance.',
        items: [
            'Firmware update (official Dahua source only; test on one branch first)',
            'NVR config backup exported',
            'Footage export to USB drill',
            'Replace HDDs older than ~3 years or with bad sectors',
            'Review repeated incidents; decide on hardware replacement',
        ],
    },
];

interface Props {
    tab?: string;
    reports: {
        data: Report[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    assets: {
        data: Asset[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    categories: Category[];
    cctvRegisters?: CctvRegister[];
    cctvIncidents?: CctvIncident[];
    branches: string[];
    assetTypes: string[];
    stats: Stats;
    filters: {
        search?: string;
        branch?: string;
        type?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
        asset_search?: string;
        asset_branch?: string;
        asset_status?: string;
        asset_type?: string;
        tab?: string;
    };
}

export default function ItSurveyIndex({
    tab = 'reports',
    reports,
    assets,
    categories,
    cctvRegisters = [],
    cctvIncidents = [],
    branches,
    assetTypes,
    stats,
    filters,
}: Props) {
    const { can } = usePermission();
    const [currentTab, setCurrentTab] = useState(filters.tab || tab || 'reports');

    // CCTV 5-Branch Setup
    const CCTV_BRANCHES = [
        'Bole Medhanialem (Flagship)',
        'Kazanchis Branch',
        'Sarbet Branch',
        'Piassa Branch',
        'CMC Branch',
    ];
    const [cctvActiveBranch, setCctvActiveBranch] = useState<string>(CCTV_BRANCHES[0]);
    const [cctvCheckedItems, setCctvCheckedItems] = useState<Record<string, boolean>>(() => {
        try {
            const saved = localStorage.getItem('kaldis_cctv_checklist_state_v1');
            return saved ? JSON.parse(saved) : {};
        } catch {
            return {};
        }
    });

    const toggleCctvCheck = (branch: string, sectionKey: string, index: number) => {
        const key = `${branch}::${sectionKey}::${index}`;
        setCctvCheckedItems((prev) => {
            const updated = { ...prev, [key]: !prev[key] };
            try {
                localStorage.setItem('kaldis_cctv_checklist_state_v1', JSON.stringify(updated));
            } catch {}
            return updated;
        });
    };

    const resetBranchChecklist = (branch: string) => {
        if (confirm(`Reset all CCTV checklist progress for "${branch}"?`)) {
            setCctvCheckedItems((prev) => {
                const updated = { ...prev };
                Object.keys(updated).forEach((k) => {
                    if (k.startsWith(`${branch}::`)) {
                        delete updated[k];
                    }
                });
                try {
                    localStorage.setItem('kaldis_cctv_checklist_state_v1', JSON.stringify(updated));
                } catch {}
                return updated;
            });
        }
    };

    const [copiedSnippet, setCopiedSnippet] = useState<string | null>(null);
    const copySnippet = (text: string, id: string) => {
        navigator.clipboard.writeText(text);
        setCopiedSnippet(id);
        setTimeout(() => setCopiedSnippet(null), 2500);
    };

    // CCTV Branch Register State & Modal
    const [isEditRegisterOpen, setIsEditRegisterOpen] = useState(false);
    const [registerForm, setRegisterForm] = useState<Partial<CctvRegister>>({
        branch_no: 1,
        branch_name: CCTV_BRANCHES[0],
        nvr_model_ip: 'Dahua NVR5216-4KS2 / 192.168.10.200',
        camera_count: 16,
        hdd_size_days: '2x 4TB WD Purple / 30 days',
        firmware: 'V4.002.0000000.1.R',
        last_check: new Date().toISOString().split('T')[0],
        status: 'operational',
        issues: '',
    });

    const openEditRegisterModal = (reg: CctvRegister) => {
        setRegisterForm({ ...reg });
        setIsEditRegisterOpen(true);
    };

    const handleSaveRegister = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/it-surveys/cctv/register', registerForm, {
            preserveScroll: true,
            onSuccess: () => setIsEditRegisterOpen(false),
        });
    };

    // CCTV Incident Log State & Modal
    const [isLogIncidentOpen, setIsLogIncidentOpen] = useState(false);
    const [incidentForm, setIncidentForm] = useState({
        branch_name: CCTV_BRANCHES[0],
        device_name: '',
        issue_type: 'Offline',
        severity: 'major' as 'critical' | 'major' | 'minor',
        impact: '',
        first_check_done: 'Power / Cable / PoE / Network link verified',
        reported_by: '',
        reported_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        status: 'open' as 'open' | 'investigating' | 'in_progress' | 'resolved',
        action_taken: '',
        announce_to_telegram: true,
    });

    const handleSaveIncident = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/it-surveys/cctv/incidents', incidentForm, {
            preserveScroll: true,
            onSuccess: () => {
                setIsLogIncidentOpen(false);
                setIncidentForm({
                    branch_name: CCTV_BRANCHES[0],
                    device_name: '',
                    issue_type: 'Offline',
                    severity: 'major',
                    impact: '',
                    first_check_done: 'Power / Cable / PoE / Network link verified',
                    reported_by: '',
                    reported_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                    status: 'open',
                    action_taken: '',
                    announce_to_telegram: true,
                });
            },
        });
    };

    // Resolve CCTV Incident State & Modal
    const [isResolveModalOpen, setIsResolveModalOpen] = useState(false);
    const [resolvingIncident, setResolvingIncident] = useState<CctvIncident | null>(null);
    const [resolveForm, setResolveForm] = useState({
        status: 'resolved' as 'open' | 'investigating' | 'in_progress' | 'resolved',
        action_taken: '',
        fixed_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        announce_resolution: true,
    });

    const openResolveModal = (incident: CctvIncident) => {
        setResolvingIncident(incident);
        setResolveForm({
            status: 'resolved',
            action_taken: incident.action_taken || 'Technician confirmed fix with playback test.',
            fixed_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            announce_resolution: true,
        });
        setIsResolveModalOpen(true);
    };

    const handleSaveResolve = (e: React.FormEvent) => {
        e.preventDefault();
        if (!resolvingIncident) return;
        router.put(`/it-surveys/cctv/incidents/${resolvingIncident.id}`, resolveForm, {
            preserveScroll: true,
            onSuccess: () => {
                setIsResolveModalOpen(false);
                setResolvingIncident(null);
            },
        });
    };

    const [deletingCctvIncident, setDeletingCctvIncident] = useState<CctvIncident | null>(null);
    const handleDeleteCctvIncident = () => {
        if (!deletingCctvIncident) return;
        router.delete(`/it-surveys/cctv/incidents/${deletingCctvIncident.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeletingCctvIncident(null),
        });
    };

    const [cctvIncidentFilter, setCctvIncidentFilter] = useState<string>('all');

    const getCctvSeverityBadge = (severity: string) => {
        switch (severity) {
            case 'critical':
                return (
                    <Badge variant="outline" className="border-rose-400 text-rose-700 bg-rose-50 font-bold gap-1">
                        🔴 Critical (Immediate)
                    </Badge>
                );
            case 'major':
                return (
                    <Badge variant="outline" className="border-amber-400 text-amber-700 bg-amber-50 font-semibold gap-1">
                        🟠 Major (&le; 1h)
                    </Badge>
                );
            case 'minor':
                return (
                    <Badge variant="outline" className="border-yellow-400 text-yellow-800 bg-yellow-50 font-medium gap-1">
                        🟡 Minor (Daily)
                    </Badge>
                );
            default:
                return <Badge variant="outline">{severity}</Badge>;
        }
    };

    const getCctvStatusBadge = (status: string) => {
        switch (status) {
            case 'resolved':
                return <Badge variant="outline" className="border-emerald-300 text-emerald-700 bg-emerald-50">✅ Resolved</Badge>;
            case 'investigating':
                return <Badge variant="outline" className="border-sky-300 text-sky-700 bg-sky-50">🔍 Investigating</Badge>;
            case 'in_progress':
                return <Badge variant="outline" className="border-indigo-300 text-indigo-700 bg-indigo-50">⚙️ In Progress</Badge>;
            case 'open':
            default:
                return <Badge variant="outline" className="border-rose-300 text-rose-700 bg-rose-50 font-semibold">🚨 Open</Badge>;
        }
    };

    // Report filters state
    const [reportSearch, setReportSearch] = useState(filters.search || '');
    const [reportBranch, setReportBranch] = useState(filters.branch || 'all');
    const [reportType, setReportType] = useState(filters.type || 'all');
    const [reportStatus, setReportStatus] = useState(filters.status || 'all');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    // Hardware filters state
    const [assetSearch, setAssetSearch] = useState(filters.asset_search || '');
    const [assetBranch, setAssetBranch] = useState(filters.asset_branch || 'all');
    const [assetStatus, setAssetStatus] = useState(filters.asset_status || 'all');
    const [assetTypeFilter, setAssetTypeFilter] = useState(filters.asset_type || 'all');

    // Delete Report Modal
    const [deletingReport, setDeletingReport] = useState<Report | null>(null);

    // Telegram Mini App Modal
    const [telegramModalOpen, setTelegramModalOpen] = useState(false);
    const [copiedLink, setCopiedLink] = useState(false);

    // Delete Asset Modal
    const [deletingAsset, setDeletingAsset] = useState<Asset | null>(null);

    // Asset Add/Edit Modal
    const [assetModalOpen, setAssetModalOpen] = useState(false);
    const [assetForm, setAssetForm] = useState<Partial<Asset>>({
        branch_name: branches[0] || 'Bole Medhanialem',
        asset_type: 'pos',
        status: 'operational',
        asset_tag: '',
        serial_number: '',
        brand: '',
        model: '',
        assigned_to: '',
        location: '',
        ip_address: '',
        notes: '',
    });

    const handleApplyReportFilters = () => {
        router.get('/it-surveys', {
            tab: 'reports',
            search: reportSearch || undefined,
            branch: reportBranch !== 'all' ? reportBranch : undefined,
            type: reportType !== 'all' ? reportType : undefined,
            status: reportStatus !== 'all' ? reportStatus : undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
        }, { preserveState: true });
    };

    const handleResetReportFilters = () => {
        setReportSearch('');
        setReportBranch('all');
        setReportType('all');
        setReportStatus('all');
        setDateFrom('');
        setDateTo('');
        router.get('/it-surveys', { tab: 'reports' });
    };

    const handleApplyAssetFilters = () => {
        router.get('/it-surveys', {
            tab: 'hardware',
            asset_search: assetSearch || undefined,
            asset_branch: assetBranch !== 'all' ? assetBranch : undefined,
            asset_status: assetStatus !== 'all' ? assetStatus : undefined,
            asset_type: assetTypeFilter !== 'all' ? assetTypeFilter : undefined,
        }, { preserveState: true });
    };

    const handleResetAssetFilters = () => {
        setAssetSearch('');
        setAssetBranch('all');
        setAssetStatus('all');
        setAssetTypeFilter('all');
        router.get('/it-surveys', { tab: 'hardware' });
    };

    const openAddAssetModal = () => {
        setAssetForm({
            branch_name: branches[0] || 'Bole Medhanialem',
            asset_type: 'pos',
            status: 'operational',
            asset_tag: '',
            serial_number: '',
            brand: '',
            model: '',
            assigned_to: '',
            location: '',
            ip_address: '',
            notes: '',
        });
        setAssetModalOpen(true);
    };

    const openEditAssetModal = (asset: Asset) => {
        setAssetForm({ ...asset });
        setAssetModalOpen(true);
    };

    const handleSaveAsset = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/it-surveys/hardware', assetForm as any, {
            preserveState: true,
            onSuccess: () => setAssetModalOpen(false),
        });
    };

    const handleDeleteReport = () => {
        if (!deletingReport) return;
        router.delete(`/it-surveys/${deletingReport.id}`, {
            preserveState: true,
            onSuccess: () => setDeletingReport(null),
        });
    };

    const handleDeleteAsset = () => {
        if (!deletingAsset) return;
        router.delete(`/it-surveys/hardware/${deletingAsset.id}`, {
            preserveState: true,
            onSuccess: () => setDeletingAsset(null),
        });
    };

    const getHealthBadge = (score: number, status: string) => {
        if (score >= 85 || status === 'Good') {
            return (
                <Badge variant="outline" className="border-emerald-300 text-emerald-700 bg-emerald-50/60 font-semibold gap-1">
                    <CheckCircle2 className="w-3 h-3 text-emerald-600" />
                    {score}% • Good
                </Badge>
            );
        }
        if (score >= 60 || status === 'Warning') {
            return (
                <Badge variant="outline" className="border-amber-300 text-amber-700 bg-amber-50/60 font-semibold gap-1">
                    <AlertTriangle className="w-3 h-3 text-amber-600" />
                    {score}% • Warning
                </Badge>
            );
        }
        return (
            <Badge variant="outline" className="border-rose-300 text-rose-700 bg-rose-50/60 font-semibold gap-1">
                <XCircle className="w-3 h-3 text-rose-600" />
                {score}% • Critical
            </Badge>
        );
    };

    const getAssetStatusBadge = (status: string) => {
        switch (status) {
            case 'operational':
                return <Badge variant="outline" className="border-emerald-300 text-emerald-700 bg-emerald-50/50">Operational</Badge>;
            case 'maintenance':
            case 'repair':
                return <Badge variant="outline" className="border-amber-300 text-amber-700 bg-amber-50/50">Maintenance</Badge>;
            case 'faulty':
                return <Badge variant="outline" className="border-rose-300 text-rose-700 bg-rose-50/50">Faulty</Badge>;
            case 'missing':
                return <Badge variant="outline" className="border-purple-300 text-purple-700 bg-purple-50/50">Missing</Badge>;
            case 'retired':
                return <Badge variant="outline" className="border-slate-300 text-slate-600 bg-slate-50">Retired</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'IT Survey & Audit', href: '/it-surveys' },
        ]}>
            <Head title="IT Branch Survey & Audit" />

            <div className="flex h-full flex-1 flex-col gap-5 p-4 md:p-6 max-w-7xl mx-auto w-full">
                {/* Header Nav */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-1 border-b border-slate-200">
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-slate-900">IT Branch Survey & Audit</h1>
                        <p className="text-xs text-muted-foreground mt-0.5">
                            Branch infrastructure inspections, POS & network health audits, and hardware assets.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <div className="flex items-center gap-1.5">
                            <a
                                href="/it-surveys/export/reports"
                                className="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition"
                                title="Export all audit reports to CSV"
                            >
                                <Download className="w-3.5 h-3.5 text-slate-500" />
                                Export Reports
                            </a>
                            <a
                                href="/it-surveys/export/hardware"
                                className="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition"
                                title="Export hardware inventory to CSV"
                            >
                                <Download className="w-3.5 h-3.5 text-slate-500" />
                                Export Assets
                            </a>
                        </div>

                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => setTelegramModalOpen(true)}
                            className="gap-1.5 h-8 text-xs text-sky-700 hover:text-sky-800 border-sky-200 bg-sky-50/50 hover:bg-sky-50"
                        >
                            <Send className="h-3.5 w-3.5 text-sky-600" />
                            Telegram Mini App
                        </Button>

                        {can('it_survey.manage') && (
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={openAddAssetModal}
                                className="gap-1.5 h-8 text-xs"
                            >
                                <Plus className="h-3.5 w-3.5" />
                                Add Hardware Asset
                            </Button>
                        )}

                        {can('it_survey.create') && (
                            <Link href="/it-surveys/create">
                                <Button size="sm" className="gap-1.5 h-8 text-xs">
                                    <Plus className="h-3.5 w-3.5" />
                                    New Inspection
                                </Button>
                            </Link>
                        )}
                    </div>
                </div>

                {/* KPI Metrics */}
                <div className="grid gap-4 grid-cols-2 lg:grid-cols-4">
                    <Card className="border shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between pb-1 space-y-0">
                            <CardTitle className="text-xs font-medium text-muted-foreground">Total Audits</CardTitle>
                            <ClipboardCheck className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_reports}</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                Avg Health Score: <span className="font-semibold text-slate-800">{stats.avg_score}%</span>
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between pb-1 space-y-0">
                            <CardTitle className="text-xs font-medium text-muted-foreground">Good Condition</CardTitle>
                            <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-emerald-700">{stats.good_count}</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                Score &ge; 85% passed
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between pb-1 space-y-0">
                            <CardTitle className="text-xs font-medium text-muted-foreground">Needs Attention</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-amber-600" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-amber-700">{stats.warning_count}</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                Score 60% – 84%
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between pb-1 space-y-0">
                            <CardTitle className="text-xs font-medium text-muted-foreground">Hardware Assets</CardTitle>
                            <HardDrive className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_assets}</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {stats.operational_assets} OK • {stats.faulty_assets + stats.maintenance_assets} issue/repair
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Tabs Container */}
                <Tabs value={currentTab} onValueChange={setCurrentTab} className="w-full space-y-4">
                    <TabsList className="bg-slate-100 p-1 rounded-lg">
                        <TabsTrigger value="reports" className="text-xs gap-1.5">
                            <BarChart3 className="w-3.5 h-3.5" />
                            Audit Reports ({reports.total})
                        </TabsTrigger>
                        <TabsTrigger value="hardware" className="text-xs gap-1.5">
                            <HardDrive className="w-3.5 h-3.5" />
                            Hardware Inventory ({assets.total})
                        </TabsTrigger>
                        <TabsTrigger value="standards" className="text-xs gap-1.5">
                            <ClipboardCheck className="w-3.5 h-3.5" />
                            Inspection Standards ({categories.length})
                        </TabsTrigger>
                        <TabsTrigger value="cctv" className="text-xs gap-1.5 font-medium text-slate-800">
                            <Video className="w-3.5 h-3.5 text-rose-600" />
                            CCTV Audit &amp; Checklist (5 Branches)
                        </TabsTrigger>
                    </TabsList>

                    {/* ==================================================== */}
                    {/* TAB 1: AUDIT REPORTS                                  */}
                    {/* ==================================================== */}
                    <TabsContent value="reports" className="space-y-4 m-0">
                        {/* Filter Bar */}
                        <Card className="border shadow-none p-3">
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2.5">
                                <div className="lg:col-span-2">
                                    <Input
                                        placeholder="Search branch, tech, manager, issues..."
                                        value={reportSearch}
                                        onChange={(e) => setReportSearch(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && handleApplyReportFilters()}
                                        className="h-8 text-xs"
                                    />
                                </div>

                                <div>
                                    <Select value={reportBranch} onValueChange={setReportBranch}>
                                        <SelectTrigger className="h-8 text-xs">
                                            <SelectValue placeholder="Branch" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Branches</SelectItem>
                                            {branches.map((b) => (
                                                <SelectItem key={b} value={b}>{b}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Select value={reportType} onValueChange={setReportType}>
                                        <SelectTrigger className="h-8 text-xs">
                                            <SelectValue placeholder="Type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Audit Types</SelectItem>
                                            <SelectItem value="hardware">Hardware Survey</SelectItem>
                                            <SelectItem value="software">Software Survey</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Select value={reportStatus} onValueChange={setReportStatus}>
                                        <SelectTrigger className="h-8 text-xs">
                                            <SelectValue placeholder="Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Statuses</SelectItem>
                                            <SelectItem value="Good">Good (&ge;85%)</SelectItem>
                                            <SelectItem value="Warning">Warning (60-84%)</SelectItem>
                                            <SelectItem value="Critical">Critical (&lt;60%)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex items-center gap-1.5">
                                    <Button
                                        size="sm"
                                        onClick={handleApplyReportFilters}
                                        className="h-8 text-xs flex-1"
                                    >
                                        <Search className="w-3.5 h-3.5 mr-1" /> Filter
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={handleResetReportFilters}
                                        className="h-8 text-xs px-2.5"
                                        title="Reset filters"
                                    >
                                        <RotateCcw className="w-3.5 h-3.5" />
                                    </Button>
                                </div>
                            </div>
                        </Card>

                        {/* Reports Table */}
                        <Card className="border shadow-none overflow-hidden">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-slate-50/75">
                                        <TableHead className="text-xs font-semibold">Branch</TableHead>
                                        <TableHead className="text-xs font-semibold">Audit Type</TableHead>
                                        <TableHead className="text-xs font-semibold">Health Score</TableHead>
                                        <TableHead className="text-xs font-semibold">Checklist Results</TableHead>
                                        <TableHead className="text-xs font-semibold">Technician / Manager</TableHead>
                                        <TableHead className="text-xs font-semibold">Inspection Date</TableHead>
                                        <TableHead className="text-xs font-semibold text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {reports.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={7} className="text-center py-10 text-muted-foreground text-xs">
                                                No branch audit reports found matching your criteria.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        reports.data.map((r) => (
                                            <TableRow key={r.id} className="hover:bg-slate-50/60">
                                                <TableCell>
                                                    <div className="font-medium text-slate-900 text-xs">{r.branch_name}</div>
                                                    <div className="text-[10px] text-muted-foreground font-mono truncate max-w-[150px]">
                                                        {r.report_uuid}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline" className={`capitalize text-[11px] ${
                                                        r.type === 'hardware' ? 'bg-indigo-50/50 text-indigo-700 border-indigo-200' : 'bg-cyan-50/50 text-cyan-700 border-cyan-200'
                                                    }`}>
                                                        {r.type}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {getHealthBadge(r.health_score, r.health_status)}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-1.5 text-xs">
                                                        <span className="text-emerald-700 font-medium">{r.passed_items} OK</span>
                                                        <span className="text-slate-300">•</span>
                                                        <span className={`font-medium ${r.failed_items > 0 ? 'text-rose-600' : 'text-slate-500'}`}>
                                                            {r.failed_items} Issues
                                                        </span>
                                                        {r.skipped_items > 0 && (
                                                            <>
                                                                <span className="text-slate-300">•</span>
                                                                <span className="text-slate-500">{r.skipped_items} Skip</span>
                                                            </>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="text-xs font-medium text-slate-800">{r.technician_name}</div>
                                                    <div className="text-[11px] text-muted-foreground">Mgr: {r.branch_manager}</div>
                                                </TableCell>
                                                <TableCell className="text-xs text-muted-foreground whitespace-nowrap">
                                                    {new Date(r.submitted_at).toLocaleDateString('en-US', {
                                                        month: 'short',
                                                        day: 'numeric',
                                                        year: 'numeric',
                                                    })}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Link href={`/it-surveys/${r.id}`}>
                                                            <Button size="sm" variant="ghost" className="h-7 px-2 text-xs text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50">
                                                                <Eye className="w-3.5 h-3.5 mr-1" /> View
                                                            </Button>
                                                        </Link>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            className="h-7 px-2 text-xs text-slate-600 hover:text-slate-900"
                                                            onClick={() => window.open(`/it-surveys/${r.id}?print=1`, '_blank')}
                                                            title="Print Report"
                                                        >
                                                            <Printer className="w-3.5 h-3.5" />
                                                        </Button>
                                                        {can('it_survey.manage') && (
                                                            <Button
                                                                size="sm"
                                                                variant="ghost"
                                                                className="h-7 px-2 text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                                                onClick={() => setDeletingReport(r)}
                                                                title="Delete Report"
                                                            >
                                                                <Trash2 className="w-3.5 h-3.5" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>

                            {/* Pagination */}
                            {reports.last_page > 1 && (
                                <div className="flex items-center justify-between p-3 border-t border-slate-100 text-xs text-muted-foreground">
                                    <div>Showing page {reports.current_page} of {reports.last_page} ({reports.total} total)</div>
                                    <div className="flex gap-1.5">
                                        {reports.prev_page_url && (
                                            <Link href={reports.prev_page_url}>
                                                <Button size="sm" variant="outline" className="h-7 text-xs">
                                                    Previous
                                                </Button>
                                            </Link>
                                        )}
                                        {reports.next_page_url && (
                                            <Link href={reports.next_page_url}>
                                                <Button size="sm" variant="outline" className="h-7 text-xs">
                                                    Next
                                                </Button>
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            )}
                        </Card>
                    </TabsContent>

                    {/* ==================================================== */}
                    {/* TAB 2: HARDWARE ASSETS INVENTORY                      */}
                    {/* ==================================================== */}
                    <TabsContent value="hardware" className="space-y-4 m-0">
                        {/* Filter Bar */}
                        <Card className="border shadow-none p-3">
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2.5">
                                <div className="lg:col-span-2">
                                    <Input
                                        placeholder="Search asset tag, serial, brand, model, IP..."
                                        value={assetSearch}
                                        onChange={(e) => setAssetSearch(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && handleApplyAssetFilters()}
                                        className="h-8 text-xs"
                                    />
                                </div>

                                <div>
                                    <Select value={assetBranch} onValueChange={setAssetBranch}>
                                        <SelectTrigger className="h-8 text-xs">
                                            <SelectValue placeholder="Branch" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Branches</SelectItem>
                                            {branches.map((b) => (
                                                <SelectItem key={b} value={b}>{b}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Select value={assetTypeFilter} onValueChange={setAssetTypeFilter}>
                                        <SelectTrigger className="h-8 text-xs">
                                            <SelectValue placeholder="Asset Type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Asset Types</SelectItem>
                                            {assetTypes.map((t) => (
                                                <SelectItem key={t} value={t}>{t.toUpperCase()}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <Select value={assetStatus} onValueChange={setAssetStatus}>
                                        <SelectTrigger className="h-8 text-xs">
                                            <SelectValue placeholder="Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Statuses</SelectItem>
                                            <SelectItem value="operational">Operational</SelectItem>
                                            <SelectItem value="maintenance">Maintenance</SelectItem>
                                            <SelectItem value="repair">Under Repair</SelectItem>
                                            <SelectItem value="faulty">Faulty</SelectItem>
                                            <SelectItem value="missing">Missing</SelectItem>
                                            <SelectItem value="retired">Retired</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex items-center gap-1.5">
                                    <Button
                                        size="sm"
                                        onClick={handleApplyAssetFilters}
                                        className="h-8 text-xs flex-1"
                                    >
                                        <Search className="w-3.5 h-3.5 mr-1" /> Filter
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={handleResetAssetFilters}
                                        className="h-8 text-xs px-2.5"
                                        title="Reset filters"
                                    >
                                        <RotateCcw className="w-3.5 h-3.5" />
                                    </Button>
                                </div>
                            </div>
                        </Card>

                        {/* Assets Table */}
                        <Card className="border shadow-none overflow-hidden">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-slate-50/75">
                                        <TableHead className="text-xs font-semibold">Asset Tag</TableHead>
                                        <TableHead className="text-xs font-semibold">Branch</TableHead>
                                        <TableHead className="text-xs font-semibold">Type</TableHead>
                                        <TableHead className="text-xs font-semibold">Brand / Model</TableHead>
                                        <TableHead className="text-xs font-semibold">Serial Number</TableHead>
                                        <TableHead className="text-xs font-semibold">Status</TableHead>
                                        <TableHead className="text-xs font-semibold">Assigned / IP</TableHead>
                                        <TableHead className="text-xs font-semibold text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {assets.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={8} className="text-center py-10 text-muted-foreground text-xs">
                                                No hardware assets found matching your criteria.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        assets.data.map((a) => (
                                            <TableRow key={a.id} className="hover:bg-slate-50/60">
                                                <TableCell>
                                                    <span className="font-mono font-medium text-xs text-slate-900">
                                                        {a.asset_tag || '—'}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-xs font-medium text-slate-800">
                                                    {a.branch_name}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline" className="uppercase text-[10px] tracking-wider font-semibold">
                                                        {a.asset_type}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-xs text-slate-700">
                                                    <div>{a.brand || '—'}</div>
                                                    <div className="text-[11px] text-muted-foreground">{a.model || ''}</div>
                                                </TableCell>
                                                <TableCell className="text-xs font-mono text-muted-foreground">
                                                    {a.serial_number || '—'}
                                                </TableCell>
                                                <TableCell>
                                                    {getAssetStatusBadge(a.status)}
                                                </TableCell>
                                                <TableCell className="text-xs text-muted-foreground">
                                                    <div>{a.assigned_to || '—'}</div>
                                                    {a.ip_address && <div className="text-[11px] font-mono text-slate-500">{a.ip_address}</div>}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        {can('it_survey.manage') && (
                                                            <>
                                                                <Button
                                                                    size="sm"
                                                                    variant="ghost"
                                                                    className="h-7 px-2 text-xs text-slate-700 hover:text-slate-900"
                                                                    onClick={() => openEditAssetModal(a)}
                                                                    title="Edit Asset"
                                                                >
                                                                    <Edit2 className="w-3.5 h-3.5" />
                                                                </Button>
                                                                <Button
                                                                    size="sm"
                                                                    variant="ghost"
                                                                    className="h-7 px-2 text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                                                    onClick={() => setDeletingAsset(a)}
                                                                    title="Delete Asset"
                                                                >
                                                                    <Trash2 className="w-3.5 h-3.5" />
                                                                </Button>
                                                            </>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>

                            {/* Pagination */}
                            {assets.last_page > 1 && (
                                <div className="flex items-center justify-between p-3 border-t border-slate-100 text-xs text-muted-foreground">
                                    <div>Showing page {assets.current_page} of {assets.last_page} ({assets.total} total)</div>
                                    <div className="flex gap-1.5">
                                        {assets.prev_page_url && (
                                            <Link href={assets.prev_page_url}>
                                                <Button size="sm" variant="outline" className="h-7 text-xs">
                                                    Previous
                                                </Button>
                                            </Link>
                                        )}
                                        {assets.next_page_url && (
                                            <Link href={assets.next_page_url}>
                                                <Button size="sm" variant="outline" className="h-7 text-xs">
                                                    Next
                                                </Button>
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            )}
                        </Card>
                    </TabsContent>

                    {/* ==================================================== */}
                    {/* TAB 3: INSPECTION STANDARDS                           */}
                    {/* ==================================================== */}
                    <TabsContent value="standards" className="space-y-4 m-0">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {categories.map((cat) => (
                                <Card key={cat.id} className="border shadow-none">
                                    <CardHeader className="pb-2">
                                        <div className="flex items-center justify-between">
                                            <CardTitle className="text-sm font-semibold text-slate-900 flex items-center gap-2">
                                                {cat.type === 'hardware' ? (
                                                    <HardDrive className="w-4 h-4 text-indigo-600" />
                                                ) : (
                                                    <Laptop className="w-4 h-4 text-cyan-600" />
                                                )}
                                                {cat.name}
                                            </CardTitle>
                                            <Badge variant="outline" className="capitalize text-[10px]">
                                                {cat.type}
                                            </Badge>
                                        </div>
                                        {cat.description && (
                                            <p className="text-xs text-muted-foreground mt-1">{cat.description}</p>
                                        )}
                                    </CardHeader>
                                    <CardContent>
                                        <div className="text-xs font-medium text-slate-500 mb-2">Checklist Verification Points:</div>
                                        <ul className="space-y-1.5 text-xs">
                                            {cat.items && cat.items.length > 0 ? (
                                                cat.items.map((item, idx) => (
                                                    <li key={item.id} className="flex items-start gap-2 text-slate-700">
                                                        <span className="w-4 h-4 rounded-full bg-slate-100 text-slate-500 text-[10px] flex items-center justify-center flex-shrink-0 mt-0.5">
                                                            {idx + 1}
                                                        </span>
                                                        <div>
                                                            <div>{item.question}</div>
                                                            {item.help_text && (
                                                                <div className="text-[11px] text-muted-foreground">{item.help_text}</div>
                                                            )}
                                                        </div>
                                                    </li>
                                                ))
                                            ) : (
                                                <li className="text-muted-foreground italic">No checklist items defined.</li>
                                            )}
                                        </ul>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </TabsContent>

                    {/* ==================================================== */}
                    {/* TAB 4: CCTV FULL CHECKLIST & 5-BRANCH AUDIT         */}
                    {/* ==================================================== */}
                    <TabsContent value="cctv" className="space-y-6 m-0">
                        {/* CCTV Scope & Header */}
                        <div className="rounded-xl border border-rose-200/80 bg-gradient-to-r from-rose-50/70 via-amber-50/40 to-slate-50 p-4 sm:p-5 shadow-sm">
                            <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2">
                                        <div className="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center shadow-sm">
                                            <Video className="w-4 h-4" />
                                        </div>
                                        <div>
                                            <h2 className="text-lg font-bold text-slate-900 tracking-tight">
                                                Kaldi's Coffee – CCTV Full Checklist
                                            </h2>
                                            <p className="text-xs font-medium text-slate-600">
                                                Scope: <span className="font-semibold text-rose-700">5 branches</span> · Dahua cameras &amp; NVRs · Owner: <span className="font-semibold text-slate-800">IT Manager</span> · Standard Date: 2026-11-21
                                            </p>
                                        </div>
                                    </div>
                                    <p className="text-xs text-slate-600 pl-10 max-w-3xl">
                                        Standard operating checklist covering one-time hardening, daily remote health, weekly playback tests, monthly on-site physical audits, quarterly lifecycle, and immediate incident escalation flow.
                                    </p>
                                </div>

                                <div className="flex flex-wrap items-center gap-2 pl-10 lg:pl-0">
                                    <Button
                                        size="sm"
                                        onClick={() => setIsLogIncidentOpen(true)}
                                        className="h-8 text-xs gap-1.5 bg-rose-600 hover:bg-rose-700 text-white shadow-sm"
                                    >
                                        <AlertOctagon className="w-3.5 h-3.5" />
                                        🚨 Log CCTV Incident
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => {
                                            const regEl = document.getElementById('cctv-branch-register-section');
                                            regEl?.scrollIntoView({ behavior: 'smooth' });
                                        }}
                                        className="h-8 text-xs gap-1.5 bg-white text-slate-700 border-slate-300 hover:bg-slate-50"
                                    >
                                        <HardDrive className="w-3.5 h-3.5 text-slate-600" />
                                        5-Branch Register
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => {
                                            const escEl = document.getElementById('cctv-escalation-sop-section');
                                            escEl?.scrollIntoView({ behavior: 'smooth' });
                                        }}
                                        className="h-8 text-xs gap-1.5 bg-white text-slate-700 border-slate-300 hover:bg-slate-50"
                                    >
                                        <PhoneCall className="w-3.5 h-3.5 text-rose-600" />
                                        Escalation SOP
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => resetBranchChecklist(cctvActiveBranch)}
                                        className="h-8 text-xs gap-1 text-slate-500 hover:text-slate-800"
                                        title="Reset checklist progress for the currently selected branch"
                                    >
                                        <RefreshCw className="w-3 h-3" />
                                        Reset Branch
                                    </Button>
                                </div>
                            </div>

                            {/* Branch Selector Switcher */}
                            <div className="mt-4 pt-3 border-t border-rose-200/60 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                <div className="flex items-center gap-1.5">
                                    <span className="text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                        Select Branch to Audit:
                                    </span>
                                </div>
                                <div className="flex flex-wrap items-center gap-1.5">
                                    {CCTV_BRANCHES.map((bName, idx) => {
                                        const isSelected = cctvActiveBranch === bName;
                                        // Count checked items for this branch
                                        const checkedCount = Object.keys(cctvCheckedItems).filter(
                                            (k) => k.startsWith(`${bName}::`) && cctvCheckedItems[k]
                                        ).length;

                                        return (
                                            <button
                                                key={bName}
                                                type="button"
                                                onClick={() => setCctvActiveBranch(bName)}
                                                className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition ${
                                                    isSelected
                                                        ? 'bg-rose-700 text-white shadow-sm ring-2 ring-rose-700/20'
                                                        : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100'
                                                }`}
                                            >
                                                <span className={`w-4 h-4 rounded-full text-[10px] flex items-center justify-center font-bold ${
                                                    isSelected ? 'bg-rose-800 text-white' : 'bg-slate-100 text-slate-600'
                                                }`}>
                                                    {idx + 1}
                                                </span>
                                                {bName}
                                                <span className={`text-[10px] px-1.5 py-0.2 rounded-full ${
                                                    isSelected ? 'bg-rose-900/60 text-rose-100' : 'bg-slate-100 text-slate-500'
                                                }`}>
                                                    {checkedCount}/30
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        </div>

                        {/* Interactive Verification Sections 1 to 5 */}
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h3 className="text-sm font-bold text-slate-800 flex items-center gap-2">
                                        <CheckSquare className="w-4 h-4 text-emerald-600" />
                                        Standard Verification Checklists for{' '}
                                        <span className="text-rose-700 underline underline-offset-4 font-extrabold">
                                            {cctvActiveBranch}
                                        </span>
                                    </h3>
                                    <p className="text-xs text-muted-foreground mt-0.5">
                                        Check off items as you perform checks. Progress persists in your local session.
                                    </p>
                                </div>
                                <div className="text-xs font-semibold text-slate-700 bg-white border border-slate-200 px-3 py-1 rounded-full shadow-sm">
                                    Overall Progress:{' '}
                                    <span className="text-emerald-700">
                                        {Object.keys(cctvCheckedItems).filter(
                                            (k) => k.startsWith(`${cctvActiveBranch}::`) && cctvCheckedItems[k]
                                        ).length}
                                    </span>
                                    /30 Items
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {CCTV_CHECKLIST_SECTIONS.map((sec) => {
                                    const checkedSectionCount = sec.items.filter((_, idx) =>
                                        cctvCheckedItems[`${cctvActiveBranch}::${sec.key}::${idx}`]
                                    ).length;
                                    const isComplete = checkedSectionCount === sec.items.length;

                                    return (
                                        <Card
                                            key={sec.key}
                                            className={`border shadow-sm flex flex-col justify-between transition ${
                                                isComplete ? 'border-emerald-300 bg-emerald-50/20' : 'border-slate-200 bg-white'
                                            }`}
                                        >
                                            <CardHeader className="p-4 pb-2">
                                                <div className="flex items-start justify-between gap-2">
                                                    <div>
                                                        <CardTitle className="text-xs font-bold text-slate-900 leading-snug">
                                                            {sec.title}
                                                        </CardTitle>
                                                        <p className="text-[11px] text-muted-foreground mt-1">
                                                            {sec.description}
                                                        </p>
                                                    </div>
                                                    <Badge
                                                        variant="outline"
                                                        className={`text-[10px] whitespace-nowrap flex-shrink-0 ${
                                                            isComplete
                                                                ? 'border-emerald-300 bg-emerald-50 text-emerald-700 font-bold'
                                                                : 'border-slate-200 text-slate-600'
                                                        }`}
                                                    >
                                                        {checkedSectionCount}/{sec.items.length} done
                                                    </Badge>
                                                </div>
                                            </CardHeader>

                                            <CardContent className="p-4 pt-1 space-y-2">
                                                <div className="divide-y divide-slate-100">
                                                    {sec.items.map((itemText, idx) => {
                                                        const isChecked = !!cctvCheckedItems[`${cctvActiveBranch}::${sec.key}::${idx}`];

                                                        return (
                                                            <div
                                                                key={idx}
                                                                onClick={() => toggleCctvCheck(cctvActiveBranch, sec.key, idx)}
                                                                className={`py-2 px-1 flex items-start gap-2.5 rounded cursor-pointer transition select-none hover:bg-slate-50 ${
                                                                    isChecked ? 'opacity-80 bg-emerald-50/40' : ''
                                                                }`}
                                                            >
                                                                <div
                                                                    className={`w-4 h-4 mt-0.5 rounded border flex items-center justify-center flex-shrink-0 transition ${
                                                                        isChecked
                                                                            ? 'bg-emerald-600 border-emerald-600 text-white'
                                                                            : 'border-slate-300 bg-white'
                                                                    }`}
                                                                >
                                                                    {isChecked && <Check className="w-3 h-3 stroke-[3]" />}
                                                                </div>
                                                                <span
                                                                    className={`text-xs leading-relaxed ${
                                                                        isChecked
                                                                            ? 'line-through text-slate-500 font-normal'
                                                                            : 'text-slate-800 font-medium'
                                                                    }`}
                                                                >
                                                                    {itemText}
                                                                </span>
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    );
                                })}
                            </div>
                        </div>

                        {/* SECTION 6: INCIDENT ESCALATION SOP */}
                        <div id="cctv-escalation-sop-section" className="space-y-4 pt-2">
                            <Card className="border border-rose-200 bg-white shadow-sm overflow-hidden">
                                <CardHeader className="bg-rose-50/60 border-b border-rose-100 p-4">
                                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div className="flex items-center gap-2">
                                            <div className="w-7 h-7 rounded-md bg-rose-600 text-white flex items-center justify-center">
                                                <PhoneCall className="w-3.5 h-3.5" />
                                            </div>
                                            <div>
                                                <CardTitle className="text-sm font-bold text-slate-900">
                                                    6. Incident Escalation Standard Operating Procedure (SOP)
                                                </CardTitle>
                                                <p className="text-xs text-rose-800 font-medium">
                                                    Escalation Flow: Technician / Helpdesk &rarr; IT Manager &rarr; Telegram Management Group
                                                </p>
                                            </div>
                                        </div>
                                        <Badge variant="outline" className="border-rose-300 text-rose-700 bg-white text-xs font-bold self-start sm:self-auto">
                                            Strict Protocol
                                        </Badge>
                                    </div>
                                </CardHeader>

                                <CardContent className="p-4 sm:p-5 space-y-6">
                                    {/* Escalation Visual Flow */}
                                    <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                                        <div className="border border-slate-200 rounded-lg p-3 bg-slate-50/70">
                                            <div className="text-[11px] font-bold text-rose-700 uppercase tracking-wider">Step 1 (Within 15m)</div>
                                            <div className="text-xs font-semibold text-slate-900 mt-1">Technician / Helpdesk</div>
                                            <p className="text-[11px] text-slate-600 mt-0.5">
                                                Discovers offline/malfunction. Calls or sends Telegram DM to IT Manager immediately.
                                            </p>
                                        </div>

                                        <div className="border border-slate-200 rounded-lg p-3 bg-slate-50/70">
                                            <div className="text-[11px] font-bold text-amber-700 uppercase tracking-wider">Step 2 (Triage)</div>
                                            <div className="text-xs font-semibold text-slate-900 mt-1">First Checks Protocol</div>
                                            <p className="text-[11px] text-slate-600 mt-0.5">
                                                Checks Power/UPS/PoE, cable link light, ping reachability, camera port reboot.
                                            </p>
                                        </div>

                                        <div className="border border-slate-200 rounded-lg p-3 bg-slate-50/70">
                                            <div className="text-[11px] font-bold text-indigo-700 uppercase tracking-wider">Step 3 (Announcement)</div>
                                            <div className="text-xs font-semibold text-slate-900 mt-1">IT Manager Broadcast</div>
                                            <p className="text-[11px] text-slate-600 mt-0.5">
                                                Classifies severity (🔴/🟠/🟡) and posts standard template to Telegram Management Group.
                                            </p>
                                        </div>

                                        <div className="border border-slate-200 rounded-lg p-3 bg-slate-50/70">
                                            <div className="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Step 4 (Resolution)</div>
                                            <div className="text-xs font-semibold text-slate-900 mt-1">Close the Loop</div>
                                            <p className="text-[11px] text-slate-600 mt-0.5">
                                                Tech confirms playback &rarr; IT Manager posts "✅ Resolved: [Branch] [Issue], [time]".
                                            </p>
                                        </div>
                                    </div>

                                    {/* Step 1 & Step 2 Details */}
                                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                        {/* Step 1 Report Template */}
                                        <div className="border border-slate-200 rounded-lg p-3.5 bg-slate-50/50 space-y-2">
                                            <div className="flex items-center justify-between">
                                                <span className="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                                    <span className="w-4 h-4 rounded-full bg-rose-100 text-rose-700 text-[10px] flex items-center justify-center font-bold">1</span>
                                                    Technician Report to IT Manager (Within 15 Min)
                                                </span>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => {
                                                        const tmpl = `🚨 CCTV ISSUE\nBranch: ${cctvActiveBranch}\nDevice: Camera name/# or NVR\nIssue: Offline / No recording / HDD error / Time wrong / Other\nFound at: ${new Date().toISOString().split('T')[0]} ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}\nImpact: e.g. cashier area not covered, all cameras down\nFirst check done: power / cable / PoE / network\nReported by: `;
                                                        copySnippet(tmpl, 'step1');
                                                    }}
                                                    className="h-7 text-[11px] gap-1 px-2"
                                                >
                                                    {copiedSnippet === 'step1' ? <Check className="w-3 h-3 text-emerald-600" /> : <Copy className="w-3 h-3" />}
                                                    {copiedSnippet === 'step1' ? 'Copied' : 'Copy Template'}
                                                </Button>
                                            </div>

                                            <pre className="text-[11px] font-mono bg-slate-900 text-rose-200 p-3 rounded-md overflow-x-auto leading-relaxed">
{`🚨 CCTV ISSUE
Branch: ${cctvActiveBranch}
Device: Camera name/# or NVR
Issue: Offline / No recording / HDD error / Time wrong / Other
Found at: ${new Date().toISOString().split('T')[0]} ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
Impact: (e.g. cashier area not covered, all cameras down)
First check done: (power / cable / PoE / network)
Reported by: `}
                                            </pre>
                                        </div>

                                        {/* Step 2 First Checks */}
                                        <div className="border border-slate-200 rounded-lg p-3.5 bg-slate-50/50 space-y-2.5">
                                            <span className="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                                <span className="w-4 h-4 rounded-full bg-amber-100 text-amber-700 text-[10px] flex items-center justify-center font-bold">2</span>
                                                Step 2 – First Checks Protocol
                                            </span>
                                            <p className="text-xs text-slate-600">
                                                Technician must verify the following physical and network checks before escalating replacement:
                                            </p>
                                            <ul className="space-y-1.5 text-xs text-slate-700">
                                                <li className="flex items-center gap-2">
                                                    <CheckCircle2 className="w-3.5 h-3.5 text-amber-600 flex-shrink-0" />
                                                    <strong>Power / UPS / PoE on?</strong> Verify UPS input, PoE switch power light and NVR power LED.
                                                </li>
                                                <li className="flex items-center gap-2">
                                                    <CheckCircle2 className="w-3.5 h-3.5 text-amber-600 flex-shrink-0" />
                                                    <strong>Cable and link light OK?</strong> Inspect RJ45 connector, patch panel link LED on switch port.
                                                </li>
                                                <li className="flex items-center gap-2">
                                                    <CheckCircle2 className="w-3.5 h-3.5 text-amber-600 flex-shrink-0" />
                                                    <strong>NVR reachable by ping?</strong> Ping IP address from local terminal / SmartPSS workstation.
                                                </li>
                                                <li className="flex items-center gap-2">
                                                    <CheckCircle2 className="w-3.5 h-3.5 text-amber-600 flex-shrink-0" />
                                                    <strong>Restart camera port:</strong> Power-cycle PoE port on switch or reboot camera if safe.
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    {/* Step 3: Classification Matrix & Telegram Management Group Template */}
                                    <div className="space-y-3">
                                        <div className="flex items-center justify-between">
                                            <span className="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                                <span className="w-4 h-4 rounded-full bg-indigo-100 text-indigo-700 text-[10px] flex items-center justify-center font-bold">3</span>
                                                Step 3 – IT Manager Classifies &amp; Announces
                                            </span>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => {
                                                    const tmpl = `📹 CCTV Update – ${cctvActiveBranch}\nStatus: 🔴/🟠/🟡\nIssue: \nArea not covered: \nCause (if known): \nAction / ETA: \nNext update: \n— IT Manager`;
                                                    copySnippet(tmpl, 'step3');
                                                }}
                                                className="h-7 text-[11px] gap-1 px-2"
                                            >
                                                {copiedSnippet === 'step3' ? <Check className="w-3 h-3 text-emerald-600" /> : <Copy className="w-3 h-3" />}
                                                {copiedSnippet === 'step3' ? 'Copied' : 'Copy Telegram Template'}
                                            </Button>
                                        </div>

                                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                            {/* Classification Matrix */}
                                            <div className="border border-slate-200 rounded-lg overflow-hidden">
                                                <Table>
                                                    <TableHeader className="bg-slate-50">
                                                        <TableRow>
                                                            <TableHead className="text-[11px] font-bold text-slate-700">Severity Level</TableHead>
                                                            <TableHead className="text-[11px] font-bold text-slate-700">Example Fault</TableHead>
                                                            <TableHead className="text-[11px] font-bold text-slate-700">Announcement Window</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        <TableRow className="bg-rose-50/30">
                                                            <TableCell className="text-xs font-bold text-rose-700 whitespace-nowrap">
                                                                🔴 Critical
                                                            </TableCell>
                                                            <TableCell className="text-xs text-slate-800">
                                                                NVR down, no recording, all cameras offline, HDD failure
                                                            </TableCell>
                                                            <TableCell className="text-xs font-bold text-rose-700 whitespace-nowrap">
                                                                Immediately
                                                            </TableCell>
                                                        </TableRow>
                                                        <TableRow className="bg-amber-50/30">
                                                            <TableCell className="text-xs font-bold text-amber-700 whitespace-nowrap">
                                                                🟠 Major
                                                            </TableCell>
                                                            <TableCell className="text-xs text-slate-800">
                                                                Key-area camera offline (cashier, store, entrance)
                                                            </TableCell>
                                                            <TableCell className="text-xs font-bold text-amber-700 whitespace-nowrap">
                                                                Within 1 hour
                                                            </TableCell>
                                                        </TableRow>
                                                        <TableRow className="bg-yellow-50/30">
                                                            <TableCell className="text-xs font-bold text-yellow-800 whitespace-nowrap">
                                                                🟡 Minor
                                                            </TableCell>
                                                            <TableCell className="text-xs text-slate-800">
                                                                One camera blurry or angle moved
                                                            </TableCell>
                                                            <TableCell className="text-xs font-semibold text-slate-600 whitespace-nowrap">
                                                                Daily summary
                                                            </TableCell>
                                                        </TableRow>
                                                    </TableBody>
                                                </Table>
                                            </div>

                                            {/* Telegram Management Group Template */}
                                            <div className="border border-sky-200 rounded-lg p-3 bg-sky-50/30 space-y-1.5">
                                                <div className="flex items-center justify-between">
                                                    <div className="text-[11px] font-bold text-sky-800 flex items-center gap-1">
                                                        <Send className="w-3 h-3 text-sky-600" />
                                                        Telegram Management Group Announcement Template
                                                    </div>
                                                </div>
                                                <pre className="text-[11px] font-mono bg-slate-900 text-sky-200 p-3 rounded-md overflow-x-auto leading-relaxed">
{`📹 CCTV Update – ${cctvActiveBranch}
Status: 🔴/🟠/🟡
Issue: 
Area not covered: 
Cause (if known): 
Action / ETA: 
Next update: 
— IT Manager`}
                                                </pre>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Step 4: Close the Loop & Operating Rules */}
                                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 pt-1">
                                        <div className="border border-emerald-200 bg-emerald-50/40 rounded-lg p-3.5 space-y-2">
                                            <div className="flex items-center justify-between">
                                                <span className="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                                                    <span className="w-4 h-4 rounded-full bg-emerald-200 text-emerald-800 text-[10px] flex items-center justify-center font-bold">4</span>
                                                    Step 4 – Close the Loop
                                                </span>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => {
                                                        const tmpl = `✅ Resolved: [${cctvActiveBranch}] [Issue], [${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}]`;
                                                        copySnippet(tmpl, 'step4');
                                                    }}
                                                    className="h-7 text-[11px] gap-1 px-2 border-emerald-300"
                                                >
                                                    {copiedSnippet === 'step4' ? <Check className="w-3 h-3 text-emerald-600" /> : <Copy className="w-3 h-3" />}
                                                    {copiedSnippet === 'step4' ? 'Copied' : 'Copy Format'}
                                                </Button>
                                            </div>
                                            <p className="text-xs text-emerald-800">
                                                Technician confirms fix with a <strong>playback test</strong> &rarr; IT Manager posts resolution announcement to the Management Group:
                                            </p>
                                            <div className="p-2.5 rounded bg-white border border-emerald-200 font-mono text-xs text-emerald-900 font-semibold">
                                                ✅ Resolved: [{cctvActiveBranch}] [Issue Description], [{new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}]
                                            </div>
                                        </div>

                                        <div className="border border-rose-200 bg-rose-50/30 rounded-lg p-3.5 space-y-2">
                                            <span className="text-xs font-bold text-rose-900 flex items-center gap-1.5">
                                                <ShieldAlert className="w-3.5 h-3.5 text-rose-600" />
                                                Mandatory CCTV Rules
                                            </span>
                                            <ul className="space-y-1 text-xs text-rose-950 font-medium">
                                                <li className="flex items-start gap-1.5">
                                                    <span className="text-rose-600 font-bold">•</span>
                                                    <span><strong>Technicians report only to the IT Manager</strong>, not directly to the Management Group.</span>
                                                </li>
                                                <li className="flex items-start gap-1.5">
                                                    <span className="text-rose-600 font-bold">•</span>
                                                    <span><strong>NVR not recording = Critical.</strong> Never leave a recording gap unannounced.</span>
                                                </li>
                                                <li className="flex items-start gap-1.5">
                                                    <span className="text-rose-600 font-bold">•</span>
                                                    <span><strong>Log every incident</strong> (date, branch, issue, reported time, fixed time).</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* SECTION 7: BRANCH REGISTER (5 BRANCHES) */}
                        <div id="cctv-branch-register-section" className="space-y-3 pt-2">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                        <HardDrive className="w-4 h-4 text-slate-700" />
                                        7. Branch Register (5 Branches Scope)
                                    </h3>
                                    <p className="text-xs text-muted-foreground mt-0.5">
                                        NVR hardware models, IP addresses, camera channel count, HDD capacity/retention, firmware version, and last health check.
                                    </p>
                                </div>
                            </div>

                            <Card className="border shadow-none overflow-hidden">
                                <Table>
                                    <TableHeader className="bg-slate-50">
                                        <TableRow>
                                            <TableHead className="w-12 text-center text-xs font-bold">#</TableHead>
                                            <TableHead className="text-xs font-bold">Branch</TableHead>
                                            <TableHead className="text-xs font-bold">NVR Model / IP</TableHead>
                                            <TableHead className="text-center text-xs font-bold"># Cameras</TableHead>
                                            <TableHead className="text-xs font-bold">HDD Size / Days Kept</TableHead>
                                            <TableHead className="text-xs font-bold">Firmware</TableHead>
                                            <TableHead className="text-xs font-bold">Last Check</TableHead>
                                            <TableHead className="text-xs font-bold">Status</TableHead>
                                            <TableHead className="text-xs font-bold">Issues / Remarks</TableHead>
                                            <TableHead className="text-right text-xs font-bold">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {cctvRegisters && cctvRegisters.length > 0 ? (
                                            cctvRegisters.map((reg) => (
                                                <TableRow key={reg.id} className="hover:bg-slate-50/70">
                                                    <TableCell className="text-center text-xs font-bold text-slate-500">
                                                        {reg.branch_no}
                                                    </TableCell>
                                                    <TableCell className="text-xs font-semibold text-slate-900 whitespace-nowrap">
                                                        {reg.branch_name}
                                                    </TableCell>
                                                    <TableCell className="text-xs font-mono text-slate-700 whitespace-nowrap">
                                                        {reg.nvr_model_ip}
                                                    </TableCell>
                                                    <TableCell className="text-center text-xs font-bold text-slate-800">
                                                        {reg.camera_count}
                                                    </TableCell>
                                                    <TableCell className="text-xs text-slate-700 whitespace-nowrap">
                                                        {reg.hdd_size_days}
                                                    </TableCell>
                                                    <TableCell className="text-xs font-mono text-slate-600">
                                                        {reg.firmware || '—'}
                                                    </TableCell>
                                                    <TableCell className="text-xs text-slate-600 whitespace-nowrap">
                                                        {reg.last_check || '—'}
                                                    </TableCell>
                                                    <TableCell className="text-xs">
                                                        {reg.status === 'operational' ? (
                                                            <Badge variant="outline" className="border-emerald-300 text-emerald-700 bg-emerald-50">
                                                                Operational
                                                            </Badge>
                                                        ) : reg.status === 'warning' ? (
                                                            <Badge variant="outline" className="border-amber-300 text-amber-700 bg-amber-50">
                                                                Warning
                                                            </Badge>
                                                        ) : (
                                                            <Badge variant="outline" className="border-rose-300 text-rose-700 bg-rose-50">
                                                                Critical
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-xs text-slate-600 max-w-xs truncate">
                                                        {reg.issues || 'None'}
                                                    </TableCell>
                                                    <TableCell className="text-right whitespace-nowrap">
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() => openEditRegisterModal(reg)}
                                                            className="h-7 text-xs px-2 gap-1 text-slate-700 hover:text-slate-900"
                                                        >
                                                            <Edit2 className="w-3 h-3" />
                                                            Edit
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={10} className="text-center py-6 text-xs text-muted-foreground italic">
                                                    No branch register data available.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </Card>
                        </div>

                        {/* SECTION 8: INCIDENT LOG */}
                        <div id="cctv-incident-log-section" className="space-y-3 pt-2">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                        <AlertTriangle className="w-4 h-4 text-rose-600" />
                                        8. CCTV Incident Log
                                    </h3>
                                    <p className="text-xs text-muted-foreground mt-0.5">
                                        Continuous record of CCTV offline events, camera faults, NVR recording issues, severity triage, and verified resolutions.
                                    </p>
                                </div>

                                <div className="flex items-center gap-2">
                                    <Select value={cctvIncidentFilter} onValueChange={setCctvIncidentFilter}>
                                        <SelectTrigger className="h-8 text-xs w-36">
                                            <SelectValue placeholder="Filter status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Incidents</SelectItem>
                                            <SelectItem value="open">Open / Active</SelectItem>
                                            <SelectItem value="resolved">Resolved</SelectItem>
                                            <SelectItem value="critical">Critical Severity</SelectItem>
                                        </SelectContent>
                                    </Select>

                                    <Button
                                        size="sm"
                                        onClick={() => setIsLogIncidentOpen(true)}
                                        className="h-8 text-xs gap-1.5 bg-rose-600 hover:bg-rose-700 text-white"
                                    >
                                        <Plus className="w-3.5 h-3.5" />
                                        Log Incident
                                    </Button>
                                </div>
                            </div>

                            <Card className="border shadow-none overflow-hidden">
                                <Table>
                                    <TableHeader className="bg-slate-50">
                                        <TableRow>
                                            <TableHead className="text-xs font-bold">Incident # / Date</TableHead>
                                            <TableHead className="text-xs font-bold">Branch &amp; Device</TableHead>
                                            <TableHead className="text-xs font-bold">Issue &amp; Severity</TableHead>
                                            <TableHead className="text-xs font-bold">Impact &amp; First Checks</TableHead>
                                            <TableHead className="text-xs font-bold">Reported By / Time</TableHead>
                                            <TableHead className="text-xs font-bold">Resolution &amp; Time</TableHead>
                                            <TableHead className="text-xs font-bold">Status</TableHead>
                                            <TableHead className="text-right text-xs font-bold">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {cctvIncidents && cctvIncidents.length > 0 ? (
                                            cctvIncidents
                                                .filter((inc) => {
                                                    if (cctvIncidentFilter === 'open') return inc.status !== 'resolved';
                                                    if (cctvIncidentFilter === 'resolved') return inc.status === 'resolved';
                                                    if (cctvIncidentFilter === 'critical') return inc.severity === 'critical';
                                                    return true;
                                                })
                                                .map((inc) => (
                                                    <TableRow key={inc.id} className="hover:bg-slate-50/70">
                                                        <TableCell className="text-xs whitespace-nowrap">
                                                            <div className="font-mono font-bold text-slate-800">{inc.incident_number}</div>
                                                            <div className="text-[11px] text-muted-foreground">{inc.incident_date}</div>
                                                        </TableCell>
                                                        <TableCell className="text-xs">
                                                            <div className="font-semibold text-slate-900">{inc.branch_name}</div>
                                                            <div className="text-[11px] text-slate-600 font-mono">{inc.device_name}</div>
                                                        </TableCell>
                                                        <TableCell className="text-xs">
                                                            <div className="font-medium text-slate-900">{inc.issue_type}</div>
                                                            <div className="mt-0.5">{getCctvSeverityBadge(inc.severity)}</div>
                                                        </TableCell>
                                                        <TableCell className="text-xs max-w-xs">
                                                            <div className="text-slate-800 font-medium truncate" title={inc.impact || ''}>
                                                                Area: {inc.impact || 'Not specified'}
                                                            </div>
                                                            <div className="text-[11px] text-slate-500 truncate" title={inc.first_check_done || ''}>
                                                                Check: {inc.first_check_done || 'None'}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="text-xs whitespace-nowrap">
                                                            <div className="text-slate-800 font-medium">{inc.reported_by}</div>
                                                            <div className="text-[11px] text-muted-foreground">{inc.reported_at}</div>
                                                        </TableCell>
                                                        <TableCell className="text-xs max-w-xs">
                                                            {inc.status === 'resolved' ? (
                                                                <div>
                                                                    <div className="text-emerald-700 font-medium text-[11px]">
                                                                        Fixed at {inc.fixed_at || 'Confirmed'}
                                                                    </div>
                                                                    <div className="text-[11px] text-slate-600 truncate" title={inc.action_taken || ''}>
                                                                        {inc.action_taken || 'Playback confirmed.'}
                                                                    </div>
                                                                </div>
                                                            ) : (
                                                                <span className="text-xs text-amber-700 italic">Pending resolution</span>
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="text-xs">
                                                            {getCctvStatusBadge(inc.status)}
                                                        </TableCell>
                                                        <TableCell className="text-right whitespace-nowrap space-x-1">
                                                            {inc.status !== 'resolved' ? (
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    onClick={() => openResolveModal(inc)}
                                                                    className="h-7 text-xs px-2 gap-1 border-emerald-300 text-emerald-700 hover:bg-emerald-50"
                                                                >
                                                                    <Check className="w-3 h-3" />
                                                                    Resolve
                                                                </Button>
                                                            ) : (
                                                                <Button
                                                                    size="sm"
                                                                    variant="ghost"
                                                                    onClick={() => openResolveModal(inc)}
                                                                    className="h-7 text-xs px-2 gap-1 text-slate-600"
                                                                >
                                                                    <Edit2 className="w-3 h-3" />
                                                                    Edit
                                                                </Button>
                                                            )}
                                                            {can('it_survey.delete') && (
                                                                <Button
                                                                    size="sm"
                                                                    variant="ghost"
                                                                    onClick={() => setDeletingCctvIncident(inc)}
                                                                    className="h-7 text-xs px-1.5 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                                                >
                                                                    <Trash2 className="w-3 h-3" />
                                                                </Button>
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={8} className="text-center py-6 text-xs text-muted-foreground italic">
                                                    No CCTV incidents logged yet.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                            </Card>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>

            {/* ======================================================== */}
            {/* MODAL: ADD / EDIT HARDWARE ASSET                         */}
            {/* ======================================================== */}
            <Dialog open={assetModalOpen} onOpenChange={setAssetModalOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base font-semibold">
                            {assetForm.id ? 'Edit Hardware Asset' : 'Register New Hardware Asset'}
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Add asset information for tracking branch hardware inventory.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSaveAsset} className="space-y-3 py-1">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs">Branch *</Label>
                                <Select
                                    value={assetForm.branch_name}
                                    onValueChange={(val) => setAssetForm((prev) => ({ ...prev, branch_name: val }))}
                                >
                                    <SelectTrigger className="h-8 text-xs">
                                        <SelectValue placeholder="Select Branch" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {branches.map((b) => (
                                            <SelectItem key={b} value={b}>{b}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Asset Type *</Label>
                                <Select
                                    value={assetForm.asset_type}
                                    onValueChange={(val) => setAssetForm((prev) => ({ ...prev, asset_type: val }))}
                                >
                                    <SelectTrigger className="h-8 text-xs">
                                        <SelectValue placeholder="Type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {assetTypes.map((t) => (
                                            <SelectItem key={t} value={t}>{t.toUpperCase()}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs">Asset Tag</Label>
                                <Input
                                    value={assetForm.asset_tag || ''}
                                    onChange={(e) => setAssetForm((prev) => ({ ...prev, asset_tag: e.target.value }))}
                                    placeholder="e.g. POS-BM-01"
                                    className="h-8 text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Serial Number</Label>
                                <Input
                                    value={assetForm.serial_number || ''}
                                    onChange={(e) => setAssetForm((prev) => ({ ...prev, serial_number: e.target.value }))}
                                    placeholder="e.g. SN9823423"
                                    className="h-8 text-xs"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs">Brand</Label>
                                <Input
                                    value={assetForm.brand || ''}
                                    onChange={(e) => setAssetForm((prev) => ({ ...prev, brand: e.target.value }))}
                                    placeholder="e.g. HP, Epson, Cisco"
                                    className="h-8 text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Model</Label>
                                <Input
                                    value={assetForm.model || ''}
                                    onChange={(e) => setAssetForm((prev) => ({ ...prev, model: e.target.value }))}
                                    placeholder="e.g. TM-T88VI"
                                    className="h-8 text-xs"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs">Operational Status *</Label>
                                <Select
                                    value={assetForm.status}
                                    onValueChange={(val: any) => setAssetForm((prev) => ({ ...prev, status: val }))}
                                >
                                    <SelectTrigger className="h-8 text-xs">
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="operational">Operational</SelectItem>
                                        <SelectItem value="maintenance">Maintenance</SelectItem>
                                        <SelectItem value="repair">Under Repair</SelectItem>
                                        <SelectItem value="faulty">Faulty</SelectItem>
                                        <SelectItem value="missing">Missing</SelectItem>
                                        <SelectItem value="retired">Retired</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">IP Address</Label>
                                <Input
                                    value={assetForm.ip_address || ''}
                                    onChange={(e) => setAssetForm((prev) => ({ ...prev, ip_address: e.target.value }))}
                                    placeholder="e.g. 192.168.1.105"
                                    className="h-8 text-xs"
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs">Assigned To / User</Label>
                            <Input
                                value={assetForm.assigned_to || ''}
                                onChange={(e) => setAssetForm((prev) => ({ ...prev, assigned_to: e.target.value }))}
                                placeholder="e.g. Cashier 1 / Branch Manager"
                                className="h-8 text-xs"
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs">Notes / Remarks</Label>
                            <Input
                                value={assetForm.notes || ''}
                                onChange={(e) => setAssetForm((prev) => ({ ...prev, notes: e.target.value }))}
                                placeholder="Additional details, issues, or location notes"
                                className="h-8 text-xs"
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setAssetModalOpen(false)}
                                className="h-8 text-xs"
                            >
                                Cancel
                            </Button>
                            <Button type="submit" size="sm" className="h-8 text-xs">
                                {assetForm.id ? 'Update Asset' : 'Register Asset'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ======================================================== */}
            {/* MODAL: DELETE REPORT CONFIRMATION                        */}
            {/* ======================================================== */}
            <Dialog open={!!deletingReport} onOpenChange={() => setDeletingReport(null)}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="text-base font-semibold text-rose-600">Delete Audit Report</DialogTitle>
                        <DialogDescription className="text-xs">
                            Are you sure you want to delete this inspection report for{' '}
                            <span className="font-semibold text-slate-800">{deletingReport?.branch_name}</span>? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setDeletingReport(null)}
                            className="h-8 text-xs"
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            onClick={handleDeleteReport}
                            className="h-8 text-xs"
                        >
                            Delete Report
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ======================================================== */}
            {/* MODAL: DELETE ASSET CONFIRMATION                         */}
            {/* ======================================================== */}
            <Dialog open={!!deletingAsset} onOpenChange={() => setDeletingAsset(null)}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="text-base font-semibold text-rose-600">Delete Hardware Asset</DialogTitle>
                        <DialogDescription className="text-xs">
                            Are you sure you want to remove asset{' '}
                            <span className="font-semibold text-slate-800">{deletingAsset?.asset_tag || deletingAsset?.serial_number || 'this asset'}</span> from the inventory?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setDeletingAsset(null)}
                            className="h-8 text-xs"
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            onClick={handleDeleteAsset}
                            className="h-8 text-xs"
                        >
                            Remove Asset
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            {/* ======================================================== */}
            {/* MODAL: TELEGRAM MINI APP INTEGRATION                     */}
            {/* ======================================================== */}
            <Dialog open={telegramModalOpen} onOpenChange={setTelegramModalOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <div className="flex items-center gap-2">
                            <div className="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center">
                                <Send className="w-4 h-4" />
                            </div>
                            <div>
                                <DialogTitle className="text-base font-semibold">Telegram Mini App</DialogTitle>
                                <DialogDescription className="text-xs">
                                    Open surveys directly inside Telegram on mobile or desktop without web login.
                                </DialogDescription>
                            </div>
                        </div>
                    </DialogHeader>

                    <div className="space-y-3 py-2">
                        {/* Primary Link */}
                        <div className="space-y-1">
                            <Label className="text-xs font-medium text-slate-700">Mini App Direct URL</Label>
                            <div className="flex items-center gap-1.5">
                                <Input
                                    readOnly
                                    value={typeof window !== 'undefined' ? `${window.location.origin}/it-surveys/mini-app` : '/it-surveys/mini-app'}
                                    className="h-8 text-xs font-mono bg-slate-50"
                                />
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => {
                                        const url = `${window.location.origin}/it-surveys/mini-app`;
                                        navigator.clipboard.writeText(url);
                                        setCopiedLink(true);
                                        setTimeout(() => setCopiedLink(false), 2000);
                                    }}
                                    className="h-8 text-xs px-2.5 gap-1"
                                >
                                    {copiedLink ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                    {copiedLink ? 'Copied' : 'Copy'}
                                </Button>
                            </div>
                        </div>

                        {/* Quick Launch Buttons */}
                        <div className="space-y-1.5">
                            <Label className="text-xs font-medium text-slate-700">Quick Test & Preview</Label>
                            <div className="grid grid-cols-2 gap-2">
                                <a
                                    href="/it-surveys/mini-app?type=hardware"
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-lg transition"
                                >
                                    <HardDrive className="w-3.5 h-3.5 text-indigo-600" />
                                    Hardware Audit
                                    <ExternalLink className="w-3 h-3 text-slate-400" />
                                </a>
                                <a
                                    href="/it-surveys/mini-app?type=software"
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-lg transition"
                                >
                                    <Laptop className="w-3.5 h-3.5 text-cyan-600" />
                                    Software Audit
                                    <ExternalLink className="w-3 h-3 text-slate-400" />
                                </a>
                            </div>
                        </div>

                        {/* Telegram Bot Instructions */}
                        <div className="rounded-lg bg-sky-50/70 border border-sky-100 p-2.5 text-xs text-sky-900 space-y-1">
                            <div className="font-semibold flex items-center gap-1 text-sky-800">
                                <Smartphone className="w-3.5 h-3.5" />
                                How to configure in Telegram:
                            </div>
                            <ol className="list-decimal list-inside space-y-0.5 text-[11px] text-sky-800/90">
                                <li>Send <code className="font-mono bg-sky-100 px-1 rounded">/setmenubutton</code> to <strong>@BotFather</strong>.</li>
                                <li>Select your bot and paste the Mini App URL above.</li>
                                <li>Technicians can also type <code className="font-mono bg-sky-100 px-1 rounded">/survey</code> in your bot to open the Web App button.</li>
                            </ol>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setTelegramModalOpen(false)}
                            className="h-8 text-xs"
                        >
                            Close
                        </Button>
                        <a
                            href="/it-surveys/mini-app"
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-1.5 h-8 px-3 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition"
                        >
                            Open Mini App
                            <ExternalLink className="w-3.5 h-3.5" />
                        </a>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ======================================================== */}
            {/* MODAL: EDIT CCTV BRANCH REGISTER                         */}
            {/* ======================================================== */}
            <Dialog open={isEditRegisterOpen} onOpenChange={setIsEditRegisterOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base font-semibold flex items-center gap-2">
                            <Video className="w-4 h-4 text-rose-600" />
                            Edit Branch CCTV Register
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Update NVR specifications, camera count, storage retention, and firmware for{' '}
                            <span className="font-semibold text-slate-800">{registerForm.branch_name}</span>.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSaveRegister} className="space-y-3 py-1">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs">Branch Name</Label>
                                <Input
                                    value={registerForm.branch_name || ''}
                                    onChange={(e) => setRegisterForm((p) => ({ ...p, branch_name: e.target.value }))}
                                    className="h-8 text-xs"
                                    required
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Camera Count</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    max="64"
                                    value={registerForm.camera_count || 0}
                                    onChange={(e) => setRegisterForm((p) => ({ ...p, camera_count: parseInt(e.target.value) || 0 }))}
                                    className="h-8 text-xs"
                                    required
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs">NVR Model / IP Address *</Label>
                            <Input
                                value={registerForm.nvr_model_ip || ''}
                                onChange={(e) => setRegisterForm((p) => ({ ...p, nvr_model_ip: e.target.value }))}
                                placeholder="e.g. Dahua NVR5216-4KS2 / 192.168.10.200"
                                className="h-8 text-xs font-mono"
                                required
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs">HDD Size / Days Kept *</Label>
                                <Input
                                    value={registerForm.hdd_size_days || ''}
                                    onChange={(e) => setRegisterForm((p) => ({ ...p, hdd_size_days: e.target.value }))}
                                    placeholder="e.g. 2x 4TB WD Purple / 30 days"
                                    className="h-8 text-xs"
                                    required
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Firmware Version</Label>
                                <Input
                                    value={registerForm.firmware || ''}
                                    onChange={(e) => setRegisterForm((p) => ({ ...p, firmware: e.target.value }))}
                                    placeholder="e.g. V4.002.0000000.1.R"
                                    className="h-8 text-xs font-mono"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs">Last Health Check</Label>
                                <Input
                                    type="date"
                                    value={registerForm.last_check || ''}
                                    onChange={(e) => setRegisterForm((p) => ({ ...p, last_check: e.target.value }))}
                                    className="h-8 text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Status</Label>
                                <Select
                                    value={registerForm.status || 'operational'}
                                    onValueChange={(val: any) => setRegisterForm((p) => ({ ...p, status: val }))}
                                >
                                    <SelectTrigger className="h-8 text-xs">
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="operational">Operational</SelectItem>
                                        <SelectItem value="warning">Warning / Issue</SelectItem>
                                        <SelectItem value="critical">Critical / Offline</SelectItem>
                                        <SelectItem value="maintenance">Maintenance</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs">Known Issues / Remarks</Label>
                            <Input
                                value={registerForm.issues || ''}
                                onChange={(e) => setRegisterForm((p) => ({ ...p, issues: e.target.value }))}
                                placeholder="e.g. All channels OK, or Camera #3 angle adjustment needed"
                                className="h-8 text-xs"
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setIsEditRegisterOpen(false)}
                                className="h-8 text-xs"
                            >
                                Cancel
                            </Button>
                            <Button type="submit" size="sm" className="h-8 text-xs bg-rose-600 hover:bg-rose-700 text-white">
                                Save Register
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ======================================================== */}
            {/* MODAL: LOG NEW CCTV INCIDENT                             */}
            {/* ======================================================== */}
            <Dialog open={isLogIncidentOpen} onOpenChange={setIsLogIncidentOpen}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="text-base font-semibold text-rose-600 flex items-center gap-2">
                            <AlertOctagon className="w-4 h-4" />
                            Log CCTV Incident (Escalation Step 1 &amp; 3)
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Record a CCTV malfunction, specify severity triage, and immediately prepare or broadcast the escalation announcement.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSaveIncident} className="space-y-3 py-1">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs">Branch *</Label>
                                <Select
                                    value={incidentForm.branch_name}
                                    onValueChange={(val) => setIncidentForm((p) => ({ ...p, branch_name: val }))}
                                >
                                    <SelectTrigger className="h-8 text-xs">
                                        <SelectValue placeholder="Branch" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {CCTV_BRANCHES.map((b) => (
                                            <SelectItem key={b} value={b}>{b}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Severity Level *</Label>
                                <Select
                                    value={incidentForm.severity}
                                    onValueChange={(val: any) => setIncidentForm((p) => ({ ...p, severity: val }))}
                                >
                                    <SelectTrigger className="h-8 text-xs">
                                        <SelectValue placeholder="Severity" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="critical">🔴 Critical (NVR down / HDD / All cams)</SelectItem>
                                        <SelectItem value="major">🟠 Major (Key area: Cashier/Store/Entrance)</SelectItem>
                                        <SelectItem value="minor">🟡 Minor (Angle moved / Blurry)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs">Device Name / Channel *</Label>
                                <Input
                                    value={incidentForm.device_name}
                                    onChange={(e) => setIncidentForm((p) => ({ ...p, device_name: e.target.value }))}
                                    placeholder="e.g. Camera #2 (Cashier) or NVR"
                                    className="h-8 text-xs"
                                    required
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Issue Type *</Label>
                                <Select
                                    value={incidentForm.issue_type}
                                    onValueChange={(val) => setIncidentForm((p) => ({ ...p, issue_type: val }))}
                                >
                                    <SelectTrigger className="h-8 text-xs">
                                        <SelectValue placeholder="Issue Type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Offline">Offline</SelectItem>
                                        <SelectItem value="No recording">No recording</SelectItem>
                                        <SelectItem value="HDD error">HDD error</SelectItem>
                                        <SelectItem value="Time wrong">Time wrong</SelectItem>
                                        <SelectItem value="Angle moved">Angle moved</SelectItem>
                                        <SelectItem value="Blurry / IR issue">Blurry / IR issue</SelectItem>
                                        <SelectItem value="Other">Other</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs">Impact / Area Not Covered *</Label>
                            <Input
                                value={incidentForm.impact}
                                onChange={(e) => setIncidentForm((p) => ({ ...p, impact: e.target.value }))}
                                placeholder="e.g. Cashier 1 transaction counter not covered, blind spot created"
                                className="h-8 text-xs"
                                required
                            />
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs">Step 2: First Check Done</Label>
                            <Input
                                value={incidentForm.first_check_done}
                                onChange={(e) => setIncidentForm((p) => ({ ...p, first_check_done: e.target.value }))}
                                placeholder="e.g. Power / PoE link light OK, NVR pingable, rebooted port"
                                className="h-8 text-xs"
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs">Reported By *</Label>
                                <Input
                                    value={incidentForm.reported_by}
                                    onChange={(e) => setIncidentForm((p) => ({ ...p, reported_by: e.target.value }))}
                                    placeholder="e.g. Technician / Helpdesk Name"
                                    className="h-8 text-xs"
                                    required
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Reported At Time</Label>
                                <Input
                                    value={incidentForm.reported_at}
                                    onChange={(e) => setIncidentForm((p) => ({ ...p, reported_at: e.target.value }))}
                                    placeholder="e.g. 10:15 AM"
                                    className="h-8 text-xs font-mono"
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs">Action Taken / Initial ETA</Label>
                            <Input
                                value={incidentForm.action_taken}
                                onChange={(e) => setIncidentForm((p) => ({ ...p, action_taken: e.target.value }))}
                                placeholder="e.g. Technician dispatched on-site / ETA 30 min"
                                className="h-8 text-xs"
                            />
                        </div>

                        <div className="p-2.5 rounded-lg bg-sky-50 border border-sky-100 flex items-center justify-between">
                            <div className="space-y-0.5">
                                <div className="text-xs font-semibold text-sky-900 flex items-center gap-1.5">
                                    <Send className="w-3.5 h-3.5 text-sky-600" />
                                    Telegram Broadcast
                                </div>
                                <div className="text-[11px] text-sky-700">
                                    Broadcast official announcement to Telegram Management Group now.
                                </div>
                            </div>
                            <input
                                type="checkbox"
                                checked={incidentForm.announce_to_telegram}
                                onChange={(e) => setIncidentForm((p) => ({ ...p, announce_to_telegram: e.target.checked }))}
                                className="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500 cursor-pointer"
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setIsLogIncidentOpen(false)}
                                className="h-8 text-xs"
                            >
                                Cancel
                            </Button>
                            <Button type="submit" size="sm" className="h-8 text-xs bg-rose-600 hover:bg-rose-700 text-white font-semibold">
                                Submit &amp; Log Incident
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ======================================================== */}
            {/* MODAL: RESOLVE CCTV INCIDENT                             */}
            {/* ======================================================== */}
            <Dialog open={isResolveModalOpen} onOpenChange={setIsResolveModalOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-base font-semibold text-emerald-700 flex items-center gap-2">
                            <CheckCircle2 className="w-4 h-4" />
                            Resolve CCTV Incident (Step 4 – Close the Loop)
                        </DialogTitle>
                        <DialogDescription className="text-xs">
                            Confirm playback test verification and publish resolution for{' '}
                            <span className="font-semibold text-slate-900">{resolvingIncident?.incident_number}</span>.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSaveResolve} className="space-y-3 py-1">
                        <div className="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-xs space-y-1">
                            <div><strong>Branch:</strong> {resolvingIncident?.branch_name}</div>
                            <div><strong>Device:</strong> {resolvingIncident?.device_name} ({resolvingIncident?.issue_type})</div>
                            <div><strong>Impact:</strong> {resolvingIncident?.impact}</div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label className="text-xs">Status</Label>
                                <Select
                                    value={resolveForm.status}
                                    onValueChange={(val: any) => setResolveForm((p) => ({ ...p, status: val }))}
                                >
                                    <SelectTrigger className="h-8 text-xs">
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="resolved">Resolved</SelectItem>
                                        <SelectItem value="investigating">Investigating</SelectItem>
                                        <SelectItem value="in_progress">In Progress</SelectItem>
                                        <SelectItem value="open">Open</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Fixed Time</Label>
                                <Input
                                    value={resolveForm.fixed_at}
                                    onChange={(e) => setResolveForm((p) => ({ ...p, fixed_at: e.target.value }))}
                                    placeholder="e.g. 11:30 AM"
                                    className="h-8 text-xs font-mono"
                                />
                            </div>
                        </div>

                        <div className="space-y-1">
                            <Label className="text-xs">Action Taken / Playback Test Details *</Label>
                            <Input
                                value={resolveForm.action_taken}
                                onChange={(e) => setResolveForm((p) => ({ ...p, action_taken: e.target.value }))}
                                placeholder="e.g. Technician reconnected PoE patch cord; playback test verified OK."
                                className="h-8 text-xs"
                                required
                            />
                        </div>

                        <div className="p-2.5 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-between">
                            <div className="space-y-0.5">
                                <div className="text-xs font-semibold text-emerald-900 flex items-center gap-1.5">
                                    <Send className="w-3.5 h-3.5 text-emerald-600" />
                                    Post "✅ Resolved" to Telegram
                                </div>
                                <div className="text-[11px] text-emerald-700">
                                    Announces loop closure to Telegram Management Group.
                                </div>
                            </div>
                            <input
                                type="checkbox"
                                checked={resolveForm.announce_resolution}
                                onChange={(e) => setResolveForm((p) => ({ ...p, announce_resolution: e.target.checked }))}
                                className="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setIsResolveModalOpen(false)}
                                className="h-8 text-xs"
                            >
                                Cancel
                            </Button>
                            <Button type="submit" size="sm" className="h-8 text-xs bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">
                                Complete &amp; Save Resolution
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* ======================================================== */}
            {/* MODAL: DELETE CCTV INCIDENT CONFIRMATION                 */}
            {/* ======================================================== */}
            <Dialog open={!!deletingCctvIncident} onOpenChange={() => setDeletingCctvIncident(null)}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="text-base font-semibold text-rose-600">Delete Incident Log</DialogTitle>
                        <DialogDescription className="text-xs">
                            Are you sure you want to delete incident{' '}
                            <span className="font-semibold text-slate-800">{deletingCctvIncident?.incident_number}</span>?
                            This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setDeletingCctvIncident(null)}
                            className="h-8 text-xs"
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            onClick={handleDeleteCctvIncident}
                            className="h-8 text-xs"
                        >
                            Delete Record
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
