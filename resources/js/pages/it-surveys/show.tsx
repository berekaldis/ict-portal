import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Calendar,
    Camera,
    CheckCircle2,
    ClipboardCheck,
    Download,
    FileText,
    HardDrive,
    Info,
    Laptop,
    Printer,
    SkipForward,
    User,
} from 'lucide-react';
import React, { useState } from 'react';

interface ChecklistResult {
    category_id: number;
    category_name: string;
    item_id: number;
    question: string;
    status: 'OK' | 'Issue' | 'Skip';
    detail?: string;
    skip_reason?: string;
}

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
    checklist_data: ChecklistResult[];
    issues_summary: string | null;
    actions_taken: string | null;
    recommendations: string | null;
    photos: string[] | null;
    submitted_at: string;
}

interface Props {
    report: Report;
}

export default function ItSurveyShow({ report }: Props) {
    const [selectedPhoto, setSelectedPhoto] = useState<string | null>(null);

    const getHealthBadge = (score: number, status: string) => {
        if (score >= 85 || status === 'Good') {
            return (
                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    <span className="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                    {score}% • Good Health
                </span>
            );
        }
        if (score >= 60 || status === 'Warning') {
            return (
                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-bold bg-amber-100 text-amber-800 border border-amber-300">
                    <span className="w-2.5 h-2.5 rounded-full bg-amber-600"></span>
                    {score}% • Warning
                </span>
            );
        }
        return (
            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-bold bg-rose-100 text-rose-800 border border-rose-300">
                <span className="w-2.5 h-2.5 rounded-full bg-rose-600"></span>
                {score}% • Critical Issues
            </span>
        );
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'IT Survey & Audit', href: '/it-surveys' },
            { title: `${report.branch_name} Report`, href: `/it-surveys/${report.id}` },
        ]}>
            <Head title={`Audit Report - ${report.branch_name}`} />

            <div className="p-6 max-w-5xl mx-auto space-y-6">
                {/* Top Action Bar */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                    <Link
                        href="/it-surveys"
                        className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800 transition"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Back to Audits List
                    </Link>

                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => window.print()}
                            className="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 shadow-sm transition"
                        >
                            <Printer className="w-3.5 h-3.5" />
                            Print Report
                        </button>
                        <Link
                            href="/it-surveys/create"
                            className="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition"
                        >
                            New Inspection
                        </Link>
                    </div>
                </div>

                {/* Main Report Header Card */}
                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 pb-5">
                        <div className="flex items-center gap-4">
                            <div className="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                {report.type === 'hardware' ? <HardDrive className="w-7 h-7" /> : <Laptop className="w-7 h-7" />}
                            </div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="text-2xl font-black text-slate-900">{report.branch_name}</h1>
                                    <span className="px-2.5 py-0.5 rounded-md text-xs font-semibold uppercase bg-slate-100 text-slate-700">
                                        {report.type} Survey
                                    </span>
                                </div>
                                <div className="text-xs text-slate-400 font-mono mt-0.5">UUID: {report.report_uuid}</div>
                            </div>
                        </div>

                        <div>
                            {getHealthBadge(report.health_score, report.health_status)}
                        </div>
                    </div>

                    {/* Metadata Grid */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                        <div className="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                            <div className="text-slate-400 font-medium">Technician</div>
                            <div className="text-slate-900 font-bold text-sm mt-0.5 flex items-center gap-1">
                                <User className="w-3.5 h-3.5 text-slate-400" />
                                {report.technician_name}
                            </div>
                        </div>

                        <div className="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                            <div className="text-slate-400 font-medium">Branch Manager</div>
                            <div className="text-slate-900 font-bold text-sm mt-0.5">
                                {report.branch_manager}
                            </div>
                        </div>

                        <div className="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                            <div className="text-slate-400 font-medium">Inspection Date</div>
                            <div className="text-slate-900 font-bold text-sm mt-0.5 flex items-center gap-1">
                                <Calendar className="w-3.5 h-3.5 text-slate-400" />
                                {new Date(report.submitted_at).toLocaleDateString()}
                            </div>
                        </div>

                        <div className="bg-slate-50 p-3.5 rounded-xl border border-slate-100">
                            <div className="text-slate-400 font-medium">Evaluation Breakdown</div>
                            <div className="text-slate-900 font-bold text-sm mt-0.5">
                                {report.passed_items} OK / {report.failed_items} Issues
                            </div>
                        </div>
                    </div>
                </div>

                {/* Summary / Notes Cards */}
                {(report.issues_summary || report.actions_taken || report.recommendations) && (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-2">
                            <h3 className="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5 text-rose-600">
                                <AlertCircle className="w-4 h-4" />
                                Issues Summary
                            </h3>
                            <p className="text-sm text-slate-600 whitespace-pre-line">
                                {report.issues_summary || 'No major issues recorded.'}
                            </p>
                        </div>

                        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-2">
                            <h3 className="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5 text-indigo-600">
                                <CheckCircle2 className="w-4 h-4" />
                                Actions Taken
                            </h3>
                            <p className="text-sm text-slate-600 whitespace-pre-line">
                                {report.actions_taken || 'No immediate corrective action taken.'}
                            </p>
                        </div>

                        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-2">
                            <h3 className="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5 text-amber-600">
                                <Info className="w-4 h-4" />
                                Recommendations
                            </h3>
                            <p className="text-sm text-slate-600 whitespace-pre-line">
                                {report.recommendations || 'No specific follow-up required.'}
                            </p>
                        </div>
                    </div>
                )}

                {/* Photo Evidence Gallery */}
                {report.photos && report.photos.length > 0 && (
                    <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                        <h3 className="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <Camera className="w-4 h-4 text-indigo-600" />
                            Inspection Photo Evidence ({report.photos.length})
                        </h3>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            {report.photos.map((photo, i) => (
                                <button
                                    key={i}
                                    type="button"
                                    onClick={() => setSelectedPhoto(photo)}
                                    className="group relative rounded-xl overflow-hidden border border-slate-200 aspect-video bg-slate-100 focus:outline-none"
                                >
                                    <img
                                        src={photo}
                                        alt={`Evidence ${i + 1}`}
                                        className="w-full h-full object-cover group-hover:scale-105 transition"
                                    />
                                    <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-medium transition">
                                        Click to Zoom
                                    </div>
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                {/* Detailed Checklist Results Table */}
                <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-4 p-5">
                    <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                        <ClipboardCheck className="w-5 h-5 text-indigo-600" />
                        Checklist Inspection Results
                    </h3>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 border-y border-slate-200 text-slate-600 font-semibold text-xs uppercase">
                                <tr>
                                    <th className="px-4 py-3">Category</th>
                                    <th className="px-4 py-3">Item / Question</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3">Notes / Fault Details</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {report.checklist_data?.map((item, idx) => (
                                    <tr key={idx} className="hover:bg-slate-50/70 transition">
                                        <td className="px-4 py-3.5 font-medium text-slate-900 text-xs whitespace-nowrap">
                                            {item.category_name}
                                        </td>
                                        <td className="px-4 py-3.5 text-slate-700 text-xs">
                                            {item.question}
                                        </td>
                                        <td className="px-4 py-3.5 whitespace-nowrap">
                                            {item.status === 'OK' ? (
                                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <CheckCircle2 className="w-3 h-3" />
                                                    OK
                                                </span>
                                            ) : item.status === 'Issue' ? (
                                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                    <AlertCircle className="w-3 h-3" />
                                                    Issue
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                                    <SkipForward className="w-3 h-3" />
                                                    Skipped
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3.5 text-xs text-slate-600">
                                            {item.status === 'Issue' && item.detail && (
                                                <span className="text-rose-600 font-medium">{item.detail}</span>
                                            )}
                                            {item.status === 'Skip' && item.skip_reason && (
                                                <span className="text-purple-600 italic">{item.skip_reason}</span>
                                            )}
                                            {item.status === 'OK' && (
                                                <span className="text-slate-400">Normal</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Photo Modal */}
            {selectedPhoto && (
                <div
                    className="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4 cursor-pointer"
                    onClick={() => setSelectedPhoto(null)}
                >
                    <div className="max-w-3xl max-h-[85vh] bg-white rounded-2xl overflow-hidden shadow-2xl p-2">
                        <img
                            src={selectedPhoto}
                            alt="Inspection full evidence"
                            className="max-w-full max-h-[80vh] object-contain rounded-xl"
                        />
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
