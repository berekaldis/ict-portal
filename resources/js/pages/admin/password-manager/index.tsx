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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Activity,
    Check,
    CheckCircle2,
    Clock,
    Copy,
    Database,
    Download,
    Edit2,
    ExternalLink,
    Eye,
    EyeOff,
    Filter,
    Globe,
    HardDrive,
    Info,
    Key,
    KeyRound,
    Lock,
    Network,
    PlusCircle,
    RefreshCw,
    Search,
    Server,
    Shield,
    ShieldAlert,
    Trash2,
    Video,
    Wifi,
    X,
} from 'lucide-react';
import React, { useMemo, useState } from 'react';
import { toast } from 'sonner';

interface PasswordEntryItem {
    id: number;
    title: string;
    device_type?: string | null;
    category: string;
    ip_address?: string | null;
    port?: number | null;
    protocol?: string | null;
    link_url?: string | null;
    username?: string | null;
    auth_type: string;
    branch_name?: string | null;
    environment: string;
    notes?: string | null;
    is_active: boolean;
    has_password?: boolean;
    has_secondary_secret?: boolean;
    created_at?: string;
    updated_at?: string;
    last_revealed_at?: string | null;
    creator?: { id: number; name: string } | null;
    updater?: { id: number; name: string } | null;
    revealer?: { id: number; name: string } | null;
}

interface AuditLogItem {
    id: number;
    user_id?: number | null;
    entry_id?: number | null;
    action: string;
    details?: string | null;
    ip_address?: string | null;
    created_at: string;
    user?: { id: number; name: string } | null;
    entry?: { id: number; title: string; category: string } | null;
}

