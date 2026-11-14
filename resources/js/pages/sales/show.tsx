import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Calendar,
    CheckCircle2,
    Clock,
    DollarSign,
    Download,
    FileText,
    History,
    MessageSquare,
    Paperclip,
    Printer,
    Receipt,
    Send,
    ShieldCheck,
    User,
} from 'lucide-react';
import React, { useState } from 'react';

interface Comment {
    id: number;
    username: string;
    comment: string;
    created_at: string;
}

interface HistoryItem {
    id: number;
    username: string;
    action: string;
    details: string | null;
    created_at: string;
}

interface Attachment {
    id: number;
    file_path: string;
    file_name: string;
    file_size: number;
    uploaded_by: string | null;
    uploaded_at: string;
}

interface CaseItem {
    id: number;
    case_id: string;
    date: string;
    branch_name: string;
    z_report: number;
    cash_sales: number;
    difference: number;
    progress: 'Not Start' | 'In Progress' | 'Escalated' | 'Done';
    remark: string | null;
    submitted_by: string | null;
    technical_name: string | null;
    escalated_to: string | null;
    escalation_date: string | null;
    case_fixed_date: string | null;
    approved: boolean;
    approved_by: string | null;
    approved_at: string | null;
    attachment_file: string | null;
    attachment_name: string | null;
    comments: Comment[];
    history: HistoryItem[];
    case_attachments: Attachment[];
}

interface Props {
    caseItem: CaseItem;
    technicians: string[];
}

