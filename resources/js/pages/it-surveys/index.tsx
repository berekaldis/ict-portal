import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    BarChart3,
    CheckCircle2,
    ClipboardCheck,
    Download,
    Eye,
    Filter,
    HardDrive,
    PlusCircle,
    Search,
    XCircle,
} from 'lucide-react';
import React, { useState } from 'react';

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

interface Stats {
    total_reports: number;
    good_count: number;
    warning_count: number;
    critical_count: number;
    avg_score: number;
    total_assets: number;
    faulty_assets: number;
}

interface Props {
    reports: {
        data: Report[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    branches: string[];
    stats: Stats;
    filters: {
        search?: string;
        branch?: string;
        type?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
    };
}

export default function ItSurveyIndex({ reports, branches, stats, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedBranch, setSelectedBranch] = useState(filters.branch || '');
    const [selectedType, setSelectedType] = useState(filters.type || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const applyFilters = () => {
        router.get('/it-surveys', {
            search,
            branch: selectedBranch,
            type: selectedType,
            status: selectedStatus,
            date_from: dateFrom,
            date_to: dateTo,
        }, { preserveState: true });
    };

    const resetFilters = () => {
        setSearch('');
        setSelectedBranch('');
        setSelectedType('');
        setSelectedStatus('');
        setDateFrom('');
        setDateTo('');
        router.get('/it-surveys');
    };

    const getHealthBadge = (score: number, status: string) => {
        if (score >= 85 || status === 'Good') {
            return (
                <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    {score}% • Good
                </span>
            );
        }
        if (score >= 60 || status === 'Warning') {
            return (
                <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                    <span className="w-2 h-2 rounded-full bg-amber-500"></span>
                    {score}% • Warning
                </span>
            );
        }
        return (
            <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                <span className="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                {score}% • Critical
            </span>
        );
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'IT Survey & Audit', href: '/it-surveys' }]}>
            <Head title="IT Branch Survey & Hardware Audit" />

            <div className="p-6 space-y-6 max-w-7xl mx-auto">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <div>
                        <div className="flex items-center gap-2">
                            <div className="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                <ClipboardCheck className="w-5 h-5" />
                            </div>
                            <div>
                                <h1 className="text-xl font-bold text-slate-900">Branch Infrastructure Survey & Audit</h1>
                                <p className="text-sm text-slate-500">POS, Network, CNET ERP & Hardware Health Inspections</p>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap items-center gap-2.5">
                        <a
                            href="/it-surveys/export/reports"
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition"
                        >
                            <Download className="w-4 h-4 text-slate-500" />
                            Export CSV
                        </a>
                        <Link
                            href="/it-surveys/hardware"
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition"
                        >
                            <HardDrive className="w-4 h-4 text-slate-500" />
                            Hardware Inventory ({stats.total_assets})
                        </Link>
                        <Link
                            href="/it-surveys/create"
                            className="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition"
                        >
                            <PlusCircle className="w-4 h-4" />
                            New Branch Inspection
                        </Link>
                    </div>
                </div>

                {/* KPI Cards */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <Activity className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-2xl font-black text-slate-900">{stats.total_reports}</div>
                            <div className="text-xs font-medium text-slate-500">Total Audits Conducted</div>
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <CheckCircle2 className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-2xl font-black text-emerald-700">{stats.good_count}</div>
                            <div className="text-xs font-medium text-slate-500">Good Status (&ge;85%)</div>
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <AlertTriangle className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-2xl font-black text-amber-700">{stats.warning_count}</div>
                            <div className="text-xs font-medium text-slate-500">Warning Status (60-84%)</div>
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <XCircle className="w-6 h-6" />
                        </div>
                        <div>
                            <div className="text-2xl font-black text-rose-700">{stats.critical_count}</div>
                            <div className="text-xs font-medium text-slate-500">Critical (&lt;60% Score)</div>
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                    <div className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <Filter className="w-4 h-4 text-slate-500" />
                        Filter Audit Reports
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                        <div className="lg:col-span-2 relative">
                            <Search className="w-4 h-4 absolute left-3 top-3 text-slate-400" />
                            <input
                                type="text"
                                placeholder="Search branch, tech, manager..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                className="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <select
                                value={selectedBranch}
                                onChange={(e) => setSelectedBranch(e.target.value)}
                                className="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">All Branches</option>
                                {branches.map((b) => (
                                    <option key={b} value={b}>{b}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <select
                                value={selectedType}
                                onChange={(e) => setSelectedType(e.target.value)}
                                className="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">All Types</option>
                                <option value="hardware">Hardware Survey</option>
                                <option value="software">Software Survey</option>
                            </select>
                        </div>
                        <div>
                            <select
                                value={selectedStatus}
                                onChange={(e) => setSelectedStatus(e.target.value)}
                                className="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">All Health Status</option>
                                <option value="Good">Good (&ge;85%)</option>
                                <option value="Warning">Warning (60-84%)</option>
                                <option value="Critical">Critical (&lt;60%)</option>
                            </select>
                        </div>
                        <div className="flex gap-2">
                            <button
                                onClick={applyFilters}
                                className="flex-1 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition"
                            >
                                Apply
                            </button>
                            <button
                                onClick={resetFilters}
                                className="px-3 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition"
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                {/* Audit Reports Table */}
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold">
                                <tr>
                                    <th className="px-5 py-3.5">Branch</th>
                                    <th className="px-4 py-3.5">Audit Type</th>
                                    <th className="px-4 py-3.5">Health Score</th>
                                    <th className="px-4 py-3.5">Checklist Breakdown</th>
                                    <th className="px-4 py-3.5">Technician / Manager</th>
                                    <th className="px-4 py-3.5">Audit Date</th>
                                    <th className="px-4 py-3.5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {reports.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="text-center py-12 text-slate-400">
                                            <ClipboardCheck className="w-10 h-10 mx-auto mb-2 opacity-40" />
                                            No branch audit reports found matching your criteria.
                                        </td>
                                    </tr>
                                ) : (
                                    reports.data.map((r) => (
                                        <tr key={r.id} className="hover:bg-slate-50/80 transition">
                                            <td className="px-5 py-4">
                                                <div className="font-semibold text-slate-900">{r.branch_name}</div>
                                                <div className="text-xs text-slate-400 font-mono truncate max-w-[140px]">{r.report_uuid}</div>
                                            </td>
                                            <td className="px-4 py-4">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium uppercase tracking-wider ${
                                                    r.type === 'hardware' ? 'bg-indigo-50 text-indigo-700' : 'bg-cyan-50 text-cyan-700'
                                                }`}>
                                                    {r.type}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4">
                                                {getHealthBadge(r.health_score, r.health_status)}
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex items-center gap-1.5 text-xs">
                                                    <span className="text-emerald-700 font-medium" title="Passed">{r.passed_items} OK</span>
                                                    <span className="text-slate-300">•</span>
                                                    <span className="text-rose-600 font-medium" title="Issues">{r.failed_items} Issues</span>
                                                    {r.skipped_items > 0 && (
                                                        <>
                                                            <span className="text-slate-300">•</span>
                                                            <span className="text-purple-600 font-medium" title="Skipped">{r.skipped_items} Skip</span>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="text-slate-800 font-medium">{r.technician_name}</div>
                                                <div className="text-xs text-slate-400">Mgr: {r.branch_manager}</div>
                                            </td>
                                            <td className="px-4 py-4 text-xs text-slate-500 whitespace-nowrap">
                                                {new Date(r.submitted_at).toLocaleDateString('en-US', {
                                                    month: 'short',
                                                    day: 'numeric',
                                                    year: 'numeric',
                                                    hour: '2-digit',
                                                    minute: '2-digit',
                                                })}
                                            </td>
                                            <td className="px-4 py-4 text-right">
                                                <Link
                                                    href={`/it-surveys/${r.id}`}
                                                    className="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition"
                                                >
                                                    <Eye className="w-3.5 h-3.5" />
                                                    View Report
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {reports.last_page > 1 && (
                        <div className="flex items-center justify-between p-4 border-t border-slate-100 text-sm text-slate-500">
                            <div>Showing page {reports.current_page} of {reports.last_page} ({reports.total} total)</div>
                            <div className="flex gap-2">
                                {reports.prev_page_url && (
                                    <Link
                                        href={reports.prev_page_url}
                                        className="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {reports.next_page_url && (
                                    <Link
                                        href={reports.next_page_url}
                                        className="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
                                    >
                                        Next
                                    </Link>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
