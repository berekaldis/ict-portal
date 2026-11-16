import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import React, { useEffect, useState } from 'react';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    AlertCircle,
    AlertTriangle,
    ArrowLeft,
    Calendar,
    Camera,
    CheckCircle2,
    ClipboardCheck,
    HardDrive,
    Info,
    Laptop,
    Printer,
    SkipForward,
    Trash2,
    User,
    X,
} from 'lucide-react';

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
    const { can } = usePermission();
    const [selectedPhoto, setSelectedPhoto] = useState<string | null>(null);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === '1') {
            setTimeout(() => {
                window.print();
            }, 600);
        }
    }, []);

    const handleDelete = () => {
        router.delete(`/it-surveys/${report.id}`);
    };

    const getHealthBadge = (score: number, status: string) => {
        if (score >= 85 || status === 'Good') {
            return (
                <Badge variant="outline" className="border-emerald-300 text-emerald-800 bg-emerald-50 px-2.5 py-1 text-xs font-semibold gap-1.5">
                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                    {score}% • Good Condition
                </Badge>
            );
        }
        if (score >= 60 || status === 'Warning') {
            return (
                <Badge variant="outline" className="border-amber-300 text-amber-800 bg-amber-50 px-2.5 py-1 text-xs font-semibold gap-1.5">
                    <AlertTriangle className="w-3.5 h-3.5 text-amber-600" />
                    {score}% • Warning (Needs Attention)
                </Badge>
            );
        }
        return (
            <Badge variant="outline" className="border-rose-300 text-rose-800 bg-rose-50 px-2.5 py-1 text-xs font-semibold gap-1.5">
                <AlertCircle className="w-3.5 h-3.5 text-rose-600" />
                {score}% • Critical Issues
            </Badge>
        );
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'IT Survey & Audit', href: '/it-surveys' },
            { title: `${report.branch_name} Inspection`, href: `/it-surveys/${report.id}` },
        ]}>
            <Head title={`Audit Report - ${report.branch_name}`} />

            <div className="flex h-full flex-1 flex-col gap-5 p-4 md:p-6 max-w-5xl mx-auto w-full">
                {/* Print Controls / Action Bar */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 print:hidden">
                    <Link
                        href="/it-surveys"
                        className="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-slate-800 transition"
                    >
                        <ArrowLeft className="w-3.5 h-3.5" />
                        Back to Audits List
                    </Link>

                    <div className="flex items-center gap-2">
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => window.print()}
                            className="h-8 text-xs gap-1.5"
                        >
                            <Printer className="w-3.5 h-3.5 text-slate-600" />
                            Print Report
                        </Button>

                        {can('it_survey.create') && (
                            <Link href="/it-surveys/create">
                                <Button size="sm" className="h-8 text-xs">
                                    New Inspection
                                </Button>
                            </Link>
                        )}

                        {can('it_survey.manage') && (
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setDeleteDialogOpen(true)}
                                className="h-8 text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 border-rose-200"
                            >
                                <Trash2 className="w-3.5 h-3.5 mr-1" />
                                Delete
                            </Button>
                        )}
                    </div>
                </div>

                {/* Printable Header Card */}
                <Card className="border shadow-none">
                    <CardHeader className="pb-3 border-b border-slate-100">
                        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                            <div>
                                <div className="flex items-center gap-2">
                                    <h1 className="text-lg font-bold text-slate-900">{report.branch_name}</h1>
                                    <Badge variant="outline" className="capitalize text-xs">
                                        {report.type} Survey
                                    </Badge>
                                </div>
                                <div className="text-xs text-muted-foreground font-mono mt-0.5">
                                    UUID: {report.report_uuid}
                                </div>
                            </div>
                            <div>
                                {getHealthBadge(report.health_score, report.health_status)}
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="pt-4">
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                            <div className="border border-slate-100 rounded-md p-2.5 bg-slate-50/50">
                                <span className="text-muted-foreground">Inspector / Technician:</span>
                                <div className="font-semibold text-slate-800 mt-0.5">{report.technician_name}</div>
                            </div>
                            <div className="border border-slate-100 rounded-md p-2.5 bg-slate-50/50">
                                <span className="text-muted-foreground">Branch Manager:</span>
                                <div className="font-semibold text-slate-800 mt-0.5">{report.branch_manager}</div>
                            </div>
                            <div className="border border-slate-100 rounded-md p-2.5 bg-slate-50/50">
                                <span className="text-muted-foreground">Audit Date:</span>
                                <div className="font-semibold text-slate-800 mt-0.5">
                                    {new Date(report.submitted_at).toLocaleDateString('en-US', {
                                        month: 'short',
                                        day: 'numeric',
                                        year: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    })}
                                </div>
                            </div>
                            <div className="border border-slate-100 rounded-md p-2.5 bg-slate-50/50">
                                <span className="text-muted-foreground">Audit Results:</span>
                                <div className="font-semibold text-slate-800 mt-0.5">
                                    {report.passed_items} OK • {report.failed_items} Issues • {report.skipped_items} Skip
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Findings & Actions Summary */}
                {(report.issues_summary || report.actions_taken || report.recommendations) && (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <Card className="border shadow-none">
                            <CardHeader className="py-2.5 px-4 bg-slate-50/50 border-b border-slate-100">
                                <CardTitle className="text-xs font-semibold text-rose-700 flex items-center gap-1.5">
                                    <AlertCircle className="w-3.5 h-3.5" /> Issues Summary
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-3 text-xs text-slate-700 whitespace-pre-line leading-relaxed">
                                {report.issues_summary || 'No major issues recorded.'}
                            </CardContent>
                        </Card>

                        <Card className="border shadow-none">
                            <CardHeader className="py-2.5 px-4 bg-slate-50/50 border-b border-slate-100">
                                <CardTitle className="text-xs font-semibold text-indigo-700 flex items-center gap-1.5">
                                    <CheckCircle2 className="w-3.5 h-3.5" /> Actions Taken
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-3 text-xs text-slate-700 whitespace-pre-line leading-relaxed">
                                {report.actions_taken || 'No immediate corrective action taken.'}
                            </CardContent>
                        </Card>

                        <Card className="border shadow-none">
                            <CardHeader className="py-2.5 px-4 bg-slate-50/50 border-b border-slate-100">
                                <CardTitle className="text-xs font-semibold text-amber-700 flex items-center gap-1.5">
                                    <Info className="w-3.5 h-3.5" /> Recommendations
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-3 text-xs text-slate-700 whitespace-pre-line leading-relaxed">
                                {report.recommendations || 'No specific follow-up required.'}
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Inspection Photo Gallery */}
                {report.photos && report.photos.length > 0 && (
                    <Card className="border shadow-none print:hidden">
                        <CardHeader className="py-2.5 px-4 border-b border-slate-100">
                            <CardTitle className="text-xs font-semibold text-slate-900 flex items-center gap-1.5">
                                <Camera className="w-3.5 h-3.5 text-indigo-600" />
                                Inspection Evidence ({report.photos.length} photos)
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-3">
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                {report.photos.map((photo, i) => (
                                    <button
                                        key={i}
                                        type="button"
                                        onClick={() => setSelectedPhoto(photo)}
                                        className="relative rounded-md overflow-hidden border border-slate-200 aspect-video bg-slate-50 focus:outline-none hover:opacity-90 transition group"
                                    >
                                        <img
                                            src={photo}
                                            alt={`Evidence ${i + 1}`}
                                            className="w-full h-full object-cover"
                                        />
                                        <div className="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[11px] font-medium transition">
                                            Click to view
                                        </div>
                                    </button>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Detailed Checklist Table */}
                <Card className="border shadow-none overflow-hidden">
                    <CardHeader className="py-3 px-4 border-b border-slate-100">
                        <CardTitle className="text-xs font-semibold text-slate-900 flex items-center gap-1.5">
                            <ClipboardCheck className="w-3.5 h-3.5 text-indigo-600" />
                            Detailed Checklist Inspection Results
                        </CardTitle>
                    </CardHeader>
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-slate-50/75">
                                <TableHead className="text-xs font-semibold">Category</TableHead>
                                <TableHead className="text-xs font-semibold">Checklist Item / Question</TableHead>
                                <TableHead className="text-xs font-semibold">Status</TableHead>
                                <TableHead className="text-xs font-semibold">Findings & Details</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {report.checklist_data?.map((item, idx) => (
                                <TableRow key={idx} className="hover:bg-slate-50/60">
                                    <TableCell className="text-xs font-medium text-slate-900 whitespace-nowrap">
                                        {item.category_name}
                                    </TableCell>
                                    <TableCell className="text-xs text-slate-700">
                                        {item.question}
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap">
                                        {item.status === 'OK' ? (
                                            <Badge variant="outline" className="border-emerald-300 text-emerald-700 bg-emerald-50 text-[11px]">
                                                <CheckCircle2 className="w-3 h-3 mr-1 text-emerald-600" /> OK
                                            </Badge>
                                        ) : item.status === 'Issue' ? (
                                            <Badge variant="outline" className="border-rose-300 text-rose-700 bg-rose-50 text-[11px]">
                                                <AlertCircle className="w-3 h-3 mr-1 text-rose-600" /> Issue
                                            </Badge>
                                        ) : (
                                            <Badge variant="outline" className="border-slate-300 text-slate-600 bg-slate-50 text-[11px]">
                                                <SkipForward className="w-3 h-3 mr-1 text-slate-400" /> Skipped
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-xs text-slate-600">
                                        {item.status === 'Issue' && item.detail && (
                                            <span className="text-rose-700 font-medium">{item.detail}</span>
                                        )}
                                        {item.status === 'Skip' && item.skip_reason && (
                                            <span className="text-slate-500 italic">{item.skip_reason}</span>
                                        )}
                                        {item.status === 'OK' && (
                                            <span className="text-muted-foreground">Normal</span>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </Card>

                {/* Printable Sign-off Block (Visible only on print or clean bottom) */}
                <div className="hidden print:block border-t border-slate-300 pt-6 mt-6">
                    <div className="grid grid-cols-2 gap-8 text-xs text-slate-700">
                        <div>
                            <div className="font-semibold mb-6">Technician / Inspector:</div>
                            <div className="border-b border-slate-400 pb-1">{report.technician_name}</div>
                            <div className="mt-1 text-muted-foreground">Signature & Date</div>
                        </div>
                        <div>
                            <div className="font-semibold mb-6">Branch Manager Confirmation:</div>
                            <div className="border-b border-slate-400 pb-1">{report.branch_manager}</div>
                            <div className="mt-1 text-muted-foreground">Signature & Date</div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Photo Zoom Dialog */}
            <Dialog open={!!selectedPhoto} onOpenChange={() => setSelectedPhoto(null)}>
                <DialogContent className="sm:max-w-3xl p-2 bg-black/95 border-none">
                    <div className="relative flex items-center justify-center">
                        <img
                            src={selectedPhoto || ''}
                            alt="Evidence zoom"
                            className="max-h-[80vh] w-auto object-contain rounded-md"
                        />
                    </div>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle className="text-base font-semibold text-rose-600">Delete Audit Report</DialogTitle>
                        <DialogDescription className="text-xs">
                            Are you sure you want to delete this inspection report for{' '}
                            <span className="font-semibold text-slate-800">{report.branch_name}</span>? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setDeleteDialogOpen(false)}
                            className="h-8 text-xs"
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            onClick={handleDelete}
                            className="h-8 text-xs"
                        >
                            Delete Report
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
