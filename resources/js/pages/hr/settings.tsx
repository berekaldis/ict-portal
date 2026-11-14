import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Bot,
    Building2,
    Briefcase,
    Eye,
    EyeOff,
    Plus,
    Radio,
    Save,
    Send,
    Settings,
    Shield,
    Trash2,
    Users,
} from 'lucide-react';
import React, { useState } from 'react';

interface SettingItem {
    id: number;
    type: string;
    value: string;
    chat_id?: string;
}

interface Props {
    grouped: {
        branches: SettingItem[];
        jobs: SettingItem[];
        ranks: SettingItem[];
    };
    botToken: string;
}

export default function HrSettingsPage({ grouped, botToken }: Props) {
    const [newBranch, setNewBranch] = useState('');
    const [newBranchChat, setNewBranchChat] = useState('');
    const [newJob, setNewJob] = useState('');
    const [newRank, setNewRank] = useState('');
    const [showBotToken, setShowBotToken] = useState(false);
    const [testingChannel, setTestingChannel] = useState<string | null>(null);

    const { data, setData, post, processing } = useForm({
        bot_token: botToken || '',
    });

    const handleSaveBotToken = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hr.settings.bot-token'));
    };

    const handleAddBranch = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newBranch.trim()) return;

        router.post(
            route('hr.settings.store'),
            { type: 'branch', value: newBranch.trim(), chat_id: newBranchChat.trim() || undefined },
            {
                onSuccess: () => {
                    setNewBranch('');
                    setNewBranchChat('');
                },
            }
        );
    };

    const handleAddJob = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newJob.trim()) return;

        router.post(
            route('hr.settings.store'),
            { type: 'job', value: newJob.trim() },
            { onSuccess: () => setNewJob('') }
        );
    };

    const handleAddRank = (e: React.FormEvent) => {
        e.preventDefault();
        if (!newRank.trim()) return;

        router.post(
            route('hr.settings.store'),
            { type: 'rank', value: newRank.trim() },
            { onSuccess: () => setNewRank('') }
        );
    };

    const handleUpdateChatId = (id: number, chatId: string) => {
        router.put(route('hr.settings.update-chat', id), { chat_id: chatId });
    };

    const handleDeleteSetting = (id: number) => {
        if (confirm('Delete this setting item?')) {
            router.delete(route('hr.settings.destroy', id));
        }
    };

    const handleSendTestMessage = (chatId: string, branchName: string) => {
        if (!chatId.trim()) {
            alert('Please enter a Chat ID first.');
            return;
        }

        setTestingChannel(branchName);
        router.post(
            route('hr.settings.test-telegram'),
            { chat_id: chatId.trim(), branch: branchName },
            { onFinish: () => setTestingChannel(null) }
        );
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'HR Management', href: '/hr' }, { title: 'Settings & Telegram' }]}>
            <Head title="HR Settings & Telegram Routing | Kaldis Coffee" />

            <div className="space-y-6 max-w-5xl mx-auto pb-12">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-border/40">
                    <div className="flex items-center gap-3">
                        <Button asChild variant="outline" size="icon" className="h-9 w-9">
                            <Link href={route('hr.dashboard')}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                                <Settings className="h-5 w-5 text-amber-600" /> HR Settings & Telegram Notification Routing
                            </h1>
                            <p className="text-xs text-muted-foreground">
                                Configure HR Telegram bot credentials, branch chat routing, positions, and managerial ranks
                            </p>
                        </div>
                    </div>
                </div>

                {/* HR Bot Token Card */}
                <Card className="border-border/60 shadow-xs">
                    <CardHeader className="py-3.5 px-5 border-b border-border/40 bg-amber-500/5 flex flex-row items-center justify-between">
                        <div className="space-y-0.5">
                            <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                <Bot className="w-4 h-4 text-amber-600" /> HR Telegram Bot API Token
                            </CardTitle>
                            <CardDescription className="text-xs">
                                Powers automated Telegram dispatch notifications for internships, probation qualifications, and placements
                            </CardDescription>
                        </div>
                        <Badge variant="outline" className="text-[10px] bg-amber-500/10 text-amber-700 border-amber-500/30">
                            HR Bot
                        </Badge>
                    </CardHeader>
                    <CardContent className="p-5">
                        <form onSubmit={handleSaveBotToken} className="space-y-3 max-w-2xl">
                            <Label className="text-xs font-semibold">Bot Token (from @BotFather)</Label>
                            <div className="flex gap-2">
                                <div className="relative flex-1">
                                    <Input
                                        type={showBotToken ? 'text' : 'password'}
                                        placeholder="e.g. 8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8"
                                        value={data.bot_token}
                                        onChange={(e) => setData('bot_token', e.target.value)}
                                        className="text-xs h-9 font-mono pr-10"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowBotToken(!showBotToken)}
                                        className="absolute right-2.5 top-2.5 text-muted-foreground hover:text-foreground"
                                        title={showBotToken ? 'Hide' : 'Show'}
                                    >
                                        {showBotToken ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                                    </button>
                                </div>
                                <Button type="submit" size="sm" disabled={processing} className="h-9 text-xs bg-amber-700 hover:bg-amber-800 text-white shrink-0">
                                    <Save className="w-3.5 h-3.5 mr-1" />
                                    {processing ? 'Saving...' : 'Save Token'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Tabs */}
                <Tabs defaultValue="branches" className="space-y-4">
                    <TabsList className="bg-muted/50 p-1 border border-border/40">
                        <TabsTrigger value="branches" className="text-xs flex items-center gap-1.5">
                            <Building2 className="w-3.5 h-3.5 text-amber-600" /> Branches & Telegram Routing ({grouped.branches.length})
                        </TabsTrigger>
                        <TabsTrigger value="jobs" className="text-xs flex items-center gap-1.5">
                            <Briefcase className="w-3.5 h-3.5 text-blue-600" /> Positions / Jobs ({grouped.jobs.length})
                        </TabsTrigger>
                        <TabsTrigger value="ranks" className="text-xs flex items-center gap-1.5">
                            <Users className="w-3.5 h-3.5 text-purple-600" /> Managerial Ranks ({grouped.ranks.length})
                        </TabsTrigger>
                    </TabsList>

                    {/* Branches Tab with Chat ID Routing */}
                    <TabsContent value="branches" className="space-y-4">
                        <Card className="border-border/60 shadow-xs">
                            <CardHeader className="py-3 px-5 border-b border-border/40">
                                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                    <Radio className="w-4 h-4 text-amber-600" /> Branch Notification Chat IDs
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    Set individual Telegram group or manager Chat IDs for each Kaldis branch to receive direct staff alerts
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-4 space-y-4">
                                <form onSubmit={handleAddBranch} className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                    <Input
                                        placeholder="Branch Name (e.g. Bole Medhanialem)..."
                                        value={newBranch}
                                        onChange={(e) => setNewBranch(e.target.value)}
                                        className="text-xs h-8"
                                    />
                                    <Input
                                        placeholder="Telegram Chat ID (e.g. -1001502645326)..."
                                        value={newBranchChat}
                                        onChange={(e) => setNewBranchChat(e.target.value)}
                                        className="text-xs h-8 font-mono"
                                    />
                                    <Button type="submit" size="sm" className="h-8 text-xs bg-amber-700 hover:bg-amber-800 text-white">
                                        <Plus className="w-3.5 h-3.5 mr-1" /> Add Branch
                                    </Button>
                                </form>

                                <div className="rounded-lg border border-border/50 divide-y divide-border/30 overflow-hidden text-xs">
                                    {grouped.branches.map((b) => (
                                        <div key={b.id} className="p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-muted/15">
                                            <div className="font-semibold text-foreground">{b.value}</div>
                                            <div className="flex items-center gap-2">
                                                <Input
                                                    defaultValue={b.chat_id || ''}
                                                    placeholder="Chat ID..."
                                                    onBlur={(e) => handleUpdateChatId(b.id, e.target.value)}
                                                    className="h-7 text-xs font-mono w-44"
                                                />
                                                {b.chat_id && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleSendTestMessage(b.chat_id!, b.value)}
                                                        disabled={testingChannel === b.value}
                                                        className="h-7 text-[10px] px-2 text-foreground"
                                                    >
                                                        <Send className="w-3 h-3 mr-1" />
                                                        {testingChannel === b.value ? 'Testing...' : 'Test'}
                                                    </Button>
                                                )}
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => handleDeleteSetting(b.id)}
                                                    className="h-7 w-7 text-destructive hover:text-destructive"
                                                >
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Jobs / Positions Tab */}
                    <TabsContent value="jobs" className="space-y-4">
                        <Card className="border-border/60 shadow-xs">
                            <CardHeader className="py-3 px-5 border-b border-border/40">
                                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                    <Briefcase className="w-4 h-4 text-blue-600" /> Job Positions
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    Positions selectable across internships, qualifications, and recruitments
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3">
                                <form onSubmit={handleAddJob} className="flex gap-2">
                                    <Input
                                        placeholder="Position Name (e.g. Barista Trainer)..."
                                        value={newJob}
                                        onChange={(e) => setNewJob(e.target.value)}
                                        className="h-8 text-xs flex-1"
                                    />
                                    <Button type="submit" size="sm" className="h-8 text-xs bg-blue-700 hover:bg-blue-800 text-white">
                                        <Plus className="w-3.5 h-3.5 mr-1" /> Add Position
                                    </Button>
                                </form>

                                <div className="rounded-lg border border-border/50 divide-y divide-border/30 overflow-hidden text-xs">
                                    {grouped.jobs.map((j) => (
                                        <div key={j.id} className="p-2.5 flex items-center justify-between hover:bg-muted/15">
                                            <span className="font-medium text-foreground">{j.value}</span>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => handleDeleteSetting(j.id)}
                                                className="h-6 w-6 text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Managerial Ranks Tab */}
                    <TabsContent value="ranks" className="space-y-4">
                        <Card className="border-border/60 shadow-xs">
                            <CardHeader className="py-3 px-5 border-b border-border/40">
                                <CardTitle className="text-sm font-semibold flex items-center gap-2">
                                    <Users className="w-4 h-4 text-purple-600" /> Managerial Titles & Ranks
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    Titles for supervisors and managers endorsing probation qualifications
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="p-4 space-y-3">
                                <form onSubmit={handleAddRank} className="flex gap-2">
                                    <Input
                                        placeholder="Rank Title (e.g. Area Operations Manager)..."
                                        value={newRank}
                                        onChange={(e) => setNewRank(e.target.value)}
                                        className="h-8 text-xs flex-1"
                                    />
                                    <Button type="submit" size="sm" className="h-8 text-xs bg-purple-700 hover:bg-purple-800 text-white">
                                        <Plus className="w-3.5 h-3.5 mr-1" /> Add Rank
                                    </Button>
                                </form>

                                <div className="rounded-lg border border-border/50 divide-y divide-border/30 overflow-hidden text-xs">
                                    {grouped.ranks.map((r) => (
                                        <div key={r.id} className="p-2.5 flex items-center justify-between hover:bg-muted/15">
                                            <span className="font-medium text-foreground">{r.value}</span>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => handleDeleteSetting(r.id)}
                                                className="h-6 w-6 text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
