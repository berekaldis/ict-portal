import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    CheckCircle2,
    Clock,
    DollarSign,
    Download,
    Eye,
    Filter,
    PlusCircle,
    Receipt,
    Search,
    ShieldCheck,
} from 'lucide-react';
import React, { useState } from 'react';

interface SalesCaseItem {
    id: number;
    case_id: string;
    date: string;
    branch_name: string;
    z_report: number;
    cash_sales: number;
    difference: number;
    progress: 'Not Start' | 'In Progress' | 'Escalated' | 'Done';
    technical_name: string | null;
    escalated_to: string | null;
    approved: boolean;
    approved_by: string | null;
    remark: string | null;
    submitted_by: string | null;
}

interface Props {
    cases: {
        data: SalesCaseItem[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    branches: string[];
    technicians: string[];
    filters: {
        search?: string;
        progress?: string;
        branch?: string;
        date_preset?: string;
        start_date?: string;
        end_date?: string;
    };
}

export default function SalesIndex({ cases, branches, technicians, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedProgress, setSelectedProgress] = useState(filters.progress || 'all');
    const [selectedBranch, setSelectedBranch] = useState(filters.branch || '');
    const [datePreset, setDatePreset] = useState(filters.date_preset || 'all');
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');

    const applyFilters = () => {
        router.get('/sales/cases', {
            search,
            progress: selectedProgress,
            branch: selectedBranch,
            date_preset: datePreset,
            start_date: startDate,
            end_date: endDate,
        }, { preserveState: true });
    };

    const resetFilters = () => {
        setSearch('');
        setSelectedProgress('all');
        setSelectedBranch('');
        setDatePreset('all');
        setStartDate('');
        setEndDate('');
        router.get('/sales/cases');
    };

    const handleQuickStatus = (id: number, progress: string) => {
        router.post(`/sales/cases/${id}/progress`, { progress }, {
            preserveScroll: true,
        });
    };

    const handleApprove = (id: number) => {
        if (confirm('Approve this sales reconciliation case?')) {
            router.post(`/sales/cases/${id}/approve`, {}, { preserveScroll: true });
        }
    };

    const getProgressBadge = (status: string) => {
        switch (status) {
            case 'Done':
                return <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Done</span>;
            case 'In Progress':
                return <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">In Progress</span>;
            case 'Escalated':
                return <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Escalated</span>;
            default:
                return <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Not Start</span>;
        }
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'Sales Reconciliation', href: '/sales/dashboard' },
            { title: 'All Cases', href: '/sales/cases' },
        ]}>
            <Head title="Sales Reconciliation Cases" />

            <div className="p-6 space-y-6 max-w-7xl mx-auto">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <div>
                        <div className="flex items-center gap-2">
                            <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                <Receipt className="w-5 h-5" />
                            </div>
                            <div>
                                <h1 className="text-xl font-bold text-slate-900">Reconciliation Cases</h1>
                                <p className="text-sm text-slate-500">POS Z-Report vs. Cash Sales differences</p>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        <a
                            href="/sales/export"
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition"
                        >
                            <Download className="w-4 h-4 text-slate-500" />
                            Export CSV
                        </a>
                        <Link
                            href="/sales/create"
                            className="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm transition"
                        >
                            <PlusCircle className="w-4 h-4" />
                            Add Sales Case
                        </Link>
                    </div>
                </div>

                {/* Filter Bar */}
                <div className="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                    <div className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <Filter className="w-4 h-4 text-slate-500" />
                        Filter Cases
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                        <div className="lg:col-span-2 relative">
                            <Search className="w-4 h-4 absolute left-3 top-3 text-slate-400" />
                            <input
                                type="text"
                                placeholder="Search case ID, branch, technician..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                className="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <select
                                value={selectedProgress}
                                onChange={(e) => setSelectedProgress(e.target.value)}
                                className="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="all">All Statuses</option>
                                <option value="Not Start">Not Start</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Escalated">Escalated</option>
                                <option value="Done">Done</option>
                            </select>
                        </div>

