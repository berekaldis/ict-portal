import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    CheckCircle2,
    Edit2,
    Filter,
    KeyRound,
    Lock,
    PlusCircle,
    Power,
    RefreshCw,
    Search,
    Shield,
    Users,
    X,
} from 'lucide-react';
import React, { useState } from 'react';

interface UserItem {
    id: number;
    username: string;
    name: string;
    role?: string;
    email?: string;
    department?: string;
    branch?: string;
    is_active: boolean;
    roles: Array<{ id: number; name: string }>;
}

interface Props {
    users: {
        data: UserItem[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    filters: any;
    roles: string[];
    branches: string[];
}

export default function UsersIndex({ users, filters, roles, branches }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [role, setRole] = useState(filters.role || 'All');
    const [status, setStatus] = useState(filters.status || 'All');
    const [syncing, setSyncing] = useState(false);

    // Reset Password Modal
    const [resetUser, setResetUser] = useState<UserItem | null>(null);
    const [newPassword, setNewPassword] = useState('admin123');

    const applyFilter = (newOverrides: Record<string, any> = {}) => {
        router.get(
            route('users.index'),
            {
                search,
                role,
                status,
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
        setRole('All');
        setStatus('All');
        router.get(route('users.index'), {}, { replace: true });
    };

    const handleSyncAll = () => {
        setSyncing(true);
        router.post(route('users.sync-all-users'), {}, {
            onFinish: () => setSyncing(false),
        });
    };

    const handleToggleActive = (id: number) => {
        router.post(route('users.toggle-active', id));
    };

    const handleResetPassword = () => {
        if (!resetUser) return;

        router.post(
            route('users.reset-password', resetUser.id),
            { password: newPassword },
            {
                onSuccess: () => setResetUser(null),
            }
        );
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Access Control', href: '/users' }, { title: 'Users' }]}>
            <Head title="User Management" />

            <div className="space-y-4">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div className="flex items-center gap-2">
                            <Users className="h-5 w-5 text-primary" />
                            <h2 className="text-xl font-bold tracking-tight text-foreground">User Management</h2>
                            <Badge variant="secondary" className="bg-primary/10 text-primary text-xs">
                                {users.total} Total
                            </Badge>
                        </div>
                        <p className="text-xs text-muted-foreground mt-0.5">
                            Manage user accounts, department associations, and system roles
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            className="h-9"
                            onClick={handleSyncAll}
                            disabled={syncing}
                        >
                            <RefreshCw className={`h-4 w-4 mr-1.5 ${syncing ? 'animate-spin' : ''}`} />
                            {syncing ? 'Syncing...' : 'Sync All 769 Users'}
                        </Button>
                        <Button asChild size="sm" className="h-9">
                            <Link href={route('users.create')}>
                                <PlusCircle className="h-4 w-4 mr-1.5" /> Create User
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <div className="p-3.5 rounded-xl border border-border/60 bg-card shadow-xs">
                    <form onSubmit={handleSearch} className="flex flex-col md:flex-row gap-2.5 items-stretch md:items-center">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search by name, username, email, branch..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-9 h-9"
                            />
                        </div>

                        <div className="flex items-center gap-2 flex-wrap">
                            <Select
                                value={role}
                                onValueChange={(val) => {
                                    setRole(val);
                                    applyFilter({ role: val });
                                }}
                            >
                                <SelectTrigger className="w-[140px] h-9 text-xs">
                                    <SelectValue placeholder="Role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="All">All Roles</SelectItem>
                                    {roles.map((r) => (
                                        <SelectItem key={r} value={r}>{r}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select
                                value={status}
                                onValueChange={(val) => {
                                    setStatus(val);
                                    applyFilter({ status: val });
                                }}
                            >
                                <SelectTrigger className="w-[120px] h-9 text-xs">
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="All">All Status</SelectItem>
                                    <SelectItem value="active">Active Only</SelectItem>
                                    <SelectItem value="inactive">Inactive Only</SelectItem>
                                </SelectContent>
                            </Select>

                            <Button type="submit" variant="secondary" size="sm" className="h-9 text-xs">
                                <Filter className="h-3.5 w-3.5 mr-1" /> Filter
                            </Button>

                            {(search || role !== 'All' || status !== 'All') && (
                                <Button type="button" variant="ghost" size="sm" onClick={clearFilters} className="h-9 text-xs text-muted-foreground">
                                    <X className="h-3.5 w-3.5 mr-1" /> Reset
                                </Button>
                            )}
                        </div>
                    </form>
                </div>

                {/* Users Table */}
                <div className="rounded-xl border border-border/60 bg-card overflow-hidden shadow-xs">
                    <Table>
                        <TableHeader className="bg-muted/30">
                            <TableRow>
                                <TableHead className="w-[160px]">Username</TableHead>
                                <TableHead className="w-[180px]">Full Name</TableHead>
                                <TableHead className="w-[180px]">Role / Department</TableHead>
                                <TableHead className="w-[140px]">Branch</TableHead>
                                <TableHead className="w-[100px]">Status</TableHead>
                                <TableHead className="w-[140px] text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center py-8 text-xs text-muted-foreground">
                                        No users match the criteria.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                users.data.map((u) => (
                                    <TableRow key={u.id}>
                                        <TableCell className="font-mono text-xs font-bold text-foreground">
                                            {u.username}
                                            {u.email && (
                                                <div className="text-[10px] text-muted-foreground font-sans font-normal truncate max-w-[150px]">
                                                    {u.email}
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-xs font-medium text-foreground">
                                            {u.name}
                                        </TableCell>
                                        <TableCell className="text-xs space-y-1">
                                            <div className="flex flex-wrap gap-1">
                                                {u.roles && u.roles.map((r) => (
                                                    <Badge key={r.id || r.name} variant="secondary" className="text-[10px] py-0 font-medium bg-primary/10 text-primary">
                                                        {r.name}
                                                    </Badge>
                                                ))}
                                            </div>
                                            {u.department && (
                                                <div className="text-[10px] text-muted-foreground">Dept: {u.department}</div>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-xs text-foreground/80">
                                            {u.branch || <span className="text-muted-foreground italic">-</span>}
                                        </TableCell>
                                        <TableCell>
                                            {u.is_active ? (
                                                <Badge variant="secondary" className="bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 font-medium text-[11px]">
                                                    Active
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-muted-foreground text-[11px]">
                                                    Inactive
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex items-center justify-end gap-1">
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    className="h-7 w-7 text-muted-foreground hover:text-foreground"
                                                    onClick={() => setResetUser(u)}
                                                    title="Reset Password"
                                                >
                                                    <KeyRound className="h-3.5 w-3.5" />
                                                </Button>

                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    className={`h-7 w-7 ${u.is_active ? 'text-amber-600 hover:text-amber-700' : 'text-emerald-600 hover:text-emerald-700'}`}
                                                    onClick={() => handleToggleActive(u.id)}
                                                    title={u.is_active ? 'Deactivate User' : 'Activate User'}
                                                >
                                                    <Power className="h-3.5 w-3.5" />
                                                </Button>

                                                <Button asChild size="icon" variant="ghost" className="h-7 w-7 text-primary">
                                                    <Link href={route('users.edit', u.id)}>
                                                        <Edit2 className="h-3.5 w-3.5" />
                                                    </Link>
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
                {users.last_page > 1 && (
                    <div className="flex items-center justify-between text-xs text-muted-foreground pt-1">
                        <div>
                            Showing page {users.current_page} of {users.last_page} ({users.total} users)
                        </div>
                        <div className="flex items-center gap-1.5">
                            {users.prev_page_url && (
                                <Button asChild variant="outline" size="sm" className="h-8 text-xs">
                                    <Link href={users.prev_page_url}>Previous</Link>
                                </Button>
                            )}
                            {users.next_page_url && (
                                <Button asChild variant="outline" size="sm" className="h-8 text-xs">
                                    <Link href={users.next_page_url}>Next</Link>
                                </Button>
                            )}
                        </div>
                    </div>
                )}
            </div>

            {/* Reset Password Dialog */}
            <Dialog open={!!resetUser} onOpenChange={(open) => !open && setResetUser(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Reset Password for {resetUser?.username}</DialogTitle>
                        <DialogDescription>
                            Enter a temporary password. The user will be required to change it upon next login.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-3 py-2">
                        <Label htmlFor="new_password">New Temporary Password</Label>
                        <Input
                            id="new_password"
                            value={newPassword}
                            onChange={(e) => setNewPassword(e.target.value)}
                        />
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setResetUser(null)}>Cancel</Button>
                        <Button onClick={handleResetPassword}>Reset Password</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
