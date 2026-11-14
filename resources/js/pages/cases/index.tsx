import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowUpDown,
    Briefcase,
    CheckCircle2,
    Clock,
    Download,
    Eye,
    Filter,
    PlusCircle,
    RefreshCw,
    Search,
    Trash2,
    UserCheck,
    X,
} from 'lucide-react';
import React, { useState } from 'react';

interface CaseItem {
    id: number;
    case_id: string;
    case_type: string;
    description: string;
    created_date: string;
    due_date?: string;
    status: string;
    priority: string;
    assigned_to?: string;
    created_by: string;
    remarks?: string;
}

interface Props {
    cases: {
        data: CaseItem[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    filters: any;
    types: string[];
    assignees: string[];
}

export default function CasesIndex({ cases, filters, types, assignees }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'All');
    const [priority, setPriority] = useState(filters.priority || 'All');
    const [caseType, setCaseType] = useState(filters.case_type || 'All');
    const [assignedTo, setAssignedTo] = useState(filters.assigned_to || 'All');
    const [assignedToMe, setAssignedToMe] = useState(!!filters.assignedToMe);

    // Bulk selection
    const [selectedIds, setSelectedIds] = useState<string[]>([]);
    const [bulkModalOpen, setBulkModalOpen] = useState(false);
    const [bulkStatus, setBulkStatus] = useState('');
    const [bulkAssignee, setBulkAssignee] = useState('');
    const [bulkRemarks, setBulkRemarks] = useState('');

    const applyFilter = (newOverrides: Record<string, any> = {}) => {
        router.get(
            route('cases.index'),
            {
                search,
                status,
                priority,
                case_type: caseType,
                assigned_to: assignedTo,
                assignedToMe: assignedToMe ? 1 : undefined,
                ...newOverrides,
            },
            { preserveState: true, replace: true }
        );
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilter();
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('All');
        setPriority('All');
        setCaseType('All');
        setAssignedTo('All');
        setAssignedToMe(false);
        router.get(route('cases.index'), {}, { replace: true });
    };

    const toggleSelectAll = () => {
        if (selectedIds.length === cases.data.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(cases.data.map((c) => c.case_id));
        }
    };

    const toggleSelectCase = (caseId: string) => {
        if (selectedIds.includes(caseId)) {
            setSelectedIds(selectedIds.filter((id) => id !== caseId));
        } else {
            setSelectedIds([...selectedIds, caseId]);
        }
    };

    const submitBulkUpdate = () => {
        if (selectedIds.length === 0) return;

        router.post(
            route('cases.bulk-update'),
            {
                case_ids: selectedIds,
                status: bulkStatus || undefined,
                assigned_to: bulkAssignee || undefined,
                remarks: bulkRemarks || undefined,
            },
            {
                onSuccess: () => {
                    setBulkModalOpen(false);
                    setSelectedIds([]);
                    setBulkStatus('');
                    setBulkAssignee('');
                    setBulkRemarks('');
                },
            }
        );
    };

    const getStatusBadge = (st: string) => {
        switch (st) {
            case 'Completed':
                return <Badge variant="secondary" className="bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 font-medium">Completed</Badge>;
            case 'Fixed':
                return <Badge variant="secondary" className="bg-blue-500/15 text-blue-700 dark:text-blue-400 font-medium">Fixed</Badge>;
            case 'In Progress':
                return <Badge variant="secondary" className="bg-amber-500/15 text-amber-700 dark:text-amber-400 font-medium">In Progress</Badge>;
            case 'Return':
                return <Badge variant="secondary" className="bg-orange-500/15 text-orange-700 dark:text-orange-400 font-medium">Return</Badge>;
            case 'Reject':
                return <Badge variant="destructive">Reject</Badge>;
            default:
                return <Badge variant="outline">Not Started</Badge>;
        }
    };

    const getPriorityBadge = (pr: string) => {
        switch (pr) {
            case 'Critical':
                return <span className="inline-flex items-center text-xs font-semibold text-rose-600 dark:text-rose-400">🔴 Critical</span>;
            case 'Medium':
                return <span className="inline-flex items-center text-xs font-medium text-amber-600 dark:text-amber-400">🟡 Medium</span>;
            case 'Low':
                return <span className="inline-flex items-center text-xs text-muted-foreground">🟢 Low</span>;
            default:
                return <span>{pr}</span>;
        }
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Case Management', href: '/cases' }, { title: 'All Cases' }]}>
            <Head title="Cases - IT Support" />

            <div className="space-y-4">
                {/* Header Actions */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div className="flex items-center gap-2">
                            <Briefcase className="h-5 w-5 text-primary" />
                            <h2 className="text-xl font-bold tracking-tight text-foreground">IT Case Management</h2>
                            <Badge variant="secondary" className="bg-primary/10 text-primary text-xs">
                                {cases.total} Total
                            </Badge>
                        </div>
                        <p className="text-xs text-muted-foreground mt-0.5">
                            Track, assign, and resolve CNET & system support tickets
                        </p>
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                        <Button asChild variant="outline" size="sm" className="h-9">
                            <a href={route('cases.export')} target="_blank" rel="noreferrer">
                                <Download className="h-3.5 w-3.5 mr-1" /> Export CSV
                            </a>
                        </Button>
                        <Button asChild size="sm" className="h-9">
                            <Link href={route('cases.create')}>
                                <PlusCircle className="h-4 w-4 mr-1.5" /> Log New Case
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Filter Toolbar */}
                <div className="p-3.5 rounded-xl border border-border/60 bg-card shadow-xs space-y-3">
                    <form onSubmit={handleSearch} className="flex flex-col md:flex-row gap-2.5 items-stretch md:items-center">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search by case ID, description, assignee, creator..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9 h-9"
                            />
                        </div>

                        <div className="flex items-center gap-2 flex-wrap">
                            <Select
                                value={status}
                                onValueChange={(val) => {
                                    setStatus(val);
                                    applyFilter({ status: val });
                                }}
                            >
                                <SelectTrigger className="w-[130px] h-9 text-xs">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="All">All Statuses</SelectItem>
                                    <SelectItem value="Not Started">Not Started</SelectItem>
                                    <SelectItem value="In Progress">In Progress</SelectItem>
                                    <SelectItem value="Fixed">Fixed</SelectItem>
                                    <SelectItem value="Return">Return</SelectItem>
                                    <SelectItem value="Reject">Reject</SelectItem>
                                    <SelectItem value="Completed">Completed</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select
                                value={priority}
                                onValueChange={(val) => {
                                    setPriority(val);
                                    applyFilter({ priority: val });
                                }}
                            >
                                <SelectTrigger className="w-[120px] h-9 text-xs">
                                    <SelectValue placeholder="Priority" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="All">All Priorities</SelectItem>
                                    <SelectItem value="Critical">🔴 Critical</SelectItem>
                                    <SelectItem value="Medium">🟡 Medium</SelectItem>
                                    <SelectItem value="Low">🟢 Low</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select
                                value={caseType}
                                onValueChange={(val) => {
                                    setCaseType(val);
                                    applyFilter({ case_type: val });
                                }}
                            >
                                <SelectTrigger className="w-[130px] h-9 text-xs">
                                    <SelectValue placeholder="Case Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="All">All Types</SelectItem>
                                    {types.map((t) => (
                                        <SelectItem key={t} value={t}>{t}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Button type="submit" variant="secondary" size="sm" className="h-9 text-xs">
                                <Filter className="h-3.5 w-3.5 mr-1" /> Filter
                            </Button>

                            <Button
                                type="button"
                                variant={assignedToMe ? 'default' : 'outline'}
                                size="sm"
                                onClick={() => {
                                    const next = !assignedToMe;
                                    setAssignedToMe(next);
                                    applyFilter({ assignedToMe: next ? 1 : undefined });
                                }}
                                className="h-9 text-xs"
                            >
                                <UserCheck className="h-3.5 w-3.5 mr-1" /> Assigned to Me
                            </Button>

                            {(search || status !== 'All' || priority !== 'All' || caseType !== 'All' || assignedTo !== 'All' || assignedToMe) && (
                                <Button type="button" variant="ghost" size="sm" onClick={clearFilters} className="h-9 text-xs text-muted-foreground">
                                    <X className="h-3.5 w-3.5 mr-1" /> Reset
                                </Button>
                            )}
                        </div>
                    </form>
                </div>

                {/* Bulk Action Banner */}
                {selectedIds.length > 0 && (
                    <div className="flex items-center justify-between p-3 rounded-xl bg-primary/10 border border-primary/30 text-xs">
                        <span className="font-semibold text-primary">
                            {selectedIds.length} case{selectedIds.length > 1 ? 's' : ''} selected
                        </span>
                        <div className="flex items-center gap-2">
                            <Button size="sm" variant="default" onClick={() => setBulkModalOpen(true)} className="h-7 text-xs">
                                Bulk Update
                            </Button>
                            <Button size="sm" variant="ghost" onClick={() => setSelectedIds([])} className="h-7 text-xs">
                                Deselect All
                            </Button>
                        </div>
                    </div>
                )}

                {/* Cases Table */}
                <div className="rounded-xl border border-border/60 bg-card overflow-hidden shadow-xs">
                    <Table>
                        <TableHeader className="bg-muted/30">
                            <TableRow>
                                <TableHead className="w-[40px]">
                                    <Checkbox
                                        checked={cases.data.length > 0 && selectedIds.length === cases.data.length}
                                        onCheckedChange={toggleSelectAll}
                                    />
                                </TableHead>
                                <TableHead className="w-[140px]">Case ID</TableHead>
                                <TableHead className="w-[120px]">Type</TableHead>
                                <TableHead className="min-w-[200px]">Description</TableHead>
                                <TableHead className="w-[100px]">Priority</TableHead>
                                <TableHead className="w-[120px]">Status</TableHead>
                                <TableHead className="w-[130px]">Assignee</TableHead>
                                <TableHead className="w-[110px]">Due Date</TableHead>
                                <TableHead className="w-[80px] text-right">Action</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {cases.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={9} className="text-center py-8 text-xs text-muted-foreground">
                                        No cases match the selected criteria.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                cases.data.map((c) => {
                                    const isSelected = selectedIds.includes(c.case_id);
                                    const isOverdue = c.due_date && c.due_date < new Date().toISOString().split('T')[0] && !['Completed', 'Reject'].includes(c.status);

                                    return (
                                        <TableRow key={c.id} className={isSelected ? 'bg-primary/5' : undefined}>
                                            <TableCell>
                                                <Checkbox
                                                    checked={isSelected}
                                                    onCheckedChange={() => toggleSelectCase(c.case_id)}
                                                />
                                            </TableCell>
                                            <TableCell className="font-mono text-xs font-bold text-primary">
                                                <Link href={route('cases.show', c.id)} className="hover:underline">
                                                    {c.case_id}
                                                </Link>
                                            </TableCell>
                                            <TableCell className="text-xs font-medium text-foreground/90">
                                                {c.case_type}
                                            </TableCell>
                                            <TableCell className="text-xs text-foreground/80">
                                                <div className="line-clamp-2 max-w-md">{c.description}</div>
                                                {c.remarks && (
                                                    <div className="text-[11px] text-muted-foreground italic mt-0.5 line-clamp-1">
                                                        Remarks: {c.remarks}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell>{getPriorityBadge(c.priority)}</TableCell>
                                            <TableCell>{getStatusBadge(c.status)}</TableCell>
                                            <TableCell className="text-xs">
                                                {c.assigned_to ? (
                                                    <span className="font-medium text-foreground">{c.assigned_to}</span>
                                                ) : (
                                                    <span className="text-muted-foreground italic">Unassigned</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-xs font-mono">
                                                {c.due_date ? (
                                                    <span className={isOverdue ? 'text-destructive font-bold flex items-center gap-1' : 'text-muted-foreground'}>
                                                        {isOverdue && <AlertCircle className="h-3 w-3" />}
                                                        {new Date(c.due_date).toLocaleDateString()}
                                                    </span>
                                                ) : (
                                                    <span className="text-muted-foreground">-</span>
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
                </div>

                {/* Pagination */}
                {cases.last_page > 1 && (
                    <div className="flex items-center justify-between text-xs text-muted-foreground pt-1">
                        <div>
                            Showing page {cases.current_page} of {cases.last_page} ({cases.total} results)
                        </div>
                        <div className="flex items-center gap-1.5">
                            {cases.prev_page_url && (
                                <Button asChild variant="outline" size="sm" className="h-8 text-xs">
                                    <Link href={cases.prev_page_url}>Previous</Link>
                                </Button>
                            )}
                            {cases.next_page_url && (
                                <Button asChild variant="outline" size="sm" className="h-8 text-xs">
                                    <Link href={cases.next_page_url}>Next</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>

            {/* Bulk Update Modal */}
            <Dialog open={bulkModalOpen} onOpenChange={setBulkModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Bulk Update ({selectedIds.length} Cases)</DialogTitle>
                        <DialogDescription>
                            Apply status, assignee, or remarks to all selected cases at once.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-3 py-2">
                        <div className="space-y-1.5">
                            <Label>New Status</Label>
                            <Select value={bulkStatus} onValueChange={setBulkStatus}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Leave unchanged or select status" />
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
                            <Label>Reassign To</Label>
                            <Select value={bulkAssignee} onValueChange={setBulkAssignee}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Leave unchanged or select assignee" />
                                </SelectTrigger>
                                <SelectContent>
                                    {assignees.map((a) => (
                                        <SelectItem key={a} value={a}>{a}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label>Remarks / Resolution Notes</Label>
                            <Input
                                placeholder="Optional remarks to add to all cases..."
                                value={bulkRemarks}
                                onChange={(e) => setBulkRemarks(e.target.value)}
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setBulkModalOpen(false)}>Cancel</Button>
                        <Button onClick={submitBulkUpdate}>Apply Updates</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
