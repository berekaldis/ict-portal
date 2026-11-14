import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Briefcase,
    Calendar,
    CheckCircle2,
    Clock,
    FileText,
    History,
    MessageSquare,
    Paperclip,
    Send,
    Tag,
    Trash2,
    User,
    UserCheck,
} from 'lucide-react';
import React, { useState } from 'react';

interface Props {
    caseItem: any;
    types: string[];
    assignees: string[];
}

export default function CaseShow({ caseItem, types, assignees }: Props) {
    const [statusModalOpen, setStatusModalOpen] = useState(false);
    const [selectedStatus, setSelectedStatus] = useState(caseItem.status);
    const [remarks, setRemarks] = useState(caseItem.remarks || '');
    const [assignee, setAssignee] = useState(caseItem.assigned_to || '');

    const { data: commentData, setData: setCommentData, post: postComment, processing: commentProcessing, reset: resetComment } = useForm({
        comment: '',
    });

    const handleCommentSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!commentData.comment.trim()) return;

        postComment(route('cases.comments.store', caseItem.id), {
            onSuccess: () => resetComment(),
        });
    };

    const handleStatusSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.put(
            route('cases.update', caseItem.id),
            {
                status: selectedStatus,
                remarks: remarks,
                assigned_to: assignee || undefined,
            },
            {
                onSuccess: () => setStatusModalOpen(false),
            }
        );
    };

    const handleDelete = () => {
        if (confirm(`Are you sure you want to delete case ${caseItem.case_id}?`)) {
            router.delete(route('cases.destroy', caseItem.id));
        }
    };

    const isOverdue = caseItem.due_date && caseItem.due_date < new Date().toISOString().split('T')[0] && !['Completed', 'Reject'].includes(caseItem.status);

    const getStatusBadge = (st: string) => {
        switch (st) {
            case 'Completed':
                return <Badge variant="secondary" className="bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 font-semibold px-2.5 py-0.5">Completed</Badge>;
            case 'Fixed':
                return <Badge variant="secondary" className="bg-blue-500/15 text-blue-700 dark:text-blue-400 font-semibold px-2.5 py-0.5">Fixed</Badge>;
            case 'In Progress':
                return <Badge variant="secondary" className="bg-amber-500/15 text-amber-700 dark:text-amber-400 font-semibold px-2.5 py-0.5">In Progress</Badge>;
            case 'Return':
                return <Badge variant="secondary" className="bg-orange-500/15 text-orange-700 dark:text-orange-400 font-semibold px-2.5 py-0.5">Return</Badge>;
            case 'Reject':
                return <Badge variant="destructive" className="font-semibold px-2.5 py-0.5">Reject</Badge>;
            default:
                return <Badge variant="outline" className="font-semibold px-2.5 py-0.5">Not Started</Badge>;
        }
    };

    const getPriorityBadge = (pr: string) => {
        switch (pr) {
            case 'Critical':
                return <Badge variant="destructive" className="font-bold">🔴 Critical</Badge>;
            case 'Medium':
                return <Badge variant="secondary" className="bg-amber-500/15 text-amber-700 dark:text-amber-400 font-semibold">🟡 Medium</Badge>;
            case 'Low':
                return <Badge variant="outline" className="font-medium text-muted-foreground">🟢 Low</Badge>;
            default:
                return <Badge variant="outline">{pr}</Badge>;
        }
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Cases', href: '/cases' }, { title: caseItem.case_id }]}>
            <Head title={`Case ${caseItem.case_id}`} />

            <div className="space-y-6 max-w-5xl mx-auto">
                {/* Top Nav & Action Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Button asChild variant="outline" size="icon" className="h-9 w-9">
                            <Link href={route('cases.index')}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-bold font-mono text-primary">{caseItem.case_id}</h1>
                                {getStatusBadge(caseItem.status)}
                                {getPriorityBadge(caseItem.priority)}
                            </div>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                Type: <strong>{caseItem.case_type}</strong> &bull; Created by {caseItem.created_by} on {new Date(caseItem.created_date).toLocaleDateString()}
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button onClick={() => setStatusModalOpen(true)} className="h-9">
                            Update Status / Assignee
                        </Button>
                        <Button variant="ghost" size="icon" onClick={handleDelete} className="text-destructive h-9 w-9">
                            <Trash2 className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                {/* Overdue Alert */}
                {isOverdue && (
                    <div className="p-3.5 rounded-xl border border-destructive/40 bg-destructive/10 text-destructive text-xs flex items-center gap-2 font-medium">
                        <AlertCircle className="h-4 w-4 shrink-0" />
                        <span>This case is past its SLA due date ({new Date(caseItem.due_date).toLocaleDateString()}). Please expedite resolution.</span>
                    </div>
                )}

                {/* Overview Cards */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {/* Left Column: Details & Comments */}
                    <div className="md:col-span-2 space-y-6">
                        {/* Description */}
                        <Card className="border-border/60 shadow-xs">
                            <CardHeader className="py-3 px-5 border-b border-border/40">
                                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                    <FileText className="h-4 w-4 text-primary" /> Case Description
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-5 space-y-4 text-sm leading-relaxed text-foreground whitespace-pre-wrap">
                                {caseItem.description}

                                {caseItem.remarks && (
                                    <div className="mt-4 p-3 rounded-lg bg-muted/40 border border-border/50 text-xs">
                                        <div className="font-semibold text-foreground mb-1">Remarks / Resolution Notes:</div>
                                        <div className="text-muted-foreground">{caseItem.remarks}</div>
                                    </div>
                                )}

                                {caseItem.attachments && (
                                    <div className="mt-4 pt-3 border-t border-border/40">
                                        <div className="text-xs font-semibold text-foreground mb-1 flex items-center gap-1.5">
                                            <Paperclip className="h-3.5 w-3.5 text-primary" /> Attachment:
                                        </div>
                                        <a
                                            href={`/storage/${caseItem.attachments}`}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-xs text-primary hover:underline font-mono"
                                        >
                                            View Uploaded File
                                        </a>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Comments Section */}
                        <Card className="border-border/60 shadow-xs">
                            <CardHeader className="py-3 px-5 border-b border-border/40">
                                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                    <MessageSquare className="h-4 w-4 text-primary" /> Support Notes & Comments ({caseItem.comments?.length || 0})
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-5 space-y-4">
                                <form onSubmit={handleCommentSubmit} className="space-y-2">
                                    <Textarea
                                        placeholder="Add an internal support note or status update..."
                                        value={commentData.comment}
                                        onChange={(e) => setCommentData('comment', e.target.value)}
                                        rows={3}
                                        className="text-xs"
                                    />
                                    <div className="flex justify-end">
                                        <Button type="submit" size="sm" disabled={commentProcessing} className="text-xs h-8">
                                            <Send className="h-3.5 w-3.5 mr-1" /> Post Note
                                        </Button>
                                    </div>
                                </form>

                                <div className="space-y-3 pt-2">
                                    {caseItem.comments && caseItem.comments.length > 0 ? (
                                        caseItem.comments.map((cm: any) => (
                                            <div key={cm.id} className="p-3 rounded-lg border border-border/40 bg-muted/20 space-y-1">
                                                <div className="flex items-center justify-between text-xs">
                                                    <span className="font-semibold text-foreground">{cm.author}</span>
                                                    <span className="text-[10px] text-muted-foreground font-mono">
                                                        {new Date(cm.created_at).toLocaleString()}
                                                    </span>
                                                </div>
                                                <p className="text-xs text-muted-foreground whitespace-pre-wrap">{cm.comment}</p>
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-center text-xs text-muted-foreground py-3">No notes posted yet.</p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Column: Case Meta & Audit Trail */}
                    <div className="space-y-6">
                        {/* Meta Card */}
                        <Card className="border-border/60 shadow-xs">
                            <CardHeader className="py-3 px-4 border-b border-border/40">
                                <CardTitle className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                    Case Properties
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3 text-xs">
                                <div className="flex items-center justify-between py-1 border-b border-border/30">
                                    <span className="text-muted-foreground flex items-center gap-1.5">
                                        <UserCheck className="h-3.5 w-3.5 text-muted-foreground" /> Assignee:
                                    </span>
                                    <span className="font-medium text-foreground">{caseItem.assigned_to || 'Unassigned'}</span>
                                </div>

                                <div className="flex items-center justify-between py-1 border-b border-border/30">
                                    <span className="text-muted-foreground flex items-center gap-1.5">
                                        <Calendar className="h-3.5 w-3.5 text-muted-foreground" /> Due Date:
                                    </span>
                                    <span className={`font-mono ${isOverdue ? 'text-destructive font-bold' : 'text-foreground'}`}>
                                        {caseItem.due_date ? new Date(caseItem.due_date).toLocaleDateString() : 'Not Set'}
                                    </span>
                                </div>

                                <div className="flex items-center justify-between py-1 border-b border-border/30">
                                    <span className="text-muted-foreground flex items-center gap-1.5">
                                        <Tag className="h-3.5 w-3.5 text-muted-foreground" /> Category:
                                    </span>
                                    <span className="font-medium text-foreground">{caseItem.case_type}</span>
                                </div>

                                <div className="flex items-center justify-between py-1">
                                    <span className="text-muted-foreground flex items-center gap-1.5">
                                        <User className="h-3.5 w-3.5 text-muted-foreground" /> Reported By:
                                    </span>
                                    <span className="font-medium text-foreground">{caseItem.created_by}</span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Audit Trail Timeline */}
                        <Card className="border-border/60 shadow-xs">
                            <CardHeader className="py-3 px-4 border-b border-border/40">
                                <CardTitle className="text-xs font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                    <History className="h-3.5 w-3.5" /> Audit History
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4">
                                <div className="space-y-3 relative pl-3 border-l-2 border-border/50">
                                    {caseItem.history && caseItem.history.map((h: any) => (
                                        <div key={h.id} className="relative text-xs space-y-0.5">
                                            <div className="absolute -left-[19px] top-1 h-2 w-2 rounded-full bg-primary" />
                                            <div className="flex items-center justify-between">
                                                <span className="font-semibold text-foreground">{h.action}</span>
                                                <span className="text-[10px] text-muted-foreground font-mono">
                                                    {new Date(h.timestamp).toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                                                </span>
                                            </div>
                                            <div className="text-[11px] text-muted-foreground">{h.details}</div>
                                            <div className="text-[10px] text-muted-foreground/80 italic">By {h.user}</div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            {/* Update Status Modal */}
            <Dialog open={statusModalOpen} onOpenChange={setStatusModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Update Case {caseItem.case_id}</DialogTitle>
                        <DialogDescription>
                            Change resolution status or reassign ticket.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleStatusSubmit} className="space-y-3 py-2">
                        <div className="space-y-1.5">
                            <Label>Status</Label>
                            <Select value={selectedStatus} onValueChange={setSelectedStatus}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Not Started">Not Started</SelectItem>
                                    <SelectItem value="In Progress">In Progress</SelectItem>
                                    <SelectItem value="Fixed">Fixed</SelectItem>
                                    <SelectItem value="Return">Return</SelectItem>
                                    <SelectItem value="Reject">Reject</SelectItem>
                                    <SelectItem value="Completed">Completed</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label>Assignee</Label>
                            <Select value={assignee} onValueChange={setAssignee}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select assignee" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Unassigned">Unassigned</SelectItem>
                                    {assignees.map((a) => (
                                        <SelectItem key={a} value={a}>{a}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label>Remarks / Resolution Notes</Label>
                            <Textarea
                                placeholder="Add notes regarding this status change..."
                                value={remarks}
                                onChange={(e) => setRemarks(e.target.value)}
                                rows={3}
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setStatusModalOpen(false)}>Cancel</Button>
                            <Button type="submit">Save Changes</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
