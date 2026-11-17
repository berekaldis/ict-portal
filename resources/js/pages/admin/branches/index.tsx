import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head, router, useForm } from '@inertiajs/react';
import {
    Building2,
    CheckCircle2,
    Edit2,
    PlusCircle,
    Power,
    Radio,
    RefreshCw,
    Search,
    Send,
    Trash2,
    XCircle,
} from 'lucide-react';
import React, { useState } from 'react';

interface BranchItem {
    id: number;
    branch_name: string;
    telegram_chat_id?: string | null;
    is_active: boolean;
    created_at?: string;
    updated_at?: string;
}

interface Props {
    branches: {
        data: BranchItem[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    stats: {
        total: number;
        active: number;
        with_telegram: number;
    };
    filters: {
        search?: string;
        status?: string;
    };
}

export default function BranchesIndex({ branches, stats, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [syncing, setSyncing] = useState(false);

    // Create Modal state
    const [createOpen, setCreateOpen] = useState(false);
    const {
        data: createData,
        setData: setCreateData,
        post: postCreate,
        processing: creating,
        reset: resetCreate,
        errors: createErrors,
    } = useForm({
        branch_name: '',
        telegram_chat_id: '',
        is_active: true,
    });

    // Edit Modal state
    const [editBranch, setEditBranch] = useState<BranchItem | null>(null);
    const {
        data: editData,
        setData: setEditData,
        put: putEdit,
        processing: updating,
        reset: resetEdit,
        errors: editErrors,
    } = useForm({
        branch_name: '',
        telegram_chat_id: '',
        is_active: true,
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            route('admin.branches.index'),
            { search: search.trim() || undefined, status: status !== 'all' ? status : undefined },
            { preserveState: true }
        );
    };

    const handleStatusFilter = (newStatus: string) => {
        setStatus(newStatus);
        router.get(
            route('admin.branches.index'),
            { search: search.trim() || undefined, status: newStatus !== 'all' ? newStatus : undefined },
            { preserveState: true }
        );
    };

    const handleSync = () => {
        if (confirm('Sync active branches from Company System?')) {
            setSyncing(true);
            router.post(
                route('admin.branches.sync'),
                {},
                {
                    onFinish: () => setSyncing(false),
                }
            );
        }
    };

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        postCreate(route('admin.branches.store'), {
            onSuccess: () => {
                setCreateOpen(false);
                resetCreate();
            },
        });
    };

    const openEdit = (b: BranchItem) => {
        setEditBranch(b);
        setEditData({
            branch_name: b.branch_name,
            telegram_chat_id: b.telegram_chat_id || '',
            is_active: b.is_active,
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editBranch) return;

        putEdit(route('admin.branches.update', editBranch.id), {
            onSuccess: () => {
                setEditBranch(null);
                resetEdit();
            },
        });
    };

    const handleDelete = (b: BranchItem) => {
        if (confirm(`Delete branch '${b.branch_name}'? This will also remove it from HR settings.`)) {
            router.delete(route('admin.branches.destroy', b.id));
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'System Administration', href: '/users' },
                { title: 'Branches', href: route('admin.branches.index') },
            ]}
        >
            <Head title="Branch Management | System Administration" />

            <div className="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <Building2 className="h-6 w-6 text-amber-600 dark:text-amber-500" />
                            Branch Management (የቅርንጫፎች አስተዳደር)
                        </h1>
                        <p className="text-xs text-muted-foreground mt-0.5">
                            Manage operational coffee branches, status, and Telegram alert chat IDs
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleSync}
                            disabled={syncing}
                            className="h-9 text-xs font-semibold gap-1.5"
                        >
                            <RefreshCw className={`h-3.5 w-3.5 ${syncing ? 'animate-spin text-amber-600' : ''}`} />
                            {syncing ? 'Syncing...' : 'Sync Company System'}
                        </Button>

                        <Button
                            size="sm"
                            onClick={() => setCreateOpen(true)}
                            className="h-9 text-xs font-semibold bg-amber-700 hover:bg-amber-800 text-white gap-1.5 shadow-xs"
                        >
                            <PlusCircle className="h-3.5 w-3.5" />
                            Add Branch
                        </Button>
                    </div>
                </div>

                {/* Stat Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                    <Card className="border-border/60 shadow-xs">
                        <CardHeader className="pb-2 pt-4 px-4 flex flex-row items-center justify-between space-y-0">
                            <CardTitle className="text-xs font-medium text-muted-foreground">Total Branches</CardTitle>
                            <Building2 className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent className="px-4 pb-4">
                            <div className="text-2xl font-bold">{stats.total}</div>
                            <p className="text-[11px] text-muted-foreground mt-0.5">Configured in ICT Portal</p>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 shadow-xs">
                        <CardHeader className="pb-2 pt-4 px-4 flex flex-row items-center justify-between space-y-0">
                            <CardTitle className="text-xs font-medium text-muted-foreground">Active Branches</CardTitle>
                            <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                        </CardHeader>
                        <CardContent className="px-4 pb-4">
                            <div className="text-2xl font-bold text-emerald-600">{stats.active}</div>
                            <p className="text-[11px] text-muted-foreground mt-0.5">Operational coffee shops</p>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 shadow-xs">
                        <CardHeader className="pb-2 pt-4 px-4 flex flex-row items-center justify-between space-y-0">
                            <CardTitle className="text-xs font-medium text-muted-foreground">Telegram Connected</CardTitle>
                            <Send className="h-4 w-4 text-sky-600" />
                        </CardHeader>
                        <CardContent className="px-4 pb-4">
                            <div className="text-2xl font-bold text-sky-600">{stats.with_telegram}</div>
                            <p className="text-[11px] text-muted-foreground mt-0.5">With direct alert Chat IDs</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filter & Search Bar */}
                <div className="bg-card rounded-xl p-3 border border-border/60 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-xs">
                    <form onSubmit={handleSearch} className="flex items-center gap-2 w-full sm:w-80">
                        <div className="relative w-full">
                            <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-muted-foreground" />
                            <Input
                                type="text"
                                placeholder="Search by branch name or chat ID..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-8 text-xs h-9 bg-muted/20 border-border/60"
                            />
                        </div>
                        <Button type="submit" size="sm" variant="secondary" className="h-9 text-xs px-3 font-semibold">
                            Search
                        </Button>
                    </form>

                    <div className="flex items-center gap-2 w-full sm:w-auto">
                        <span className="text-xs text-muted-foreground font-medium">Status:</span>
                        <select
                            value={status}
                            onChange={(e) => handleStatusFilter(e.target.value)}
                            className="h-9 text-xs rounded-lg border-border/60 bg-muted/20 px-2.5 font-medium"
                        >
                            <option value="all">All Branches</option>
                            <option value="active">Active Only</option>
                            <option value="inactive">Inactive Only</option>
                        </select>
                    </div>
                </div>

                {/* Branches Table */}
                <Card className="border-border/60 shadow-xs overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/40 font-semibold text-muted-foreground text-xs">
                                    <TableHead className="w-16">ID</TableHead>
                                    <TableHead>Branch Name</TableHead>
                                    <TableHead>Telegram Alert Chat ID</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {branches.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="p-8 text-center text-muted-foreground text-xs">
                                            No branches matching your criteria.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    branches.data.map((b) => (
                                        <TableRow key={b.id} className="hover:bg-muted/20 transition-colors text-xs">
                                            <TableCell className="font-mono font-medium text-muted-foreground">{b.id}</TableCell>
                                            <TableCell className="font-semibold text-foreground flex items-center gap-2">
                                                <Building2 className="h-4 w-4 text-amber-600 shrink-0" />
                                                <span>{b.branch_name}</span>
                                            </TableCell>
                                            <TableCell>
                                                {b.telegram_chat_id ? (
                                                    <span className="font-mono text-sky-700 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/40 px-2 py-0.5 rounded border border-sky-200 dark:border-sky-800 text-[11px]">
                                                        {b.telegram_chat_id}
                                                    </span>
                                                ) : (
                                                    <span className="text-muted-foreground italic text-[11px]">None (default group)</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {b.is_active ? (
                                                    <Badge variant="outline" className="text-[10px] bg-emerald-500/10 text-emerald-600 border-emerald-500/30">
                                                        Active
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline" className="text-[10px] bg-red-500/10 text-red-600 border-red-500/30">
                                                        Inactive
                                                    </Badge>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1.5">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => openEdit(b)}
                                                        className="h-7 w-7 p-0 text-muted-foreground hover:text-foreground"
                                                        title="Edit Branch"
                                                    >
                                                        <Edit2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(b)}
                                                        className="h-7 w-7 p-0 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/30"
                                                        title="Delete Branch"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Pagination */}
                    {branches.last_page > 1 && (
                        <div className="flex items-center justify-between p-3 border-t border-border/50 text-xs">
                            <span className="text-muted-foreground">
                                Showing {branches.data.length} of {branches.total} branches
                            </span>
                            <div className="flex gap-1.5">
                                {branches.prev_page_url && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => router.get(branches.prev_page_url!)}
                                        className="h-8 text-xs"
                                    >
                                        Previous
                                    </Button>
                                )}
                                {branches.next_page_url && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => router.get(branches.next_page_url!)}
                                        className="h-8 text-xs"
                                    >
                                        Next
                                    </Button>
                                )}
                            </div>
                        </div>
                    )}
                </Card>

                {/* Create Branch Modal */}
                <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                    <DialogContent className="max-w-md border-border/60">
                        <DialogHeader>
                            <DialogTitle className="text-base font-bold flex items-center gap-2">
                                <Building2 className="h-4 w-4 text-amber-600" />
                                Add New Branch
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                Create an operational branch entry and configure its Telegram chat ID.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleCreateSubmit} className="space-y-4 pt-2">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">
                                    Branch Name <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    value={createData.branch_name}
                                    onChange={(e) => setCreateData('branch_name', e.target.value)}
                                    placeholder="e.g. Sarbet, Bole Medhanialem..."
                                    required
                                    className="text-xs h-9"
                                />
                                {createErrors.branch_name && <p className="text-xs text-destructive">{createErrors.branch_name}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Telegram Chat ID (Optional)</Label>
                                <Input
                                    value={createData.telegram_chat_id}
                                    onChange={(e) => setCreateData('telegram_chat_id', e.target.value)}
                                    placeholder="e.g. 6492922880 or -100..."
                                    className="text-xs h-9 font-mono"
                                />
                                {createErrors.telegram_chat_id && <p className="text-xs text-destructive">{createErrors.telegram_chat_id}</p>}
                            </div>

                            <div className="flex items-center gap-2 pt-1">
                                <input
                                    type="checkbox"
                                    id="create_is_active"
                                    checked={createData.is_active}
                                    onChange={(e) => setCreateData('is_active', e.target.checked)}
                                    className="rounded border-border text-amber-600 focus:ring-amber-500"
                                />
                                <Label htmlFor="create_is_active" className="text-xs font-medium cursor-pointer">
                                    Active Operational Branch
                                </Label>
                            </div>

                            <DialogFooter className="pt-2">
                                <Button type="button" variant="outline" size="sm" onClick={() => setCreateOpen(false)}>
                                    Cancel
                                </Button>
                                <Button type="submit" size="sm" disabled={creating} className="bg-amber-700 hover:bg-amber-800 text-white">
                                    {creating ? 'Saving...' : 'Create Branch'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Edit Branch Modal */}
                <Dialog open={!!editBranch} onOpenChange={(open) => !open && setEditBranch(null)}>
                    <DialogContent className="max-w-md border-border/60">
                        <DialogHeader>
                            <DialogTitle className="text-base font-bold flex items-center gap-2">
                                <Edit2 className="h-4 w-4 text-amber-600" />
                                Edit Branch: {editBranch?.branch_name}
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                Update branch details and Telegram alert destination.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleEditSubmit} className="space-y-4 pt-2">
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">
                                    Branch Name <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    value={editData.branch_name}
                                    onChange={(e) => setEditData('branch_name', e.target.value)}
                                    required
                                    className="text-xs h-9"
                                />
                                {editErrors.branch_name && <p className="text-xs text-destructive">{editErrors.branch_name}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Telegram Chat ID</Label>
                                <Input
                                    value={editData.telegram_chat_id}
                                    onChange={(e) => setEditData('telegram_chat_id', e.target.value)}
                                    placeholder="e.g. 6492922880 or -100..."
                                    className="text-xs h-9 font-mono"
                                />
                                {editErrors.telegram_chat_id && <p className="text-xs text-destructive">{editErrors.telegram_chat_id}</p>}
                            </div>

                            <div className="flex items-center gap-2 pt-1">
                                <input
                                    type="checkbox"
                                    id="edit_is_active"
                                    checked={editData.is_active}
                                    onChange={(e) => setEditData('is_active', e.target.checked)}
                                    className="rounded border-border text-amber-600 focus:ring-amber-500"
                                />
                                <Label htmlFor="edit_is_active" className="text-xs font-medium cursor-pointer">
                                    Active Operational Branch
                                </Label>
                            </div>

                            <DialogFooter className="pt-2">
                                <Button type="button" variant="outline" size="sm" onClick={() => setEditBranch(null)}>
                                    Cancel
                                </Button>
                                <Button type="submit" size="sm" disabled={updating} className="bg-amber-700 hover:bg-amber-800 text-white">
                                    {updating ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
