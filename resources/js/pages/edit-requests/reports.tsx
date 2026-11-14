import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowDown,
    ArrowLeft,
    ArrowUp,
    ArrowUpDown,
    BarChart3,
    Building2,
    Calendar,
    CheckCircle2,
    Clock,
    Download,
    Eye,
    FileSpreadsheet,
    FileText,
    Filter,
    Layers,
    ListOrdered,
    PieChart,
    Printer,
    RefreshCw,
    RotateCcw,
    Search,
    Send,
    Tag,
    Trophy,
    UserCheck,
    X,
} from 'lucide-react';
import React, { useMemo, useState } from 'react';

interface RequestItem {
    id: number;
    request_id: string;
    timestamp: string | null;
    timestamp_formatted: string;
    date_part: string;
    time_part: string;
    branch: string;
    user_name: string;
    store_user?: string | null;
    action_type: string;
    voucher_code?: string | null;
    voucher_type?: string | null;
    edit_type?: string | null;
    shift?: string | null;
    incorrect_date?: string | null;
    correct_date?: string | null;
    target_dept: string;
    reason: string;
    status: 'Pending' | 'Approved' | 'Rejected' | 'Done' | string;
    approver: string;
}

interface RankedBranch {
    rank: number;
    medal: string;
    name: string;
    count: number;
    percent: number;
    sharePercent: number;
    edit: number;
    void: number;
    on_hand: number;
}

interface DeptMetric {
    department: string;
    edit: number;
    void: number;
    on_hand: number;
    total: number;
}

interface StatusItem {
    key: string;
    label: string;
    count: number;
    percent: number;
    color: string;
}

interface Props {
    filters: {
        start_date?: string | null;
        end_date?: string | null;
        branch?: string;
        user_name?: string;
        target_dept?: string;
        action_type?: string;
        status?: string;
        view_mode?: 'both' | 'detail' | 'summary';
    };
    filterOptions: {
        branches: string[];
        departments: string[];
        voucherTypes: string[];
        editTypes: string[];
        users: string[];
    };
    summary: {
        totalRequests: number;
        avgResolutionTime: string;
        approvalRate: number;
        pending: number;
        approved: number;
        rejected: number;
        done: number;
    };
    statusDistribution: StatusItem[];
    rankedBranches: RankedBranch[];
    deptMetrics: DeptMetric[];
    requests: RequestItem[];
}

