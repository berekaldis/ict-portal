import TelecomHeaderNav from '@/components/telecom/TelecomHeaderNav';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    Archive,
    Building2,
    Download,
    Edit,
    ExternalLink,
    Hash,
    MessageCircle,
    Plus,
    QrCode,
    RotateCcw,
    Search,
    Trash2,
    Users,
} from 'lucide-react';
import React, { useState } from 'react';

type Group = {
    id: number;
    name: string;
    chat_id: string | null;
    username: string | null;
    group_type: string;
    category: string | null;
    purpose: string | null;
    status: string;
    invite_link: string | null;
    qr_code_path: string | null;
    is_public: boolean;
    member_count: number | null;
    link_generated_at: string | null;
    branch?: { id: number; name?: string; branch_name?: string } | null;
    department?: { id: number; name: string } | null;
    creator?: { id: number; name: string } | null;
    ticket?: { id: number; title: string } | null;
    created_at: string;
};

type Stats = { total: number; active: number; inactive: number; archived: number };

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

type PageProps = {
    groups: Paginated<Group>;
    stats: Stats;
    branches: { id: number; name?: string; branch_name?: string }[];
    departments: { id: number; name: string }[];
    groupTypes: string[];
    categories: string[];
    statuses: string[];
    filters: {
        search?: string;
        status?: string;
        category?: string;
        group_type?: string;
        branch_id?: string;
        per_page?: string;
    };
};

function statusColor(status: string) {
    if (status === 'Active') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400';
    if (status === 'Inactive') return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400';
    return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
}

function typeColor(type: string) {
    if (type === 'Channel') return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400';
    if (type === 'Supergroup') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
    return 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400';
}

function TelegramIcon({ className }: { className?: string }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="currentColor">
            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
        </svg>
    );
}