export default function SalesShow({ caseItem, technicians }: Props) {
    const [newComment, setNewComment] = useState('');
    const [isSubmittingComment, setIsSubmittingComment] = useState(false);
    const [progressStatus, setProgressStatus] = useState(caseItem.progress);

    const handleStatusChange = (newStatus: string) => {
        setProgressStatus(newStatus as any);
        router.post(`/sales/cases/${caseItem.id}/progress`, {
            progress: newStatus,
        }, { preserveScroll: true });
    };

    const handleApprove = () => {
        if (confirm(`Approve sales reconciliation for ${caseItem.case_id}?`)) {
            router.post(`/sales/cases/${caseItem.id}/approve`, {}, { preserveScroll: true });
        }
    };

    const handleAddComment = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newComment.trim()) return;

        setIsSubmittingComment(true);
        router.post(`/sales/cases/${caseItem.id}/comments`, {
            comment: newComment,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setNewComment('');
                setIsSubmittingComment(false);
            },
            onError: () => setIsSubmittingComment(false),
        });
    };

    const getProgressBadge = (status: string) => {
        switch (status) {
            case 'Done':
                return <span className="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">Done</span>;
            case 'In Progress':
                return <span className="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-300">In Progress</span>;
            case 'Escalated':
                return <span className="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">Escalated</span>;
            default:
                return <span className="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700">Not Started</span>;
        }
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'Sales Reconciliation', href: '/sales/dashboard' },
            { title: 'Cases', href: '/sales/cases' },
            { title: caseItem.case_id, href: `/sales/cases/${caseItem.id}` },
        ]}>
            <Head title={`Case ${caseItem.case_id} - ${caseItem.branch_name}`} />

            <div className="p-6 max-w-5xl mx-auto space-y-6">
                {/* Action Bar */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <Link
                        href="/sales/cases"
                        className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800 transition"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Back to Cases
                    </Link>

                    <div className="flex flex-wrap items-center gap-2">
                        {/* Quick Status Dropdown */}
                        <div className="flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl px-2.5 py-1 shadow-sm">
                            <span className="text-xs text-slate-500 font-medium">Status:</span>
                            <select
                                value={progressStatus}
                                onChange={(e) => handleStatusChange(e.target.value)}
                                className="text-xs font-bold text-slate-800 bg-transparent border-0 focus:ring-0 cursor-pointer"
                            >
                                <option value="Not Start">Not Start</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Escalated">Escalated</option>
                                <option value="Done">Done</option>
                            </select>
                        </div>

                        {!caseItem.approved && (
                            <button
                                onClick={handleApprove}
                                className="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm transition"
                            >
                                <ShieldCheck className="w-4 h-4" />
                                Approve Case
                            </button>
                        )}

                        <button
                            onClick={() => window.print()}
                            className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-sm transition"
                        >
                            <Printer className="w-3.5 h-3.5" />
                            Print
                        </button>
                    </div>
                </div>

                {/* Case Header Card */}
                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 pb-5">
                        <div className="flex items-center gap-4">
                            <div className="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                <Receipt className="w-7 h-7" />
                            </div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="text-2xl font-black text-slate-900">{caseItem.case_id}</h1>
                                    {getProgressBadge(caseItem.progress)}
                                    {caseItem.approved && (
                                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                            <ShieldCheck className="w-3 h-3" /> Approved
                                        </span>
                                    )}
                                </div>
                                <div className="text-sm font-semibold text-slate-600 mt-0.5">{caseItem.branch_name}</div>
                            </div>
                        </div>

                        <div className="text-right">
                            <div className="text-xs text-slate-400 font-medium">Discrepancy (Z-Report - Cash)</div>
                            <div className={`text-2xl font-mono font-black ${
                                caseItem.difference > 0 ? 'text-rose-600' : 'text-emerald-700'
                            }`}>
                                {Number(caseItem.difference).toLocaleString('en-US', { minimumFractionDigits: 2 })} ETB
                            </div>
                            <div className="text-[11px] text-slate-400">
                                {caseItem.difference > 0 ? 'Cash Shortage' : caseItem.difference < 0 ? 'Cash Surplus' : 'Balanced'}
                            </div>
                        </div>
                    </div>

                    {/* Financial Figures */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div className="text-xs font-semibold text-slate-400 uppercase">POS Z-Report Total</div>
                            <div className="text-xl font-mono font-bold text-slate-900 mt-1">
                                {Number(caseItem.z_report).toLocaleString('en-US', { minimumFractionDigits: 2 })} ETB
                            </div>
                        </div>

                        <div className="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div className="text-xs font-semibold text-slate-400 uppercase">Actual Cash Deposited</div>
                            <div className="text-xl font-mono font-bold text-slate-900 mt-1">
                                {Number(caseItem.cash_sales).toLocaleString('en-US', { minimumFractionDigits: 2 })} ETB
                            </div>
                        </div>

                        <div className="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div className="text-xs font-semibold text-slate-400 uppercase">Reconciliation Status</div>
                            <div className="text-sm font-bold text-slate-900 mt-1">
                                {caseItem.approved ? `Approved by ${caseItem.approved_by || 'Admin'}` : 'Pending Investigation Approval'}
                            </div>
                        </div>
                    </div>

                    {/* Details Grid */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs pt-2">
                        <div>
                            <span className="text-slate-400 block font-medium">Transaction Date</span>
                            <span className="font-semibold text-slate-800">{new Date(caseItem.date).toLocaleDateString()}</span>
                        </div>
                        <div>
                            <span className="text-slate-400 block font-medium">Assigned Technician</span>
                            <span className="font-semibold text-slate-800">{caseItem.technical_name || 'Unassigned'}</span>
                        </div>
                        <div>
                            <span className="text-slate-400 block font-medium">Escalated To</span>
                            <span className="font-semibold text-slate-800">{caseItem.escalated_to || 'None'}</span>
                        </div>
                        <div>
                            <span className="text-slate-400 block font-medium">Submitted By</span>
                            <span className="font-semibold text-slate-800">{caseItem.submitted_by || 'System'}</span>
                        </div>
                    </div>

                    {/* Remarks */}
                    {caseItem.remark && (
                        <div className="bg-amber-50/60 p-4 rounded-xl border border-amber-200">
                            <div className="text-xs font-bold text-amber-800 uppercase mb-1">Investigation Remark</div>
                            <p className="text-xs text-slate-700 whitespace-pre-line">{caseItem.remark}</p>
                        </div>
                    )}
                </div>

                {/* Attachments */}
                {(caseItem.attachment_file || (caseItem.case_attachments && caseItem.case_attachments.length > 0)) && (
                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                        <h3 className="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                            <Paperclip className="w-4 h-4 text-emerald-600" />
                            Case Attachments (Receipt Slips & Documents)
                        </h3>

                        <div className="flex flex-wrap gap-3">
                            {caseItem.attachment_file && (
                                <a
                                    href={caseItem.attachment_file}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-xl border border-emerald-200 transition"
                                >
                                    <FileText className="w-4 h-4" />
                                    {caseItem.attachment_name || 'Primary Attachment'}
                                    <Download className="w-3.5 h-3.5 ml-1 text-emerald-500" />
                                </a>
                            )}

                            {caseItem.case_attachments?.map((att) => (
                                <a
                                    key={att.id}
                                    href={att.file_path}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 rounded-xl border border-slate-200 transition"
                                >
                                    <FileText className="w-4 h-4 text-slate-500" />
                                    {att.file_name}
                                    <Download className="w-3.5 h-3.5 ml-1 text-slate-400" />
                                </a>
                            ))}
                        </div>
                    </div>
                )}

                {/* Comments Thread & History Timeline in 2 columns */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {/* Comments Thread */}
                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                        <h3 className="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <MessageSquare className="w-4 h-4 text-emerald-600" />
                            Case Comments & Communication
                        </h3>

                        <div className="space-y-3 max-h-80 overflow-y-auto pr-1">
                            {caseItem.comments?.length === 0 ? (
                                <p className="text-xs text-slate-400 py-4 text-center">No comments yet. Start the discussion below.</p>
                            ) : (
                                caseItem.comments?.map((c) => (
                                    <div key={c.id} className="bg-slate-50 p-3 rounded-xl border border-slate-100 text-xs space-y-1">
                                        <div className="flex justify-between font-semibold text-slate-800">
                                            <span>{c.username}</span>
                                            <span className="text-[10px] text-slate-400 font-normal">
                                                {new Date(c.created_at).toLocaleString()}
                                            </span>
                                        </div>
                                        <p className="text-slate-600">{c.comment}</p>
                                    </div>
                                ))
                            )}
                        </div>

                        {/* Add Comment Input */}
                        <form onSubmit={handleAddComment} className="flex gap-2 pt-2 border-t border-slate-100">
                            <input
                                type="text"
                                placeholder="Write a comment or investigation note..."
                                value={newComment}
                                onChange={(e) => setNewComment(e.target.value)}
                                className="flex-1 px-3 py-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"
                            />
                            <button
                                type="submit"
                                disabled={isSubmittingComment || !newComment.trim()}
                                className="px-3.5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm transition disabled:opacity-50"
                            >
                                <Send className="w-3.5 h-3.5" />
                            </button>
                        </form>
                    </div>

                    {/* History Audit Trail */}
                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                        <h3 className="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <History className="w-4 h-4 text-slate-500" />
                            Timeline & Activity History
                        </h3>

                        <div className="space-y-3 max-h-80 overflow-y-auto pr-1">
                            {caseItem.history?.length === 0 ? (
                                <p className="text-xs text-slate-400 py-4 text-center">No history logs recorded.</p>
                            ) : (
                                caseItem.history?.map((h) => (
                                    <div key={h.id} className="text-xs border-l-2 border-emerald-500 pl-3 py-1 space-y-0.5">
                                        <div className="flex justify-between font-semibold text-slate-800">
                                            <span>{h.action}</span>
                                            <span className="text-[10px] text-slate-400 font-normal">
                                                {new Date(h.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                            </span>
                                        </div>
                                        <p className="text-slate-600 text-[11px]">{h.details}</p>
                                        <span className="text-[10px] text-slate-400 block font-mono">By {h.username}</span>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