interface Props {
    entries: {
        data: PasswordEntryItem[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url?: string;
        next_page_url?: string;
    };
    stats: {
        total: number;
        network_count: number;
        cctv_count: number;
        server_count: number;
        cloud_count: number;
    };
    branches: string[];
    categories: string[];
    authTypes: string[];
    protocols: string[];
    recentLogs: AuditLogItem[];
    filters: {
        search?: string;
        category?: string;
        branch?: string;
        auth_type?: string;
        environment?: string;
        status?: string;
    };
}

export default function PasswordManagerIndex({
    entries,
    stats,
    branches,
    categories,
    authTypes,
    protocols,
    recentLogs,
    filters,
}: Props) {
    const { auth } = usePage().props as any;
    const canManage =
        auth?.permissions?.includes('password_manager.manage') ||
        auth?.isItTeam ||
        auth?.roles?.includes('Super Admin') ||
        auth?.roles?.includes('IT Admin');
    const canReveal =
        auth?.permissions?.includes('password_manager.reveal') ||
        auth?.isItTeam ||
        auth?.roles?.includes('Super Admin') ||
        auth?.roles?.includes('IT Admin');

    // Filters state
    const [search, setSearch] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category || 'all');
    const [selectedBranch, setSelectedBranch] = useState(filters.branch || 'all');
    const [selectedAuthType, setSelectedAuthType] = useState(filters.auth_type || 'all');
    const [selectedEnvironment, setSelectedEnvironment] = useState(filters.environment || 'all');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || 'active');

    // Modals
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editModalOpen, setEditModalOpen] = useState(false);
    const [editingItem, setEditingItem] = useState<PasswordEntryItem | null>(null);
    const [detailModalOpen, setDetailModalOpen] = useState(false);
    const [viewingItem, setViewingItem] = useState<PasswordEntryItem | null>(null);
    const [generatorModalOpen, setGeneratorModalOpen] = useState(false);
    const [auditLogsModalOpen, setAuditLogsModalOpen] = useState(false);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [deletingItem, setDeletingItem] = useState<PasswordEntryItem | null>(null);

    // Decrypted secrets store: { [id: number]: { password?: string; secondary?: string; timeoutId?: any } }
    const [revealedSecrets, setRevealedSecrets] = useState<{
        [id: number]: { password?: string; secondary_secret?: string };
    }>({});
    const [revealingId, setRevealingId] = useState<number | null>(null);

    // Form for Create
    const createForm = useForm({
        title: '',
        device_type: '',
        category: 'Router & Switch',
        ip_address: '',
        port: '',
        protocol: 'HTTPS',
        link_url: '',
        username: 'admin',
        password: '',
        auth_type: 'Password',
        secondary_secret: '',
        branch_name: 'Head Office',
        environment: 'Production',
        notes: '',
        is_active: true,
    });

    // Form for Edit
    const editForm = useForm({
        title: '',
        device_type: '',
        category: 'Router & Switch',
        ip_address: '',
        port: '',
        protocol: 'HTTPS',
        link_url: '',
        username: '',
        password: '',
        auth_type: 'Password',
        secondary_secret: '',
        branch_name: 'Head Office',
        environment: 'Production',
        notes: '',
        is_active: true,
    });

    // Password Generator state
    const [genLength, setGenLength] = useState(20);
    const [genUpper, setGenUpper] = useState(true);
    const [genLower, setGenLower] = useState(true);
    const [genNums, setGenNums] = useState(true);
    const [genSyms, setGenSyms] = useState(true);
    const [generatedPassword, setGeneratedPassword] = useState('');

    const generateRandomPassword = (
        len = genLength,
        upper = genUpper,
        lower = genLower,
        nums = genNums,
        syms = genSyms
    ) => {
        let chars = '';
        if (upper) chars += 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        if (lower) chars += 'abcdefghijkmnpqrstuvwxyz';
        if (nums) chars += '23456789';
        if (syms) chars += '!@#$%^&*()-_=+[]{}|;:,.<>?';

        if (!chars) chars = 'abcdefghijkmnpqrstuvwxyz23456789';

        let res = '';
        const array = new Uint32Array(len);
        window.crypto.getRandomValues(array);
        for (let i = 0; i < len; i++) {
            res += chars[array[i] % chars.length];
        }
        return res;
    };

    const handleRunGenerator = () => {
        const pw = generateRandomPassword();
        setGeneratedPassword(pw);
    };

    // Filter submit
    const applyFilters = (overrides?: any) => {
        const params: any = {
            search: search.trim() || undefined,
            category: selectedCategory !== 'all' ? selectedCategory : undefined,
            branch: selectedBranch !== 'all' ? selectedBranch : undefined,
            auth_type: selectedAuthType !== 'all' ? selectedAuthType : undefined,
            environment: selectedEnvironment !== 'all' ? selectedEnvironment : undefined,
            status: selectedStatus !== 'active' ? selectedStatus : undefined,
            ...overrides,
        };

        router.get(route('admin.password-manager.index'), params, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearch('');
        setSelectedCategory('all');
        setSelectedBranch('all');
        setSelectedAuthType('all');
        setSelectedEnvironment('all');
        setSelectedStatus('active');
        router.get(route('admin.password-manager.index'), {}, { replace: true });
    };

    // Reveal Password
    const handleRevealSecret = async (entryId: number) => {
        if (!canReveal) {
            toast.error('You do not have permission to reveal passwords.');
            return;
        }

        if (revealedSecrets[entryId]) {
            // Already revealed -> hide it
            setRevealedSecrets((prev) => {
                const copy = { ...prev };
                delete copy[entryId];
                return copy;
            });
            return;
        }

        setRevealingId(entryId);
        try {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/admin/password-manager/${entryId}/reveal`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            });

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || 'Unauthorized or failed to reveal password');
            }

            const data = await res.json();
            setRevealedSecrets((prev) => ({
                ...prev,
                [entryId]: {
                    password: data.password,
                    secondary_secret: data.secondary_secret,
                },
            }));
            toast.success('Password revealed for 60 seconds (audit logged).');

            // Automatically hide after 60 seconds
            setTimeout(() => {
                setRevealedSecrets((prev) => {
                    const copy = { ...prev };
                    delete copy[entryId];
                    return copy;
                });
            }, 60000);
        } catch (err: any) {
            toast.error(err.message || 'Could not reveal secret.');
        } finally {
            setRevealingId(null);
        }
    };

    // Copy to clipboard helper
    const copyToClipboard = async (text?: string | null, label = 'Password') => {
        if (!text) {
            toast.error(`No ${label.toLowerCase()} available to copy.`);
            return;
        }
        try {
            await navigator.clipboard.writeText(text);
            toast.success(`${label} copied to clipboard!`);
        } catch (e) {
            toast.error('Failed to copy to clipboard.');
        }
    };

    // Quick copy secret (fetches if not revealed, copies directly)
    const handleQuickCopySecret = async (entryId: number, label = 'Password') => {
        if (!canReveal) {
            toast.error('You do not have permission to copy passwords.');
            return;
        }
        if (revealedSecrets[entryId]?.password) {
            copyToClipboard(revealedSecrets[entryId].password, label);
            return;
        }

        setRevealingId(entryId);
        try {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(`/admin/password-manager/${entryId}/reveal`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            });

            if (!res.ok) throw new Error('Failed to copy');
            const data = await res.json();
            copyToClipboard(data.password, label);
            setRevealedSecrets((prev) => ({
                ...prev,
                [entryId]: {
                    password: data.password,
                    secondary_secret: data.secondary_secret,
                },
            }));
        } catch (err: any) {
            toast.error('Failed to fetch and copy secret.');
        } finally {
            setRevealingId(null);
        }
    };

    // Open Edit Modal
    const openEditModal = (item: PasswordEntryItem) => {
        setEditingItem(item);
        editForm.setData({
            title: item.title,
            device_type: item.device_type || '',
            category: item.category,
            ip_address: item.ip_address || '',
            port: item.port ? String(item.port) : '',
            protocol: item.protocol || 'HTTPS',
            link_url: item.link_url || '',
            username: item.username || '',
            password: '', // Keep empty unless updating
            auth_type: item.auth_type,
            secondary_secret: '',
            branch_name: item.branch_name || 'Head Office',
            environment: item.environment,
            notes: item.notes || '',
            is_active: item.is_active,
        });
        setEditModalOpen(true);
    };

    // Submit Create
    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post(route('admin.password-manager.store'), {
            onSuccess: () => {
                toast.success('New credential successfully encrypted and stored in vault.');
                setCreateModalOpen(false);
                createForm.reset();
            },
            onError: (err) => {
                const firstErr = Object.values(err)[0] as string;
                toast.error(firstErr || 'Failed to save credential.');
            },
        });
    };

    // Submit Edit
    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingItem) return;
        editForm.put(route('admin.password-manager.update', editingItem.id), {
            onSuccess: () => {
                toast.success('Credential updated successfully.');
                setEditModalOpen(false);
                setEditingItem(null);
                editForm.reset();
            },
            onError: (err) => {
                const firstErr = Object.values(err)[0] as string;
                toast.error(firstErr || 'Failed to update credential.');
            },
        });
    };

    // Confirm Delete
    const handleDelete = () => {
        if (!deletingItem) return;
        router.delete(route('admin.password-manager.destroy', deletingItem.id), {
            onSuccess: () => {
                toast.success('Credential deleted from vault.');
                setDeleteModalOpen(false);
                setDeletingItem(null);
            },
            onError: () => {
                toast.error('Failed to delete credential.');
            },
        });
    };

    // Category styling helper
    const getCategoryBadge = (cat: string) => {
        switch (cat) {
            case 'Router & Switch':
                return (
                    <Badge className="bg-blue-600/10 text-blue-700 hover:bg-blue-600/20 border-blue-200">
                        <Network className="w-3 h-3 mr-1" />
                        Router & Switch
                    </Badge>
                );
            case 'CCTV & NVR':
                return (
                    <Badge className="bg-amber-600/10 text-amber-700 hover:bg-amber-600/20 border-amber-200">
                        <Video className="w-3 h-3 mr-1" />
                        CCTV & NVR
                    </Badge>
                );
            case 'Server & VM':
                return (
                    <Badge className="bg-purple-600/10 text-purple-700 hover:bg-purple-600/20 border-purple-200">
                        <Server className="w-3 h-3 mr-1" />
                        Server & VM
                    </Badge>
                );
            case 'Firewall & Security':
                return (
                    <Badge className="bg-rose-600/10 text-rose-700 hover:bg-rose-600/20 border-rose-200">
                        <Shield className="w-3 h-3 mr-1" />
                        Firewall & Security
                    </Badge>
                );
            case 'Wi-Fi Access Point':
                return (
                    <Badge className="bg-cyan-600/10 text-cyan-700 hover:bg-cyan-600/20 border-cyan-200">
                        <Wifi className="w-3 h-3 mr-1" />
                        Wi-Fi AP
                    </Badge>
                );
            case 'ERP & Database':
                return (
                    <Badge className="bg-indigo-600/10 text-indigo-700 hover:bg-indigo-600/20 border-indigo-200">
                        <Database className="w-3 h-3 mr-1" />
                        ERP & DB
                    </Badge>
                );
            case 'Web & Cloud Service':
            case 'Email & Communication':
                return (
                    <Badge className="bg-emerald-600/10 text-emerald-700 hover:bg-emerald-600/20 border-emerald-200">
                        <Globe className="w-3 h-3 mr-1" />
                        {cat}
                    </Badge>
                );
            default:
                return (
                    <Badge variant="outline" className="text-gray-700">
                        <HardDrive className="w-3 h-3 mr-1" />
                        {cat}
                    </Badge>
                );
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'System Administration', href: '/users' },
                { title: 'Password Manager', href: '/admin/password-manager' },
            ]}
        >
            <Head title="Password & Credential Manager - Kaldis ICT" />

            <div className="p-4 md:p-6 space-y-6 max-w-7xl mx-auto">
                {/* Header Section */}
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-border/40">
                    <div>
                        <div className="flex items-center gap-2.5">
                            <div className="p-2.5 rounded-xl bg-amber-500/10 text-amber-600 border border-amber-500/20">
                                <KeyRound className="w-6 h-6" />
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
                                    Password & Credential Manager
                                    <Badge variant="secondary" className="text-xs font-normal">
                                        Vault AES-256
                                    </Badge>
                                </h1>
                                <p className="text-xs md:text-sm text-muted-foreground mt-0.5">
                                    Manage device logins, network switches, Dahua NVRs, server portals, management URLs, and encrypted passwords.
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Header Action Buttons */}
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            className="h-9 gap-1.5"
                            onClick={() => {
                                handleRunGenerator();
                                setGeneratorModalOpen(true);
                            }}
                        >
                            <Key className="w-4 h-4 text-amber-600" />
                            <span>Password Generator</span>
                        </Button>

                        <Button
                            variant="outline"
                            size="sm"
                            className="h-9 gap-1.5"
                            onClick={() => setAuditLogsModalOpen(true)}
                        >
                            <Activity className="w-4 h-4 text-blue-600" />
                            <span>Audit Log</span>
                        </Button>

                        <a
                            href={route('admin.password-manager.export', {
                                search: search.trim() || undefined,
                                category: selectedCategory !== 'all' ? selectedCategory : undefined,
                                branch: selectedBranch !== 'all' ? selectedBranch : undefined,
                                auth_type: selectedAuthType !== 'all' ? selectedAuthType : undefined,
                                environment: selectedEnvironment !== 'all' ? selectedEnvironment : undefined,
                            })}
                            className="inline-flex"
                        >
                            <Button variant="outline" size="sm" className="h-9 gap-1.5">
                                <Download className="w-4 h-4 text-emerald-600" />
                                <span>Export CSV</span>
                            </Button>
                        </a>

                        {canManage && (
                            <Button
                                size="sm"
                                className="h-9 gap-1.5 bg-amber-600 hover:bg-amber-700 text-white shadow-sm"
                                onClick={() => {
                                    createForm.reset();
                                    createForm.setData('password', generateRandomPassword(20));
                                    setCreateModalOpen(true);
                                }}
                            >
                                <PlusCircle className="w-4 h-4" />
                                <span>Add Credential</span>
                            </Button>
                        )}
                    </div>
                </div>

                {/* KPI / Stats Cards */}
                <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
                    <Card className="border border-border/60 shadow-xs">
                        <CardHeader className="p-3 pb-1">
                            <CardTitle className="text-xs font-medium text-muted-foreground flex items-center justify-between">
                                Total Vault
                                <KeyRound className="w-4 h-4 text-amber-500" />
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-3 pt-0">
                            <div className="text-2xl font-bold text-foreground">{stats.total}</div>
                            <p className="text-[11px] text-muted-foreground mt-0.5">Active credentials</p>
                        </CardContent>
                    </Card>

                    <Card className="border border-border/60 shadow-xs">
                        <CardHeader className="p-3 pb-1">
                            <CardTitle className="text-xs font-medium text-muted-foreground flex items-center justify-between">
                                Network & APs
                                <Network className="w-4 h-4 text-blue-500" />
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-3 pt-0">
                            <div className="text-2xl font-bold text-foreground">{stats.network_count}</div>
                            <p className="text-[11px] text-muted-foreground mt-0.5">Routers, switches, APs</p>
                        </CardContent>
                    </Card>

                    <Card className="border border-border/60 shadow-xs">
                        <CardHeader className="p-3 pb-1">
                            <CardTitle className="text-xs font-medium text-muted-foreground flex items-center justify-between">
                                CCTV & NVRs
                                <Video className="w-4 h-4 text-amber-500" />
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-3 pt-0">
                            <div className="text-2xl font-bold text-foreground">{stats.cctv_count}</div>
                            <p className="text-[11px] text-muted-foreground mt-0.5">Branch Dahua NVRs</p>
                        </CardContent>
                    </Card>

                    <Card className="border border-border/60 shadow-xs">
                        <CardHeader className="p-3 pb-1">
                            <CardTitle className="text-xs font-medium text-muted-foreground flex items-center justify-between">
                                Servers & DB
                                <Server className="w-4 h-4 text-purple-500" />
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-3 pt-0">
                            <div className="text-2xl font-bold text-foreground">{stats.server_count}</div>
                            <p className="text-[11px] text-muted-foreground mt-0.5">ERP, VMs & Databases</p>
                        </CardContent>
                    </Card>

                    <Card className="border border-border/60 shadow-xs col-span-2 md:col-span-1">
                        <CardHeader className="p-3 pb-1">
                            <CardTitle className="text-xs font-medium text-muted-foreground flex items-center justify-between">
                                Cloud & Web
                                <Globe className="w-4 h-4 text-emerald-500" />
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-3 pt-0">
                            <div className="text-2xl font-bold text-foreground">{stats.cloud_count}</div>
                            <p className="text-[11px] text-muted-foreground mt-0.5">Portals & Services</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filter and Search Bar */}
                <Card className="border border-border/60 shadow-xs">
                    <CardContent className="p-3 md:p-4 space-y-3">
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                applyFilters();
                            }}
                            className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-2.5 items-end"
                        >
                            {/* Search */}
                            <div className="sm:col-span-2 md:col-span-4 space-y-1">
                                <Label className="text-xs font-medium text-muted-foreground">Search Vault</Label>
                                <div className="relative">
                                    <Search className="w-4 h-4 absolute left-3 top-2.5 text-muted-foreground" />
                                    <Input
                                        placeholder="Device title, IP address, username, URL..."
                                        className="pl-9 h-9 text-xs"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                    />
                                    {search && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setSearch('');
                                                applyFilters({ search: undefined });
                                            }}
                                            className="absolute right-2.5 top-2.5 text-muted-foreground hover:text-foreground"
                                        >
                                            <X className="w-3.5 h-3.5" />
                                        </button>
                                    )}
                                </div>
                            </div>

                            {/* Category Filter */}
                            <div className="md:col-span-2 space-y-1">
                                <Label className="text-xs font-medium text-muted-foreground">Category</Label>
                                <Select
                                    value={selectedCategory}
                                    onValueChange={(val) => {
                                        setSelectedCategory(val);
                                        applyFilters({ category: val !== 'all' ? val : undefined });
                                    }}
                                >
                                    <SelectTrigger className="h-9 text-xs">
                                        <SelectValue placeholder="All Categories" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Categories</SelectItem>
                                        {categories.map((c) => (
                                            <SelectItem key={c} value={c}>
                                                {c}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Branch Filter */}
                            <div className="md:col-span-2 space-y-1">
                                <Label className="text-xs font-medium text-muted-foreground">Branch / Location</Label>
                                <Select
                                    value={selectedBranch}
                                    onValueChange={(val) => {
                                        setSelectedBranch(val);
                                        applyFilters({ branch: val !== 'all' ? val : undefined });
                                    }}
                                >
                                    <SelectTrigger className="h-9 text-xs">
                                        <SelectValue placeholder="All Branches" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Locations</SelectItem>
                                        {branches.map((b) => (
                                            <SelectItem key={b} value={b}>
                                                {b}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Auth Type Filter */}
                            <div className="md:col-span-2 space-y-1">
                                <Label className="text-xs font-medium text-muted-foreground">Auth Type</Label>
                                <Select
                                    value={selectedAuthType}
                                    onValueChange={(val) => {
                                        setSelectedAuthType(val);
                                        applyFilters({ auth_type: val !== 'all' ? val : undefined });
                                    }}
                                >
                                    <SelectTrigger className="h-9 text-xs">
                                        <SelectValue placeholder="All Types" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Auth Types</SelectItem>
                                        {authTypes.map((a) => (
                                            <SelectItem key={a} value={a}>
                                                {a}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Actions Buttons */}
                            <div className="md:col-span-2 flex items-center gap-1.5">
                                <Button type="submit" size="sm" className="h-9 flex-1 gap-1 text-xs">
                                    <Filter className="w-3.5 h-3.5" />
                                    <span>Filter</span>
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="h-9 px-2 text-xs text-muted-foreground hover:text-foreground"
                                    onClick={clearFilters}
                                    title="Reset filters"
                                >
                                    <RefreshCw className="w-3.5 h-3.5" />
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Main Credentials Table */}
                <Card className="border border-border/60 shadow-xs overflow-hidden">
                    <div className="overflow-x-auto">
                        <Table>
                            <TableHeader className="bg-muted/30">
                                <TableRow>
                                    <TableHead className="w-[280px]">Device & Title</TableHead>
                                    <TableHead className="w-[240px]">Access & Management Link</TableHead>
                                    <TableHead className="w-[180px]">Username & Auth</TableHead>
                                    <TableHead className="w-[220px]">Encrypted Password</TableHead>
                                    <TableHead className="w-[150px]">Location</TableHead>
                                    <TableHead className="w-[110px] text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {entries.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="h-44 text-center">
                                            <div className="flex flex-col items-center justify-center text-muted-foreground space-y-2">
                                                <KeyRound className="w-8 h-8 text-muted-foreground/40" />
                                                <div className="text-sm font-medium">No credentials found in vault</div>
                                                <div className="text-xs text-muted-foreground max-w-sm">
                                                    {search || selectedCategory !== 'all' || selectedBranch !== 'all'
                                                        ? 'Try adjusting your search criteria or clearing filters.'
                                                        : 'Get started by adding your first device login or NVR password.'}
                                                </div>
                                                {canManage && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="mt-2 text-xs"
                                                        onClick={() => {
                                                            createForm.reset();
                                                            setCreateModalOpen(true);
                                                        }}
                                                    >
                                                        <PlusCircle className="w-3.5 h-3.5 mr-1 text-amber-600" />
                                                        Add New Credential
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    entries.data.map((item) => {
                                        const isRevealed = !!revealedSecrets[item.id];
                                        const revealedSecret = revealedSecrets[item.id]?.password;
                                        const isRevealingThis = revealingId === item.id;

                                        return (
                                            <TableRow key={item.id} className="hover:bg-muted/20">
                                                {/* Device & Title */}
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <div className="font-semibold text-foreground text-sm flex items-center gap-1.5">
                                                            {item.title}
                                                            {!item.is_active && (
                                                                <Badge
                                                                    variant="outline"
                                                                    className="text-[10px] px-1 py-0 text-muted-foreground"
                                                                >
                                                                    Archived
                                                                </Badge>
                                                            )}
                                                        </div>
                                                        <div className="flex items-center gap-1.5 flex-wrap">
                                                            {getCategoryBadge(item.category)}
                                                            {item.device_type && (
                                                                <span className="text-[11px] text-muted-foreground font-mono bg-muted/60 px-1.5 py-0.5 rounded">
                                                                    {item.device_type}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </TableCell>

                                                {/* Access & Management Link */}
                                                <TableCell>
                                                    <div className="space-y-1 text-xs">
                                                        {item.ip_address ? (
                                                            <div className="flex items-center gap-1.5 font-mono text-foreground font-medium">
                                                                <span>
                                                                    {item.ip_address}
                                                                    {item.port ? `:${item.port}` : ''}
                                                                </span>
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        copyToClipboard(
                                                                            `${item.ip_address}${item.port ? `:${item.port}` : ''}`,
                                                                            'IP & Port'
                                                                        )
                                                                    }
                                                                    className="text-muted-foreground hover:text-foreground transition-colors p-0.5"
                                                                    title="Copy IP:Port"
                                                                >
                                                                    <Copy className="w-3 h-3" />
                                                                </button>
                                                                {item.protocol && (
                                                                    <Badge
                                                                        variant="outline"
                                                                        className="text-[10px] px-1 py-0 uppercase"
                                                                    >
                                                                        {item.protocol}
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <span className="text-muted-foreground text-[11px]">
                                                                No IP assigned
                                                            </span>
                                                        )}

                                                        {/* Clickable Management Link */}
                                                        {item.link_url ? (
                                                            <div className="pt-0.5">
                                                                <a
                                                                    href={
                                                                        item.link_url.startsWith('http')
                                                                            ? item.link_url
                                                                            : `https://${item.link_url}`
                                                                    }
                                                                    target="_blank"
                                                                    rel="noreferrer"
                                                                    className="inline-flex items-center gap-1 text-[11px] font-medium text-amber-700 hover:text-amber-800 dark:text-amber-500 hover:underline"
                                                                    title={item.link_url}
                                                                >
                                                                    <ExternalLink className="w-3 h-3" />
                                                                    <span className="truncate max-w-[160px]">
                                                                        Open Web GUI
                                                                    </span>
                                                                </a>
                                                            </div>
                                                        ) : null}
                                                    </div>
                                                </TableCell>

                                                {/* Username & Auth */}
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        {item.username ? (
                                                            <div className="flex items-center gap-1 font-mono text-xs text-foreground font-medium">
                                                                <span>{item.username}</span>
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        copyToClipboard(item.username, 'Username')
                                                                    }
                                                                    className="text-muted-foreground hover:text-foreground p-0.5"
                                                                    title="Copy username"
                                                                >
                                                                    <Copy className="w-3 h-3" />
                                                                </button>
                                                            </div>
                                                        ) : (
                                                            <span className="text-xs text-muted-foreground italic">
                                                                (None)
                                                            </span>
                                                        )}
                                                        <Badge variant="secondary" className="text-[10px] font-normal">
                                                            {item.auth_type}
                                                        </Badge>
                                                    </div>
                                                </TableCell>

                                                {/* Encrypted Password Cell */}
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <div className="flex items-center gap-1.5">
                                                            <div className="font-mono text-xs bg-muted/50 border border-border/50 px-2 py-1 rounded max-w-[140px] truncate select-all">
                                                                {isRevealed ? (
                                                                    <span className="text-emerald-700 dark:text-emerald-400 font-semibold">
                                                                        {revealedSecret}
                                                                    </span>
                                                                ) : (
                                                                    <span className="text-muted-foreground tracking-widest">
                                                                        ••••••••••••
                                                                    </span>
                                                                )}
                                                            </div>

                                                            {/* Reveal / Hide Toggle */}
                                                            {canReveal && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleRevealSecret(item.id)}
                                                                    disabled={isRevealingThis}
                                                                    className="p-1 rounded hover:bg-muted text-muted-foreground hover:text-foreground transition-colors disabled:opacity-50"
                                                                    title={isRevealed ? 'Hide Password' : 'Reveal Password'}
                                                                >
                                                                    {isRevealingThis ? (
                                                                        <RefreshCw className="w-3.5 h-3.5 animate-spin text-amber-600" />
                                                                    ) : isRevealed ? (
                                                                        <EyeOff className="w-3.5 h-3.5 text-amber-600" />
                                                                    ) : (
                                                                        <Eye className="w-3.5 h-3.5" />
                                                                    )}
                                                                </button>
                                                            )}

                                                            {/* Quick Copy Password */}
                                                            {canReveal && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleQuickCopySecret(item.id, 'Password')}
                                                                    disabled={isRevealingThis}
                                                                    className="p-1 rounded hover:bg-muted text-muted-foreground hover:text-emerald-600 transition-colors"
                                                                    title="Copy Password"
                                                                >
                                                                    <Copy className="w-3.5 h-3.5" />
                                                                </button>
                                                            )}
                                                        </div>

                                                        {item.has_secondary_secret && (
                                                            <div className="text-[10px] text-muted-foreground flex items-center gap-1">
                                                                <Shield className="w-2.5 h-2.5 text-blue-500" />
                                                                <span>Has 2FA/PIN secret</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                </TableCell>

                                                {/* Branch & Environment */}
                                                <TableCell>
                                                    <div className="space-y-0.5 text-xs">
                                                        <div className="font-medium text-foreground">
                                                            {item.branch_name || 'Head Office'}
                                                        </div>
                                                        <div className="text-[11px] text-muted-foreground flex items-center gap-1">
                                                            <span
                                                                className={`w-1.5 h-1.5 rounded-full ${
                                                                    item.environment === 'Production'
                                                                        ? 'bg-emerald-500'
                                                                        : item.environment === 'Branch Local'
                                                                        ? 'bg-amber-500'
                                                                        : 'bg-blue-500'
                                                                }`}
                                                            />
                                                            <span>{item.environment}</span>
                                                        </div>
                                                    </div>
                                                </TableCell>

                                                {/* Actions */}
                                                <TableCell className="text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="h-8 w-8 p-0"
                                                            onClick={() => {
                                                                setViewingItem(item);
                                                                setDetailModalOpen(true);
                                                            }}
                                                            title="View Details"
                                                        >
                                                            <Info className="w-3.5 h-3.5" />
                                                        </Button>

                                                        {canManage && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                                                onClick={() => openEditModal(item)}
                                                                title="Edit Credential"
                                                            >
                                                                <Edit2 className="w-3.5 h-3.5" />
                                                            </Button>
                                                        )}

                                                        {canManage && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                className="h-8 w-8 p-0 text-rose-600 hover:text-rose-700"
                                                                onClick={() => {
                                                                    setDeletingItem(item);
                                                                    setDeleteModalOpen(true);
                                                                }}
                                                                title="Delete Credential"
                                                            >
                                                                <Trash2 className="w-3.5 h-3.5" />
                                                            </Button>
                                                        )}
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
                    {entries.total > 15 && (
                        <div className="p-3 border-t border-border/50 flex items-center justify-between text-xs text-muted-foreground">
                            <div>
                                Showing {(entries.current_page - 1) * 15 + 1} to{' '}
                                {Math.min(entries.current_page * 15, entries.total)} of {entries.total} credentials
                            </div>
                            <div className="flex items-center gap-1.5">
                                {entries.prev_page_url && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="h-8 text-xs"
                                        onClick={() => router.get(entries.prev_page_url!)}
                                    >
                                        Previous
                                    </Button>
                                )}
                                <span className="px-2">
                                    Page {entries.current_page} of {entries.last_page}
                                </span>
                                {entries.next_page_url && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="h-8 text-xs"
                                        onClick={() => router.get(entries.next_page_url!)}
                                    >
                                        Next
                                    </Button>
                                )}
                            </div>
                        </div>
                    )}
                </Card>
            </div>

            {/* CREATE CREDENTIAL MODAL */}
            <Dialog open={createModalOpen} onOpenChange={setCreateModalOpen}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <PlusCircle className="w-5 h-5 text-amber-600" />
                            Add New Credential to Vault
                        </DialogTitle>
                        <DialogDescription>
                            Store network gear, Dahua NVR, server, or portal logins with AES-256 encryption.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleCreateSubmit} className="space-y-4 py-2">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Title */}
                            <div className="space-y-1.5 md:col-span-2">
                                <Label className="text-xs font-semibold">
                                    Device Name / Title <span className="text-rose-500">*</span>
                                </Label>
                                <Input
                                    placeholder="e.g. Bole Branch Dahua NVR or Main DC Core Switch"
                                    value={createForm.data.title}
                                    onChange={(e) => createForm.setData('title', e.target.value)}
                                    required
                                />
                                {createForm.errors.title && (
                                    <p className="text-xs text-rose-500">{createForm.errors.title}</p>
                                )}
                            </div>

                            {/* Category */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">
                                    Device Category <span className="text-rose-500">*</span>
                                </Label>
                                <Select
                                    value={createForm.data.category}
                                    onValueChange={(val) => createForm.setData('category', val)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((c) => (
                                            <SelectItem key={c} value={c}>
                                                {c}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Device Model / Hardware Type */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Hardware Model / Type</Label>
                                <Input
                                    placeholder="e.g. Dahua NVR5216-16P, MikroTik RB4011"
                                    value={createForm.data.device_type}
                                    onChange={(e) => createForm.setData('device_type', e.target.value)}
                                />
                            </div>

                            {/* Branch / Location */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Associated Branch / Location</Label>
                                <Select
                                    value={createForm.data.branch_name}
                                    onValueChange={(val) => createForm.setData('branch_name', val)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select branch" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {branches.map((b) => (
                                            <SelectItem key={b} value={b}>
                                                {b}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Environment */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Environment</Label>
                                <Select
                                    value={createForm.data.environment}
                                    onValueChange={(val) => createForm.setData('environment', val)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Environment" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Production">Production</SelectItem>
                                        <SelectItem value="Branch Local">Branch Local</SelectItem>
                                        <SelectItem value="DR / Staging">DR / Staging</SelectItem>
                                        <SelectItem value="Testing / Lab">Testing / Lab</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* IP Address */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">IP Address / Host</Label>
                                <Input
                                    placeholder="e.g. 192.168.10.20 or switch.kaldis.local"
                                    value={createForm.data.ip_address}
                                    onChange={(e) => createForm.setData('ip_address', e.target.value)}
                                />
                            </div>

                            {/* Port & Protocol */}
                            <div className="grid grid-cols-2 gap-2">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Port</Label>
                                    <Input
                                        placeholder="80, 443, 8291"
                                        value={createForm.data.port}
                                        onChange={(e) => createForm.setData('port', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Protocol</Label>
                                    <Select
                                        value={createForm.data.protocol}
                                        onValueChange={(val) => createForm.setData('protocol', val)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {protocols.map((p) => (
                                                <SelectItem key={p} value={p}>
                                                    {p}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            {/* Web Link / Management URL */}
                            <div className="space-y-1.5 md:col-span-2">
                                <Label className="text-xs font-semibold">
                                    Management Web Link / URL
                                </Label>
                                <div className="relative">
                                    <ExternalLink className="w-4 h-4 absolute left-3 top-2.5 text-muted-foreground" />
                                    <Input
                                        className="pl-9"
                                        placeholder="e.g. https://192.168.10.20:443/ or https://portal.kaldiscoffee.com"
                                        value={createForm.data.link_url}
                                        onChange={(e) => createForm.setData('link_url', e.target.value)}
                                    />
                                </div>
                            </div>

                            {/* Username */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Username / Login ID</Label>
                                <Input
                                    placeholder="admin, root, cnet_admin"
                                    value={createForm.data.username}
                                    onChange={(e) => createForm.setData('username', e.target.value)}
                                />
                            </div>

                            {/* Auth Type */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Authentication Type</Label>
                                <Select
                                    value={createForm.data.auth_type}
                                    onValueChange={(val) => createForm.setData('auth_type', val)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {authTypes.map((a) => (
                                            <SelectItem key={a} value={a}>
                                                {a}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Password Field with Generator Button */}
                            <div className="space-y-1.5 md:col-span-2">
                                <div className="flex items-center justify-between">
                                    <Label className="text-xs font-semibold">
                                        Password / Secret Key <span className="text-rose-500">*</span>
                                    </Label>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            const pw = generateRandomPassword(20);
                                            createForm.setData('password', pw);
                                            toast.success('Generated new high-entropy password!');
                                        }}
                                        className="text-xs text-amber-600 hover:text-amber-700 font-medium flex items-center gap-1"
                                    >
                                        <Key className="w-3 h-3" />
                                        Generate Strong Password
                                    </button>
                                </div>
                                <div className="relative">
                                    <Input
                                        type="text"
                                        className="font-mono pr-10"
                                        placeholder="Enter or generate password"
                                        value={createForm.data.password}
                                        onChange={(e) => createForm.setData('password', e.target.value)}
                                        required
                                    />
                                    {createForm.data.password && (
                                        <button
                                            type="button"
                                            onClick={() => copyToClipboard(createForm.data.password, 'Password')}
                                            className="absolute right-2.5 top-2.5 text-muted-foreground hover:text-foreground"
                                            title="Copy"
                                        >
                                            <Copy className="w-4 h-4" />
                                        </button>
                                    )}
                                </div>
                            </div>

                            {/* Secondary Secret / PIN */}
                            <div className="space-y-1.5 md:col-span-2">
                                <Label className="text-xs font-semibold">
                                    Secondary Secret / PIN / 2FA Recovery Key (Optional)
                                </Label>
                                <Input
                                    placeholder="Optional PIN, enable secret, or backup recovery codes"
                                    value={createForm.data.secondary_secret}
                                    onChange={(e) => createForm.setData('secondary_secret', e.target.value)}
                                />
                            </div>

                            {/* Notes */}
                            <div className="space-y-1.5 md:col-span-2">
                                <Label className="text-xs font-semibold">Operational Notes & Recovery Guidelines</Label>
                                <Textarea
                                    rows={3}
                                    placeholder="Add any specific VLAN requirements, backup credentials, reboot procedures, or physical location notes..."
                                    value={createForm.data.notes}
                                    onChange={(e) => createForm.setData('notes', e.target.value)}
                                />
                            </div>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setCreateModalOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={createForm.processing}
                                className="bg-amber-600 hover:bg-amber-700 text-white"
                            >
                                {createForm.processing ? 'Encrypting & Saving...' : 'Save to Vault'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* EDIT CREDENTIAL MODAL */}
            <Dialog open={editModalOpen} onOpenChange={setEditModalOpen}>
                <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Edit2 className="w-5 h-5 text-blue-600" />
                            Edit Vault Credential: {editingItem?.title}
                        </DialogTitle>
                        <DialogDescription>
                            Update device credentials, management URLs, or IPs.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleEditSubmit} className="space-y-4 py-2">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Title */}
                            <div className="space-y-1.5 md:col-span-2">
                                <Label className="text-xs font-semibold">
                                    Device Name / Title <span className="text-rose-500">*</span>
                                </Label>
                                <Input
                                    value={editForm.data.title}
                                    onChange={(e) => editForm.setData('title', e.target.value)}
                                    required
                                />
                            </div>

                            {/* Category */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Device Category</Label>
                                <Select
                                    value={editForm.data.category}
                                    onValueChange={(val) => editForm.setData('category', val)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((c) => (
                                            <SelectItem key={c} value={c}>
                                                {c}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Device Model */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Hardware Model / Type</Label>
                                <Input
                                    value={editForm.data.device_type}
                                    onChange={(e) => editForm.setData('device_type', e.target.value)}
                                />
                            </div>

                            {/* Branch */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Branch / Location</Label>
                                <Select
                                    value={editForm.data.branch_name}
                                    onValueChange={(val) => editForm.setData('branch_name', val)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {branches.map((b) => (
                                            <SelectItem key={b} value={b}>
                                                {b}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Environment */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Environment</Label>
                                <Select
                                    value={editForm.data.environment}
                                    onValueChange={(val) => editForm.setData('environment', val)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Production">Production</SelectItem>
                                        <SelectItem value="Branch Local">Branch Local</SelectItem>
                                        <SelectItem value="DR / Staging">DR / Staging</SelectItem>
                                        <SelectItem value="Testing / Lab">Testing / Lab</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* IP Address */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">IP Address / Host</Label>
                                <Input
                                    value={editForm.data.ip_address}
                                    onChange={(e) => editForm.setData('ip_address', e.target.value)}
                                />
                            </div>

                            {/* Port & Protocol */}
                            <div className="grid grid-cols-2 gap-2">
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Port</Label>
                                    <Input
                                        value={editForm.data.port}
                                        onChange={(e) => editForm.setData('port', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs font-semibold">Protocol</Label>
                                    <Select
                                        value={editForm.data.protocol}
                                        onValueChange={(val) => editForm.setData('protocol', val)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {protocols.map((p) => (
                                                <SelectItem key={p} value={p}>
                                                    {p}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            {/* Web Link / Management URL */}
                            <div className="space-y-1.5 md:col-span-2">
                                <Label className="text-xs font-semibold">Management Web Link / URL</Label>
                                <div className="relative">
                                    <ExternalLink className="w-4 h-4 absolute left-3 top-2.5 text-muted-foreground" />
                                    <Input
                                        className="pl-9"
                                        placeholder="https://192.168.10.20:443/"
                                        value={editForm.data.link_url}
                                        onChange={(e) => editForm.setData('link_url', e.target.value)}
                                    />
                                </div>
                            </div>

                            {/* Username */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Username / Login ID</Label>
                                <Input
                                    value={editForm.data.username}
                                    onChange={(e) => editForm.setData('username', e.target.value)}
                                />
                            </div>

                            {/* Auth Type */}
                            <div className="space-y-1.5">
                                <Label className="text-xs font-semibold">Authentication Type</Label>
                                <Select
                                    value={editForm.data.auth_type}
                                    onValueChange={(val) => editForm.setData('auth_type', val)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {authTypes.map((a) => (
                                            <SelectItem key={a} value={a}>
                                                {a}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Password Field (Leave empty to keep) */}
                            <div className="space-y-1.5 md:col-span-2">
                                <div className="flex items-center justify-between">
                                    <Label className="text-xs font-semibold">
                                        Password (Leave empty to keep current password)
                                    </Label>
                                    <button
                                        type="button"
                                        onClick={() => {
                                            const pw = generateRandomPassword(20);
                                            editForm.setData('password', pw);
                                            toast.success('Generated new password for replacement.');
                                        }}
                                        className="text-xs text-amber-600 hover:text-amber-700 font-medium flex items-center gap-1"
                                    >
                                        <Key className="w-3 h-3" />
                                        Generate New
                                    </button>
                                </div>
                                <Input
                                    type="text"
                                    className="font-mono"
                                    placeholder="•••••••••••• (Leave blank to keep unchanged)"
                                    value={editForm.data.password}
                                    onChange={(e) => editForm.setData('password', e.target.value)}
                                />
                            </div>

                            {/* Secondary Secret */}
                            <div className="space-y-1.5 md:col-span-2">
                                <Label className="text-xs font-semibold">
                                    Secondary Secret / PIN / 2FA Recovery Key
                                </Label>
                                <Input
                                    placeholder="Leave blank to keep unchanged"
                                    value={editForm.data.secondary_secret}
                                    onChange={(e) => editForm.setData('secondary_secret', e.target.value)}
                                />
                            </div>

                            {/* Notes */}
                            <div className="space-y-1.5 md:col-span-2">
                                <Label className="text-xs font-semibold">Notes & Recovery Guidelines</Label>
                                <Textarea
                                    rows={3}
                                    value={editForm.data.notes}
                                    onChange={(e) => editForm.setData('notes', e.target.value)}
                                />
                            </div>

                            {/* Active Toggle */}
                            <div className="flex items-center justify-between p-3 border rounded-lg md:col-span-2">
                                <div>
                                    <div className="text-xs font-semibold text-foreground">Active Credential</div>
                                    <div className="text-[11px] text-muted-foreground">
                                        Unchecking archives this device from standard operational views.
                                    </div>
                                </div>
                                <Switch
                                    checked={editForm.data.is_active}
                                    onCheckedChange={(checked) => editForm.setData('is_active', checked)}
                                />
                            </div>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditModalOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={editForm.processing}
                                className="bg-blue-600 hover:bg-blue-700 text-white"
                            >
                                {editForm.processing ? 'Saving...' : 'Update Credential'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* DETAIL & AUDIT MODAL */}
            <Dialog open={detailModalOpen} onOpenChange={setDetailModalOpen}>
                <DialogContent className="max-w-xl">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Info className="w-5 h-5 text-amber-600" />
                            {viewingItem?.title}
                        </DialogTitle>
                        <DialogDescription>
                            Technical specification and access log metadata.
                        </DialogDescription>
                    </DialogHeader>

                    {viewingItem && (
                        <div className="space-y-4 py-2 text-sm">
                            <div className="grid grid-cols-2 gap-3 p-3 bg-muted/40 rounded-lg">
                                <div>
                                    <span className="text-xs text-muted-foreground block">Category:</span>
                                    <span className="font-medium text-foreground">{viewingItem.category}</span>
                                </div>
                                <div>
                                    <span className="text-xs text-muted-foreground block">Hardware Model:</span>
                                    <span className="font-medium text-foreground">
                                        {viewingItem.device_type || 'N/A'}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-xs text-muted-foreground block">Branch / Location:</span>
                                    <span className="font-medium text-foreground">
                                        {viewingItem.branch_name || 'Head Office'}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-xs text-muted-foreground block">Environment:</span>
                                    <span className="font-medium text-foreground">{viewingItem.environment}</span>
                                </div>
                                <div>
                                    <span className="text-xs text-muted-foreground block">IP & Port:</span>
                                    <span className="font-mono font-medium text-foreground">
                                        {viewingItem.ip_address || 'None'}
                                        {viewingItem.port ? `:${viewingItem.port}` : ''}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-xs text-muted-foreground block">Auth Protocol:</span>
                                    <span className="font-medium text-foreground">
                                        {viewingItem.protocol || 'N/A'} ({viewingItem.auth_type})
                                    </span>
                                </div>
                            </div>

                            {viewingItem.link_url && (
                                <div className="p-3 border rounded-lg bg-amber-500/5 border-amber-500/20">
                                    <span className="text-xs text-muted-foreground block mb-1">
                                        Management URL:
                                    </span>
                                    <a
                                        href={
                                            viewingItem.link_url.startsWith('http')
                                                ? viewingItem.link_url
                                                : `https://${viewingItem.link_url}`
                                        }
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-xs font-mono text-amber-700 dark:text-amber-500 hover:underline flex items-center gap-1.5 break-all"
                                    >
                                        <ExternalLink className="w-3.5 h-3.5 shrink-0" />
                                        <span>{viewingItem.link_url}</span>
                                    </a>
                                </div>
                            )}

                            {viewingItem.notes && (
                                <div className="space-y-1">
                                    <span className="text-xs text-muted-foreground font-semibold block">
                                        Operational Notes:
                                    </span>
                                    <div className="p-3 rounded-lg bg-muted text-xs text-foreground whitespace-pre-wrap">
                                        {viewingItem.notes}
                                    </div>
                                </div>
                            )}

                            <div className="pt-2 border-t text-xs text-muted-foreground space-y-1">
                                <div>
                                    Created by:{' '}
                                    <span className="text-foreground font-medium">
                                        {viewingItem.creator?.name || 'System Admin'}
                                    </span>{' '}
                                    on {new Date(viewingItem.created_at || '').toLocaleString()}
                                </div>
                                {viewingItem.updater && (
                                    <div>
                                        Last modified by:{' '}
                                        <span className="text-foreground font-medium">
                                            {viewingItem.updater?.name}
                                        </span>{' '}
                                        on {new Date(viewingItem.updated_at || '').toLocaleString()}
                                    </div>
                                )}
                                {viewingItem.last_revealed_at && (
                                    <div className="text-amber-600 flex items-center gap-1">
                                        <Activity className="w-3 h-3" />
                                        Last viewed by{' '}
                                        <span className="font-medium">
                                            {viewingItem.revealer?.name || 'Admin'}
                                        </span>{' '}
                                        at {new Date(viewingItem.last_revealed_at).toLocaleString()}
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDetailModalOpen(false)}>
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* PASSWORD GENERATOR MODAL */}
            <Dialog open={generatorModalOpen} onOpenChange={setGeneratorModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Key className="w-5 h-5 text-amber-600" />
                            Enterprise Password Generator
                        </DialogTitle>
                        <DialogDescription>
                            Generate cryptographically strong passwords for routers, switches, and NVRs.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-2">
                        {/* Output Field */}
                        <div className="p-3 bg-muted rounded-lg border flex items-center justify-between">
                            <span className="font-mono text-sm font-semibold text-foreground select-all break-all">
                                {generatedPassword}
                            </span>
                            <div className="flex items-center gap-1 shrink-0 ml-2">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-8 w-8 p-0"
                                    onClick={handleRunGenerator}
                                    title="Regenerate"
                                >
                                    <RefreshCw className="w-4 h-4 text-amber-600" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-8 w-8 p-0"
                                    onClick={() => copyToClipboard(generatedPassword, 'Password')}
                                    title="Copy Password"
                                >
                                    <Copy className="w-4 h-4 text-emerald-600" />
                                </Button>
                            </div>
                        </div>

                        {/* Controls */}
                        <div className="space-y-3">
                            <div className="flex items-center justify-between text-xs">
                                <Label className="font-medium">Password Length: {genLength} characters</Label>
                            </div>
                            <input
                                type="range"
                                min={8}
                                max={48}
                                value={genLength}
                                onChange={(e) => {
                                    const val = Number(e.target.value);
                                    setGenLength(val);
                                    setGeneratedPassword(generateRandomPassword(val));
                                }}
                                className="w-full accent-amber-600"
                            />

                            <div className="grid grid-cols-2 gap-2 text-xs pt-1">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={genUpper}
                                        onChange={(e) => {
                                            setGenUpper(e.target.checked);
                                            setGeneratedPassword(
                                                generateRandomPassword(genLength, e.target.checked, genLower, genNums, genSyms)
                                            );
                                        }}
                                        className="rounded accent-amber-600"
                                    />
                                    <span>Uppercase (A-Z)</span>
                                </label>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={genLower}
                                        onChange={(e) => {
                                            setGenLower(e.target.checked);
                                            setGeneratedPassword(
                                                generateRandomPassword(genLength, genUpper, e.target.checked, genNums, genSyms)
                                            );
                                        }}
                                        className="rounded accent-amber-600"
                                    />
                                    <span>Lowercase (a-z)</span>
                                </label>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={genNums}
                                        onChange={(e) => {
                                            setGenNums(e.target.checked);
                                            setGeneratedPassword(
                                                generateRandomPassword(genLength, genUpper, genLower, e.target.checked, genSyms)
                                            );
                                        }}
                                        className="rounded accent-amber-600"
                                    />
                                    <span>Numbers (0-9)</span>
                                </label>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={genSyms}
                                        onChange={(e) => {
                                            setGenSyms(e.target.checked);
                                            setGeneratedPassword(
                                                generateRandomPassword(genLength, genUpper, genLower, genNums, e.target.checked)
                                            );
                                        }}
                                        className="rounded accent-amber-600"
                                    />
                                    <span>Special Symbols (!@#$)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setGeneratorModalOpen(false)}
                        >
                            Done
                        </Button>
                        <Button
                            onClick={() => {
                                copyToClipboard(generatedPassword, 'Password');
                                setGeneratorModalOpen(false);
                            }}
                            className="bg-amber-600 hover:bg-amber-700 text-white"
                        >
                            Copy & Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* AUDIT LOGS MODAL */}
            <Dialog open={auditLogsModalOpen} onOpenChange={setAuditLogsModalOpen}>
                <DialogContent className="max-w-3xl max-h-[85vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Activity className="w-5 h-5 text-blue-600" />
                            Vault Security Audit Trail
                        </DialogTitle>
                        <DialogDescription>
                            All actions, password reveals, creations, and updates are permanently recorded.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="py-2">
                        {recentLogs.length === 0 ? (
                            <div className="text-center py-8 text-sm text-muted-foreground">
                                No security logs recorded yet.
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Timestamp</TableHead>
                                        <TableHead>Admin User</TableHead>
                                        <TableHead>Action</TableHead>
                                        <TableHead>Device</TableHead>
                                        <TableHead>IP Address</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {recentLogs.map((log) => (
                                        <TableRow key={log.id} className="text-xs">
                                            <TableCell className="font-mono text-muted-foreground">
                                                {new Date(log.created_at).toLocaleString()}
                                            </TableCell>
                                            <TableCell className="font-medium text-foreground">
                                                {log.user?.name || 'System / Automated'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant="outline"
                                                    className={
                                                        log.action === 'revealed'
                                                            ? 'border-amber-400 text-amber-700 bg-amber-50'
                                                            : log.action === 'created'
                                                            ? 'border-emerald-400 text-emerald-700 bg-emerald-50'
                                                            : log.action === 'deleted'
                                                            ? 'border-rose-400 text-rose-700 bg-rose-50'
                                                            : 'border-blue-400 text-blue-700 bg-blue-50'
                                                    }
                                                >
                                                    {log.action}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {log.entry?.title || log.details || 'Vault'}
                                            </TableCell>
                                            <TableCell className="font-mono text-muted-foreground">
                                                {log.ip_address || '—'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setAuditLogsModalOpen(false)}>
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* DELETE CONFIRMATION MODAL */}
            <Dialog open={deleteModalOpen} onOpenChange={setDeleteModalOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-rose-600">
                            <ShieldAlert className="w-5 h-5" />
                            Delete Credential
                        </DialogTitle>
                        <DialogDescription>
                            Are you sure you want to permanently remove{' '}
                            <span className="font-semibold text-foreground">{deletingItem?.title}</span> from the
                            vault? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter className="pt-2">
                        <Button variant="outline" onClick={() => setDeleteModalOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDelete}
                            className="bg-rose-600 hover:bg-rose-700 text-white"
                        >
                            Delete Credential
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
