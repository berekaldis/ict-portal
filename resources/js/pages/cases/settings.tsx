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
    Mail,
    Phone,
    Plus,
    Radio,
    Save,
    Send,
    Settings2,
    Shield,
    Tag,
    Trash2,
    Users,
} from 'lucide-react';
import React, { useState } from 'react';

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
}

export default function SettingsPage({ types, assignees, settings }: Props) {
    const [newType, setNewType] = useState('');
    const [newAssigneeName, setNewAssigneeName] = useState('');
    const [newAssigneeTelegram, setNewAssigneeTelegram] = useState('');
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

    const handleAddType = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newType.trim()) return;

        router.post(
            route('cases.types.store'),
            { name: newType.trim() },
            { onSuccess: () => setNewType('') }
        );
    };

    const handleDeleteType = (id: number) => {
        if (confirm('Delete this case category?')) {
            router.delete(route('cases.types.destroy', id));
        }
    };

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
                                Manage independent Telegram bots, group routing, support ticket parameters, and portal details
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
                        <TabsTrigger value="edit-requests" className="text-xs py-2 flex items-center gap-1.5 data-[state=active]:bg-background">
                            <FileEdit className="h-3.5 w-3.5 text-blue-500" />
                            <span>Edit Request Telegram</span>
                        </TabsTrigger>
                        <TabsTrigger value="cases" className="text-xs py-2 flex items-center gap-1.5 data-[state=active]:bg-background">
                            <Headphones className="h-3.5 w-3.5 text-emerald-500" />
                            <span>Case Telegram</span>
                        </TabsTrigger>
                        <TabsTrigger value="case-module" className="text-xs py-2 flex items-center gap-1.5 data-[state=active]:bg-background">
                            <Tag className="h-3.5 w-3.5 text-amber-500" />
                            <span>Categories & Assignees</span>
                        </TabsTrigger>
                        <TabsTrigger value="company" className="text-xs py-2 flex items-center gap-1.5 data-[state=active]:bg-background">
                            <Building2 className="h-3.5 w-3.5 text-purple-500" />
                            <span>Company Profile</span>
                        </TabsTrigger>
                    </TabsList>

                    {/* TAB 1: EDIT & VOID REQUESTS TELEGRAM */}
                    <TabsContent value="edit-requests" className="space-y-4">
                        <Card className="border-border/60 shadow-xs">
                            <CardHeader className="py-3.5 px-5 border-b border-border/40 bg-blue-500/5">
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <CardTitle className="text-sm font-semibold flex items-center gap-2 text-foreground">
                                            <Bot className="h-4 w-4 text-blue-500" /> Edit & Void Requests Bot Token
                                        </CardTitle>
                                        <CardDescription className="text-xs">
                                            Dedicated bot for Voucher Edits, Void Approvals, and On Hand Deletes (from legacy <code>edit_request/config.php</code>).
                                        </CardDescription>
                                    </div>
                                    <Badge variant="outline" className="text-[10px] bg-blue-500/10 text-blue-600 border-blue-500/30">
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
                                            {showEditRequestToken ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                        </button>
                                    </div>
                                    <p className="text-[11px] text-muted-foreground">
                                        Used exclusively to send status notifications for voucher edit/void submissions.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Edit Request Department Channel Routing */}
                        <Card className="border-border/60 shadow-xs">
                            <CardHeader className="py-3.5 px-5 border-b border-border/40">
                                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                    <Radio className="h-4 w-4 text-blue-500" /> Edit Request Department Notification Routing
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    Target chat and group IDs for Finance, F&B, On-Hand Delete, IT Execution, and Broadcasts.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-5">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {/* IT Admin / Execution */}
                                    <div className="p-3.5 rounded-lg border border-border/50 bg-muted/10 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold text-foreground flex items-center gap-1.5">
                                                <Shield className="h-3.5 w-3.5 text-blue-500" /> IT Admin Execution Group
                                            </Label>
                                            <Badge variant="outline" className="text-[10px] bg-blue-500/10 text-blue-600 border-blue-500/20">
                                                Execution
                                            </Badge>
                                        </div>
                                        <p className="text-[11px] text-muted-foreground">
                                            Receives approved requests ready for IT database and CNET execution.
                                        </p>
                                        <div className="flex gap-2">
                                            <Input
                                                value={data.edit_request_telegram_chat_id_admin}
                                                onChange={(e) => setData('edit_request_telegram_chat_id_admin', e.target.value)}
                                                placeholder="-1001502645326"
                                                className="font-mono text-xs h-8 flex-1"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleSendTestMessage(data.edit_request_telegram_chat_id_admin, 'IT Admin Group', 'edit_request')}
                                                disabled={testingChannel === 'edit_request_IT Admin Group'}
                                                className="h-8 text-xs shrink-0"
                                            >
                                                <Send className="h-3 w-3 mr-1" />
                                                {testingChannel === 'edit_request_IT Admin Group' ? 'Sending...' : 'Test'}
                                            </Button>
                                        </div>
                                    </div>

                                    {/* Finance Department Manager Group */}
                                    <div className="p-3.5 rounded-lg border border-border/50 bg-muted/10 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold text-foreground">
                                                Finance Department Group
                                            </Label>
                                            <Badge variant="outline" className="text-[10px] bg-emerald-500/10 text-emerald-600 border-emerald-500/20">
                                                Finance
                                            </Badge>
                                        </div>
                                        <p className="text-[11px] text-muted-foreground">
                                            Target for voucher edit requests (Date Changes, Price, Supplier Name changes).
                                        </p>
                                        <div className="flex gap-2">
                                            <Input
                                                value={data.edit_request_telegram_chat_id_finance}
                                                onChange={(e) => setData('edit_request_telegram_chat_id_finance', e.target.value)}
                                                placeholder="-1003609053132"
                                                className="font-mono text-xs h-8 flex-1"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleSendTestMessage(data.edit_request_telegram_chat_id_finance, 'Finance Group', 'edit_request')}
                                                disabled={testingChannel === 'edit_request_Finance Group'}
                                                className="h-8 text-xs shrink-0"
                                            >
                                                <Send className="h-3 w-3 mr-1" />
                                                {testingChannel === 'edit_request_Finance Group' ? 'Sending...' : 'Test'}
                                            </Button>
                                        </div>
                                    </div>

                                    {/* F&B Department Manager Group */}
                                    <div className="p-3.5 rounded-lg border border-border/50 bg-muted/10 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold text-foreground">
                                                F&B Department Manager Group
                                            </Label>
                                            <Badge variant="outline" className="text-[10px] bg-amber-500/10 text-amber-600 border-amber-500/20">
                                                F&B
                                            </Badge>
                                        </div>
                                        <p className="text-[11px] text-muted-foreground">
                                            Target for new On Hand Delete submissions and store inventory issue requests.
                                        </p>
                                        <div className="flex gap-2">
                                            <Input
                                                value={data.edit_request_telegram_chat_id_fb}
                                                onChange={(e) => setData('edit_request_telegram_chat_id_fb', e.target.value)}
                                                placeholder="-1003821366530"
                                                className="font-mono text-xs h-8 flex-1"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleSendTestMessage(data.edit_request_telegram_chat_id_fb, 'F&B Group', 'edit_request')}
                                                disabled={testingChannel === 'edit_request_F&B Group'}
                                                className="h-8 text-xs shrink-0"
                                            >
                                                <Send className="h-3 w-3 mr-1" />
                                                {testingChannel === 'edit_request_F&B Group' ? 'Sending...' : 'Test'}
                                            </Button>
                                        </div>
                                    </div>

                                    {/* On Hand Delete Finalized Group */}
                                    <div className="p-3.5 rounded-lg border border-border/50 bg-muted/10 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold text-foreground">
                                                On Hand Delete Finalized Group
                                            </Label>
                                            <Badge variant="outline" className="text-[10px] bg-purple-500/10 text-purple-600 border-purple-500/20">
                                                Inventory
                                            </Badge>
                                        </div>
                                        <p className="text-[11px] text-muted-foreground">
                                            Receives completion notifications once IT has deleted or rejected the on-hand record.
                                        </p>
                                        <div className="flex gap-2">
                                            <Input
                                                value={data.edit_request_telegram_chat_id_onhand_delete}
                                                onChange={(e) => setData('edit_request_telegram_chat_id_onhand_delete', e.target.value)}
                                                placeholder="-1002426639974"
                                                className="font-mono text-xs h-8 flex-1"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleSendTestMessage(data.edit_request_telegram_chat_id_onhand_delete, 'On Hand Delete Group', 'edit_request')}
                                                disabled={testingChannel === 'edit_request_On Hand Delete Group'}
                                                className="h-8 text-xs shrink-0"
                                            >
                                                <Send className="h-3 w-3 mr-1" />
                                                {testingChannel === 'edit_request_On Hand Delete Group' ? 'Sending...' : 'Test'}
                                            </Button>
                                        </div>
                                    </div>

                                    {/* Training Coordinator Chat */}
                                    <div className="p-3.5 rounded-lg border border-border/50 bg-muted/10 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold text-foreground">
                                                Training & Coordinator Chat ID
                                            </Label>
                                            <Badge variant="outline" className="text-[10px] bg-zinc-500/10 text-zinc-600 border-zinc-500/20">
                                                Coordinator
                                            </Badge>
                                        </div>
                                        <p className="text-[11px] text-muted-foreground">
                                            Direct channel for training coordinators and administrative dispatch.
                                        </p>
                                        <div className="flex gap-2">
                                            <Input
                                                value={data.edit_request_telegram_coordinator_chat_id}
                                                onChange={(e) => setData('edit_request_telegram_coordinator_chat_id', e.target.value)}
                                                placeholder="814523272"
                                                className="font-mono text-xs h-8 flex-1"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleSendTestMessage(data.edit_request_telegram_coordinator_chat_id, 'Coordinator Chat', 'edit_request')}
                                                disabled={testingChannel === 'edit_request_Coordinator Chat'}
                                                className="h-8 text-xs shrink-0"
                                            >
                                                <Send className="h-3 w-3 mr-1" />
                                                {testingChannel === 'edit_request_Coordinator Chat' ? 'Sending...' : 'Test'}
                                            </Button>
                                        </div>
                                    </div>

                                    {/* Branch Managers Broadcast Group */}
                                    <div className="p-3.5 rounded-lg border border-border/50 bg-muted/10 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-bold text-foreground">
                                                Branch Managers Broadcast Group
                                            </Label>
                                            <Badge variant="outline" className="text-[10px] bg-sky-500/10 text-sky-600 border-sky-500/20">
                                                Branches
                                            </Badge>
                                        </div>
                                        <p className="text-[11px] text-muted-foreground">
                                            Channel for company-wide branch manager notices and policy announcements.
                                        </p>
                                        <div className="flex gap-2">
                                            <Input
                                                value={data.edit_request_telegram_branch_managers_group_chat_id}
                                                onChange={(e) => setData('edit_request_telegram_branch_managers_group_chat_id', e.target.value)}
                                                placeholder="1626288394"
                                                className="font-mono text-xs h-8 flex-1"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleSendTestMessage(data.edit_request_telegram_branch_managers_group_chat_id, 'Branch Managers Group', 'edit_request')}
                                                disabled={testingChannel === 'edit_request_Branch Managers Group'}
                                                className="h-8 text-xs shrink-0"
                                            >
                                                <Send className="h-3 w-3 mr-1" />
                                                {testingChannel === 'edit_request_Branch Managers Group' ? 'Sending...' : 'Test'}
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
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
                                            Dedicated bot for IT Support Cases, ticket dispatch, and SLA alerts (from legacy <code>case/case_config.php</code>).
                                        </CardDescription>
                                    </div>
                                    <Badge variant="outline" className="text-[10px] bg-emerald-500/10 text-emerald-600 border-emerald-500/30">
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
                                            {showCaseToken ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
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
                                                onClick={() => handleSendTestMessage(data.case_telegram_group_chat_id, 'Case Alerts Group', 'case')}
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
                                            <Label htmlFor="case_notify_assignee_direct" className="text-xs font-semibold cursor-pointer">
                                                Notify Assignee Privately
                                            </Label>
                                            <p className="text-[11px] text-muted-foreground">
                                                Also send a private alert to the assignee's personal Telegram Chat ID.
                                            </p>
                                        </div>
                                        <Switch
                                            id="case_notify_assignee_direct"
                                            checked={data.case_notify_assignee_direct === '1'}
                                            onCheckedChange={(checked) => setData('case_notify_assignee_direct', checked ? '1' : '0')}
                                        />
                                    </div>
                                    <p className="text-[11px] text-muted-foreground">
                                        Configure individual Telegram Chat IDs in the <span className="font-semibold text-foreground">Categories & Assignees</span> tab.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* TAB 3: CASE CATEGORIES & ASSIGNEES */}
                    <TabsContent value="case-module" className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Case Types Management */}
                            <Card className="border-border/60 shadow-xs">
                                <CardHeader className="py-3 px-5 border-b border-border/40">
                                    <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                        <Tag className="h-4 w-4 text-amber-500" /> Case Types / Categories
                                    </CardTitle>
                                    <CardDescription className="text-xs">
                                        Classification types selectable when logging a new support ticket
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-4 space-y-3">
                                    <form onSubmit={handleAddType} className="flex gap-2">
                                        <Input
                                            placeholder="e.g. CNET POS & Billing Error..."
                                            value={newType}
                                            onChange={(e) => setNewType(e.target.value)}
                                            className="h-8 text-xs flex-1"
                                        />
                                        <Button type="submit" size="sm" className="h-8 text-xs">
                                            <Plus className="h-3.5 w-3.5 mr-1" /> Add
                                        </Button>
                                    </form>

                                    <div className="rounded-lg border border-border/50 divide-y divide-border/30 overflow-hidden text-xs max-h-72 overflow-y-auto">
                                        {types.length === 0 ? (
                                            <div className="p-4 text-center text-muted-foreground text-xs">No categories yet</div>
                                        ) : (
                                            types.map((t) => (
                                                <div key={t.id} className="p-2.5 flex items-center justify-between hover:bg-muted/20">
                                                    <span className="font-medium text-foreground">{t.name}</span>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => handleDeleteType(t.id)}
                                                        className="h-6 w-6 text-destructive hover:text-destructive"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Assignees Management with Telegram ID */}
                            <Card className="border-border/60 shadow-xs">
                                <CardHeader className="py-3 px-5 border-b border-border/40">
                                    <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                        <Users className="h-4 w-4 text-amber-500" /> IT Case Assignees & Telegram IDs
                                    </CardTitle>
                                    <CardDescription className="text-xs">
                                        Staff eligible to be assigned support tickets with individual Telegram routing
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-4 space-y-3">
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
                                        <Button type="submit" size="sm" className="w-full h-8 text-xs">
                                            <Plus className="h-3.5 w-3.5 mr-1" /> Add Assignee
                                        </Button>
                                    </form>

                                    <div className="rounded-lg border border-border/50 divide-y divide-border/30 overflow-hidden text-xs max-h-72 overflow-y-auto">
                                        {assignees.length === 0 ? (
                                            <div className="p-4 text-center text-muted-foreground text-xs">No assignees configured</div>
                                        ) : (
                                            assignees.map((a) => (
                                                <div key={a.id} className="p-2.5 flex items-center justify-between hover:bg-muted/20">
                                                    <div>
                                                        <span className="font-medium text-foreground">{a.name}</span>
                                                        {a.telegram_id ? (
                                                            <span className="block text-[10px] text-muted-foreground font-mono">
                                                                Chat ID: {a.telegram_id}
                                                            </span>
                                                        ) : (
                                                            <span className="block text-[10px] text-amber-600">No Telegram ID</span>
                                                        )}
                                                    </div>
                                                    <div className="flex items-center gap-1">
                                                        {a.telegram_id && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleSendTestMessage(a.telegram_id!, a.name, 'case')}
                                                                className="h-6 text-[10px] px-2 text-muted-foreground hover:text-foreground"
                                                            >
                                                                Test
                                                            </Button>
                                                        )}
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => handleDeleteAssignee(a.id)}
                                                            className="h-6 w-6 text-destructive hover:text-destructive"
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
                                    <Building2 className="h-4 w-4 text-purple-500" /> Portal Profile & Contact Information
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    Company metadata embedded in PDF/CSV export documents and alert messages.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-5 space-y-4">
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="company_name" className="text-xs font-semibold">Company / Entity Name</Label>
                                        <Input
                                            id="company_name"
                                            value={data.company_name}
                                            onChange={(e) => setData('company_name', e.target.value)}
                                            className="text-xs h-9"
                                        />
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label htmlFor="company_phone" className="text-xs font-semibold flex items-center gap-1">
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
                                        <Label htmlFor="support_email" className="text-xs font-semibold flex items-center gap-1">
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
