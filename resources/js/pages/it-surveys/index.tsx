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
    branches,
    assetTypes,
    stats,
    filters,
}: Props) {
    const { can } = usePermission();
    const [currentTab, setCurrentTab] = useState(filters.tab || tab || 'reports');

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
        </AppLayout>
    );
}
