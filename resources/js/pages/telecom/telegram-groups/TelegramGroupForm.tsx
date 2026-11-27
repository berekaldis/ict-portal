import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    ExternalLink,
    Hash,
    Info,
    Link as LinkIcon,
    MessageCircle,
    QrCode,
    Save,
    Users,
} from 'lucide-react';
import React from 'react';

export type GroupFormData = {
    name: string;
    chat_id: string;
    username: string;
    group_type: string;
    category: string;
    purpose: string;
    description: string;
    branch_id: string;
    department_id: string;
    managed_by: string;
    status: string;
    invite_link: string;
    is_public: boolean;
    member_count: string;
    notes: string;
    ticket_id?: string | number | null;
};

type Option = { id: number; name?: string; branch_name?: string };

type Props = {
    data: GroupFormData;
    setData: (field: keyof GroupFormData, value: string | boolean) => void;
    errors: Partial<Record<keyof GroupFormData, string>>;
    processing: boolean;
    onSubmit: (e: React.FormEvent) => void;
    branches: Option[];
    departments: Option[];
    groupTypes: string[];
    categories: string[];
    statuses: string[];
    mode: 'create' | 'edit';
    existingQr?: string | null;
    existingLink?: string | null;
};

function TelegramIcon({ className }: { className?: string }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="currentColor">
            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
        </svg>
    );
}

