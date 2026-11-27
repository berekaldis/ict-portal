import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Bell,
    Bot,
    Building2,
    Eye,
    EyeOff,
    FileEdit,
    Headphones,
    Layers,
    Mail,
    Phone,
    Plus,
    Radio,
    Receipt,
    Save,
    Search,
    Send,
    Settings2,
    Shield,
    Store,
    Tag,
    Trash2,
    Users,
    X,
} from 'lucide-react';
import React, { useMemo, useState } from 'react';

interface CaseTypeItem {
    id: number;
    name: string;
}

interface CaseAssigneeItem {
    id: number;
    name: string;
    telegram_id?: string;
    status: string;
}

interface Props {
    types: CaseTypeItem[];
    assignees: CaseAssigneeItem[];
    settings: Record<string, string>;
    editTypes?: string[];
    voucherTypes?: string[];
    storeDepartments?: string[];
}

interface ListManagerProps {
    title: string;
    badgeCount: number;
    badgeColor?: string;
    icon: React.ElementType;
    description: string;
    placeholder: string;
    items: { key: string | number; name: string }[];
    searchValue: string;
    onSearchChange: (val: string) => void;
    newValue: string;
    onNewChange: (val: string) => void;
    onAdd: (e: React.FormEvent) => void;
    onDelete: (item: { key: string | number; name: string }) => void;
    emptyMessage: string;
    buttonLabel?: string;
}

