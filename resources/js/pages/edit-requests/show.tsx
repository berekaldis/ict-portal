import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { usePermission } from '@/hooks/user-permissions';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    CheckCircle2,
    Clock,
    FileText,
    History,
    Trash2,
    X,
} from 'lucide-react';
import React from 'react';

interface Props {
    requestItem: any;
    logs: any[];
}

export default function EditRequestShow({ requestItem, logs }: Props) {
    const { can } = usePermission();

    const isOHD = requestItem.action_type === 'On Hand Delete';
    const canApprove = can('edit_request.approve');
    const canMarkDone = can('edit_request.mark_done');

    const handleStatus = (newStatus: string) => {
        router.put(route('edit-requests.status', requestItem.id), { status: newStatus });
    };

    const handleMarkDone = () => {
        router.put(route('edit-requests.mark-done', requestItem.id));
    };

    const handleDelete = () => {
        if (confirm(`Delete request ${requestItem.request_id}?`)) {
            router.delete(route('edit-requests.destroy', requestItem.id));
        }
    };

    const getStatusBadge = (st: string) => {
        switch (st) {
            case 'Done':
                return <Badge variant="secondary" className="bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 font-semibold px-2.5 py-0.5">Completed (Done) ✅</Badge>;
            case 'Approved':
                return <Badge variant="secondary" className="bg-blue-500/15 text-blue-700 dark:text-blue-400 font-semibold px-2.5 py-0.5">Approved</Badge>;
            case 'Pending':
                return <Badge variant="secondary" className="bg-amber-500/15 text-amber-700 dark:text-amber-400 font-semibold px-2.5 py-0.5">Pending Review</Badge>;
            case 'Rejected':
                return <Badge variant="destructive" className="font-semibold px-2.5 py-0.5">Rejected</Badge>;
            default:
                return <Badge variant="outline">{st}</Badge>;
        }
    };

    const step2Done = requestItem.status === 'Approved' || requestItem.status === 'Done';
    const step2Rejected = requestItem.status === 'Rejected';
    const step3Done = requestItem.status === 'Done';

    return (
        <AppLayout breadcrumbs={[{ title: 'Voucher Requests', href: '/edit-requests' }, { title: requestItem.request_id }]}>
            <Head title={`Request ${requestItem.request_id}`} />

            <div className="space-y-5 max-w-4xl mx-auto py-2">
                {/* Header Action Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-border/40">
                    <div className="flex items-center gap-3">
                        <Button asChild variant="outline" size="icon" className="h-9 w-9 shrink-0">
                            <Link href={route('edit-requests.index')}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-bold font-mono text-primary">{requestItem.request_id}</h1>
                                {getStatusBadge(requestItem.status)}
                                <Badge variant="outline" className="text-xs">{requestItem.action_type}</Badge>
                            </div>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                Branch: <span className="font-semibold text-foreground">{requestItem.branch}</span> &bull; Submitted {new Date(requestItem.timestamp).toLocaleString()}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        {requestItem.status === 'Pending' && canApprove && (
                            <>
                                <Button
                                    onClick={() => handleStatus('Approved')}
                                    className="bg-emerald-600 hover:bg-emerald-700 text-white h-9"
                                    size="sm"
                                >
                                    <Check className="h-4 w-4 mr-1.5" /> Approve
                                </Button>
                                <Button
                                    onClick={() => handleStatus('Rejected')}
                                    variant="outline"
                                    className="text-destructive border-destructive/40 hover:bg-destructive/10 h-9"
                                    size="sm"
                                >
                                    <X className="h-4 w-4 mr-1.5" /> Reject
                                </Button>
                            </>
                        )}

                        {requestItem.status === 'Approved' && canMarkDone && (
                            <Button
                                onClick={handleMarkDone}
                                className="bg-emerald-600 hover:bg-emerald-700 text-white h-9"
                                size="sm"
                            >
                                <CheckCircle2 className="h-4 w-4 mr-1.5" /> Mark Executed (Done)
                            </Button>
                        )}

                        {can('edit_request.delete') && (
                            <Button variant="ghost" size="icon" onClick={handleDelete} className="text-destructive hover:bg-destructive/10 h-9 w-9" title="Delete Request">
                                <Trash2 className="h-4 w-4" />
                            </Button>
                        )}
                    </div>
                </div>

                {/* Streamlined Stepper Banner */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div className="p-3 rounded-lg border border-border/50 bg-muted/20 flex items-center gap-3">
                        <div className="h-6 w-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold shrink-0">
                            ✓
                        </div>
                        <div className="text-xs min-w-0">
                            <div className="font-semibold text-foreground">1. Submitted</div>
                            <div className="text-muted-foreground truncate">{requestItem.user_name} ({requestItem.branch})</div>
                        </div>
                    </div>

                    <div className={`p-3 rounded-lg border flex items-center gap-3 ${
                        step2Done
                            ? 'bg-blue-500/10 border-blue-500/30'
                            : step2Rejected
                            ? 'bg-destructive/10 border-destructive/30'
                            : 'bg-muted/10 border-border/40'
                    }`}>
                        <div className={`h-6 w-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 ${
                            step2Done
                                ? 'bg-blue-600 text-white'
                                : step2Rejected
                                ? 'bg-destructive text-white'
                                : 'bg-muted text-muted-foreground'
                        }`}>
                            {step2Done ? '✓' : step2Rejected ? '✕' : '2'}
                        </div>
                        <div className="text-xs min-w-0">
                            <div className="font-semibold text-foreground">2. Department Review</div>
                            <div className="text-muted-foreground truncate">
                                {step2Done ? `Approved by ${requestItem.approver || 'Manager'}` : step2Rejected ? 'Rejected' : `Pending: ${requestItem.target_dept}`}
                            </div>
                        </div>
                    </div>

                    <div className={`p-3 rounded-lg border flex items-center gap-3 ${
                        step3Done
                            ? 'bg-emerald-500/10 border-emerald-500/30'
                            : 'bg-muted/10 border-border/40'
                    }`}>
                        <div className={`h-6 w-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 ${
                            step3Done ? 'bg-emerald-600 text-white' : 'bg-muted text-muted-foreground'
                        }`}>
                            {step3Done ? '✓' : '3'}
                        </div>
                        <div className="text-xs min-w-0">
                            <div className="font-semibold text-foreground">3. IT Execution</div>
                            <div className="text-muted-foreground truncate">
                                {step3Done ? 'Executed by IT' : isOHD ? 'Pending Deletion' : 'Pending IT'}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Clean Unified Details Card */}
                <Card className="border-border/60 shadow-xs">
                    <CardHeader className="py-3 px-5 border-b border-border/40">
                        <CardTitle className="text-sm font-semibold flex items-center gap-2">
                            <FileText className="h-4 w-4 text-primary" /> Request Details
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-5 space-y-4">
                        {/* Key-Value Specs Grid */}
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                            <div className="space-y-1">
                                <span className="text-muted-foreground">Branch</span>
                                <div className="font-semibold text-foreground text-sm">{requestItem.branch}</div>
                            </div>

                            <div className="space-y-1">
                                <span className="text-muted-foreground">Submitted By</span>
                                <div className="font-semibold text-foreground text-sm">{requestItem.user_name}</div>
                            </div>

                            <div className="space-y-1">
                                <span className="text-muted-foreground">Target Department</span>
                                <div className="font-semibold text-foreground text-sm">{requestItem.target_dept}</div>
                            </div>

                            <div className="space-y-1">
                                <span className="text-muted-foreground">Action Type</span>
                                <div className="font-semibold text-foreground text-sm">{requestItem.action_type}</div>
                            </div>

                            {isOHD ? (
                                <>
                                    <div className="space-y-1">
                                        <span className="text-muted-foreground">Store User / Keeper</span>
                                        <div className="font-semibold text-foreground text-sm">{requestItem.store_user || '-'}</div>
                                    </div>

                                    <div className="space-y-1">
                                        <span className="text-muted-foreground">Shift</span>
                                        <div className="font-semibold text-foreground text-sm">{requestItem.shift || 'Shift 1'}</div>
                                    </div>

                                    <div className="space-y-1 sm:col-span-2">
                                        <span className="text-muted-foreground">Deletion Date</span>
                                        <div className="font-semibold text-foreground text-sm">{requestItem.correct_date || '-'}</div>
                                    </div>
                                </>
                            ) : (
                                <>
                                    <div className="space-y-1">
                                        <span className="text-muted-foreground">Voucher Code</span>
                                        <div className="font-mono font-semibold text-foreground text-sm">{requestItem.voucher_code || '-'}</div>
                                    </div>

                                    <div className="space-y-1">
                                        <span className="text-muted-foreground">Voucher Type</span>
                                        <div className="font-semibold text-foreground text-sm">{requestItem.voucher_type || '-'}</div>
                                    </div>

                                    <div className="space-y-1">
                                        <span className="text-muted-foreground">Edit Type</span>
                                        <div className="font-semibold text-foreground text-sm">{requestItem.edit_type || '-'}</div>
                                    </div>

                                    {(requestItem.incorrect_date || requestItem.correct_date) && (
                                        <div className="space-y-1">
                                            <span className="text-muted-foreground">Date Change</span>
                                            <div className="font-mono text-sm font-semibold text-foreground">
                                                {requestItem.incorrect_date || '-'} ➔ {requestItem.correct_date || '-'}
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}
                        </div>

                        {/* Reason / Notes */}
                        <div className="pt-3 border-t border-border/40 space-y-1">
                            <span className="text-xs font-semibold text-muted-foreground">Reason & Explanation</span>
                            <div className="p-3 rounded-lg bg-muted/30 border border-border/40 text-xs text-foreground leading-relaxed whitespace-pre-wrap">
                                {requestItem.reason || 'No explanation provided.'}
                            </div>
                        </div>

                        {/* Minimalist Activity Timeline */}
                        {logs && logs.length > 0 && (
                            <div className="pt-3 border-t border-border/40 space-y-2">
                                <span className="text-xs font-semibold text-muted-foreground flex items-center gap-1.5">
                                    <History className="h-3.5 w-3.5" /> Recent Activity
                                </span>
                                <div className="space-y-1.5">
                                    {logs.map((log) => (
                                        <div key={log.id} className="flex items-center justify-between text-xs py-1 px-2.5 rounded bg-muted/20 border border-border/30">
                                            <div className="flex items-center gap-2">
                                                <span className="font-medium text-foreground">{log.action}</span>
                                                <span className="text-muted-foreground">&bull; by {log.actor}</span>
                                            </div>
                                            <span className="text-[11px] text-muted-foreground font-mono">
                                                {new Date(log.timestamp).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