export default function TelegramGroupForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    branches,
    departments,
    groupTypes,
    categories,
    statuses,
    mode,
    existingQr,
}: Props) {
    const telegramUrl = data.username
        ? `https://t.me/${data.username.replace('@', '')}`
        : data.invite_link || null;

    return (
        <form onSubmit={onSubmit} className="space-y-6">
            {/* Header */}
            <div className="flex items-center gap-3">
                <Link href="/telecom/telegram-groups">
                    <Button type="button" variant="ghost" size="sm" className="gap-1.5">
                        <ArrowLeft className="h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div className="flex items-center gap-2">
                    <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-sky-400 shadow">
                        <TelegramIcon className="h-4.5 w-4.5 text-white" />
                    </div>
                    <div>
                        <h1 className="text-lg font-bold text-foreground">
                            {mode === 'create' ? 'Register Telegram Group' : 'Edit Telegram Group'}
                        </h1>
                        <p className="text-xs text-muted-foreground">
                            {mode === 'create'
                                ? 'Add a new Kaldis Coffee Telegram group or broadcast channel'
                                : 'Update group details, invite links, and QR code'}
                        </p>
                    </div>
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                {/* ── Left / Main ── */}
                <div className="lg:col-span-2 space-y-5">
                    {/* Basic Info */}
                    <Card className="border shadow-xs">
                        <CardHeader className="pb-3 pt-4">
                            <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                                <MessageCircle className="h-4 w-4 text-blue-500" />
                                Group Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="name" className="text-sm font-medium">
                                        Group Name <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        placeholder="e.g. Kaldis HQ Operations"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className={errors.name ? 'border-destructive' : ''}
                                    />
                                    {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="group_type" className="text-sm font-medium">
                                        Group Type <span className="text-destructive">*</span>
                                    </Label>
                                    <Select value={data.group_type} onValueChange={(v) => setData('group_type', v)}>
                                        <SelectTrigger id="group_type" className={errors.group_type ? 'border-destructive' : ''}>
                                            <SelectValue placeholder="Select type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {groupTypes.map((t) => (
                                                <SelectItem key={t} value={t}>
                                                    {t}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.group_type && <p className="text-xs text-destructive">{errors.group_type}</p>}
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="category" className="text-sm font-medium">
                                        Category
                                    </Label>
                                    <Select
                                        value={data.category || '__none__'}
                                        onValueChange={(v) => setData('category', v === '__none__' ? '' : v)}
                                    >
                                        <SelectTrigger id="category">
                                            <SelectValue placeholder="Select category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__none__">— No Category —</SelectItem>
                                            {categories.map((c) => (
                                                <SelectItem key={c} value={c}>
                                                    {c}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="status" className="text-sm font-medium">
                                        Status <span className="text-destructive">*</span>
                                    </Label>
                                    <Select value={data.status} onValueChange={(v) => setData('status', v)}>
                                        <SelectTrigger id="status" className={errors.status ? 'border-destructive' : ''}>
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {statuses.map((s) => (
                                                <SelectItem key={s} value={s}>
                                                    {s}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.status && <p className="text-xs text-destructive">{errors.status}</p>}
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="purpose" className="text-sm font-medium">
                                    Purpose (short)
                                </Label>
                                <Input
                                    id="purpose"
                                    placeholder="e.g. Daily operations updates for HQ staff"
                                    value={data.purpose}
                                    onChange={(e) => setData('purpose', e.target.value)}
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="description" className="text-sm font-medium">
                                    Full Description
                                </Label>
                                <Textarea
                                    id="description"
                                    rows={3}
                                    placeholder="Detailed description of the group's purpose and usage…"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Telegram Identity */}
                    <Card className="border shadow-xs">
                        <CardHeader className="pb-3 pt-4">
                            <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                                <Hash className="h-4 w-4 text-blue-500" />
                                Telegram Identity
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="chat_id" className="text-sm font-medium">
                                        Chat ID
                                    </Label>
                                    <Input
                                        id="chat_id"
                                        placeholder="e.g. -1001234567890"
                                        value={data.chat_id}
                                        onChange={(e) => setData('chat_id', e.target.value)}
                                        className={`font-mono ${errors.chat_id ? 'border-destructive' : ''}`}
                                    />
                                    <p className="text-[11px] text-muted-foreground">
                                        Numeric Telegram Chat ID (negative for groups/channels)
                                    </p>
                                    {errors.chat_id && <p className="text-xs text-destructive">{errors.chat_id}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="username" className="text-sm font-medium">
                                        Username
                                    </Label>
                                    <div className="relative">
                                        <span className="absolute left-3 top-2.5 text-sm text-muted-foreground">@</span>
                                        <Input
                                            id="username"
                                            placeholder="kaldis_hq_ops"
                                            className="pl-7"
                                            value={data.username.replace('@', '')}
                                            onChange={(e) => setData('username', e.target.value)}
                                        />
                                    </div>
                                    <p className="text-[11px] text-muted-foreground">
                                        Only for public groups/channels
                                    </p>
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="member_count" className="text-sm font-medium">
                                        Member Count
                                    </Label>
                                    <div className="relative">
                                        <Users className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            id="member_count"
                                            type="number"
                                            min="0"
                                            placeholder="e.g. 245"
                                            className="pl-8"
                                            value={data.member_count}
                                            onChange={(e) => setData('member_count', e.target.value)}
                                        />
                                    </div>
                                    <p className="text-[11px] text-muted-foreground">Last known member count</p>
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label className="text-sm font-medium">Visibility</Label>
                                    <div className="flex items-center gap-3 rounded-lg border p-3">
                                        <Switch
                                            id="is_public"
                                            checked={data.is_public}
                                            onCheckedChange={(v) => setData('is_public', v)}
                                        />
                                        <div>
                                            <Label htmlFor="is_public" className="text-sm cursor-pointer font-medium">
                                                {data.is_public ? 'Public Group' : 'Private Group'}
                                            </Label>
                                            <p className="text-[11px] text-muted-foreground">
                                                {data.is_public ? 'Visible via username/link' : 'Invite-only access'}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Assignment */}
                    <Card className="border shadow-xs">
                        <CardHeader className="pb-3 pt-4">
                            <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                                <Building2 className="h-4 w-4 text-blue-500" />
                                Assignment &amp; Ownership
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-1.5">
                                    <Label htmlFor="branch_id" className="text-sm font-medium">
                                        Branch
                                    </Label>
                                    <Select
                                        value={data.branch_id || '__none__'}
                                        onValueChange={(v) => setData('branch_id', v === '__none__' ? '' : v)}
                                    >
                                        <SelectTrigger id="branch_id">
                                            <SelectValue placeholder="Select branch" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__none__">— No Branch —</SelectItem>
                                            {branches.map((b) => (
                                                <SelectItem key={b.id} value={String(b.id)}>
                                                    {b.name || b.branch_name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="department_id" className="text-sm font-medium">
                                        Department
                                    </Label>
                                    <Select
                                        value={data.department_id || '__none__'}
                                        onValueChange={(v) => setData('department_id', v === '__none__' ? '' : v)}
                                    >
                                        <SelectTrigger id="department_id">
                                            <SelectValue placeholder="Select department" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__none__">— No Department —</SelectItem>
                                            {departments.map((d) => (
                                                <SelectItem key={d.id} value={String(d.id)}>
                                                    {d.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="managed_by" className="text-sm font-medium">
                                    Managed By
                                </Label>
                                <Input
                                    id="managed_by"
                                    placeholder="e.g. IT Department / Manager Name"
                                    value={data.managed_by}
                                    onChange={(e) => setData('managed_by', e.target.value)}
                                />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="notes" className="text-sm font-medium">
                                    Notes
                                </Label>
                                <Textarea
                                    id="notes"
                                    rows={2}
                                    placeholder="Internal notes or comments…"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* ── Right sidebar ── */}
                <div className="space-y-5">
                    {/* Invite Link & QR */}
                    <Card className="border shadow-xs">
                        <CardHeader className="pb-3 pt-4">
                            <CardTitle className="flex items-center gap-2 text-sm font-semibold">
                                <LinkIcon className="h-4 w-4 text-purple-500" />
                                Invite Link &amp; QR Code
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-1.5">
                                <Label htmlFor="invite_link" className="text-sm font-medium">
                                    Invite Link
                                </Label>
                                <Input
                                    id="invite_link"
                                    placeholder="https://t.me/+XXXXXXXX"
                                    value={data.invite_link}
                                    onChange={(e) => setData('invite_link', e.target.value)}
                                    className={errors.invite_link ? 'border-destructive' : ''}
                                />
                                {errors.invite_link && <p className="text-xs text-destructive">{errors.invite_link}</p>}
                                <p className="text-[11px] text-muted-foreground">
                                    Paste Telegram invite link. QR code will auto-generate on save.
                                </p>
                            </div>

                            {telegramUrl && (
                                <a
                                    href={telegramUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700 hover:bg-blue-100 transition-colors dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-400"
                                >
                                    <ExternalLink className="h-3.5 w-3.5 flex-shrink-0" />
                                    <span className="truncate">{telegramUrl}</span>
                                </a>
                            )}

                            {existingQr && (
                                <div className="space-y-2">
                                    <Label className="text-xs font-medium text-muted-foreground">Current QR Code</Label>
                                    <div className="flex flex-col items-center gap-2 rounded-xl border-2 border-dashed border-purple-200 bg-purple-50/50 p-4 dark:border-purple-800 dark:bg-purple-950/10">
                                        <img
                                            src={`/storage/${existingQr}`}
                                            alt="QR Code"
                                            className="h-40 w-40 rounded-lg object-contain"
                                        />
                                        <a
                                            href={`/storage/${existingQr}`}
                                            download
                                            className="flex items-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700 transition-colors"
                                        >
                                            <QrCode className="h-3.5 w-3.5" />
                                            Download QR
                                        </a>
                                    </div>
                                </div>
                            )}

                            {!existingQr && (
                                <div className="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-border/60 bg-muted/20 p-6">
                                    <QrCode className="h-10 w-10 text-muted-foreground/40" />
                                    <p className="text-xs text-center text-muted-foreground">
                                        QR code will be generated automatically when saved with an invite link.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Info card */}
                    <Card className="border border-blue-100 bg-blue-50/50 shadow-xs dark:border-blue-900/30 dark:bg-blue-950/10">
                        <CardContent className="p-4">
                            <div className="flex gap-2">
                                <Info className="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" />
                                <div className="space-y-1.5 text-xs text-muted-foreground">
                                    <p className="font-semibold text-blue-700 dark:text-blue-400">Telegram Group Info Guide</p>
                                    <ul className="space-y-1 list-disc list-inside">
                                        <li>Add <strong>@userinfobot</strong> to the group to inspect Chat ID</li>
                                        <li>Generate invite link in Telegram: Group Info &rarr; Invite Link</li>
                                        <li>QR code generates automatically from the invite link</li>
                                    </ul>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Submit */}
                    <Button
                        type="submit"
                        disabled={processing}
                        className="w-full gap-2 font-semibold shadow-sm"
                    >
                        <Save className="h-4 w-4" />
                        {processing
                            ? mode === 'create'
                                ? 'Registering…'
                                : 'Saving…'
                            : mode === 'create'
                            ? 'Register Group'
                            : 'Save Changes'}
                    </Button>
                </div>
            </div>
        </form>
    );
}
