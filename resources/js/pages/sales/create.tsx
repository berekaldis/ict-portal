import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    CheckCircle2,
    DollarSign,
    FileText,
    PlusCircle,
    Receipt,
    Upload,
} from 'lucide-react';
import React, { useMemo, useState } from 'react';

interface Branch {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
}

interface Props {
    nextCaseId: string;
    branches: Branch[];
    technicians: User[];
}

export default function SalesCreate({ nextCaseId, branches, technicians }: Props) {
    const [date, setDate] = useState(new Date().toISOString().split('T')[0]);
    const [selectedBranch, setSelectedBranch] = useState(branches[0]?.name || '');
    const [zReport, setZReport] = useState<number | ''>('');
    const [cashSales, setCashSales] = useState<number | ''>('');
    const [progress, setProgress] = useState<'Not Start' | 'In Progress' | 'Escalated' | 'Done'>('Not Start');
    const [technicalName, setTechnicalName] = useState('');
    const [escalatedTo, setEscalatedTo] = useState('');
    const [remark, setRemark] = useState('');
    const [attachment, setAttachment] = useState<File | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Live discrepancy calculation
    const difference = useMemo(() => {
        const z = typeof zReport === 'number' ? zReport : 0;
        const c = typeof cashSales === 'number' ? cashSales : 0;
        return z - c;
    }, [zReport, cashSales]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (zReport === '' || cashSales === '') {
            alert('Please enter both Z-Report total and Cash Sales.');
            return;
        }

        setIsSubmitting(true);

        const formData = new FormData();
        formData.append('date', date);
        formData.append('branch_name', selectedBranch);
        formData.append('z_report', String(zReport));
        formData.append('cash_sales', String(cashSales));
        formData.append('progress', progress);
        if (technicalName) formData.append('technical_name', technicalName);
        if (escalatedTo) formData.append('escalated_to', escalatedTo);
        if (remark) formData.append('remark', remark);
        if (attachment) formData.append('attachment', attachment);

        router.post('/sales/cases', formData, {
            onError: () => setIsSubmitting(false),
        });
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'Sales Reconciliation', href: '/sales/dashboard' },
            { title: 'New Case', href: '/sales/create' },
        ]}>
            <Head title="Add Sales Reconciliation Case" />

            <div className="p-6 max-w-3xl mx-auto space-y-6">
                <div>
                    <Link
                        href="/sales/cases"
                        className="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-slate-800 mb-2 transition"
                    >
                        <ArrowLeft className="w-3.5 h-3.5" />
                        Back to Cases
                    </Link>
                    <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
                        <Receipt className="w-6 h-6 text-emerald-600" />
                        Record Sales Discrepancy Case
                    </h1>
                    <p className="text-sm text-slate-500">Log POS Z-Report vs. actual cash deposited for branch audit</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Case Number & Date Header Card */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 pb-4">
                            <div>
                                <span className="text-xs font-semibold text-slate-400 uppercase">System Generated ID</span>
                                <div className="text-lg font-mono font-black text-emerald-700">{nextCaseId}</div>
                            </div>
                            <div className="w-full sm:w-auto">
                                <label className="block text-xs font-semibold text-slate-700 uppercase mb-1">
                                    Transaction / Z-Report Date <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="date"
                                    value={date}
                                    onChange={(e) => setDate(e.target.value)}
                                    className="px-3.5 py-2 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                    required
                                />
                            </div>
                        </div>

                        {/* Branch Selection */}
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                Branch <span className="text-rose-500">*</span>
                            </label>
                            <select
                                value={selectedBranch}
                                onChange={(e) => setSelectedBranch(e.target.value)}
                                className="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 font-medium"
                                required
                            >
                                {branches.map((b) => (
                                    <option key={b.id} value={b.name}>{b.name}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {/* Financial Figures & Discrepancy Calculation Card */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-5">
                        <h2 className="text-sm font-bold text-slate-900 uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                            <DollarSign className="w-4 h-4 text-emerald-600" />
                            Financial Reconciliation (ETB)
                        </h2>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                    Z-Report Total (ETB) <span className="text-rose-500">*</span>
                                </label>
                                <div className="relative">
                                    <input
                                        type="number"
                                        step="0.01"
                                        placeholder="0.00"
                                        value={zReport}
                                        onChange={(e) => setZReport(e.target.value === '' ? '' : parseFloat(e.target.value))}
                                        className="w-full px-3.5 py-2.5 text-base font-mono border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                        required
                                    />
                                    <span className="absolute right-3 top-3 text-xs font-bold text-slate-400">ETB</span>
                                </div>
                                <span className="text-[11px] text-slate-400">Total revenue printed on daily POS Z-Report</span>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                    Actual Cash Sales (ETB) <span className="text-rose-500">*</span>
                                </label>
                                <div className="relative">
                                    <input
                                        type="number"
                                        step="0.01"
                                        placeholder="0.00"
                                        value={cashSales}
                                        onChange={(e) => setCashSales(e.target.value === '' ? '' : parseFloat(e.target.value))}
                                        className="w-full px-3.5 py-2.5 text-base font-mono border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                        required
                                    />
                                    <span className="absolute right-3 top-3 text-xs font-bold text-slate-400">ETB</span>
                                </div>
                                <span className="text-[11px] text-slate-400">Actual physical cash deposited / counted</span>
                            </div>
                        </div>

                        {/* Live Discrepancy Card */}
                        <div className={`p-4 rounded-xl border flex items-center justify-between ${
                            difference > 0
                                ? 'bg-rose-50 border-rose-200 text-rose-800'
                                : difference < 0
                                ? 'bg-amber-50 border-amber-200 text-amber-800'
                                : 'bg-emerald-50 border-emerald-200 text-emerald-800'
                        }`}>
                            <div>
                                <div className="text-xs font-semibold uppercase tracking-wider">
                                    Calculated Discrepancy (Z-Report - Cash)
                                </div>
                                <div className="text-xs mt-0.5 opacity-80">
                                    {difference > 0 ? '⚠️ Cash Shortage (POS recorded higher than deposited)' : difference < 0 ? 'ℹ️ Cash Surplus' : '✅ Balanced'}
                                </div>
                            </div>
                            <div className="text-xl font-mono font-black">
                                {difference.toLocaleString('en-US', { minimumFractionDigits: 2 })} ETB
                            </div>
                        </div>
                    </div>

                    {/* Progress & Assigned Technician */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                        <h2 className="text-sm font-bold text-slate-900 uppercase tracking-wider text-slate-700">
                            Investigation Status & Assignment
                        </h2>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                    Progress Status <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    value={progress}
                                    onChange={(e) => setProgress(e.target.value as any)}
                                    className="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                    required
                                >
                                    <option value="Not Start">Not Start</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Escalated">Escalated</option>
                                    <option value="Done">Done</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                    Assigned Technician
                                </label>
                                <input
                                    type="text"
                                    placeholder="e.g. Abebe / Finance Officer"
                                    value={technicalName}
                                    onChange={(e) => setTechnicalName(e.target.value)}
                                    className="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                    Escalated To (if applicable)
                                </label>
                                <input
                                    type="text"
                                    placeholder="e.g. Finance Supervisor"
                                    value={escalatedTo}
                                    onChange={(e) => setEscalatedTo(e.target.value)}
                                    className="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                Investigation Remark / Findings
                            </label>
                            <textarea
                                rows={3}
                                placeholder="Details regarding missing vouchers, offline CNET orders, or cashier discrepancy..."
                                value={remark}
                                onChange={(e) => setRemark(e.target.value)}
                                className="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                            ></textarea>
                        </div>

                        {/* Attachment Upload */}
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                Attachment (Z-Report Slip, Bank Deposit Slip, Voucher)
                            </label>
                            <input
                                type="file"
                                onChange={(e) => setAttachment(e.target.files?.[0] || null)}
                                className="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                            />
                            <p className="text-[11px] text-slate-400 mt-1">Allowed formats: JPG, PNG, PDF, Excel, Word (Up to 20MB)</p>
                        </div>
                    </div>

                    {/* Submit Bar */}
                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Link
                            href="/sales/cases"
                            className="px-5 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-800 transition"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            className="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md transition disabled:opacity-50"
                        >
                            <PlusCircle className="w-4 h-4" />
                            {isSubmitting ? 'Creating Case...' : 'Create Sales Case'}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