                        <div>
                            <select
                                value={selectedBranch}
                                onChange={(e) => setSelectedBranch(e.target.value)}
                                className="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="">All Branches</option>
                                {branches.map((b) => (
                                    <option key={b} value={b}>{b}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <select
                                value={datePreset}
                                onChange={(e) => setDatePreset(e.target.value)}
                                className="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="all">All Dates</option>
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="this_week">This Week</option>
                                <option value="this_month">This Month</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>

                        <div className="flex gap-2">
                            <button
                                onClick={applyFilters}
                                className="flex-1 px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition"
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

                    {datePreset === 'custom' && (
                        <div className="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1">Start Date</label>
                                <input
                                    type="date"
                                    value={startDate}
                                    onChange={(e) => setStartDate(e.target.value)}
                                    className="w-full px-3 py-1.5 text-xs border border-slate-200 rounded-lg"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600 mb-1">End Date</label>
                                <input
                                    type="date"
                                    value={endDate}
                                    onChange={(e) => setEndDate(e.target.value)}
                                    className="w-full px-3 py-1.5 text-xs border border-slate-200 rounded-lg"
                                />
                            </div>
                        </div>
                    )}
                </div>

                {/* Cases Table */}
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-xs uppercase">
                                <tr>
                                    <th className="px-5 py-3.5">Case ID</th>
                                    <th className="px-4 py-3.5">Date</th>
                                    <th className="px-4 py-3.5">Branch</th>
                                    <th className="px-4 py-3.5 text-right">Z-Report</th>
                                    <th className="px-4 py-3.5 text-right">Cash Sales</th>
                                    <th className="px-4 py-3.5 text-right">Difference</th>
                                    <th className="px-4 py-3.5">Assigned To</th>
                                    <th className="px-4 py-3.5">Status</th>
                                    <th className="px-4 py-3.5">Approval</th>
                                    <th className="px-4 py-3.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {cases.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={10} className="text-center py-12 text-slate-400">
                                            No sales reconciliation cases found matching criteria.
                                        </td>
                                    </tr>
                                ) : (
                                    cases.data.map((c) => (
                                        <tr key={c.id} className="hover:bg-slate-50/80 transition">
                                            <td className="px-5 py-4 font-mono font-bold text-xs text-slate-900">
                                                <Link href={`/sales/cases/${c.id}`} className="hover:text-emerald-600">
                                                    {c.case_id}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-4 text-xs text-slate-600 whitespace-nowrap">
                                                {new Date(c.date).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 py-4 font-medium text-slate-900">
                                                {c.branch_name}
                                            </td>
                                            <td className="px-4 py-4 text-right font-mono text-xs text-slate-700">
                                                {Number(c.z_report).toLocaleString()}
                                            </td>
                                            <td className="px-4 py-4 text-right font-mono text-xs text-slate-700">
                                                {Number(c.cash_sales).toLocaleString()}
                                            </td>
                                            <td className={`px-4 py-4 text-right font-mono text-xs font-bold ${
                                                c.difference > 0 ? 'text-rose-600' : 'text-emerald-600'
                                            }`}>
                                                {Number(c.difference).toLocaleString()} ETB
                                            </td>
                                            <td className="px-4 py-4 text-xs text-slate-700">
                                                {c.technical_name || '—'}
                                            </td>
                                            <td className="px-4 py-4">
                                                {getProgressBadge(c.progress)}
                                            </td>
                                            <td className="px-4 py-4 text-xs">
                                                {c.approved ? (
                                                    <span className="inline-flex items-center gap-1 text-emerald-700 font-semibold">
                                                        <ShieldCheck className="w-3.5 h-3.5" />
                                                        Approved
                                                    </span>
                                                ) : (
                                                    <button
                                                        onClick={() => handleApprove(c.id)}
                                                        className="text-xs font-medium text-slate-500 hover:text-emerald-700 underline"
                                                    >
                                                        Approve
                                                    </button>
                                                )}
                                            </td>
                                            <td className="px-4 py-4 text-right">
                                                <Link
                                                    href={`/sales/cases/${c.id}`}
                                                    className="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition"
                                                >
                                                    <Eye className="w-3.5 h-3.5" />
                                                    View
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {cases.last_page > 1 && (
                        <div className="flex items-center justify-between p-4 border-t border-slate-100 text-sm text-slate-500">
                            <div>Showing page {cases.current_page} of {cases.last_page} ({cases.total} cases)</div>
                            <div className="flex gap-2">
                                {cases.prev_page_url && (
                                    <Link
                                        href={cases.prev_page_url}
                                        className="px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {cases.next_page_url && (
                                    <Link
                                        href={cases.next_page_url}
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
