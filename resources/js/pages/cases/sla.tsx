import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Clock,
    Eye,
    Send,
    ShieldAlert,
} from 'lucide-react';
import React from 'react';

interface Props {
    slaData: {
        totalOpen: number;
        totalOverdue: number;
        criticalCount: number;
        mediumCount: number;
        lowCount: number;
        cases: any[];
    };
}

export default function CaseSla({ slaData }: Props) {
    const handleSendReport = () => {
        router.post(route('cases.sla.report'));
    };

    const todayStr = new Date().toISOString().split('T')[0];

    return (
        <AppLayout breadcrumbs={[{ title: 'Cases', href: '/cases' }, { title: 'SLA Tracking' }]}>
            <Head title="SLA & Escalations - Cases" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Button asChild variant="outline" size="icon" className="h-9 w-9">
                            <Link href={route('cases.index')}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                                <Clock className="h-5 w-5 text-amber-600" /> SLA & Escalation Dashboard
                            </h1>
                            <p className="text-xs text-muted-foreground">
                                Monitor service level agreements, overdue tickets, and critical incident alerts
                            </p>
                        </div>
                    </div>

                    <Button onClick={handleSendReport} variant="default" className="h-9 font-medium">
                        <Send className="h-3.5 w-3.5 mr-1.5" /> Dispatch SLA Report to Telegram
                    </Button>
                </div>

                {/* KPI Metrics */}
                <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <Card className="border-border/60">
                        <CardContent className="p-4 space-y-1">
                            <p className="text-xs font-medium text-muted-foreground">Active Open</p>
                            <p className="text-2xl font-bold text-foreground">{slaData.totalOpen}</p>
                            <p className="text-[11px] text-muted-foreground">Pending resolution</p>
                        </CardContent>
                    </Card>

                    <Card className="border-amber-500/20 bg-amber-500/5">
                        <CardContent className="p-4 space-y-1">
                            <p className="text-xs font-semibold text-amber-600 dark:text-amber-400">Overdue SLA</p>
                            <p className="text-2xl font-bold text-amber-600 dark:text-amber-400">{slaData.totalOverdue}</p>
                            <p className="text-[11px] text-muted-foreground">Breached due date</p>
                        </CardContent>
                    </Card>

                    <Card className="border-rose-500/20 bg-rose-500/5">
                        <CardContent className="p-4 space-y-1">
                            <p className="text-xs font-semibold text-rose-600 dark:text-rose-400">Critical Priority</p>
                            <p className="text-2xl font-bold text-rose-600 dark:text-rose-400">{slaData.criticalCount}</p>
                            <p className="text-[11px] text-muted-foreground">Urgent attention</p>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60">
                        <CardContent className="p-4 space-y-1">
                            <p className="text-xs font-medium text-muted-foreground">Medium Priority</p>
                            <p className="text-2xl font-bold text-amber-600">{slaData.mediumCount}</p>
                            <p className="text-[11px] text-muted-foreground">Standard SLA</p>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60">
                        <CardContent className="p-4 space-y-1">
                            <p className="text-xs font-medium text-muted-foreground">Low Priority</p>
                            <p className="text-2xl font-bold text-zinc-600 dark:text-zinc-400">{slaData.lowCount}</p>
                            <p className="text-[11px] text-muted-foreground">Minor requests</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Overdue & Open Tickets Table */}
                <Card className="border-border/60 shadow-xs">
                    <CardHeader className="py-3.5 px-5 border-b border-border/40">
                        <CardTitle className="text-sm font-semibold">Active Unresolved Cases</CardTitle>
                        <CardDescription className="text-xs">
                            Sorted by urgency and due date
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader className="bg-muted/30">
                                <TableRow>
                                    <TableHead className="w-[140px]">Case ID</TableHead>
                                    <TableHead className="w-[120px]">Priority</TableHead>
                                    <TableHead className="w-[120px]">Status</TableHead>
                                    <TableHead className="min-w-[200px]">Description</TableHead>
                                    <TableHead className="w-[140px]">Assignee</TableHead>
                                    <TableHead className="w-[130px]">Target Due Date</TableHead>
                                    <TableHead className="w-[80px] text-right">View</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {slaData.cases.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center py-8 text-xs text-muted-foreground">
                                            No active open cases. All SLAs met! 🎉
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    slaData.cases.map((c: any) => {
                                        const isOverdue = c.due_date && c.due_date < todayStr;
                                        return (
                                            <TableRow key={c.id} className={isOverdue ? 'bg-destructive/5' : undefined}>
                                                <TableCell className="font-mono text-xs font-bold text-primary">
                                                    <Link href={route('cases.show', c.id)} className="hover:underline">
                                                        {c.case_id}
                                                    </Link>
                                                </TableCell>
                                                <TableCell>
                                                    {c.priority === 'Critical' ? (
                                                        <span className="text-xs font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                                            🔴 Critical
                                                        </span>
                                                    ) : c.priority === 'Medium' ? (
                                                        <span className="text-xs font-medium text-amber-600 dark:text-amber-400">🟡 Medium</span>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground">🟢 Low</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline" className="text-[11px]">{c.status}</Badge>
                                                </TableCell>
                                                <TableCell className="text-xs text-foreground line-clamp-1 max-w-sm">
                                                    {c.description}
                                                </TableCell>
                                                <TableCell className="text-xs font-medium">
                                                    {c.assigned_to || 'Unassigned'}
                                                </TableCell>
                                                <TableCell className="text-xs font-mono">
                                                    {c.due_date ? (
                                                        <span className={isOverdue ? 'text-destructive font-bold flex items-center gap-1' : 'text-muted-foreground'}>
                                                            {isOverdue && <AlertCircle className="h-3 w-3" />}
                                                            {new Date(c.due_date).toLocaleDateString()}
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted-foreground">None</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button asChild variant="ghost" size="icon" className="h-8 w-8 text-primary">
                                                        <Link href={route('cases.show', c.id)}>
                                                            <Eye className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
