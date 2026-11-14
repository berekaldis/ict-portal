import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SearchableSelect } from '@/components/ui/searchable-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePermission } from '@/hooks/user-permissions';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    AlertCircle,
    BarChart3,
    Check,
    CheckCircle2,
    Eye,
    FileEdit,
    FileText,
    Filter,
    PlusCircle,
    Search,
    Trash2,
    X,
} from 'lucide-react';
import React, { useState } from 'react';

interface EditRequestItem {
    id: number;
    request_id: string;
    timestamp: string;
    branch: string;
    user_name: string;
    store_user?: string;
    voucher_code?: string;
    voucher_type?: string;
    action_type: string;
    edit_type?: string;
    reason: string;
    target_dept: string;
    incorrect_date?: string;
    correct_date?: string;
    shift?: string;
    status: string;
    approver?: string;
}

interface Props {
    requests: {
        data: EditRequestItem[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    filters: any;
    branches: string[];
}

export default function EditRequestsIndex({ requests, filters, branches }: Props) {
    const { can, isItTeam } = usePermission();
    const { props } = usePage<any>();
    const roles = props.auth?.roles || [];

    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'All');
    const [actionType, setActionType] = useState(filters.action_type || 'All');
    const [targetDept, setTargetDept] = useState(filters.target_dept || 'All');
    const [branch, setBranch] = useState(filters.branch || 'All');
    const [period, setPeriod] = useState(filters.period || 'all');

    // Bulk selection
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [bulkModalOpen, setBulkModalOpen] = useState(false);
    const [bulkStatus, setBulkStatus] = useState('');

    const applyFilter = (newOverrides: Record<string, any> = {}) => {
        router.get(
            route('edit-requests.index'),
            {
                search,
                status,
                action_type: actionType,
                target_dept: targetDept,
                branch,
                period,
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
        setActionType('All');
        setTargetDept('All');
        setBranch('All');
        setPeriod('all');
        router.get(route('edit-requests.index'), {}, { replace: true });
    };

    const toggleSelectAll = () => {
        if (selectedIds.length === requests.data.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(requests.data.map((r) => r.id));
        }
    };

    const toggleSelect = (id: number) => {
        if (selectedIds.includes(id)) {
            setSelectedIds(selectedIds.filter((item) => item !== id));
        } else {
            setSelectedIds([...selectedIds, id]);
        }
    };

    const submitBulkUpdate = () => {
        if (selectedIds.length === 0 || !bulkStatus) return;

        router.post(
            route('edit-requests.bulk-status'),
            {
                ids: selectedIds,
                status: bulkStatus,
            },
            {
                onSuccess: () => {
                    setBulkModalOpen(false);
                    setSelectedIds([]);
                    setBulkStatus('');
                },
            }
        );
    };

    const handleQuickStatus = (id: number, newStatus: string) => {
        router.put(route('edit-requests.status', id), { status: newStatus });
    };

    const handleMarkDone = (id: number) => {
        router.put(route('edit-requests.mark-done', id));
    };

    const getStatusBadge = (st: string) => {
        switch (st) {
            case 'Done':
                return <Badge variant="secondary" className="bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 font-semibold">Done ✅</Badge>;
            case 'Approved':
                return <Badge variant="secondary" className="bg-blue-500/15 text-blue-700 dark:text-blue-400 font-semibold">Approved</Badge>;
            case 'Pending':
                return <Badge variant="secondary" className="bg-amber-500/15 text-amber-700 dark:text-amber-400 font-semibold">Pending</Badge>;
            case 'Rejected':
                return <Badge variant="destructive" className="font-semibold">Rejected</Badge>;
            default:
                return <Badge variant="outline">{st}</Badge>;
        }
    };

    const getActionTypeBadge = (at: string) => {
        switch (at) {
            case 'On Hand Delete':
                return <Badge variant="secondary" className="bg-purple-500/15 text-purple-700 dark:text-purple-400 border-purple-500/30 text-[11px]">On Hand Delete</Badge>;
            case 'Void':
                return <Badge variant="secondary" className="bg-rose-500/15 text-rose-700 dark:text-rose-400 border-rose-500/30 text-[11px]">Void</Badge>;
            default:
                return <Badge variant="secondary" className="bg-sky-500/15 text-sky-700 dark:text-sky-400 border-sky-500/30 text-[11px]">Edit</Badge>;
        }
    };

    // Permission checks for actions
    const canApprove = can('edit_request.approve');
    const canMarkDone = can('edit_request.mark_done');

    return (
        <AppLayout breadcrumbs={[{ title: 'Voucher Requests', href: '/edit-requests' }, { title: 'All Requests' }]}>
            <Head title="Voucher Edit & Void Requests" />

            <div className="space-y-4">
                {/* Header Section */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div className="flex items-center gap-2">
                            <FileText className="h-5 w-5 text-primary" />
                            <h2 className="text-xl font-bold tracking-tight text-foreground">Voucher & On Hand Delete Requests</h2>
                            <Badge variant="secondary" className="bg-primary/10 text-primary text-xs">
                                {requests.total} Total
                            </Badge>
                        </div>
                        <p className="text-xs text-muted-foreground mt-0.5">
                            Branch voucher edit, void, and inventory on-hand deletion workflow
                        </p>
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                        {can('edit_request.reports.view') && (
                            <Button asChild variant="outline" size="sm" className="h-9">
                                <Link href={route('edit-requests.reports')}>
                                    <BarChart3 className="h-3.5 w-3.5 mr-1" /> Analytics & Reports
                                </Link>
                            </Button>
                        )}
                        {can('edit_request.create') && (
                            <Button asChild size="sm" className="h-9">
                                <Link href={route('edit-requests.create')}>
                                    <PlusCircle className="h-4 w-4 mr-1.5" /> Submit Request
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Filters Bar */}
                <div className="p-3.5 rounded-xl border border-border/60 bg-card shadow-xs space-y-3">
                    <form onSubmit={handleSearch} className="flex flex-col md:flex-row gap-2.5 items-stretch md:items-center">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search by request ID, voucher code, user, branch, reason..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9 h-9"
                            />
                        </div>

                        <div className="flex items-center gap-2 flex-wrap">
                            <SearchableSelect
                                options={[
                                    { id: 'All', name: 'All Statuses' },
                                    { id: 'Pending', name: '⏳ Pending' },
                                    { id: 'Approved', name: '✅ Approved' },
                                    { id: 'Done', name: '🎉 Done' },
                                    { id: 'Rejected', name: '❌ Rejected' },
                                ]}
                                value={status}
                                onValueChange={(val) => {
                                    setStatus(val);
                                    applyFilter({ status: val });
                                }}
                                placeholder="Status"
                                searchPlaceholder="Search status..."
                                className="w-[130px] h-9 text-xs"
                            />

                            <SearchableSelect
                                options={[
                                    { id: 'All', name: 'All Types' },
                                    { id: 'Edit', name: 'Edit Voucher' },
                                    { id: 'Void', name: 'Void Voucher' },
                                    { id: 'On Hand Delete', name: 'On Hand Delete' },
                                ]}
                                value={actionType}
                                onValueChange={(val) => {
                                    setActionType(val);
                                    applyFilter({ action_type: val });
                                }}
                                placeholder="Action Type"
                                searchPlaceholder="Search action type..."
                                className="w-[145px] h-9 text-xs"
                            />

                            <SearchableSelect
                                options={branches}
                                value={branch}
                                onValueChange={(val) => {
                                    setBranch(val);
                                    applyFilter({ branch: val });
                                }}
                                placeholder="Branch"
                                searchPlaceholder="Search active branch..."
                                allowAll={true}
                                allLabel="All Branches"
                                allValue="All"
                                className="w-[160px] h-9 text-xs"
                            />

                            <Select
                                value={period}
                                onValueChange={(val) => {
                                    setPeriod(val);
                                    applyFilter({ period: val });
                                }}
                            >
                                <SelectTrigger className="w-[110px] h-9 text-xs">
                                    <SelectValue placeholder="Period" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Time</SelectItem>
                                    <SelectItem value="7">Last 7 Days</SelectItem>
                                    <SelectItem value="30">Last 30 Days</SelectItem>
                                </SelectContent>
                            </Select>

                            <Button type="submit" variant="secondary" size="sm" className="h-9 text-xs">
                                <Filter className="h-3.5 w-3.5 mr-1" /> Filter
                            </Button>

                            {(search || status !== 'All' || actionType !== 'All' || branch !== 'All' || period !== 'all') && (
                                <Button type="button" variant="ghost" size="sm" onClick={clearFilters} className="h-9 text-xs text-muted-foreground">
                                    <X className="h-3.5 w-3.5 mr-1" /> Reset
                                </Button>
                            )}
                        </div>
                    </form>
                </div>

                {/* Bulk Action Bar */}
                {can('edit_request.bulk_action') && selectedIds.length > 0 && (
                    <div className="flex items-center justify-between p-3 rounded-xl bg-primary/10 border border-primary/30 text-xs">
                        <span className="font-semibold text-primary">
                            {selectedIds.length} request{selectedIds.length > 1 ? 's' : ''} selected
                        </span>
                        <div className="flex items-center gap-2">
                            <Button size="sm" variant="default" onClick={() => setBulkModalOpen(true)} className="h-7 text-xs">
                                Update Status
                            </Button>
                            <Button size="sm" variant="ghost" onClick={() => setSelectedIds([])} className="h-7 text-xs">
                                Deselect All
                            </Button>
                        </div>
                    </div>
                )}

                {/* Requests Table */}
                <div className="rounded-xl border border-border/60 bg-card overflow-hidden shadow-xs">
                    <Table>
                        <TableHeader className="bg-muted/30">
                            <TableRow>
                                {can('edit_request.bulk_action') && (
                                    <TableHead className="w-[40px]">
                                        <Checkbox
                                            checked={requests.data.length > 0 && selectedIds.length === requests.data.length}
                                            onCheckedChange={toggleSelectAll}
                                        />
                                    </TableHead>
                                )}
                                <TableHead className="w-[150px]">Request ID</TableHead>
                                <TableHead className="w-[120px]">Action</TableHead>
                                <TableHead className="w-[160px]">Branch / User</TableHead>
                                <TableHead className="min-w-[180px]">Voucher / Details</TableHead>
                                <TableHead className="w-[130px]">Target Dept</TableHead>
                                <TableHead className="w-[110px]">Status</TableHead>
                                <TableHead className="w-[130px] text-right">Quick Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {requests.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={can('edit_request.bulk_action') ? 8 : 7} className="text-center py-8 text-xs text-muted-foreground">
                                        No requests found for current filter.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                requests.data.map((r) => {
                                    const isSelected = selectedIds.includes(r.id);
                                    const isOHD = r.action_type === 'On Hand Delete';

                                    return (
                                        <TableRow key={r.id} className={isSelected ? 'bg-primary/5' : undefined}>
                                            {can('edit_request.bulk_action') && (
                                                <TableCell>
                                                    <Checkbox
                                                        checked={isSelected}
                                                        onCheckedChange={() => toggleSelect(r.id)}
                                                    />
                                                </TableCell>
                                            )}
                                            <TableCell className="font-mono text-xs font-bold text-primary">
                                                <Link href={route('edit-requests.show', r.id)} className="hover:underline">
                                                    {r.request_id}
                                                </Link>
                                                <div className="text-[10px] text-muted-foreground font-sans font-normal">
                                                    {new Date(r.timestamp).toLocaleDateString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {getActionTypeBadge(r.action_type)}
                                            </TableCell>
                                            <TableCell className="text-xs">
                                                <div className="font-semibold text-foreground">{r.branch}</div>
                                                <div className="text-[11px] text-muted-foreground">{r.user_name}</div>
                                            </TableCell>
                                            <TableCell className="text-xs">
                                                {isOHD ? (
                                                    <div className="space-y-0.5">
                                                        <div>Store User: <strong className="text-foreground">{r.store_user || '-'}</strong></div>
                                                        <div className="text-[11px] text-muted-foreground">Shift: {r.shift || 'Shift 1'} &bull; Delete Date: {r.correct_date || '-'}</div>
                                                    </div>
                                                ) : (
                                                    <div className="space-y-0.5">
                                                        <div className="font-mono font-medium text-foreground">{r.voucher_code || '-'}</div>
                                                        <div className="text-[11px] text-muted-foreground">{r.voucher_type || r.edit_type}</div>
                                                    </div>
                                                )}
                                                <div className="text-[11px] text-muted-foreground italic line-clamp-1 mt-0.5 max-w-xs">
                                                    {r.reason}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-xs font-medium">
                                                {r.target_dept}
                                            </TableCell>
                                            <TableCell>
                                                {getStatusBadge(r.status)}
                                                {r.approver && (
                                                    <div className="text-[10px] text-muted-foreground mt-0.5">
                                                        By: {r.approver}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    {r.status === 'Pending' && canApprove && (
                                                        <>
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                className="h-7 text-xs px-2 text-emerald-600 border-emerald-500/30 hover:bg-emerald-500/10"
                                                                onClick={() => handleQuickStatus(r.id, 'Approved')}
                                                                title="Approve Request"
                                                            >
                                                                <Check className="h-3.5 w-3.5" />
                                                            </Button>
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                className="h-7 text-xs px-2 text-destructive border-destructive/30 hover:bg-destructive/10"
                                                                onClick={() => handleQuickStatus(r.id, 'Rejected')}
                                                                title="Reject Request"
                                                            >
                                                                <X className="h-3.5 w-3.5" />
                                                            </Button>
                                                        </>
                                                    )}

                                                    {r.status === 'Approved' && canMarkDone && (
                                                        <Button
                                                            size="sm"
                                                            variant="default"
                                                            className="h-7 text-xs px-2 bg-emerald-600 hover:bg-emerald-700"
                                                            onClick={() => handleMarkDone(r.id)}
                                                            title="Mark as Executed / Done in CNET"
                                                        >
                                                            <CheckCircle2 className="h-3.5 w-3.5 mr-1" /> Done
                                                        </Button>
                                                    )}

                                                    <Button asChild variant="ghost" size="icon" className="h-7 w-7 text-primary">
                                                        <Link href={route('edit-requests.show', r.id)}>
                                                            <Eye className="h-3.5 w-3.5" />
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })
                            )}
                        </TableBody>
                    </Table>
                </div>

                {/* Pagination */}
                {requests.last_page > 1 && (
                    <div className="flex items-center justify-between text-xs text-muted-foreground pt-1">
                        <div>
                            Showing page {requests.current_page} of {requests.last_page} ({requests.total} results)
                        </div>
                        <div className="flex items-center gap-1.5">
                            {requests.prev_page_url && (
                                <Button asChild variant="outline" size="sm" className="h-8 text-xs">
                                    <Link href={requests.prev_page_url}>Previous</Link>
                                </Button>
                            )}
                            {requests.next_page_url && (
                                <Button asChild variant="outline" size="sm" className="h-8 text-xs">
                                    <Link href={requests.next_page_url}>Next</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>

            {/* Bulk Status Update Dialog */}
            <Dialog open={bulkModalOpen} onOpenChange={setBulkModalOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Update Status ({selectedIds.length} Requests)</DialogTitle>
                        <DialogDescription>
                            Apply approval or completion status to selected items.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-3 py-2">
                        <Label>New Status</Label>
                        <Select value={bulkStatus} onValueChange={setBulkStatus}>
                            <SelectTrigger>
                                <SelectValue placeholder="Choose status..." />
                            </SelectTrigger>
                            <SelectContent>
                                {canApprove && (
                                    <>
                                        <SelectItem value="Approved">✅ Approve Selected</SelectItem>
                                        <SelectItem value="Rejected">❌ Reject Selected</SelectItem>
                                    </>
                                )}
                                {canMarkDone && (
                                    <SelectItem value="Done">🎉 Mark Selected as Done (Executed)</SelectItem>
                                )}
                            </SelectContent>
                        </Select>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setBulkModalOpen(false)}>Cancel</Button>
                        <Button onClick={submitBulkUpdate} disabled={!bulkStatus}>Apply</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