export default function EditRequestReports({
    filters: initialFilters,
    filterOptions,
    summary,
    statusDistribution,
    rankedBranches,
    deptMetrics,
    requests,
}: Props) {
    // Top Bar Filters State
    const [viewMode, setViewMode] = useState<'both' | 'detail' | 'summary'>(initialFilters.view_mode || 'both');
    const [startDate, setStartDate] = useState(initialFilters.start_date || '');
    const [endDate, setEndDate] = useState(initialFilters.end_date || '');
    const [branch, setBranch] = useState(initialFilters.branch || 'all');
    const [userName, setUserName] = useState(initialFilters.user_name || '');
    const [targetDept, setTargetDept] = useState(initialFilters.target_dept || 'all');
    const [actionType, setActionType] = useState(initialFilters.action_type || 'all');
    const [status, setStatus] = useState(initialFilters.status || 'all');

    // Detail Table Interactive Filters State
    const [detailSearch, setDetailSearch] = useState('');
    const [detailVoucherType, setDetailVoucherType] = useState('all');
    const [detailEditType, setDetailEditType] = useState('all');
    const [detailActionType, setDetailActionType] = useState('all');
    const [detailStatus, setDetailStatus] = useState('all');
    const [detailBranch, setDetailBranch] = useState('all');
    const [sortColumn, setSortColumn] = useState<keyof RequestItem>('timestamp');
    const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('desc');
    const [currentPage, setCurrentPage] = useState(1);
    const [pageSize, setPageSize] = useState<number | 'all'>(50);

    // Apply Backend Filters
    const applyBackendFilters = () => {
        router.get(
            route('edit-requests.reports'),
            {
                start_date: startDate || undefined,
                end_date: endDate || undefined,
                branch: branch !== 'all' ? branch : undefined,
                user_name: userName || undefined,
                target_dept: targetDept !== 'all' ? targetDept : undefined,
                action_type: actionType !== 'all' ? actionType : undefined,
                status: status !== 'all' ? status : undefined,
                view_mode: viewMode,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Reset Backend Filters
    const resetBackendFilters = () => {
        setStartDate('');
        setEndDate('');
        setBranch('all');
        setUserName('');
        setTargetDept('all');
        setActionType('all');
        setStatus('all');
        setViewMode('both');
        router.get(
            route('edit-requests.reports'),
            {},
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    // Trigger Telegram Report Dispatch
    const handleSendTelegram = (type: 'Weekly' | 'Monthly') => {
        router.post(
            route('edit-requests.reports.telegram'),
            { type },
            {
                preserveScroll: true,
            }
        );
    };

    // Export CSV with current filters
    const handleExportCsv = () => {
        const queryParams = new URLSearchParams();
        if (startDate) queryParams.set('start_date', startDate);
        if (endDate) queryParams.set('end_date', endDate);
        if (branch !== 'all') queryParams.set('branch', branch);
        if (userName) queryParams.set('user_name', userName);
        if (targetDept !== 'all') queryParams.set('target_dept', targetDept);
        if (actionType !== 'all') queryParams.set('action_type', actionType);
        if (status !== 'all') queryParams.set('status', status);

        window.location.href = `${route('edit-requests.reports.export-csv')}?${queryParams.toString()}`;
    };

    // Print Report
    const handlePrint = () => {
        window.print();
    };

    // Table Filtering & Sorting
    const filteredRequests = useMemo(() => {
        let result = [...requests];

        if (detailSearch.trim()) {
            const q = detailSearch.toLowerCase().trim();
            result = result.filter((r) => {
                const combined = [
                    r.request_id,
                    r.branch,
                    r.user_name,
                    r.store_user,
                    r.voucher_code,
                    r.voucher_type,
                    r.edit_type,
                    r.target_dept,
                    r.reason,
                    r.status,
                    r.approver,
                ]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase();
                return combined.includes(q);
            });
        }

        if (detailVoucherType !== 'all') {
            result = result.filter((r) => r.voucher_type === detailVoucherType);
        }
        if (detailEditType !== 'all') {
            result = result.filter((r) => r.edit_type === detailEditType);
        }
        if (detailActionType !== 'all') {
            result = result.filter((r) => r.action_type === detailActionType);
        }
        if (detailStatus !== 'all') {
            result = result.filter((r) => r.status === detailStatus);
        }
        if (detailBranch !== 'all') {
            result = result.filter((r) => r.branch === detailBranch);
        }

        // Sorting
        result.sort((a, b) => {
            const valA = a[sortColumn] ?? '';
            const valB = b[sortColumn] ?? '';

            if (sortColumn === 'timestamp') {
                const timeA = a.timestamp ? new Date(a.timestamp).getTime() : 0;
                const timeB = b.timestamp ? new Date(b.timestamp).getTime() : 0;
                return sortDirection === 'asc' ? timeA - timeB : timeB - timeA;
            }

            if (typeof valA === 'number' && typeof valB === 'number') {
                return sortDirection === 'asc' ? valA - valB : valB - valA;
            }

            const strA = String(valA).toLowerCase();
            const strB = String(valB).toLowerCase();
            if (strA < strB) return sortDirection === 'asc' ? -1 : 1;
            if (strA > strB) return sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        return result;
    }, [
        requests,
        detailSearch,
        detailVoucherType,
        detailEditType,
        detailActionType,
        detailStatus,
        detailBranch,
        sortColumn,
        sortDirection,
    ]);

    // Pagination calculations
    const totalRecords = filteredRequests.length;
    const itemsPerPage = pageSize === 'all' ? totalRecords : pageSize;
    const totalPages = Math.max(1, Math.ceil(totalRecords / (itemsPerPage || 1)));
    const safeCurrentPage = Math.min(Math.max(1, currentPage), totalPages);
    const startIndex = (safeCurrentPage - 1) * (pageSize === 'all' ? totalRecords : pageSize);
    const endIndex = pageSize === 'all' ? totalRecords : Math.min(startIndex + pageSize, totalRecords);
    const paginatedRequests = filteredRequests.slice(startIndex, endIndex);

    const handleSort = (column: keyof RequestItem) => {
        if (sortColumn === column) {
            setSortDirection((prev) => (prev === 'asc' ? 'desc' : 'asc'));
        } else {
            setSortColumn(column);
            setSortDirection(column === 'timestamp' || column === 'request_id' ? 'desc' : 'asc');
        }
    };

    const resetDetailFilters = () => {
        setDetailSearch('');
        setDetailVoucherType('all');
        setDetailEditType('all');
        setDetailActionType('all');
        setDetailStatus('all');
        setDetailBranch('all');
        setSortColumn('timestamp');
        setSortDirection('desc');
        setCurrentPage(1);
    };

    const statusBadgeClass = (s: string) => {
        switch (s) {
            case 'Pending':
                return 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border-amber-300';
            case 'Approved':
                return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border-emerald-300';
            case 'Done':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300 border-blue-300';
            case 'Rejected':
                return 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 border-rose-300';
            default:
                return 'bg-muted text-muted-foreground border-border';
        }
    };

    const actionBadgeClass = (act: string) => {
        switch (act) {
            case 'Edit':
                return 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 border-sky-200';
            case 'Void':
                return 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border-amber-200';
            case 'On Hand Delete':
                return 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border-purple-200';
            default:
                return 'bg-muted text-muted-foreground';
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Voucher Requests', href: '/edit-requests' },
                { title: 'Admin Reports & Analytics' },
            ]}
        >
            <Head title="Admin Reports & Analytics - Kaldis ICT" />

            <div className="space-y-6 max-w-7xl mx-auto pb-12 print:p-0 print:m-0 print:space-y-3">
                {/* ========================================================
                    PRINT-ONLY HEADER
                ======================================================== */}
                <div className="hidden print:block border-b-2 border-stone-800 pb-3 mb-4">
                    <div className="flex justify-between items-center">
                        <div>
                            <h1 className="text-2xl font-black tracking-tight text-stone-900">KALDIS COFFEE</h1>
                            <h2 className="text-xs font-bold uppercase tracking-wider text-stone-600">
                                Voucher & Operations Performance Report
                            </h2>
                        </div>
                        <div className="text-right text-[11px] text-stone-600">
                            <div>
                                <strong>Date Generated:</strong> {new Date().toLocaleString()}
                            </div>
                            <div>
                                <strong>Scope:</strong>{' '}
                                {[
                                    branch !== 'all' ? `Branch: ${branch}` : null,
                                    targetDept !== 'all' ? `Dept: ${targetDept}` : null,
                                    actionType !== 'all' ? `Action: ${actionType}` : null,
                                    status !== 'all' ? `Status: ${status}` : null,
                                    startDate || endDate ? `Date: ${startDate || 'Start'} to ${endDate || 'End'}` : null,
                                ]
                                    .filter(Boolean)
                                    .join(' | ') || 'All Requests'}
                            </div>
                        </div>
                    </div>
                </div>

                {/* ========================================================
                    TOP ACTION BAR (Screen Only)
                ======================================================== */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 print:hidden">
                    <div className="flex items-center gap-3">
                        <Button asChild variant="outline" size="icon" className="h-9 w-9 shrink-0">
                            <Link href={route('edit-requests.index')}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                                <BarChart3 className="h-6 w-6 text-primary" /> Admin Reports & Analytics
                            </h1>
                            <p className="text-xs text-muted-foreground">
                                High-level summaries, branch rankings, department breakdowns, and full detail records
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleSendTelegram('Weekly')}
                            className="text-xs font-semibold"
                        >
                            <Send className="h-3.5 w-3.5 mr-1.5 text-sky-600" /> Send Weekly Telegram
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleSendTelegram('Monthly')}
                            className="text-xs font-semibold"
                        >
                            <Send className="h-3.5 w-3.5 mr-1.5 text-indigo-600" /> Send Monthly Telegram
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleExportCsv}
                            className="text-xs font-semibold"
                        >
                            <Download className="h-3.5 w-3.5 mr-1.5 text-emerald-600" /> Export CSV
                        </Button>
                    </div>
                </div>

                {/* ========================================================
                    COMPREHENSIVE FILTER BAR (Screen Only)
                ======================================================== */}
                <Card className="border-border/60 shadow-sm print:hidden">
                    <CardHeader className="py-3 px-4 border-b border-border/40 bg-muted/20">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">
                                <Filter className="h-3.5 w-3.5 text-primary" /> Report Criteria & Filters
                            </div>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={resetBackendFilters}
                                    className="h-7 text-xs text-muted-foreground hover:text-foreground"
                                >
                                    <RotateCcw className="h-3 w-3 mr-1" /> Reset Filters
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={handlePrint}
                                    className="h-7 text-xs"
                                >
                                    <Printer className="h-3 w-3 mr-1" /> Print Report
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                            {/* View Mode */}
                            <div className="space-y-1">
                                <Label htmlFor="reportViewMode" className="text-xs font-semibold">
                                    Report Criteria Mode
                                </Label>
                                <select
                                    id="reportViewMode"
                                    value={viewMode}
                                    onChange={(e) => {
                                        const mode = e.target.value as 'both' | 'detail' | 'summary';
                                        setViewMode(mode);
                                    }}
                                    className="w-full flex h-9 rounded-md border border-input bg-background px-3 py-1.5 text-xs ring-offset-background font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"
                                >
                                    <option value="both">All (Summary + Detail View)</option>
                                    <option value="detail">Detail View Table (Full Records)</option>
                                    <option value="summary">Summary & Rankings Only</option>
                                </select>
                            </div>

                            {/* Date Start */}
                            <div className="space-y-1">
                                <Label htmlFor="reportStartDate" className="text-xs font-semibold">
                                    Date Range (Start)
                                </Label>
                                <Input
                                    id="reportStartDate"
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                    className="h-9 text-xs"
                                />
                            </div>

                            {/* Date End */}
                            <div className="space-y-1">
                                <Label htmlFor="reportEndDate" className="text-xs font-semibold">
                                    Date Range (End)
                                </Label>
                                <Input
                                    id="reportEndDate"
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                    className="h-9 text-xs"
                                />
                            </div>

                            {/* Branch */}
                            <div className="space-y-1">
                                <Label htmlFor="reportBranchFilter" className="text-xs font-semibold">
                                    Branch
                                </Label>
                                <select
                                    id="reportBranchFilter"
                                    value={branch}
                                    onChange={(e) => setBranch(e.target.value)}
                                    className="w-full flex h-9 rounded-md border border-input bg-background px-3 py-1.5 text-xs ring-offset-background font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"
                                >
                                    <option value="all">All Branches</option>
                                    {filterOptions.branches.map((b) => (
                                        <option key={b} value={b}>
                                            {b}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* User Filter */}
                            <div className="space-y-1">
                                <Label htmlFor="reportUserFilter" className="text-xs font-semibold">
                                    User Name
                                </Label>
                                <Input
                                    id="reportUserFilter"
                                    type="text"
                                    placeholder="Filter by user or requester..."
                                    list="reportUsersDatalist"
                                    value={userName}
                                    onChange={(e) => setUserName(e.target.value)}
                                    className="h-9 text-xs"
                                />
                                <datalist id="reportUsersDatalist">
                                    {filterOptions.users.map((u) => (
                                        <option key={u} value={u} />
                                    ))}
                                </datalist>
                            </div>

                            {/* Department */}
                            <div className="space-y-1">
                                <Label htmlFor="reportDeptFilter" className="text-xs font-semibold">
                                    Department / Store
                                </Label>
                                <select
                                    id="reportDeptFilter"
                                    value={targetDept}
                                    onChange={(e) => setTargetDept(e.target.value)}
                                    className="w-full flex h-9 rounded-md border border-input bg-background px-3 py-1.5 text-xs ring-offset-background font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"
                                >
                                    <option value="all">All Departments</option>
                                    {filterOptions.departments.map((d) => (
                                        <option key={d} value={d}>
                                            {d}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Action Type */}
                            <div className="space-y-1">
                                <Label htmlFor="reportActionFilter" className="text-xs font-semibold">
                                    Action Type
                                </Label>
                                <select
                                    id="reportActionFilter"
                                    value={actionType}
                                    onChange={(e) => setActionType(e.target.value)}
                                    className="w-full flex h-9 rounded-md border border-input bg-background px-3 py-1.5 text-xs ring-offset-background font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"
                                >
                                    <option value="all">All Actions</option>
                                    <option value="Edit">Edit</option>
                                    <option value="Void">Void</option>
                                    <option value="On Hand Delete">On Hand Delete</option>
                                </select>
                            </div>

                            {/* Status */}
                            <div className="space-y-1">
                                <Label htmlFor="reportStatusFilter" className="text-xs font-semibold">
                                    Status
                                </Label>
                                <select
                                    id="reportStatusFilter"
                                    value={status}
                                    onChange={(e) => setStatus(e.target.value)}
                                    className="w-full flex h-9 rounded-md border border-input bg-background px-3 py-1.5 text-xs ring-offset-background font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"
                                >
                                    <option value="all">All Statuses</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Approved">Approved</option>
                                    <option value="Rejected">Rejected</option>
                                    <option value="Done">Done</option>
                                </select>
                            </div>
                        </div>

                        <div className="mt-4 flex justify-end">
                            <Button
                                size="sm"
                                onClick={applyBackendFilters}
                                className="bg-primary hover:bg-primary/90 text-primary-foreground font-semibold px-4"
                            >
                                <Filter className="h-3.5 w-3.5 mr-1.5" /> Apply Scope Filters
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* ========================================================
                    SUMMARY KPIS (Shown in 'both' and 'summary' modes)
                ======================================================== */}
                {(viewMode === 'both' || viewMode === 'summary') && (
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-3.5">
                        <Card className="border-border/60 shadow-sm">
                            <CardContent className="p-4 flex items-center justify-between">
                                <div>
                                    <p className="text-xs font-medium text-muted-foreground">Total Requests</p>
                                    <h3 className="text-2xl font-black tracking-tight text-foreground mt-1">
                                        {summary.totalRequests}
                                    </h3>
                                </div>
                                <div className="h-10 w-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                    <FileText className="h-5 w-5" />
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-border/60 shadow-sm">
                            <CardContent className="p-4 flex items-center justify-between">
                                <div>
                                    <p className="text-xs font-medium text-muted-foreground">Avg. Resolution</p>
                                    <h3 className="text-2xl font-black tracking-tight text-foreground mt-1">
                                        {summary.avgResolutionTime}
                                    </h3>
                                </div>
                                <div className="h-10 w-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-500">
                                    <Clock className="h-5 w-5" />
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-border/60 shadow-sm">
                            <CardContent className="p-4 flex items-center justify-between">
                                <div>
                                    <p className="text-xs font-medium text-muted-foreground">Approval Rate</p>
                                    <h3 className="text-2xl font-black tracking-tight text-foreground mt-1">
                                        {summary.approvalRate}%
                                    </h3>
                                </div>
                                <div className="h-10 w-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-500">
                                    <CheckCircle2 className="h-5 w-5" />
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="border-border/60 shadow-sm">
                            <CardContent className="p-4 flex items-center justify-between">
                                <div>
                                    <p className="text-xs font-medium text-muted-foreground">Pending Review</p>
                                    <h3 className="text-2xl font-black tracking-tight text-amber-600 dark:text-amber-500 mt-1">
                                        {summary.pending}
                                    </h3>
                                </div>
                                <div className="h-10 w-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-500">
                                    <AlertCircle className="h-5 w-5" />
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* ========================================================
                    GROUPED ANALYTICS SECTION (Status Distribution, Branch Rankings & Dept Breakdown)
                ======================================================== */}
                {(viewMode === 'both' || viewMode === 'summary') && (
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        {/* 1. Status Distribution */}
                        <Card className="border-border/60 shadow-sm lg:col-span-1">
                            <CardHeader className="py-3 px-4 border-b border-border/40">
                                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                    <PieChart className="h-4 w-4 text-primary" /> Status Distribution
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3">
                                {statusDistribution.map((item) => (
                                    <div key={item.key} className="space-y-1.5">
                                        <div className="flex items-center justify-between text-xs font-medium">
                                            <span className={`px-2 py-0.5 rounded text-[11px] font-semibold border ${statusBadgeClass(item.key)}`}>
                                                {item.label}
                                            </span>
                                            <span className="font-mono text-muted-foreground">
                                                <strong>{item.count}</strong> ({item.percent}%)
                                            </span>
                                        </div>
                                        <div className="h-2 w-full rounded-full bg-muted overflow-hidden">
                                            <div
                                                className="h-full rounded-full transition-all duration-300"
                                                style={{
                                                    width: `${item.percent}%`,
                                                    backgroundColor: item.color,
                                                }}
                                            />
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>

                        {/* 2. Departmental / Action Metrics */}
                        <Card className="border-border/60 shadow-sm lg:col-span-2">
                            <CardHeader className="py-3 px-4 border-b border-border/40">
                                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                    <Building2 className="h-4 w-4 text-primary" /> Departmental & Action Metrics
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="overflow-x-auto">
                                    <table className="w-full text-xs">
                                        <thead className="bg-muted/30 text-muted-foreground font-semibold border-b border-border/40">
                                            <tr>
                                                <th className="py-2.5 px-4 text-left">Target Department</th>
                                                <th className="py-2.5 px-4 text-center">Edit</th>
                                                <th className="py-2.5 px-4 text-center">Void</th>
                                                <th className="py-2.5 px-4 text-center">On Hand Delete</th>
                                                <th className="py-2.5 px-4 text-right">Total Volume</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-border/30">
                                            {deptMetrics.length === 0 ? (
                                                <tr>
                                                    <td colSpan={5} className="py-6 text-center text-muted-foreground">
                                                        No departmental data matching active scope.
                                                    </td>
                                                </tr>
                                            ) : (
                                                deptMetrics.map((dept) => (
                                                    <tr key={dept.department} className="hover:bg-muted/20">
                                                        <td className="py-2.5 px-4 font-semibold text-foreground">
                                                            {dept.department}
                                                        </td>
                                                        <td className="py-2.5 px-4 text-center font-mono">
                                                            {dept.edit}
                                                        </td>
                                                        <td className="py-2.5 px-4 text-center font-mono">
                                                            {dept.void}
                                                        </td>
                                                        <td className="py-2.5 px-4 text-center font-mono">
                                                            {dept.on_hand}
                                                        </td>
                                                        <td className="py-2.5 px-4 text-right font-mono font-bold text-primary">
                                                            {dept.total}
                                                        </td>
                                                    </tr>
                                                ))
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>

                        {/* 3. Branch Request Rankings (Full Width) */}
                        <Card className="border-border/60 shadow-sm lg:col-span-3">
                            <CardHeader className="py-3 px-4 border-b border-border/40 flex flex-row items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Trophy className="h-4 w-4 text-amber-500" />
                                    <CardTitle className="text-sm font-semibold">
                                        Branch Request Rankings (Ranked by Request Count — Active Branches Only)
                                    </CardTitle>
                                </div>
                                <Badge variant="secondary" className="text-xs bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                                    {rankedBranches.length} Active Branches
                                </Badge>
                            </CardHeader>
                            <CardContent className="p-4 space-y-5">
                                {/* Visual cards for top active branches */}
                                {rankedBranches.length === 0 ? (
                                    <p className="text-xs text-muted-foreground text-center py-6">
                                        No branch requests recorded for the active scope filters.
                                    </p>
                                ) : (
                                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                                        {rankedBranches.slice(0, 12).map((rb) => (
                                            <div
                                                key={rb.name}
                                                className="p-3 rounded-lg border border-border/60 bg-muted/20 space-y-2 hover:border-primary/40 transition-colors"
                                            >
                                                <div className="flex items-center justify-between text-xs">
                                                    <span className="font-bold text-foreground flex items-center gap-1.5 truncate max-w-[170px]" title={rb.name}>
                                                        <span className="font-mono text-[11px] px-1.5 py-0.5 rounded bg-background border border-border font-semibold text-primary">
                                                            {rb.medal}
                                                        </span>
                                                        <span className="truncate">{rb.name}</span>
                                                    </span>
                                                    <span className="font-mono text-[11px] font-bold text-foreground/80 shrink-0">
                                                        {rb.count} req ({rb.sharePercent}%)
                                                    </span>
                                                </div>

                                                <div className="h-1.5 w-full rounded-full bg-muted overflow-hidden">
                                                    <div
                                                        className="h-full rounded-full bg-amber-500"
                                                        style={{ width: `${rb.percent}%` }}
                                                    />
                                                </div>

                                                <div className="flex items-center gap-1.5 text-[10px] text-muted-foreground pt-0.5">
                                                    <span className="px-1.5 py-0.5 rounded bg-sky-100 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 font-mono">
                                                        Edit: {rb.edit}
                                                    </span>
                                                    <span className="px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-mono">
                                                        Void: {rb.void}
                                                    </span>
                                                    <span className="px-1.5 py-0.5 rounded bg-purple-100 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 font-mono">
                                                        On Hand: {rb.on_hand}
                                                    </span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}

                                {/* Comprehensive Ranked Branches Table */}
                                <div className="border-t border-border/40 pt-4">
                                    <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-2 flex items-center gap-1.5">
                                        <ListOrdered className="h-3.5 w-3.5 text-primary" /> Full Ranked Branch Summary Table
                                    </h4>
                                    <div className="overflow-x-auto border border-border/40 rounded-lg max-h-72 overflow-y-auto">
                                        <table className="w-full text-xs">
                                            <thead className="bg-muted/40 text-muted-foreground font-semibold sticky top-0 border-b border-border/40">
                                                <tr>
                                                    <th className="py-2 px-3 text-left w-14">Rank</th>
                                                    <th className="py-2 px-3 text-left">Branch Name</th>
                                                    <th className="py-2 px-3 text-center">Total Requests</th>
                                                    <th className="py-2 px-3 text-center">Edit Requests</th>
                                                    <th className="py-2 px-3 text-center">Void Requests</th>
                                                    <th className="py-2 px-3 text-center">On Hand Deletes</th>
                                                    <th className="py-2 px-3 text-right">% Volume Share</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-border/30">
                                                {rankedBranches.map((rb) => (
                                                    <tr key={rb.name} className="hover:bg-muted/20">
                                                        <td className="py-2 px-3 font-mono font-bold text-foreground">
                                                            {rb.medal}
                                                        </td>
                                                        <td className="py-2 px-3 font-semibold text-foreground">
                                                            {rb.name}
                                                        </td>
                                                        <td className="py-2 px-3 text-center font-mono font-bold text-primary">
                                                            {rb.count}
                                                        </td>
                                                        <td className="py-2 px-3 text-center font-mono text-muted-foreground">
                                                            {rb.edit}
                                                        </td>
                                                        <td className="py-2 px-3 text-center font-mono text-muted-foreground">
                                                            {rb.void}
                                                        </td>
                                                        <td className="py-2 px-3 text-center font-mono text-muted-foreground">
                                                            {rb.on_hand}
                                                        </td>
                                                        <td className="py-2 px-3 text-right font-mono font-semibold text-foreground">
                                                            {rb.sharePercent}%
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* ========================================================
                    DETAIL VIEW TABLE (Full Records matching legacy)
                ======================================================== */}
                {(viewMode === 'both' || viewMode === 'detail') && (
                    <Card className="border-border/60 shadow-sm">
                        <CardHeader className="py-3 px-4 border-b border-border/40">
                            <div className="flex flex-col md:flex-row md:items-center justify-between gap-3">
                                <div className="flex items-center gap-2 flex-wrap">
                                    <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                        <Layers className="h-4 w-4 text-primary" /> Detail View Table (Full Records)
                                    </CardTitle>
                                    <Badge variant="secondary" className="text-xs">
                                        {totalRecords === requests.length
                                            ? `Showing ${totalRecords} Requests`
                                            : `Showing ${totalRecords} of ${requests.length} Requests (Filtered)`}
                                    </Badge>
                                    <Badge variant="outline" className="text-[11px] font-mono text-muted-foreground">
                                        Sorted by {String(sortColumn)} ({sortDirection})
                                    </Badge>
                                </div>

                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={resetDetailFilters}
                                        className="h-8 text-xs text-muted-foreground hover:text-foreground"
                                    >
                                        <RotateCcw className="h-3 w-3 mr-1" /> Reset Table
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={handleExportCsv}
                                        className="h-8 text-xs"
                                    >
                                        <Download className="h-3 w-3 mr-1 text-emerald-600" /> Export CSV
                                    </Button>
                                </div>
                            </div>

                            {/* Detail Table In-line Filters */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-2.5 pt-3">
                                {/* Search */}
                                <div className="sm:col-span-2 relative">
                                    <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-muted-foreground" />
                                    <Input
                                        placeholder="Quick search ID, branch, user, code, reason..."
                                        value={detailSearch}
                                        onChange={(e) => {
                                            setDetailSearch(e.target.value);
                                            setCurrentPage(1);
                                        }}
                                        className="h-8 pl-8 pr-7 text-xs"
                                    />
                                    {detailSearch && (
                                        <button
                                            type="button"
                                            onClick={() => setDetailSearch('')}
                                            className="absolute right-2 top-2 text-muted-foreground hover:text-foreground"
                                        >
                                            <X className="h-3.5 w-3.5" />
                                        </button>
                                    )}
                                </div>

                                {/* Voucher Type */}
                                <div>
                                    <select
                                        value={detailVoucherType}
                                        onChange={(e) => {
                                            setDetailVoucherType(e.target.value);
                                            setCurrentPage(1);
                                        }}
                                        className="w-full flex h-8 rounded-md border border-input bg-background px-2 py-1 text-xs ring-offset-background font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"
                                    >
                                        <option value="all">All Voucher Types</option>
                                        {filterOptions.voucherTypes.map((vt) => (
                                            <option key={vt} value={vt}>
                                                {vt}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Edit Type */}
                                <div>
                                    <select
                                        value={detailEditType}
                                        onChange={(e) => {
                                            setDetailEditType(e.target.value);
                                            setCurrentPage(1);
                                        }}
                                        className="w-full flex h-8 rounded-md border border-input bg-background px-2 py-1 text-xs ring-offset-background font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"
                                    >
                                        <option value="all">All Edit Types</option>
                                        {filterOptions.editTypes.map((et) => (
                                            <option key={et} value={et}>
                                                {et}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Action Type */}
                                <div>
                                    <select
                                        value={detailActionType}
                                        onChange={(e) => {
                                            setDetailActionType(e.target.value);
                                            setCurrentPage(1);
                                        }}
                                        className="w-full flex h-8 rounded-md border border-input bg-background px-2 py-1 text-xs ring-offset-background font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"
                                    >
                                        <option value="all">All Actions</option>
                                        <option value="Edit">Edit</option>
                                        <option value="Void">Void</option>
                                        <option value="On Hand Delete">On Hand Delete</option>
                                    </select>
                                </div>

                                {/* Rows Per Page */}
                                <div>
                                    <select
                                        value={pageSize}
                                        onChange={(e) => {
                                            const val = e.target.value === 'all' ? 'all' : parseInt(e.target.value, 10);
                                            setPageSize(val);
                                            setCurrentPage(1);
                                        }}
                                        className="w-full flex h-8 rounded-md border border-input bg-background px-2 py-1 text-xs ring-offset-background font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"
                                    >
                                        <option value="25">25 per page</option>
                                        <option value="50">50 per page</option>
                                        <option value="100">100 per page</option>
                                        <option value="250">250 per page</option>
                                        <option value="all">Show All ({totalRecords})</option>
                                    </select>
                                </div>
                            </div>
                        </CardHeader>

                        <CardContent className="p-0">
                            <div className="overflow-x-auto max-h-[550px] overflow-y-auto">
                                <table className="w-full text-xs">
                                    <thead className="bg-muted/40 text-muted-foreground font-semibold sticky top-0 border-b border-border/40 z-10">
                                        <tr>
                                            <th
                                                className="py-2.5 px-3 text-left cursor-pointer hover:text-foreground whitespace-nowrap min-w-[120px]"
                                                onClick={() => handleSort('request_id')}
                                            >
                                                <div className="flex items-center gap-1">
                                                    <span>ID</span>
                                                    <ArrowUpDown className="h-3 w-3 opacity-60" />
                                                </div>
                                            </th>
                                            <th
                                                className="py-2.5 px-3 text-left cursor-pointer hover:text-foreground whitespace-nowrap min-w-[110px]"
                                                onClick={() => handleSort('timestamp')}
                                            >
                                                <div className="flex items-center gap-1">
                                                    <span>DATE</span>
                                                    <ArrowUpDown className="h-3 w-3 opacity-60" />
                                                </div>
                                            </th>
                                            <th
                                                className="py-2.5 px-3 text-left cursor-pointer hover:text-foreground whitespace-nowrap min-w-[130px]"
                                                onClick={() => handleSort('branch')}
                                            >
                                                <div className="flex items-center gap-1">
                                                    <span>BRANCH</span>
                                                    <ArrowUpDown className="h-3 w-3 opacity-60" />
                                                </div>
                                            </th>
                                            <th className="py-2.5 px-3 text-left whitespace-nowrap min-w-[110px]">USER</th>
                                            <th className="py-2.5 px-3 text-left whitespace-nowrap min-w-[110px]">STORE USER</th>
                                            <th className="py-2.5 px-3 text-left whitespace-nowrap min-w-[90px]">ACTION</th>
                                            <th className="py-2.5 px-3 text-left whitespace-nowrap min-w-[120px]">VOUCHER CODE</th>
                                            <th className="py-2.5 px-3 text-left whitespace-nowrap min-w-[130px]">VOUCHER TYPE</th>
                                            <th className="py-2.5 px-3 text-left whitespace-nowrap min-w-[120px]">EDIT TYPE</th>
                                            <th className="py-2.5 px-3 text-left whitespace-nowrap min-w-[70px]">SHIFT</th>
                                            <th className="py-2.5 px-3 text-left whitespace-nowrap min-w-[100px]">INCORRECT DATE</th>
                                            <th className="py-2.5 px-3 text-left whitespace-nowrap min-w-[100px]">CORRECT DATE</th>
                                            <th className="py-2.5 px-3 text-left whitespace-nowrap min-w-[110px]">TARGET DEPT</th>
                                            <th className="py-2.5 px-3 text-left whitespace-nowrap min-w-[140px]">REASON</th>
                                            <th className="py-2.5 px-3 text-left whitespace-nowrap min-w-[90px]">STATUS</th>
                                            <th className="py-2.5 px-3 text-left whitespace-nowrap min-w-[110px]">APPROVER</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border/30">
                                        {paginatedRequests.length === 0 ? (
                                            <tr>
                                                <td colSpan={16} className="py-10 text-center text-muted-foreground">
                                                    No requests match the current filters. Click "Reset Table" to view all records.
                                                </td>
                                            </tr>
                                        ) : (
                                            paginatedRequests.map((r) => {
                                                const isOnHand = r.action_type === 'On Hand Delete';
                                                return (
                                                    <tr key={r.id} className="hover:bg-muted/20">
                                                        <td className="py-2.5 px-3 font-mono font-bold text-foreground">
                                                            <Link
                                                                href={route('edit-requests.show', r.id)}
                                                                className="hover:underline text-primary"
                                                            >
                                                                {r.request_id}
                                                            </Link>
                                                        </td>
                                                        <td className="py-2.5 px-3 whitespace-nowrap">
                                                            <div className="font-semibold">{r.date_part}</div>
                                                            <div className="text-[10px] text-muted-foreground">{r.time_part}</div>
                                                        </td>
                                                        <td className="py-2.5 px-3 font-semibold text-foreground whitespace-nowrap">
                                                            {r.branch}
                                                        </td>
                                                        <td className="py-2.5 px-3 whitespace-nowrap">{r.user_name}</td>
                                                        <td className="py-2.5 px-3 whitespace-nowrap font-medium text-stone-700 dark:text-stone-300">
                                                            {isOnHand && r.store_user ? r.store_user : '-'}
                                                        </td>
                                                        <td className="py-2.5 px-3 whitespace-nowrap">
                                                            <span className={`px-2 py-0.5 rounded text-[10px] font-semibold border ${actionBadgeClass(r.action_type)}`}>
                                                                {r.action_type}
                                                            </span>
                                                        </td>
                                                        <td className="py-2.5 px-3 font-mono font-semibold whitespace-nowrap">
                                                            {!isOnHand && r.voucher_code ? r.voucher_code : '-'}
                                                        </td>
                                                        <td className="py-2.5 px-3 whitespace-nowrap">
                                                            {!isOnHand && r.voucher_type ? r.voucher_type : '-'}
                                                        </td>
                                                        <td className="py-2.5 px-3 whitespace-nowrap">
                                                            {!isOnHand && r.edit_type ? r.edit_type : '-'}
                                                        </td>
                                                        <td className="py-2.5 px-3 whitespace-nowrap">
                                                            {isOnHand ? (r.shift || 'Shift 1') : '-'}
                                                        </td>
                                                        <td className="py-2.5 px-3 whitespace-nowrap">
                                                            {!isOnHand && r.incorrect_date ? r.incorrect_date : '-'}
                                                        </td>
                                                        <td className="py-2.5 px-3 whitespace-nowrap">
                                                            {r.correct_date || '-'}
                                                        </td>
                                                        <td className="py-2.5 px-3 whitespace-nowrap">
                                                            <span className="px-2 py-0.5 rounded bg-muted text-[10px] font-medium border border-border">
                                                                {r.target_dept}
                                                            </span>
                                                        </td>
                                                        <td className="py-2.5 px-3 max-w-[200px] truncate" title={r.reason}>
                                                            {r.reason}
                                                        </td>
                                                        <td className="py-2.5 px-3 whitespace-nowrap">
                                                            <span className={`px-2 py-0.5 rounded text-[10px] font-semibold border ${statusBadgeClass(r.status)}`}>
                                                                {r.status}
                                                            </span>
                                                        </td>
                                                        <td className="py-2.5 px-3 whitespace-nowrap text-muted-foreground">
                                                            {r.approver || '-'}
                                                        </td>
                                                    </tr>
                                                );
                                            })
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Detail Table Pagination */}
                            {pageSize !== 'all' && totalRecords > 0 && (
                                <div className="p-3 border-t border-border/40 flex items-center justify-between text-xs text-muted-foreground">
                                    <div>
                                        Showing <strong>{startIndex + 1}</strong> to <strong>{endIndex}</strong> of{' '}
                                        <strong>{totalRecords}</strong> records
                                    </div>
                                    <div className="flex items-center gap-1.5">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={safeCurrentPage === 1}
                                            onClick={() => setCurrentPage(1)}
                                            className="h-7 px-2 text-xs"
                                        >
                                            First
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={safeCurrentPage === 1}
                                            onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                                            className="h-7 px-2.5 text-xs"
                                        >
                                            Prev
                                        </Button>
                                        <span className="px-2 font-semibold text-foreground">
                                            Page {safeCurrentPage} of {totalPages}
                                        </span>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={safeCurrentPage === totalPages}
                                            onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                                            className="h-7 px-2.5 text-xs"
                                        >
                                            Next
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={safeCurrentPage === totalPages}
                                            onClick={() => setCurrentPage(totalPages)}
                                            className="h-7 px-2 text-xs"
                                        >
                                            Last
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
