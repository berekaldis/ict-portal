import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowUpRight,
    CheckCircle2,
    Clock,
    DollarSign,
    Download,
    Eye,
    FileText,
    PlusCircle,
    Receipt,
    TrendingDown,
    TrendingUp,
} from 'lucide-react';
import React from 'react';

interface Stats {
    total_all: number;
    active: number;
    completed: number;
    approved: number;
    not_started: number;
    in_progress: number;
    escalated: number;
    total_discrepancy: number;
}

interface MonthlyCase {
    month: string;
    count: number;
    difference: number;
}

interface BranchDiscrepancy {
    branch_name: string;
    total_cases: number;
    total_difference: number;
}

interface RecentCase {
    id: number;
    case_id: string;
    date: string;
    branch_name: string;
    z_report: number;
    cash_sales: number;
    difference: number;
    progress: 'Not Start' | 'In Progress' | 'Escalated' | 'Done';
    technical_name: string | null;
    approved: boolean;
}

interface Props {
    stats: Stats;
    progressData: Record<string, number>;
    monthlyCases: MonthlyCase[];
    branchDiscrepancies: BranchDiscrepancy[];
    recentCases: RecentCase[];
}

export default function SalesDashboard({ stats, progressData, monthlyCases, branchDiscrepancies, recentCases }: Props) {
    const getProgressBadge = (status: string) => {
        switch (status) {
            case 'Done':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Done</span>;
            case 'In Progress':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">In Progress</span>;
            case 'Escalated':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Escalated</span>;
            default:
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Not Started</span>;
        }
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Sales Reconciliation', href: '/sales/dashboard' }]}>
            <Head title="Sales Discrepancy & Z-Report Dashboard" />

            <div className="p-6 space-y-6 max-w-7xl mx-auto">
                {/* Header Banner */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
                    <div>
                        <div className="flex items-center gap-2">
                            <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                <Receipt className="w-5 h-5" />
                            </div>
                            <div>
                                <h1 className="text-xl font-bold text-slate-900">Sales Discrepancy & Z-Report Reconciliation</h1>
                                <p className="text-sm text-slate-500">Track POS Z-Reports vs. Actual Cash Sales per Branch</p>
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
                            href="/sales/cases"
                            className="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition"
                        >
                            <FileText className="w-4 h-4 text-slate-500" />
                            View All Cases
                        </Link>
                        <Link
                            href="/sales/create"
                            className="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm transition"
                        >
                            <PlusCircle className="w-4 h-4" />
                            Add Sales Case
                        </Link>
                    </div>
                </div>

                {/* KPI Cards */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div className="text-xs font-medium text-slate-500">Total Cases</div>
                            <Receipt className="w-4 h-4 text-slate-400" />
                        </div>
                        <div className="text-2xl font-black text-slate-900 mt-2">{stats.total_all}</div>
                        <div className="text-xs text-slate-400 mt-1">
                            {stats.active} Active • {stats.completed} Done
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div className="text-xs font-medium text-slate-500">Net Discrepancy</div>
                            <DollarSign className="w-4 h-4 text-amber-500" />
                        </div>
                        <div className={`text-2xl font-black mt-2 ${stats.total_discrepancy > 0 ? 'text-rose-600' : 'text-slate-900'}`}>
                            {stats.total_discrepancy.toLocaleString('en-US', { minimumFractionDigits: 2 })} ETB
                        </div>
                        <div className="text-xs text-slate-400 mt-1">Z-Report minus Cash Sales</div>
                    </div>

                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div className="text-xs font-medium text-slate-500">Escalated Cases</div>
                            <AlertCircle className="w-4 h-4 text-rose-500" />
                        </div>
                        <div className="text-2xl font-black text-rose-600 mt-2">{stats.escalated}</div>
                        <div className="text-xs text-slate-400 mt-1">Requires supervisor action</div>
                    </div>

                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                        <div className="flex items-center justify-between">
                            <div className="text-xs font-medium text-slate-500">Approved Cases</div>
                            <CheckCircle2 className="w-4 h-4 text-emerald-500" />
                        </div>
                        <div className="text-2xl font-black text-emerald-700 mt-2">{stats.approved}</div>
                        <div className="text-xs text-slate-400 mt-1">Reconciliation confirmed</div>
                    </div>
                </div>

                {/* Progress Distribution & Branch Discrepancies */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Progress Distribution */}
                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                        <h2 className="text-sm font-bold text-slate-800">Investigation Progress</h2>
                        <div className="space-y-3">
                            <div>
                                <div className="flex justify-between text-xs font-medium text-slate-700 mb-1">
                                    <span>Not Started</span>
                                    <span>{stats.not_started} cases</span>
                                </div>
                                <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div
                                        className="h-full bg-slate-400 rounded-full"
                                        style={{ width: `${stats.total_all ? (stats.not_started / stats.total_all) * 100 : 0}%` }}
                                    ></div>
                                </div>
                            </div>

                            <div>
                                <div className="flex justify-between text-xs font-medium text-slate-700 mb-1">
                                    <span>In Progress</span>
                                    <span>{stats.in_progress} cases</span>
                                </div>
                                <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div
                                        className="h-full bg-blue-500 rounded-full"
                                        style={{ width: `${stats.total_all ? (stats.in_progress / stats.total_all) * 100 : 0}%` }}
                                    ></div>
                                </div>
                            </div>

                            <div>
                                <div className="flex justify-between text-xs font-medium text-slate-700 mb-1">
                                    <span>Escalated</span>
                                    <span>{stats.escalated} cases</span>
                                </div>
                                <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div
                                        className="h-full bg-rose-500 rounded-full"
                                        style={{ width: `${stats.total_all ? (stats.escalated / stats.total_all) * 100 : 0}%` }}
                                    ></div>
                                </div>
                            </div>

                            <div>
                                <div className="flex justify-between text-xs font-medium text-slate-700 mb-1">
                                    <span>Done / Resolved</span>
                                    <span>{stats.completed} cases</span>
                                </div>
                                <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div
                                        className="h-full bg-emerald-500 rounded-full"
                                        style={{ width: `${stats.total_all ? (stats.completed / stats.total_all) * 100 : 0}%` }}
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Monthly Trends */}
                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                        <h2 className="text-sm font-bold text-slate-800">Monthly Discrepancies (6 Months)</h2>
                        <div className="space-y-3">
                            {monthlyCases.map((m) => (
                                <div key={m.month} className="flex items-center justify-between text-xs border-b border-slate-100 pb-2">
                                    <div className="font-semibold text-slate-700">{m.month}</div>
                                    <div className="text-slate-500">{m.count} cases</div>
                                    <div className={`font-mono font-bold ${m.difference > 0 ? 'text-rose-600' : 'text-emerald-600'}`}>
                                        {m.difference.toLocaleString()} ETB
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Top Branch Discrepancies */}
                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                        <h2 className="text-sm font-bold text-slate-800">Top Discrepancy by Branch</h2>
                        <div className="space-y-3">
                            {branchDiscrepancies.length === 0 ? (
                                <p className="text-xs text-slate-400 py-4">No branch discrepancy records yet.</p>
                            ) : (
                                branchDiscrepancies.map((b) => (
                                    <div key={b.branch_name} className="flex items-center justify-between text-xs border-b border-slate-100 pb-2">
                                        <div>
                                            <div className="font-semibold text-slate-900">{b.branch_name}</div>
                                            <div className="text-slate-400">{b.total_cases} cases</div>
                                        </div>
                                        <div className="font-mono font-bold text-rose-600">
                                            {Number(b.total_difference).toLocaleString()} ETB
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>

                {/* Recent Reconciliation Cases */}
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-3 p-5">
                    <div className="flex justify-between items-center">
                        <h2 className="text-base font-bold text-slate-900">Recent Reconciliation Cases</h2>
                        <Link href="/sales/cases" className="text-xs font-semibold text-emerald-600 hover:text-emerald-700">
                            View All Cases &rarr;
                        </Link>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 border-y border-slate-200 text-slate-600 font-semibold text-xs uppercase">
                                <tr>
                                    <th className="px-4 py-3">Case ID</th>
                                    <th className="px-4 py-3">Date</th>
                                    <th className="px-4 py-3">Branch</th>
                                    <th className="px-4 py-3 text-right">Z-Report Total</th>
                                    <th className="px-4 py-3 text-right">Cash Sales</th>
                                    <th className="px-4 py-3 text-right">Difference</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {recentCases.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="text-center py-8 text-slate-400">
                                            No sales reconciliation cases recorded yet.
                                        </td>
                                    </tr>
                                ) : (
                                    recentCases.map((c) => (
                                        <tr key={c.id} className="hover:bg-slate-50 transition">
                                            <td className="px-4 py-3.5 font-mono text-xs font-bold text-slate-900">{c.case_id}</td>
                                            <td className="px-4 py-3.5 text-xs text-slate-600">{new Date(c.date).toLocaleDateString()}</td>
                                            <td className="px-4 py-3.5 font-medium text-slate-800">{c.branch_name}</td>
                                            <td className="px-4 py-3.5 text-right font-mono text-xs">{Number(c.z_report).toLocaleString()} ETB</td>
                                            <td className="px-4 py-3.5 text-right font-mono text-xs">{Number(c.cash_sales).toLocaleString()} ETB</td>
                                            <td className={`px-4 py-3.5 text-right font-mono text-xs font-bold ${
                                                c.difference > 0 ? 'text-rose-600' : 'text-emerald-600'
                                            }`}>
                                                {Number(c.difference).toLocaleString()} ETB
                                            </td>
                                            <td className="px-4 py-3.5">{getProgressBadge(c.progress)}</td>
                                            <td className="px-4 py-3.5 text-right">
                                                <Link
                                                    href={`/sales/cases/${c.id}`}
                                                    className="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition"
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
                </div>
            </div>
        </AppLayout>
    );
}