function ItemListManager({
    title,
    badgeCount,
    badgeColor = 'bg-primary/10 text-primary border-primary/20',
    icon: Icon,
    description,
    placeholder,
    items,
    searchValue,
    onSearchChange,
    newValue,
    onNewChange,
    onAdd,
    onDelete,
    emptyMessage,
    buttonLabel = 'Add',
}: ListManagerProps) {
    const filteredItems = useMemo(() => {
        if (!searchValue.trim()) return items;
        const q = searchValue.toLowerCase();
        return items.filter((item) => item.name.toLowerCase().includes(q));
    }, [items, searchValue]);

    return (
        <Card className="border-border/60 shadow-xs flex flex-col h-full">
            <CardHeader className="py-3 px-5 border-b border-border/40">
                <div className="flex items-center justify-between gap-2">
                    <CardTitle className="text-sm font-semibold flex items-center gap-2">
                        <Icon className="h-4 w-4 text-primary" /> {title}
                    </CardTitle>
                    <Badge variant="outline" className={`text-[10px] shrink-0 ${badgeColor}`}>
                        {badgeCount} items
                    </Badge>
                </div>
                <CardDescription className="text-xs">{description}</CardDescription>
            </CardHeader>

            <CardContent className="p-4 space-y-3 flex-1 flex flex-col">
                {/* Add new input */}
                <form onSubmit={onAdd} className="flex gap-2">
                    <Input
                        placeholder={placeholder}
                        value={newValue}
                        onChange={(e) => onNewChange(e.target.value)}
                        className="h-8 text-xs flex-1"
                    />
                    <Button type="submit" size="sm" disabled={!newValue.trim()} className="h-8 text-xs shrink-0">
                        <Plus className="h-3.5 w-3.5 mr-1" /> {buttonLabel}
                    </Button>
                </form>

                {/* Search filter */}
                <div className="relative">
                    <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-muted-foreground pointer-events-none" />
                    <Input
                        placeholder={`Search ${title.toLowerCase()}...`}
                        value={searchValue}
                        onChange={(e) => onSearchChange(e.target.value)}
                        className="h-8 pl-8 pr-7 text-xs"
                    />
                    {searchValue && (
                        <button
                            type="button"
                            onClick={() => onSearchChange('')}
                            className="absolute right-2 top-2 text-muted-foreground hover:text-foreground p-0.5"
                        >
                            <X className="h-3.5 w-3.5" />
                        </button>
                    )}
                </div>

                {/* List items */}
                <div className="rounded-lg border border-border/50 divide-y divide-border/30 overflow-hidden text-xs flex-1 max-h-72 overflow-y-auto">
                    {filteredItems.length === 0 ? (
                        <div className="p-4 text-center text-muted-foreground text-xs">
                            {searchValue ? 'No results matching search' : emptyMessage}
                        </div>
                    ) : (
                        filteredItems.map((item) => (
                            <div
                                key={item.key}
                                className="p-2.5 flex items-center justify-between gap-2 hover:bg-muted/20 transition-colors"
                            >
                                <span className="font-medium text-foreground truncate">{item.name}</span>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    type="button"
                                    onClick={() => onDelete(item)}
                                    className="h-6 w-6 text-destructive hover:text-destructive hover:bg-destructive/10 shrink-0"
                                    title="Delete item"
                                >
                                    <Trash2 className="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        ))
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

export default function SettingsPage({
    types = [],
    assignees = [],
    settings = {},
    editTypes = [],
    voucherTypes = [],
    storeDepartments = [],
}: Props) {
    // Inputs & Search States
    const [newType, setNewType] = useState('');
    const [caseTypeSearch, setCaseTypeSearch] = useState('');

    const [newVoucherType, setNewVoucherType] = useState('');
    const [voucherTypeSearch, setVoucherTypeSearch] = useState('');

    const [newEditType, setNewEditType] = useState('');
    const [editTypeSearch, setEditTypeSearch] = useState('');

    const [newStoreDept, setNewStoreDept] = useState('');
    const [storeDeptSearch, setStoreDeptSearch] = useState('');

    const [newAssigneeName, setNewAssigneeName] = useState('');
    const [newAssigneeTelegram, setNewAssigneeTelegram] = useState('');
    const [assigneeSearch, setAssigneeSearch] = useState('');

    const [testingChannel, setTestingChannel] = useState<string | null>(null);
    const [showEditRequestToken, setShowEditRequestToken] = useState(false);
    const [showCaseToken, setShowCaseToken] = useState(false);

    // Form state separating Edit Request and Case Management Telegram settings
    const { data, setData, post, processing } = useForm({
        // 1. Edit & Void Requests Telegram Configuration
        edit_request_telegram_bot_token: settings.edit_request_telegram_bot_token || settings.telegram_bot_token || '',
        edit_request_telegram_chat_id_admin: settings.edit_request_telegram_chat_id_admin || settings.telegram_chat_id_admin || '',
        edit_request_telegram_chat_id_finance: settings.edit_request_telegram_chat_id_finance || settings.telegram_chat_id_finance || '',
        edit_request_telegram_chat_id_fb: settings.edit_request_telegram_chat_id_fb || settings.telegram_chat_id_fb || '',
        edit_request_telegram_chat_id_onhand_delete: settings.edit_request_telegram_chat_id_onhand_delete || settings.telegram_chat_id_onhand_delete || '',
        edit_request_telegram_coordinator_chat_id: settings.edit_request_telegram_coordinator_chat_id || settings.telegram_coordinator_chat_id || '',
        edit_request_telegram_branch_managers_group_chat_id: settings.edit_request_telegram_branch_managers_group_chat_id || settings.telegram_branch_managers_group_chat_id || '',
        edit_request_telegram_departments_group_chat_id: settings.edit_request_telegram_departments_group_chat_id || settings.telegram_departments_group_chat_id || '',

        // 2. Case Management Telegram Configuration
        case_telegram_bot_token: settings.case_telegram_bot_token || settings.telegram_bot_token || '',
        case_telegram_group_chat_id: settings.case_telegram_group_chat_id || settings.telegram_chat_id_admin || '',
        case_notify_assignee_direct: settings.case_notify_assignee_direct ?? '1',

        // 3. Portal Profile & Branding
        company_name: settings.company_name || "Kaldi's Coffee ICT Portal",
        company_phone: settings.company_phone || '+251 11 661 6161',
        support_email: settings.support_email || 'support.ict@kaldisbunna.et',
    });

    const handleSaveSettings = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('settings.update'));
    };

    const handleSendTestMessage = (chatId: string, channelName: string, tokenType: 'edit_request' | 'case') => {
        if (!chatId.trim()) {
            alert('Please specify a valid Telegram Chat ID first.');
            return;
        }

        setTestingChannel(`${tokenType}_${channelName}`);
        router.post(
            route('settings.test-telegram'),
            {
                chat_id: chatId.trim(),
                channel_name: channelName,
                token_type: tokenType,
            },
            {
                onFinish: () => setTestingChannel(null),
            }
        );
    };

    // Case Types Actions
    const handleAddType = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newType.trim()) return;

        router.post(
            route('cases.types.store'),
            { name: newType.trim() },
            { onSuccess: () => setNewType('') }
        );
    };

    const handleDeleteType = (item: { key: string | number; name: string }) => {
        if (confirm(`Delete case type "${item.name}"?`)) {
            router.delete(route('cases.types.destroy', item.key));
        }
    };

    // Voucher Types Actions
    const handleAddVoucherType = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newVoucherType.trim()) return;

        router.post(
            route('settings.voucher-types.store'),
            { name: newVoucherType.trim() },
            { onSuccess: () => setNewVoucherType('') }
        );
    };

    const handleDeleteVoucherType = (item: { key: string | number; name: string }) => {
        if (confirm(`Delete voucher type "${item.name}"?`)) {
            router.delete(route('settings.voucher-types.destroy'), {
                data: { name: item.name },
            });
        }
    };

    // Edit Types Actions
    const handleAddEditType = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newEditType.trim()) return;

        router.post(
            route('settings.edit-types.store'),
            { name: newEditType.trim() },
            { onSuccess: () => setNewEditType('') }
        );
    };

    const handleDeleteEditType = (item: { key: string | number; name: string }) => {
        if (confirm(`Delete edit type "${item.name}"?`)) {
            router.delete(route('settings.edit-types.destroy'), {
                data: { name: item.name },
            });
        }
    };

    // Store Departments Actions
    const handleAddStoreDept = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newStoreDept.trim()) return;

        router.post(
            route('settings.store-departments.store'),
            { name: newStoreDept.trim() },
            { onSuccess: () => setNewStoreDept('') }
        );
    };

    const handleDeleteStoreDept = (item: { key: string | number; name: string }) => {
        if (confirm(`Delete department store "${item.name}"?`)) {
            router.delete(route('settings.store-departments.destroy'), {
                data: { name: item.name },
            });
        }
    };

    // Assignees Actions
    const handleAddAssignee = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newAssigneeName.trim()) return;

        router.post(
            route('cases.assignees.store'),
            {
                name: newAssigneeName.trim(),
                telegram_id: newAssigneeTelegram.trim() || undefined,
            },
            {
                onSuccess: () => {
                    setNewAssigneeName('');
                    setNewAssigneeTelegram('');
                },
            }
        );
    };

    const handleDeleteAssignee = (id: number) => {
        if (confirm('Delete this assignee?')) {
            router.delete(route('cases.assignees.destroy', id));
        }
    };

    const filteredAssignees = useMemo(() => {
        if (!assigneeSearch.trim()) return assignees;
        const q = assigneeSearch.toLowerCase();
        return assignees.filter(
            (a) =>
                a.name.toLowerCase().includes(q) ||
                (a.telegram_id && a.telegram_id.toLowerCase().includes(q))
        );
    }, [assignees, assigneeSearch]);

    // Data structures for ItemListManager
    const caseTypeItems = useMemo(() => types.map((t) => ({ key: t.id, name: t.name })), [types]);
    const voucherTypeItems = useMemo(
        () => voucherTypes.map((v) => ({ key: v, name: v })),
        [voucherTypes]
    );
    const editTypeItems = useMemo(() => editTypes.map((t) => ({ key: t, name: t })), [editTypes]);
    const storeDeptItems = useMemo(
        () => storeDepartments.map((s) => ({ key: s, name: s })),
        [storeDepartments]
    );

    return (
        <AppLayout breadcrumbs={[{ title: 'System Configuration', href: '/settings' }, { title: 'Settings' }]}>
            <Head title="System Configuration & Telegram Settings" />

            <div className="space-y-6 max-w-5xl mx-auto pb-10">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-border/40">
                    <div className="flex items-center gap-3">
                        <Button asChild variant="outline" size="icon" className="h-9 w-9">
                            <Link href={route('dashboard')}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                                <Settings2 className="h-5 w-5 text-primary" /> System Configuration & Settings
                            </h1>
                            <p className="text-xs text-muted-foreground">
                                Manage independent Telegram bots, group routing, edit request parameters, and support ticket categories
                            </p>
                        </div>
                    </div>

                    <Button onClick={handleSaveSettings} disabled={processing} className="h-9 font-medium shadow-sm">
                        <Save className="h-4 w-4 mr-1.5" />
                        {processing ? 'Saving...' : 'Save All Settings'}
                    </Button>
                </div>

                {/* Tabs */}
                <Tabs defaultValue="edit-requests" className="space-y-4">
                    <TabsList className="bg-muted/60 p-1 border border-border/50 grid grid-cols-2 sm:grid-cols-4 h-auto">
                        <TabsTrigger
                            value="edit-requests"
                            className="text-xs py-2 flex items-center gap-1.5 data-[state=active]:bg-background"
                        >
                            <FileEdit className="h-3.5 w-3.5 text-blue-500" />
                            <span>Edit Request Settings</span>
                        </TabsTrigger>
                        <TabsTrigger
                            value="cases"
                            className="text-xs py-2 flex items-center gap-1.5 data-[state=active]:bg-background"
                        >
                            <Headphones className="h-3.5 w-3.5 text-emerald-500" />
                            <span>Case Telegram</span>
                        </TabsTrigger>
                        <TabsTrigger
                            value="case-module"
                            className="text-xs py-2 flex items-center gap-1.5 data-[state=active]:bg-background"
                        >
                            <Tag className="h-3.5 w-3.5 text-amber-500" />
                            <span>Categories, Types & Stores</span>
                        </TabsTrigger>
                        <TabsTrigger
                            value="company"
                            className="text-xs py-2 flex items-center gap-1.5 data-[state=active]:bg-background"
                        >
                            <Building2 className="h-3.5 w-3.5 text-purple-500" />
                            <span>Company Profile</span>
                        </TabsTrigger>
                    </TabsList>

                    {/* TAB 1: EDIT & VOID REQUESTS */}
                    <TabsContent value="edit-requests" className="space-y-6">
                        {/* 1. Telegram Bot Token Card */}
                        <Card className="border-border/60 shadow-xs">
                            <CardHeader className="py-3.5 px-5 border-b border-border/40 bg-blue-500/5">
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <CardTitle className="text-sm font-semibold flex items-center gap-2 text-foreground">
                                            <Bot className="h-4 w-4 text-blue-500" /> Edit & Void Requests Bot Token
                                        </CardTitle>
                                        <CardDescription className="text-xs">
                                            Dedicated bot for Voucher Edits, Void Approvals, and On Hand Deletes.
                                        </CardDescription>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        className="text-[10px] bg-blue-500/10 text-blue-600 border-blue-500/30"
                                    >
                                        Edit Module Bot
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="p-5">
                                <div className="space-y-1.5 max-w-2xl">
                                    <Label htmlFor="edit_request_telegram_bot_token" className="text-xs font-semibold">
                                        Bot Token
                                    </Label>
                                    <div className="relative flex items-center">
                                        <Input
                                            id="edit_request_telegram_bot_token"
                                            type={showEditRequestToken ? 'text' : 'password'}
                                            placeholder="e.g. 8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8"
                                            value={data.edit_request_telegram_bot_token}
                                            onChange={(e) => setData('edit_request_telegram_bot_token', e.target.value)}
                                            className="font-mono text-xs pr-10"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowEditRequestToken((prev) => !prev)}
                                            className="absolute right-2 text-muted-foreground hover:text-foreground p-1 transition-colors rounded focus:outline-none focus:ring-1 focus:ring-ring"
                                            title={showEditRequestToken ? 'Hide token' : 'Show token'}
                                            aria-label={showEditRequestToken ? 'Hide token' : 'Show token'}
                                        >
                                            {showEditRequestToken ? (
                                                <EyeOff className="h-4 w-4" />
                                            ) : (
                                                <Eye className="h-4 w-4" />
                                            )}
                                        </button>
                                    </div>
                                    <p className="text-[11px] text-muted-foreground">
                                        Obtained from @BotFather. This bot delivers notifications to the configured target groups below.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* 2. Target Telegram Routing Groups */}
                        <Card className="border-border/60 shadow-xs">
                            <CardHeader className="py-3.5 px-5 border-b border-border/40">
                                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                    <Send className="h-4 w-4 text-blue-500" /> Telegram Routing Groups & Channels
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    Configure distinct chat IDs for voucher workflows. Use the &quot;Test&quot; button to send a verification ping.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-5">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {/* IT Admin Alert Group */}
                                    <div className="p-3.5 rounded-lg border border-border/50 bg-muted/10 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold text-foreground">
                                                IT Admin Monitoring Group
                                            </Label>
                                            <Badge
                                                variant="outline"
                                                className="text-[10px] bg-blue-500/10 text-blue-600 border-blue-500/20"
                                            >
                                                Admin
                                            </Badge>
                                        </div>
                                        <p className="text-[11px] text-muted-foreground">
                                            Receives all edit/void requests, system alerts, and status transitions.
                                        </p>
                                        <div className="flex gap-2">
                                            <Input
                                                value={data.edit_request_telegram_chat_id_admin}
                                                onChange={(e) =>
                                                    setData('edit_request_telegram_chat_id_admin', e.target.value)
                                                }
                                                placeholder="-1001502645326"
                                                className="font-mono text-xs h-8 flex-1"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    handleSendTestMessage(
                                                        data.edit_request_telegram_chat_id_admin,
                                                        'IT Admin Group',
                                                        'edit_request'
                                                    )
                                                }
                                                disabled={testingChannel === 'edit_request_IT Admin Group'}
                                                className="h-8 text-xs shrink-0"
                                            >
                                                <Send className="h-3 w-3 mr-1" />
                                                {testingChannel === 'edit_request_IT Admin Group'
                                                    ? 'Sending...'
                                                    : 'Test'}
                                            </Button>
                                        </div>
                                    </div>

                                    {/* Finance Department Manager Group */}
                                    <div className="p-3.5 rounded-lg border border-border/50 bg-muted/10 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold text-foreground">
                                                Finance Department Manager Group
                                            </Label>
                                            <Badge
                                                variant="outline"
                                                className="text-[10px] bg-emerald-500/10 text-emerald-600 border-emerald-500/20"
                                            >
                                                Finance
                                            </Badge>
                                        </div>
                                        <p className="text-[11px] text-muted-foreground">
                                            Target group for edit requests with target department set to Finance.
                                        </p>
                                        <div className="flex gap-2">
                                            <Input
                                                value={data.edit_request_telegram_chat_id_finance}
                                                onChange={(e) =>
                                                    setData('edit_request_telegram_chat_id_finance', e.target.value)
                                                }
                                                placeholder="-1003609053132"
                                                className="font-mono text-xs h-8 flex-1"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    handleSendTestMessage(
                                                        data.edit_request_telegram_chat_id_finance,
                                                        'Finance Group',
                                                        'edit_request'
                                                    )
                                                }
                                                disabled={testingChannel === 'edit_request_Finance Group'}
                                                className="h-8 text-xs shrink-0"
                                            >
                                                <Send className="h-3 w-3 mr-1" />
                                                {testingChannel === 'edit_request_Finance Group'
                                                    ? 'Sending...'
                                                    : 'Test'}
                                            </Button>
                                        </div>
                                    </div>

                                    {/* F&B Department Manager Group */}
                                    <div className="p-3.5 rounded-lg border border-border/50 bg-muted/10 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold text-foreground">
                                                F&B Department Manager Group
                                            </Label>
                                            <Badge
                                                variant="outline"
                                                className="text-[10px] bg-amber-500/10 text-amber-600 border-amber-500/20"
                                            >
                                                F&B
                                            </Badge>
                                        </div>
                                        <p className="text-[11px] text-muted-foreground">
                                            Target for new On Hand Delete submissions and store inventory issue requests.
                                        </p>
                                        <div className="flex gap-2">
                                            <Input
                                                value={data.edit_request_telegram_chat_id_fb}
                                                onChange={(e) =>
                                                    setData('edit_request_telegram_chat_id_fb', e.target.value)
                                                }
                                                placeholder="-1003821366530"
                                                className="font-mono text-xs h-8 flex-1"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    handleSendTestMessage(
                                                        data.edit_request_telegram_chat_id_fb,
                                                        'F&B Group',
                                                        'edit_request'
                                                    )
                                                }
                                                disabled={testingChannel === 'edit_request_F&B Group'}
                                                className="h-8 text-xs shrink-0"
                                            >
                                                <Send className="h-3 w-3 mr-1" />
                                                {testingChannel === 'edit_request_F&B Group'
                                                    ? 'Sending...'
                                                    : 'Test'}
                                            </Button>
                                        </div>
                                    </div>

                                    {/* On Hand Delete Finalized Group */}
                                    <div className="p-3.5 rounded-lg border border-border/50 bg-muted/10 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold text-foreground">
                                                On Hand Delete Finalized Group
                                            </Label>
                                            <Badge
                                                variant="outline"
                                                className="text-[10px] bg-purple-500/10 text-purple-600 border-purple-500/20"
                                            >
                                                Inventory
                                            </Badge>
                                        </div>
                                        <p className="text-[11px] text-muted-foreground">
                                            Receives completion notifications once IT has deleted or rejected the on-hand record.
                                        </p>
                                        <div className="flex gap-2">
                                            <Input
                                                value={data.edit_request_telegram_chat_id_onhand_delete}
                                                onChange={(e) =>
                                                    setData('edit_request_telegram_chat_id_onhand_delete', e.target.value)
                                                }
                                                placeholder="-1002426639974"
                                                className="font-mono text-xs h-8 flex-1"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    handleSendTestMessage(
                                                        data.edit_request_telegram_chat_id_onhand_delete,
                                                        'On Hand Delete Group',
                                                        'edit_request'
                                                    )
                                                }
                                                disabled={testingChannel === 'edit_request_On Hand Delete Group'}
                                                className="h-8 text-xs shrink-0"
                                            >
                                                <Send className="h-3 w-3 mr-1" />
                                                {testingChannel === 'edit_request_On Hand Delete Group'
                                                    ? 'Sending...'
                                                    : 'Test'}
                                            </Button>
                                        </div>
                                    </div>

                                    {/* Training Coordinator Chat */}
                                    <div className="p-3.5 rounded-lg border border-border/50 bg-muted/10 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold text-foreground">
                                                Training & Coordinator Chat ID
                                            </Label>
                                            <Badge
                                                variant="outline"
                                                className="text-[10px] bg-zinc-500/10 text-zinc-600 border-zinc-500/20"
                                            >
                                                Coordinator
                                            </Badge>
                                        </div>
                                        <p className="text-[11px] text-muted-foreground">
                                            Direct channel for training coordinators and administrative dispatch.
                                        </p>
                                        <div className="flex gap-2">
                                            <Input
                                                value={data.edit_request_telegram_coordinator_chat_id}
                                                onChange={(e) =>
                                                    setData('edit_request_telegram_coordinator_chat_id', e.target.value)
                                                }
                                                placeholder="814523272"
                                                className="font-mono text-xs h-8 flex-1"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    handleSendTestMessage(
                                                        data.edit_request_telegram_coordinator_chat_id,
                                                        'Coordinator Chat',
                                                        'edit_request'
                                                    )
                                                }
                                                disabled={testingChannel === 'edit_request_Coordinator Chat'}
                                                className="h-8 text-xs shrink-0"
                                            >
                                                <Send className="h-3 w-3 mr-1" />
                                                {testingChannel === 'edit_request_Coordinator Chat'
                                                    ? 'Sending...'
                                                    : 'Test'}
                                            </Button>
                                        </div>
                                    </div>

                                    {/* Branch Managers Broadcast Group */}
                                    <div className="p-3.5 rounded-lg border border-border/50 bg-muted/10 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold text-foreground">
                                                Branch Managers Broadcast Group
                                            </Label>
                                            <Badge
                                                variant="outline"
                                                className="text-[10px] bg-sky-500/10 text-sky-600 border-sky-500/20"
                                            >
                                                Branches
                                            </Badge>
                                        </div>
                                        <p className="text-[11px] text-muted-foreground">
                                            Channel for company-wide branch manager notices and policy announcements.
                                        </p>
                                        <div className="flex gap-2">
                                            <Input
                                                value={data.edit_request_telegram_branch_managers_group_chat_id}
                                                onChange={(e) =>
                                                    setData(
                                                        'edit_request_telegram_branch_managers_group_chat_id',
                                                        e.target.value
                                                    )
                                                }
                                                placeholder="1626288394"
                                                className="font-mono text-xs h-8 flex-1"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    handleSendTestMessage(
                                                        data.edit_request_telegram_branch_managers_group_chat_id,
                                                        'Branch Managers Group',
                                                        'edit_request'
                                                    )
                                                }
                                                disabled={testingChannel === 'edit_request_Branch Managers Group'}
                                                className="h-8 text-xs shrink-0"
                                            >
                                                <Send className="h-3 w-3 mr-1" />
                                                {testingChannel === 'edit_request_Branch Managers Group'
                                                    ? 'Sending...'
                                                    : 'Test'}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* 3. Edit Request Parameters & Master Lists (Voucher Types, Edit Types & Department Stores) */}
                        <div className="space-y-3">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h2 className="text-base font-semibold tracking-tight text-foreground flex items-center gap-2">
                                        <Layers className="h-4 w-4 text-blue-500" /> Edit Request Parameters & Master Lists
                                    </h2>
                                    <p className="text-xs text-muted-foreground">
                                        Define, add, search, and remove Voucher Types, Edit Types, and Department Stores selectable during ticket creation.
                                    </p>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <ItemListManager
                                    title="Voucher Types"
                                    badgeCount={voucherTypes.length}
                                    badgeColor="bg-emerald-500/10 text-emerald-600 border-emerald-500/20"
                                    icon={Receipt}
                                    description="Voucher categories selectable when creating edit or void requests"
                                    placeholder="e.g. Store Issue Voucher, Bank Deposit..."
                                    items={voucherTypeItems}
                                    searchValue={voucherTypeSearch}
                                    onSearchChange={setVoucherTypeSearch}
                                    newValue={newVoucherType}
                                    onNewChange={setNewVoucherType}
                                    onAdd={handleAddVoucherType}
                                    onDelete={handleDeleteVoucherType}
                                    emptyMessage="No voucher types configured"
                                    buttonLabel="Add Voucher"
                                />

                                <ItemListManager
                                    title="Edit Types"
                                    badgeCount={editTypes.length}
                                    badgeColor="bg-blue-500/10 text-blue-600 border-blue-500/20"
                                    icon={FileEdit}
                                    description="Modification types selectable when creating voucher edit requests"
                                    placeholder="e.g. Quantity Adjustment, Date Change..."
                                    items={editTypeItems}
                                    searchValue={editTypeSearch}
                                    onSearchChange={setEditTypeSearch}
                                    newValue={newEditType}
                                    onNewChange={setNewEditType}
                                    onAdd={handleAddEditType}
                                    onDelete={handleDeleteEditType}
                                    emptyMessage="No edit types configured"
                                    buttonLabel="Add Type"
                                />

                                <ItemListManager
                                    title="Department Stores"
                                    badgeCount={storeDepartments.length}
                                    badgeColor="bg-purple-500/10 text-purple-600 border-purple-500/20"
                                    icon={Store}
                                    description="Active store & kitchen departments selectable for On Hand Deletions"
                                    placeholder="e.g. Bakery Store, Main Store..."
                                    items={storeDeptItems}
                                    searchValue={storeDeptSearch}
                                    onSearchChange={setStoreDeptSearch}
                                    newValue={newStoreDept}
                                    onNewChange={setNewStoreDept}
                                    onAdd={handleAddStoreDept}
                                    onDelete={handleDeleteStoreDept}
                                    emptyMessage="No department stores configured"
                                    buttonLabel="Add Store"
                                />
                            </div>
                        </div>
                    </TabsContent>

                    {/* TAB 2: CASE MANAGEMENT TELEGRAM */}
                    <TabsContent value="cases" className="space-y-4">
                        <Card className="border-border/60 shadow-xs">
                            <CardHeader className="py-3.5 px-5 border-b border-border/40 bg-emerald-500/5">
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <CardTitle className="text-sm font-semibold flex items-center gap-2 text-foreground">
                                            <Bot className="h-4 w-4 text-emerald-500" /> Case Management Bot Token
                                        </CardTitle>
                                        <CardDescription className="text-xs">
                                            Dedicated bot for IT Support Cases, ticket dispatch, and SLA alerts.
                                        </CardDescription>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        className="text-[10px] bg-emerald-500/10 text-emerald-600 border-emerald-500/30"
                                    >
                                        Case Module Bot
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="p-5 space-y-4">
                                <div className="space-y-1.5 max-w-2xl">
                                    <Label htmlFor="case_telegram_bot_token" className="text-xs font-semibold">
                                        Case Bot API Token
                                    </Label>
                                    <div className="relative flex items-center">
                                        <Input
                                            id="case_telegram_bot_token"
                                            type={showCaseToken ? 'text' : 'password'}
                                            placeholder="e.g. 8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8"
                                            value={data.case_telegram_bot_token}
                                            onChange={(e) => setData('case_telegram_bot_token', e.target.value)}
                                            className="font-mono text-xs pr-10"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowCaseToken((prev) => !prev)}
                                            className="absolute right-2 text-muted-foreground hover:text-foreground p-1 transition-colors rounded focus:outline-none focus:ring-1 focus:ring-ring"
                                            title={showCaseToken ? 'Hide token' : 'Show token'}
                                            aria-label={showCaseToken ? 'Hide token' : 'Show token'}
                                        >
                                            {showCaseToken ? (
                                                <EyeOff className="h-4 w-4" />
                                            ) : (
                                                <Eye className="h-4 w-4" />
                                            )}
                                        </button>
                                    </div>
                                    <p className="text-[11px] text-muted-foreground">
                                        Sends messages when cases are created, status changes (Fixed, Completed, Rejected), or SLA warnings.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Case Alerts Group Chat ID */}
                            <Card className="border-border/60 shadow-xs">
                                <CardHeader className="py-3 px-5 border-b border-border/40">
                                    <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                        <Bell className="h-4 w-4 text-emerald-500" /> IT Support Alerts Group
                                    </CardTitle>
                                    <CardDescription className="text-xs">
                                        Main Telegram group where all new cases & status updates are broadcast
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-4 space-y-3">
                                    <div className="space-y-1.5">
                                        <Label className="text-xs font-semibold">Group Chat ID</Label>
                                        <div className="flex gap-2">
                                            <Input
                                                value={data.case_telegram_group_chat_id}
                                                onChange={(e) => setData('case_telegram_group_chat_id', e.target.value)}
                                                placeholder="-1001502645326"
                                                className="font-mono text-xs h-8 flex-1"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    handleSendTestMessage(
                                                        data.case_telegram_group_chat_id,
                                                        'Case Alerts Group',
                                                        'case'
                                                    )
                                                }
                                                disabled={testingChannel === 'case_Case Alerts Group'}
                                                className="h-8 text-xs shrink-0"
                                            >
                                                <Send className="h-3 w-3 mr-1" />
                                                {testingChannel === 'case_Case Alerts Group' ? 'Sending...' : 'Test'}
                                            </Button>
                                        </div>
                                    </div>
                                    <p className="text-[11px] text-muted-foreground">
                                        Every new support case created from the dashboard or form will be sent to this group.
                                    </p>
                                </CardContent>
                            </Card>

                            {/* Direct Assignee Alerts Toggle */}
                            <Card className="border-border/60 shadow-xs">
                                <CardHeader className="py-3 px-5 border-b border-border/40">
                                    <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                        <Users className="h-4 w-4 text-emerald-500" /> Direct Assignee Notifications
                                    </CardTitle>
                                    <CardDescription className="text-xs">
                                        Direct personal Telegram message to the assigned technician
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-4 space-y-3">
                                    <div className="flex items-center justify-between p-3 rounded-lg border border-border/50 bg-muted/10">
                                        <div className="space-y-0.5">
                                            <Label
                                                htmlFor="case_notify_assignee_direct"
                                                className="text-xs font-semibold cursor-pointer"
                                            >
                                                Notify Assignee Privately
                                            </Label>
                                            <p className="text-[11px] text-muted-foreground">
                                                Also send a private alert to the assignee&apos;s personal Telegram Chat ID.
                                            </p>
                                        </div>
                                        <Switch
                                            id="case_notify_assignee_direct"
                                            checked={data.case_notify_assignee_direct === '1'}
                                            onCheckedChange={(checked) =>
                                                setData('case_notify_assignee_direct', checked ? '1' : '0')
                                            }
                                        />
                                    </div>
                                    <p className="text-[11px] text-muted-foreground">
                                        Configure individual Telegram Chat IDs in the{' '}
                                        <span className="font-semibold text-foreground">Categories &amp; Assignees</span> tab.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* TAB 3: CATEGORIES, TYPES & STORES */}
                    <TabsContent value="case-module" className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* 1. Case Types / Support Categories */}
                            <ItemListManager
                                title="Case Types / Categories"
                                badgeCount={types.length}
                                badgeColor="bg-amber-500/10 text-amber-600 border-amber-500/20"
                                icon={Tag}
                                description="Classification types selectable when logging a new support ticket"
                                placeholder="e.g. CNET POS & Billing Error..."
                                items={caseTypeItems}
                                searchValue={caseTypeSearch}
                                onSearchChange={setCaseTypeSearch}
                                newValue={newType}
                                onNewChange={setNewType}
                                onAdd={handleAddType}
                                onDelete={handleDeleteType}
                                emptyMessage="No case categories configured"
                                buttonLabel="Add Category"
                            />

                            {/* 2. Voucher Types */}
                            <ItemListManager
                                title="Voucher Types (Issue, Transfer, Receipt...)"
                                badgeCount={voucherTypes.length}
                                badgeColor="bg-emerald-500/10 text-emerald-600 border-emerald-500/20"
                                icon={Receipt}
                                description="Voucher categories selectable when creating edit or void requests"
                                placeholder="e.g. Store Issue Voucher, Bank Deposit..."
                                items={voucherTypeItems}
                                searchValue={voucherTypeSearch}
                                onSearchChange={setVoucherTypeSearch}
                                newValue={newVoucherType}
                                onNewChange={setNewVoucherType}
                                onAdd={handleAddVoucherType}
                                onDelete={handleDeleteVoucherType}
                                emptyMessage="No voucher types configured"
                                buttonLabel="Add Voucher"
                            />

                            {/* 3. Edit Types */}
                            <ItemListManager
                                title="Edit Types (Voucher Changes)"
                                badgeCount={editTypes.length}
                                badgeColor="bg-blue-500/10 text-blue-600 border-blue-500/20"
                                icon={FileEdit}
                                description="Modification types selectable when creating voucher edit requests"
                                placeholder="e.g. Quantity Adjustment, Date Change..."
                                items={editTypeItems}
                                searchValue={editTypeSearch}
                                onSearchChange={setEditTypeSearch}
                                newValue={newEditType}
                                onNewChange={setNewEditType}
                                onAdd={handleAddEditType}
                                onDelete={handleDeleteEditType}
                                emptyMessage="No edit types configured"
                                buttonLabel="Add Type"
                            />

                            {/* 4. Department Stores */}
                            <ItemListManager
                                title="Department Stores (On Hand Delete)"
                                badgeCount={storeDepartments.length}
                                badgeColor="bg-purple-500/10 text-purple-600 border-purple-500/20"
                                icon={Store}
                                description="Store and branch inventory departments for on-hand deletion requests"
                                placeholder="e.g. Pastry Store, Hot Kitchen..."
                                items={storeDeptItems}
                                searchValue={storeDeptSearch}
                                onSearchChange={setStoreDeptSearch}
                                newValue={newStoreDept}
                                onNewChange={setNewStoreDept}
                                onAdd={handleAddStoreDept}
                                onDelete={handleDeleteStoreDept}
                                emptyMessage="No department stores configured"
                                buttonLabel="Add Store"
                            />

                            {/* 5. Assignees Management with Telegram ID */}
                            <Card className="md:col-span-2 border-border/60 shadow-xs flex flex-col h-full">
                                <CardHeader className="py-3 px-5 border-b border-border/40">
                                    <div className="flex items-center justify-between gap-2">
                                        <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                            <Users className="h-4 w-4 text-emerald-500" /> IT Case Assignees
                                        </CardTitle>
                                        <Badge
                                            variant="outline"
                                            className="text-[10px] bg-emerald-500/10 text-emerald-600 border-emerald-500/20 shrink-0"
                                        >
                                            {assignees.length} assignees
                                        </Badge>
                                    </div>
                                    <CardDescription className="text-xs">
                                        Staff eligible to be assigned support tickets with individual Telegram routing
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-4 space-y-3 flex-1 flex flex-col">
                                    <form onSubmit={handleAddAssignee} className="space-y-2">
                                        <div className="grid grid-cols-2 gap-2">
                                            <Input
                                                placeholder="Assignee Name..."
                                                value={newAssigneeName}
                                                onChange={(e) => setNewAssigneeName(e.target.value)}
                                                className="h-8 text-xs"
                                            />
                                            <Input
                                                placeholder="Telegram Chat ID..."
                                                value={newAssigneeTelegram}
                                                onChange={(e) => setNewAssigneeTelegram(e.target.value)}
                                                className="h-8 text-xs font-mono"
                                            />
                                        </div>
                                        <Button
                                            type="submit"
                                            size="sm"
                                            disabled={!newAssigneeName.trim()}
                                            className="w-full h-8 text-xs"
                                        >
                                            <Plus className="h-3.5 w-3.5 mr-1" /> Add Assignee
                                        </Button>
                                    </form>

                                    {/* Search assignees */}
                                    <div className="relative">
                                        <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-muted-foreground pointer-events-none" />
                                        <Input
                                            placeholder="Search assignees..."
                                            value={assigneeSearch}
                                            onChange={(e) => setAssigneeSearch(e.target.value)}
                                            className="h-8 pl-8 pr-7 text-xs"
                                        />
                                        {assigneeSearch && (
                                            <button
                                                type="button"
                                                onClick={() => setAssigneeSearch('')}
                                                className="absolute right-2 top-2 text-muted-foreground hover:text-foreground p-0.5"
                                            >
                                                <X className="h-3.5 w-3.5" />
                                            </button>
                                        )}
                                    </div>

                                    <div className="rounded-lg border border-border/50 divide-y divide-border/30 overflow-hidden text-xs flex-1 max-h-72 overflow-y-auto">
                                        {filteredAssignees.length === 0 ? (
                                            <div className="p-4 text-center text-muted-foreground text-xs">
                                                {assigneeSearch ? 'No assignees matching search' : 'No assignees configured'}
                                            </div>
                                        ) : (
                                            filteredAssignees.map((a) => (
                                                <div
                                                    key={a.id}
                                                    className="p-2.5 flex items-center justify-between hover:bg-muted/20 transition-colors"
                                                >
                                                    <div className="truncate pr-2">
                                                        <span className="font-medium text-foreground block truncate">
                                                            {a.name}
                                                        </span>
                                                        {a.telegram_id ? (
                                                            <span className="block text-[10px] text-muted-foreground font-mono">
                                                                Chat ID: {a.telegram_id}
                                                            </span>
                                                        ) : (
                                                            <span className="block text-[10px] text-amber-600">
                                                                No Telegram ID
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="flex items-center gap-1 shrink-0">
                                                        {a.telegram_id && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                type="button"
                                                                onClick={() =>
                                                                    handleSendTestMessage(a.telegram_id!, a.name, 'case')
                                                                }
                                                                className="h-6 text-[10px] px-2 text-muted-foreground hover:text-foreground"
                                                            >
                                                                Test
                                                            </Button>
                                                        )}
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            type="button"
                                                            onClick={() => handleDeleteAssignee(a.id)}
                                                            className="h-6 w-6 text-destructive hover:text-destructive hover:bg-destructive/10"
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </Button>
                                                    </div>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* TAB 4: COMPANY & BRANDING */}
                    <TabsContent value="company" className="space-y-4">
                        <Card className="border-border/60 shadow-xs">
                            <CardHeader className="py-3.5 px-5 border-b border-border/40 bg-purple-500/5">
                                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                    <Building2 className="h-4 w-4 text-purple-500" /> Portal Profile &amp; Contact Information
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    Company metadata embedded in PDF/CSV export documents and alert messages.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-5 space-y-4">
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="company_name" className="text-xs font-semibold">
                                            Company / Entity Name
                                        </Label>
                                        <Input
                                            id="company_name"
                                            value={data.company_name}
                                            onChange={(e) => setData('company_name', e.target.value)}
                                            className="text-xs h-9"
                                        />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label
                                            htmlFor="company_phone"
                                            className="text-xs font-semibold flex items-center gap-1"
                                        >
                                            <Phone className="h-3 w-3 text-muted-foreground" /> ICT Support Hotline
                                        </Label>
                                        <Input
                                            id="company_phone"
                                            value={data.company_phone}
                                            onChange={(e) => setData('company_phone', e.target.value)}
                                            className="text-xs h-9 font-mono"
                                        />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label
                                            htmlFor="support_email"
                                            className="text-xs font-semibold flex items-center gap-1"
                                        >
                                            <Mail className="h-3 w-3 text-muted-foreground" /> Support Email
                                        </Label>
                                        <Input
                                            id="support_email"
                                            value={data.support_email}
                                            onChange={(e) => setData('support_email', e.target.value)}
                                            className="text-xs h-9"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