export default function TelegramGroupsIndex({
    groups,
    stats,
    branches,
    groupTypes,
    categories,
    statuses,
    filters,
}: PageProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [qrModalGroup, setQrModalGroup] = useState<Group | null>(null);

    const applyFilter = (params: Record<string, string | undefined>) => {
        router.get('/telecom/telegram-groups', { ...filters, ...params, page: undefined }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilter({ search });
    };

    const handleResetFilters = () => {
        setSearch('');
        router.get('/telecom/telegram-groups', {}, {
            preserveState: false,
            preserveScroll: true,
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Delete this Telegram group record? This cannot be undone.')) {
            router.delete(`/telecom/telegram-groups/${id}`);
        }
    };

    const statCards = [
        { label: 'Total Groups', value: stats.total, icon: MessageCircle, color: 'text-blue-600', bg: 'bg-blue-50 dark:bg-blue-950/30' },
        { label: 'Active', value: stats.active, icon: Users, color: 'text-emerald-600', bg: 'bg-emerald-50 dark:bg-emerald-950/30' },
        { label: 'Inactive', value: stats.inactive, icon: Archive, color: 'text-amber-600', bg: 'bg-amber-50 dark:bg-amber-950/30' },
        { label: 'Archived', value: stats.archived, icon: Archive, color: 'text-slate-500', bg: 'bg-slate-100 dark:bg-slate-800/30' },
    ];

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Telecom Management', href: '/telecom/dashboard' },
                { title: 'Telegram Groups' },
            ]}
        >
            <Head title="Telegram Groups Management" />

            <div className="mx-auto max-w-screen-2xl space-y-5 p-4 sm:p-6">
                <TelecomHeaderNav
                    title="Telecom Management – Telegram Groups"
                    description="Manage, track, and generate QR codes for corporate Telegram groups and channels"
                />

                {/* Header */}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-sky-400 shadow-md">
                            <TelegramIcon className="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h2 className="text-xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Telegram Groups</h2>
                            <p className="text-xs text-muted-foreground">Manage all Kaldi's Coffee Telegram groups & channels</p>
                        </div>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Link href="/telecom/telegram-groups/create">
                            <Button className="gap-1.5 bg-blue-600 hover:bg-blue-700 shadow-sm" size="sm">
                                <Plus className="h-4 w-4" />
                                Register Group
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Stat Cards */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {statCards.map((s) => {
                        const Icon = s.icon;
                        return (
                            <Card key={s.label} className="border shadow-xs">
                                <CardContent className="flex items-center gap-3 p-4">
                                    <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${s.bg}`}>
                                        <Icon className={`h-5 w-5 ${s.color}`} />
                                    </div>
                                    <div>
                                        <p className="text-xl font-bold text-slate-900 dark:text-white">{s.value}</p>
                                        <p className="text-xs text-muted-foreground">{s.label}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                {/* Filters */}
                <Card className="border shadow-xs">
                    <CardHeader className="pb-3 pt-4">
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-sm font-semibold text-slate-700 dark:text-slate-300">Filter Groups</CardTitle>
                            {(filters.search || filters.status || filters.group_type || filters.category || filters.branch_id) && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={handleResetFilters}
                                    className="h-7 text-xs text-muted-foreground hover:text-slate-900 dark:hover:text-white"
                                >
                                    <RotateCcw className="h-3 w-3 mr-1" /> Reset
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex flex-wrap gap-3">
                            <div className="relative flex-1 min-w-[200px]">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    id="search-groups"
                                    placeholder="Search name, username, chat ID…"
                                    className="pl-8"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>
                            <Select
                                value={filters.status ?? 'all'}
                                onValueChange={(v) => applyFilter({ status: v === 'all' ? undefined : v })}
                            >
                                <SelectTrigger id="filter-status" className="w-32">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    {statuses.map((s) => <SelectItem key={s} value={s}>{s}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            <Select
                                value={filters.group_type ?? 'all'}
                                onValueChange={(v) => applyFilter({ group_type: v === 'all' ? undefined : v })}
                            >
                                <SelectTrigger id="filter-type" className="w-36">
                                    <SelectValue placeholder="Type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Types</SelectItem>
                                    {groupTypes.map((t) => <SelectItem key={t} value={t}>{t}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            <Select
                                value={filters.category ?? 'all'}
                                onValueChange={(v) => applyFilter({ category: v === 'all' ? undefined : v })}
                            >
                                <SelectTrigger id="filter-category" className="w-36">
                                    <SelectValue placeholder="Category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Categories</SelectItem>
                                    {categories.map((c) => <SelectItem key={c} value={c}>{c}</SelectItem>)}
                                </SelectContent>
                            </Select>
                            <Select
                                value={filters.branch_id ?? 'all'}
                                onValueChange={(v) => applyFilter({ branch_id: v === 'all' ? undefined : v })}
                            >
                                <SelectTrigger id="filter-branch" className="w-44">
                                    <SelectValue placeholder="Branch" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Branches</SelectItem>
                                    {branches.map((b) => (
                                        <SelectItem key={b.id} value={String(b.id)}>
                                            {b.name || b.branch_name || `Branch #${b.id}`}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button type="submit" size="sm" variant="secondary" className="gap-1">
                                <Search className="h-4 w-4" /> Filter
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Table */}
                <Card className="border shadow-xs overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-slate-50 dark:bg-slate-900/50">
                                    <TableHead className="w-12 text-center">#</TableHead>
                                    <TableHead>Group Name</TableHead>
                                    <TableHead>Type / Category</TableHead>
                                    <TableHead>Chat ID / Username</TableHead>
                                    <TableHead>Branch / Dept.</TableHead>
                                    <TableHead>Members</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Links & QR</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {groups.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={9} className="py-16 text-center">
                                            <div className="flex flex-col items-center gap-3">
                                                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                                    <TelegramIcon className="h-8 w-8 text-slate-400" />
                                                </div>
                                                <p className="font-semibold text-slate-600 dark:text-slate-400">No Telegram groups found</p>
                                                <p className="text-sm text-muted-foreground">Register your first corporate Telegram group or channel.</p>
                                                <Link href="/telecom/telegram-groups/create">
                                                    <Button size="sm" className="bg-blue-600 hover:bg-blue-700 mt-1 gap-1">
                                                        <Plus className="h-4 w-4" /> Register Group
                                                    </Button>
                                                </Link>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    groups.data.map((g, idx) => (
                                        <TableRow key={g.id} className="hover:bg-slate-50/60 dark:hover:bg-slate-900/40 transition-colors">
                                            <TableCell className="text-xs text-muted-foreground font-mono text-center">
                                                {(groups.from ?? 0) + idx}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2.5">
                                                    <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-sky-400 shadow-xs flex-shrink-0">
                                                        <TelegramIcon className="h-4 w-4 text-white" />
                                                    </div>
                                                    <div>
                                                        <p className="font-semibold text-sm text-slate-900 dark:text-slate-100">{g.name}</p>
                                                        {g.purpose && (
                                                            <p className="text-xs text-muted-foreground truncate max-w-[220px]">{g.purpose}</p>
                                                        )}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-col gap-1">
                                                    <Badge className={`text-[10px] px-1.5 py-0.5 w-fit font-medium border-0 ${typeColor(g.group_type)}`}>
                                                        {g.group_type}
                                                    </Badge>
                                                    {g.category && (
                                                        <span className="text-xs text-muted-foreground">{g.category}</span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="font-mono text-xs space-y-0.5">
                                                    {g.chat_id ? (
                                                        <div className="flex items-center gap-1 text-slate-700 dark:text-slate-300">
                                                            <Hash className="h-3 w-3 text-slate-400" />
                                                            {g.chat_id}
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted-foreground italic text-[11px]">No Chat ID</span>
                                                    )}
                                                    {g.username && (
                                                        <div className="text-blue-600 dark:text-blue-400 font-medium">@{g.username}</div>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="text-xs space-y-0.5">
                                                    {g.branch && (
                                                        <div className="flex items-center gap-1 text-slate-700 dark:text-slate-300 font-medium">
                                                            <Building2 className="h-3 w-3 text-slate-400" />
                                                            {g.branch.name || g.branch.branch_name || `Branch #${g.branch.id}`}
                                                        </div>
                                                    )}
                                                    {g.department && (
                                                        <div className="text-muted-foreground">{g.department.name}</div>
                                                    )}
                                                    {!g.branch && !g.department && (
                                                        <span className="text-muted-foreground italic">—</span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <span className="text-sm font-medium">
                                                    {g.member_count !== null ? g.member_count.toLocaleString() : '—'}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                <Badge className={`text-[10px] px-2 py-0.5 font-medium border-0 ${statusColor(g.status)}`}>
                                                    {g.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-1.5">
                                                    {g.invite_link && (
                                                        <a
                                                            href={g.invite_link}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-950/30 dark:text-blue-400 transition-colors shadow-2xs"
                                                            title="Open Telegram Link"
                                                        >
                                                            <ExternalLink className="h-3.5 w-3.5" />
                                                        </a>
                                                    )}
                                                    {g.qr_code_path && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setQrModalGroup(g)}
                                                            className="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 dark:bg-purple-950/30 dark:text-purple-400 transition-colors shadow-2xs"
                                                            title="View / Download QR Code"
                                                        >
                                                            <QrCode className="h-3.5 w-3.5" />
                                                        </button>
                                                    )}
                                                    {!g.invite_link && !g.qr_code_path && (
                                                        <span className="text-xs text-muted-foreground italic">—</span>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Link href={`/telecom/telegram-groups/${g.id}/edit`}>
                                                        <Button size="sm" variant="ghost" className="h-8 w-8 p-0 text-slate-500 hover:text-blue-600 hover:bg-blue-50">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    </Link>
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        className="h-8 w-8 p-0 text-slate-500 hover:text-red-600 hover:bg-red-50"
                                                        onClick={() => handleDelete(g.id)}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
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
                    {groups.last_page > 1 && (
                        <div className="flex items-center justify-between border-t px-4 py-3">
                            <p className="text-xs text-muted-foreground">
                                Showing {groups.from ?? 0}–{groups.to ?? 0} of {groups.total} groups
                            </p>
                            <div className="flex gap-1">
                                {groups.links.map((link, i) => (
                                    <button
                                        key={i}
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url, {}, { preserveScroll: true })}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`rounded px-2.5 py-1 text-xs transition-colors ${
                                            link.active
                                                ? 'bg-blue-600 text-white font-semibold'
                                                : link.url
                                                ? 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                                                : 'cursor-not-allowed opacity-40 bg-slate-50 text-slate-400'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </Card>
            </div>

            {/* QR Code Modal */}
            <Dialog open={!!qrModalGroup} onOpenChange={(open) => !open && setQrModalGroup(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <QrCode className="h-5 w-5 text-purple-600" />
                            <span>{qrModalGroup?.name} QR Code</span>
                        </DialogTitle>
                        <DialogDescription>
                            Scan with your phone or camera to join this Telegram group or channel.
                        </DialogDescription>
                    </DialogHeader>

                    {qrModalGroup?.qr_code_path && (
                        <div className="flex flex-col items-center justify-center gap-4 py-4">
                            <div className="rounded-xl border bg-white p-4 shadow-xs dark:bg-slate-900">
                                <img
                                    src={`/storage/${qrModalGroup.qr_code_path}`}
                                    alt={`QR Code for ${qrModalGroup.name}`}
                                    className="h-56 w-56 object-contain"
                                />
                            </div>

                            {qrModalGroup.invite_link && (
                                <a
                                    href={qrModalGroup.invite_link}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-xs text-blue-600 hover:underline dark:text-blue-400 break-all text-center max-w-xs"
                                >
                                    {qrModalGroup.invite_link}
                                </a>
                            )}

                            <div className="flex items-center gap-2 w-full pt-2">
                                <a
                                    href={`/storage/${qrModalGroup.qr_code_path}`}
                                    download={`${qrModalGroup.name.replace(/\s+/g, '_')}_QR.png`}
                                    className="flex-1"
                                >
                                    <Button className="w-full gap-1.5" variant="outline" size="sm">
                                        <Download className="h-4 w-4" /> Download QR
                                    </Button>
                                </a>
                                {qrModalGroup.invite_link && (
                                    <a
                                        href={qrModalGroup.invite_link}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="flex-1"
                                    >
                                        <Button className="w-full gap-1.5 bg-blue-600 hover:bg-blue-700" size="sm">
                                            <ExternalLink className="h-4 w-4" /> Open Link
                                        </Button>
                                    </a>
                                )}
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
