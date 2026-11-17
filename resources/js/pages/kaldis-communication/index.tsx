import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import {
    Activity,
    Bot,
    Building2,
    CheckCircle2,
    CheckSquare,
    Clock,
    Edit3,
    Eye,
    EyeOff,
    FileText,
    Filter,
    Layers,
    Link2Off,
    Lock,
    Megaphone,
    MessageSquare,
    Plus,
    RefreshCw,
    Save,
    Search,
    Send,
    Shield,
    ShieldAlert,
    Sparkles,
    Square,
    Trash2,
    UserCheck,
    UserPlus,
    Users,
    UserX,
    Volume2,
    X,
    XCircle,
    Zap,
} from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SearchableSelect } from '@/components/ui/searchable-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'System Administration', href: '/users' },
    { title: 'Kaldis Communication', href: '/kaldis-communication' },
];

type SystemStats = {
    total_communications: number;
    recorded_communications: number;
    forwarded_communications?: number;
    responded_communications: number;
    answered_communications?: number;
    unanswered_communications?: number;
    total_users: number;
    total_bindings: number;
};

const TOPIC_EMOJIS: Record<string, string> = {
    'Announcements': '📢',
    'Operations': '⚙️',
    'HR': '💼',
    'Finance': '💰',
    'Supply Chain': '📦',
    'IT': '💻',
    'Maintenance': '🔧',
    'F&B': '☕',
    'T&D': '🎓',
    'QA': '🛡️',
    'BI': '📊',
    'Logistics': '🚚',
};

type ConfigData = {
    bot_token: string;
    region_groups: {
        'Region 1': number;
        'Region 2': number;
    };
    ho_group_chat_id?: number | string;
    operations_director_user_id: number;
    database: string;
    anti_link_protection?: boolean;
    auto_welcome?: boolean;
    welcome_message?: string;
};

type RosterUser = {
    telegram_user_id: number;
    display_name: string;
    role: string;
    region: string | null;
    branch_name: string | null;
    department: string | null;
    created_at: string;
    updated_at: string;
};

type TopicBinding = {
    group_key: string;
    thread_id: number;
    topic_name: string;
    department: string;
    created_at: string;
    updated_at: string;
};

type CommunicationRecord = {
    reference_no: string;
    region: string;
    branch_name: string | null;
    topic_name: string;
    department: string;
    source_chat_id: number;
    source_message_id: number;
    source_thread_id: number | null;
    sender_user_id: number | null;
    sender_display_name: string;
    ho_chat_id: number | null;
    ho_summary_message_id: number | null;
    ho_message_id: number | null;
    regional_manager_user_id: number | null;
    department_head_user_id: number | null;
    status: string;
    created_at: string;
    updated_at: string;
};

type StandardTopicPreset = {
    name: string;
    department: string;
    emoji: string;
};

const getPresets = (mapping: any): StandardTopicPreset[] => {
    if (Array.isArray(mapping)) {
        return mapping.map((item: any) => ({
            name: typeof item === 'string' ? item : item?.name || '',
            department: typeof item === 'object' ? item?.department || 'Operations' : 'Operations',
            emoji: typeof item === 'object' ? item?.emoji || TOPIC_EMOJIS[item?.name] || '📌' : TOPIC_EMOJIS[item] || '📌',
        })).filter(p => p.name.length > 0);
    }
    if (mapping && typeof mapping === 'object') {
        return Object.entries(mapping).map(([name, department]) => ({
            name,
            department: department as string,
            emoji: TOPIC_EMOJIS[name] || '📌',
        }));
    }
    return [];
};

const getTopicEmoji = (topicName: string, mapping: any): string => {
    const presets = getPresets(mapping);
    const found = presets.find(p => p.name.toLowerCase() === topicName.toLowerCase());
    if (found && found.emoji) return found.emoji;
    return TOPIC_EMOJIS[topicName] || '📌';
};

type TopicStat = {
    topic_name: string;
    department: string;
    total: number;
    responded: number;
    unanswered: number;
};

type Props = {
    stats: SystemStats;
    config: ConfigData;
    rosterUsers: RosterUser[];
    topicBindings: TopicBinding[];
    communications: CommunicationRecord[];
    topicStats?: TopicStat[];
    defaultTopicMapping: StandardTopicPreset[] | Record<string, string>;
    departments: string[];
    branches: Array<{ id: number; name: string }>;
    systemUsers: Array<{ id: number; name: string; email: string; telegram_chat_id: string | null }>;
    filters: {
        search: string;
        region: string;
        status: string;
        topic?: string;
    };
    canManage: boolean;
};

export default function KaldisCommunicationPage({
    stats,
    config,
    rosterUsers,
    topicBindings,
    communications,
    topicStats = [],
    defaultTopicMapping,
    departments,
    branches,
    systemUsers,
    filters,
    canManage,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [regionFilter, setRegionFilter] = useState(filters.region || 'all');
    const [statusFilter, setStatusFilter] = useState(filters.status || 'all');
    const [topicFilter, setTopicFilter] = useState(filters.topic || 'all');
    const [rosterSearch, setRosterSearch] = useState('');

    const applyFilters = (s?: string, r?: string, st?: string, t?: string) => {
        const queryParams: Record<string, string> = {};
        const activeSearch = s !== undefined ? s : search;
        const activeRegion = r !== undefined ? r : regionFilter;
        const activeStatus = st !== undefined ? st : statusFilter;
        const activeTopic = t !== undefined ? t : topicFilter;

        if (activeSearch) queryParams.search = activeSearch;
        if (activeRegion && activeRegion !== 'all') queryParams.region = activeRegion;
        if (activeStatus && activeStatus !== 'all') queryParams.status = activeStatus;
        if (activeTopic && activeTopic !== 'all') queryParams.topic = activeTopic;

        router.get(route('kaldis-communication.index'), queryParams, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const departmentOptions = useMemo(
        () => departments.map((d) => ({ id: d, name: d })),
        [departments]
    );

    const branchOptions = useMemo(
        () => branches.map((b) => ({ id: b.name, name: b.name })),
        [branches]
    );

    const filteredRosterUsers = useMemo(() => {
        if (!rosterSearch.trim()) return rosterUsers;
        const q = rosterSearch.toLowerCase().trim();
        return rosterUsers.filter((u) => {
            return (
                String(u.telegram_user_id).includes(q) ||
                (u.display_name && u.display_name.toLowerCase().includes(q)) ||
                (u.role && u.role.toLowerCase().includes(q)) ||
                (u.region && u.region.toLowerCase().includes(q)) ||
                (u.branch_name && u.branch_name.toLowerCase().includes(q)) ||
                (u.department && u.department.toLowerCase().includes(q))
            );
        });
    }, [rosterUsers, rosterSearch]);

    // Dialog state
    const [isAddUserOpen, setIsAddUserOpen] = useState(false);
    const [isAddBindingOpen, setIsAddBindingOpen] = useState(false);
    const [isAddStandardTopicOpen, setIsAddStandardTopicOpen] = useState(false);
    const [isSyncingTopics, setIsSyncingTopics] = useState(false);

    const getRouteUrl = (name: string, fallback: string) => {
        try {
            return route(name);
        } catch {
            return fallback;
        }
    };

    const standardTopicForm = useForm({
        name: '',
        department: departments[0] || 'Operations',
        emoji: '📢',
    });

    const handleAddStandardTopic = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(getRouteUrl('kaldis-communication.update-config', '/kaldis-communication/config'), {
            action: 'add_standard_topic',
            name: standardTopicForm.data.name,
            department: standardTopicForm.data.department,
            emoji: standardTopicForm.data.emoji,
        }, {
            onSuccess: () => {
                toast.success(`Standard topic preset '${standardTopicForm.data.name}' added successfully!`);
                setIsAddStandardTopicOpen(false);
                standardTopicForm.reset();
            },
            onError: (errors: any) => {
                toast.error(errors.standard_topic || errors.name || 'Failed to add standard topic preset.');
            },
        });
    };

    const handleDeleteStandardTopic = (name: string) => {
        if (confirm(`Remove standard topic preset '${name}'?`)) {
            router.post(getRouteUrl('kaldis-communication.update-config', '/kaldis-communication/config'), {
                action: 'delete_standard_topic',
                name,
            }, {
                onSuccess: () => toast.success(`Standard topic preset '${name}' removed.`),
                onError: (errors: any) => toast.error(errors.standard_topic || 'Failed to remove standard topic preset.'),
            });
        }
    };

    const [isEditStandardTopicOpen, setIsEditStandardTopicOpen] = useState(false);
    const editStandardTopicForm = useForm({
        old_name: '',
        name: '',
        department: departments[0] || 'Operations',
        emoji: '📢',
    });

    const openEditStandardTopic = (preset: { name: string; department: string; emoji: string }) => {
        editStandardTopicForm.setData({
            old_name: preset.name,
            name: preset.name,
            department: preset.department,
            emoji: preset.emoji || '📢',
        });
        setIsEditStandardTopicOpen(true);
    };

    const handleUpdateStandardTopic = (e: React.FormEvent) => {
        e.preventDefault();
        router.post(getRouteUrl('kaldis-communication.update-config', '/kaldis-communication/config'), {
            action: 'update_standard_topic',
            old_name: editStandardTopicForm.data.old_name,
            name: editStandardTopicForm.data.name,
            department: editStandardTopicForm.data.department,
            emoji: editStandardTopicForm.data.emoji,
        }, {
            onSuccess: () => {
                toast.success(`Standard topic preset '${editStandardTopicForm.data.name}' updated!`);
                setIsEditStandardTopicOpen(false);
            },
            onError: (errors: any) => {
                toast.error(errors.standard_topic || errors.name || 'Failed to update standard topic preset.');
            },
        });
    };

    const handleClearServerCache = () => {
        router.post(getRouteUrl('kaldis-communication.update-config', '/kaldis-communication/config'), {
            action: 'clear_cache',
        }, {
            onSuccess: () => toast.success('Server cache cleared successfully!'),
            onError: () => toast.error('Failed to clear server cache.'),
        });
    };

    // Bulk Selection State
    const [selectedBindings, setSelectedBindings] = useState<string[]>([]);
    const [topicSubTab, setTopicSubTab] = useState('region1');

    const handleSyncTopics = (force: boolean = false, targetGroup: string = 'all') => {
        setIsSyncingTopics(true);
        router.post(route('kaldis-communication.sync-topics'), { force, target_group: targetGroup }, {
            onFinish: () => setIsSyncingTopics(false),
            onSuccess: () => toast.success(`Topic creation & sync request finished for ${targetGroup}!`),
            onError: (errors: any) => toast.error(errors.sync || 'Failed to sync topics with Telegram groups.'),
        });
    };

    const toggleSelectBinding = (key: string) => {
        if (selectedBindings.includes(key)) {
            setSelectedBindings(selectedBindings.filter(k => k !== key));
        } else {
            setSelectedBindings([...selectedBindings, key]);
        }
    };

    const toggleSelectAllGroup = (groupBindings: TopicBinding[]) => {
        const groupKeys = groupBindings.map(b => `${b.group_key}:${b.thread_id}`);
        const allSelected = groupKeys.every(k => selectedBindings.includes(k));
        if (allSelected) {
            setSelectedBindings(selectedBindings.filter(k => !groupKeys.includes(k)));
        } else {
            setSelectedBindings(Array.from(new Set([...selectedBindings, ...groupKeys])));
        }
    };

    const handleBulkDelete = (deleteFromTelegram: boolean) => {
        if (selectedBindings.length === 0) {
            toast.error('No topics selected for deletion.');
            return;
        }
        const bindingsToDelete = selectedBindings.map(k => {
            const [group_key, thread_id] = k.split(':');
            return { group_key, thread_id: parseInt(thread_id, 10) };
        });

        const msg = deleteFromTelegram
            ? `PERMANENTLY DELETE ${selectedBindings.length} selected topics from Telegram Groups AND System?`
            : `Unbind ${selectedBindings.length} selected topics from System database?`;

        if (confirm(msg)) {
            router.post(route('kaldis-communication.bulk-delete-bindings'), {
                bindings: bindingsToDelete,
                delete_from_telegram: deleteFromTelegram,
            }, {
                onSuccess: () => {
                    toast.success('Selected topics deleted successfully!');
                    setSelectedBindings([]);
                },
                onError: () => toast.error('Failed to bulk delete topics.'),
            });
        }
    };

    const handlePurgeUnlisted = (deleteFromTelegram: boolean = false) => {
        const msg = deleteFromTelegram
            ? '⚠️ PURGE UNLISTED TOPICS:\nAre you sure you want to delete all topics from Telegram Groups AND System database that are NOT in the standard 12 topic list?'
            : 'Unbind all unlisted topics from System database?';

        if (confirm(msg)) {
            router.post(route('kaldis-communication.purge-unlisted'), {
                delete_from_telegram: deleteFromTelegram,
            }, {
                onSuccess: () => toast.success('Purged unlisted topics successfully!'),
                onError: () => toast.error('Failed to purge unlisted topics.'),
            });
        }
    };

    // Config form
    const [showBotToken, setShowBotToken] = useState(false);
    const configForm = useForm({
        bot_token: config.bot_token || '',
        region_1_chat_id: config.region_groups?.['Region 1'] || '',
        region_2_chat_id: config.region_groups?.['Region 2'] || '',
        operations_director_user_id: config.operations_director_user_id || '',
    });

    // User form
    const userForm = useForm({
        telegram_user_id: '',
        display_name: '',
        role: 'branch_manager',
        region: 'Region 1',
        branch_name: '',
        department: 'Operations',
    });

    // Binding form
    const bindingForm = useForm<{
        group_key: string;
        thread_id: string;
        topic_name: string;
        department: string;
        emoji: string;
        create_on_telegram: boolean;
    }>({
        group_key: 'Region 1',
        thread_id: '',
        topic_name: 'Operations',
        department: 'Operations',
        emoji: '⚙️',
        create_on_telegram: true,
    });

    const openAddBindingPreset = (topicName: string, dept: string, emoji: string, targetGroup?: string) => {
        const groupKey = targetGroup || (topicSubTab === 'region2' ? 'Region 2' : 'Region 1');
        bindingForm.setData({
            group_key: groupKey,
            thread_id: '',
            topic_name: topicName,
            department: dept,
            emoji: emoji,
            create_on_telegram: true,
        });
        setIsAddBindingOpen(true);
    };

    // Edit Binding form
    const [isEditBindingOpen, setIsEditBindingOpen] = useState(false);
    const editBindingForm = useForm<{
        group_key: string;
        thread_id: number;
        topic_name: string;
        department: string;
        sync_to_telegram: boolean;
    }>({
        group_key: 'Region 1',
        thread_id: 0,
        topic_name: '',
        department: '',
        sync_to_telegram: true,
    });

    const openEditBinding = (b: TopicBinding) => {
        editBindingForm.setData({
            group_key: b.group_key,
            thread_id: b.thread_id,
            topic_name: b.topic_name,
            department: b.department,
            sync_to_telegram: true,
        });
        setIsEditBindingOpen(true);
    };

    const handleUpdateBinding = (e: React.FormEvent) => {
        e.preventDefault();
        editBindingForm.put(route('kaldis-communication.update-binding'), {
            onSuccess: () => {
                toast.success('Topic binding updated successfully!');
                setIsEditBindingOpen(false);
            },
            onError: (errors: any) => toast.error(errors.update || 'Failed to update topic binding.'),
        });
    };

    const handleSaveConfig = (e: React.FormEvent) => {
        e.preventDefault();
        configForm.post(route('kaldis-communication.update-config'), {
            onSuccess: () => toast.success('Bot configuration saved successfully!'),
            onError: () => toast.error('Failed to save configuration.'),
        });
    };

    const [customWebhookUrl, setCustomWebhookUrl] = useState('');
    const [isSettingWebhook, setIsSettingWebhook] = useState(false);

    const handleSetWebhook = () => {
        setIsSettingWebhook(true);
        router.post(route('kaldis-communication.set-webhook'), {
            webhook_url: customWebhookUrl,
        }, {
            onFinish: () => setIsSettingWebhook(false),
            onSuccess: () => toast.success('Live Telegram Webhook activated! Group messages, topic commands (/it, /hr), and member joins are now live.'),
            onError: (errors: any) => toast.error(errors.webhook || 'Failed to set Telegram webhook.'),
        });
    };

    const [isRegisteringCmds, setIsRegisteringCmds] = useState(false);

    const handleRegisterCommands = () => {
        setIsRegisteringCmds(true);
        router.post(route('kaldis-communication.update-config'), {
            action: 'register_commands',
        }, {
            onFinish: () => setIsRegisteringCmds(false),
            onSuccess: () => toast.success('Registered Telegram slash commands (/it, /hr, /topics) successfully!'),
            onError: () => toast.error('Failed to register Telegram slash commands.'),
        });
    };

    const handleGenerateInviteLink = (u: RosterUser) => {
        const groupKey = u.region || 'Region 1';
        router.post(route('kaldis-communication.generate-invite-link'), {
            telegram_user_id: u.telegram_user_id,
            group_key: groupKey,
        }, {
            onSuccess: () => toast.success(`Generated and sent group join link for ${u.display_name}!`),
            onError: (errors: any) => toast.error(errors.invite || 'Failed to generate invite link.'),
        });
    };

    const [isSyncingMembers, setIsSyncingMembers] = useState(false);

    const handleSyncMembers = () => {
        setIsSyncingMembers(true);
        router.post(route('kaldis-communication.sync-members'), {}, {
            onFinish: () => setIsSyncingMembers(false),
            onSuccess: () => toast.success('Telegram group members synced to Staff Roster successfully! You can now edit member details.'),
            onError: (errors: any) => toast.error(errors.sync || 'Failed to sync members from Telegram.'),
        });
    };

    const [isStandardizingNames, setIsStandardizingNames] = useState(false);

    const handleStandardizeNames = () => {
        setIsStandardizingNames(true);
        router.post(route('kaldis-communication.standardize-names'), {}, {
            onFinish: () => setIsStandardizingNames(false),
            onSuccess: () => toast.success("Standardized roster member names to Title Case 'First Name Last Name'!"),
            onError: () => toast.error('Failed to standardize member names.'),
        });
    };

    const [editingUser, setEditingUser] = useState<RosterUser | null>(null);
    const editUserForm = useForm<{
        telegram_user_id: number;
        display_name: string;
        role: string;
        region: string;
        branch_name: string;
        department: string;
    }>({
        telegram_user_id: 0,
        display_name: '',
        role: 'branch_manager',
        region: 'Region 1',
        branch_name: '',
        department: '',
    });

    const openEditUser = (u: RosterUser) => {
        setEditingUser(u);
        editUserForm.setData({
            telegram_user_id: u.telegram_user_id,
            display_name: u.display_name,
            role: u.role,
            region: u.region || 'Region 1',
            branch_name: u.branch_name || '',
            department: u.department || '',
        });
    };

    const handleUpdateUser = (e: React.FormEvent) => {
        e.preventDefault();
        editUserForm.put(route('kaldis-communication.update-user'), {
            onSuccess: () => {
                toast.success('Staff member profile updated successfully!');
                setEditingUser(null);
            },
            onError: () => toast.error('Failed to update member profile.'),
        });
    };

    const [isFetchingMember, setIsFetchingMember] = useState(false);

    const handleFetchMember = () => {
        if (!userForm.data.telegram_user_id) {
            toast.error('Please enter a Telegram User ID first.');
            return;
        }
        setIsFetchingMember(true);
        router.post(route('kaldis-communication.fetch-member'), {
            telegram_user_id: userForm.data.telegram_user_id,
        }, {
            onFinish: () => setIsFetchingMember(false),
            onSuccess: () => {
                toast.success('Fetched member info from Telegram successfully!');
                setIsAddUserOpen(false);
            },
            onError: (errors: any) => toast.error(errors.fetch || 'Could not find user in Telegram groups.'),
        });
    };

    // Moderation forms
    const moderationForm = useForm<{
        anti_link_protection: boolean;
        auto_welcome: boolean;
        welcome_message: string;
    }>({
        anti_link_protection: config.anti_link_protection ?? false,
        auto_welcome: config.auto_welcome ?? false,
        welcome_message: config.welcome_message || 'Welcome {name} to {group}! Please follow group rules and post under relevant topics.',
    });

    const handleSaveModeration = (e: React.FormEvent) => {
        e.preventDefault();
        moderationForm.post(route('kaldis-communication.moderation.settings'), {
            onSuccess: () => toast.success('Group moderation & anti-link settings updated!'),
            onError: () => toast.error('Failed to update moderation settings.'),
        });
    };

    const memberActionForm = useForm({
        action: 'ban',
        chat_id: String(config.region_groups?.['Region 1'] || ''),
        telegram_user_id: '',
    });

    const handleMemberAction = (e: React.FormEvent) => {
        e.preventDefault();
        memberActionForm.post(route('kaldis-communication.moderation.member'), {
            onSuccess: () => {
                toast.success(`Member action (${memberActionForm.data.action}) executed successfully!`);
                memberActionForm.reset('telegram_user_id');
            },
            onError: (errors: any) => toast.error(errors.moderation || 'Failed to execute member action.'),
        });
    };

    const broadcastForm = useForm<{
        chat_id: string;
        message: string;
        pin: boolean;
    }>({
        chat_id: String(config.region_groups?.['Region 1'] || ''),
        message: '',
        pin: true,
    });

    const handleBroadcast = (e: React.FormEvent) => {
        e.preventDefault();
        broadcastForm.post(route('kaldis-communication.moderation.broadcast'), {
            onSuccess: () => {
                toast.success('Broadcast message posted successfully!');
                broadcastForm.reset('message');
            },
            onError: (errors: any) => toast.error(errors.broadcast || 'Failed to post broadcast message.'),
        });
    };

    const handleAddUser = (e: React.FormEvent) => {
        e.preventDefault();
        userForm.post(route('kaldis-communication.store-user'), {
            onSuccess: () => {
                toast.success('Roster user saved successfully!');
                setIsAddUserOpen(false);
                userForm.reset();
            },
            onError: () => toast.error('Failed to save user.'),
        });
    };

    const handleDeleteUser = (telegramUserId: number, displayName: string) => {
        if (confirm(`Are you sure you want to remove "${displayName}" from the roster?`)) {
            router.delete(route('kaldis-communication.delete-user', telegramUserId), {
                onSuccess: () => toast.success('User removed from roster.'),
            });
        }
    };

    const handleAddBinding = (e: React.FormEvent) => {
        e.preventDefault();
        bindingForm.post(route('kaldis-communication.store-binding'), {
            onSuccess: () => {
                toast.success('Topic binding saved!');
                setIsAddBindingOpen(false);
                bindingForm.reset();
            },
            onError: () => toast.error('Failed to save topic binding.'),
        });
    };

    const handleDeleteBinding = (groupKey: string, threadId: number, deleteFromTelegram: boolean = false) => {
        const confirmMsg = deleteFromTelegram
            ? `⚠️ PERMANENT DELETE WARNING:\nAre you sure you want to delete topic (Thread ID: ${threadId}) directly from the ${groupKey} Telegram group? This will permanently delete the topic and all messages inside it in Telegram.`
            : `Remove topic binding for Thread ID ${threadId} in ${groupKey}?`;

        if (confirm(confirmMsg)) {
            router.delete(route('kaldis-communication.delete-binding'), {
                data: {
                    group_key: groupKey,
                    thread_id: threadId,
                    delete_from_telegram: deleteFromTelegram,
                },
                onSuccess: () => toast.success(deleteFromTelegram ? 'Topic deleted from Telegram group & system database!' : 'Binding removed.'),
                onError: (errors: any) => toast.error(errors.delete || 'Failed to remove topic binding.'),
            });
        }
    };

    const handleFilterChange = (newSearch?: string, newRegion?: string, newStatus?: string) => {
        const queryParams: Record<string, string> = {};
        const s = newSearch !== undefined ? newSearch : search;
        const r = newRegion !== undefined ? newRegion : regionFilter;
        const st = newStatus !== undefined ? newStatus : statusFilter;

        if (s) queryParams.search = s;
        if (r && r !== 'all') queryParams.region = r;
        if (st && st !== 'all') queryParams.status = st;

        router.get(route('kaldis-communication.index'), queryParams, { preserveState: true, replace: true });
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'recorded':
                return <Badge variant="secondary" className="bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">⏳ Unanswered</Badge>;
            case 'responded':
            case 'closed':
            case 'resolved':
                return <Badge variant="secondary" className="bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">✅ Answered</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    const getRoleBadge = (role: string) => {
        switch (role) {
            case 'branch_manager':
                return <Badge variant="outline" className="border-blue-500 text-blue-600">Branch Manager</Badge>;
            case 'regional_manager':
                return <Badge variant="outline" className="border-purple-500 text-purple-600">Regional Manager</Badge>;
            case 'department_head':
                return <Badge variant="outline" className="border-emerald-500 text-emerald-600">Department Head</Badge>;
            case 'operations_director':
                return <Badge variant="outline" className="border-rose-500 text-rose-600 font-bold">Ops Director</Badge>;
            default:
                return <Badge variant="outline">{role}</Badge>;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kaldis Branch Communication Platform" />

            <div className="flex flex-col gap-6 w-full">
                {/* Header Banner */}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-border/60 pb-4">
                    <div className="flex items-center gap-3">
                        <div className="h-10 w-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-700 dark:text-amber-400 shrink-0">
                            <MessageSquare className="h-5 w-5" />
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl font-bold tracking-tight text-foreground">
                                    Kaldis Communication Platform
                                </h1>
                                <Badge variant="outline" className="text-[10px] font-semibold border-amber-500/30 text-amber-700 dark:text-amber-400 bg-amber-500/5">
                                    Regional Telegram Sync
                                </Badge>
                            </div>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                Unified regional Telegram group synchronization, auto-tagging, message tracking, and topic routing
                            </p>
                        </div>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.reload()}
                            className="gap-1.5 h-8 text-xs shadow-xs"
                        >
                            <RefreshCw className="h-3.5 w-3.5" />
                            Refresh
                        </Button>
                        {canManage && (
                            <>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => handleSyncTopics(false)}
                                    disabled={isSyncingTopics}
                                    className="gap-1.5 h-8 text-xs shadow-xs"
                                >
                                    <Zap className="h-3.5 w-3.5 text-amber-600" />
                                    {isSyncingTopics ? 'Syncing...' : 'Sync Topics'}
                                </Button>
                                <Button
                                    size="sm"
                                    onClick={() => setIsAddUserOpen(true)}
                                    className="gap-1.5 h-8 text-xs bg-primary text-primary-foreground hover:bg-primary/90 shadow-xs"
                                >
                                    <UserPlus className="h-3.5 w-3.5" />
                                    Add Member
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                {/* Key Stat Metrics */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    <Card className="border shadow-xs hover:border-amber-500/40 transition-colors">
                        <CardHeader className="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
                            <CardDescription className="text-xs font-medium text-muted-foreground">Total Communications</CardDescription>
                            <div className="h-7 w-7 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-700 dark:text-amber-400 shrink-0">
                                <MessageSquare className="h-3.5 w-3.5" />
                            </div>
                        </CardHeader>
                        <CardContent className="p-4 pt-1">
                            <div className="text-2xl font-bold tracking-tight">{stats.total_communications}</div>
                            <span className="text-[11px] text-muted-foreground">Recorded messages</span>
                        </CardContent>
                    </Card>

                    <Card className="border shadow-xs hover:border-emerald-500/40 transition-colors">
                        <CardHeader className="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
                            <CardDescription className="text-xs font-medium text-muted-foreground">Answered Chats</CardDescription>
                            <div className="h-7 w-7 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                                <CheckCircle2 className="h-3.5 w-3.5" />
                            </div>
                        </CardHeader>
                        <CardContent className="p-4 pt-1">
                            <div className="text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">
                                {stats.answered_communications ?? stats.responded_communications}
                            </div>
                            <span className="text-[11px] text-muted-foreground">Responded & closed</span>
                        </CardContent>
                    </Card>

                    <Card className="border shadow-xs hover:border-amber-500/40 transition-colors">
                        <CardHeader className="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
                            <CardDescription className="text-xs font-medium text-muted-foreground">Awaiting Response</CardDescription>
                            <div className="h-7 w-7 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                                <Clock className="h-3.5 w-3.5" />
                            </div>
                        </CardHeader>
                        <CardContent className="p-4 pt-1">
                            <div className="text-2xl font-bold tracking-tight text-amber-600 dark:text-amber-400">
                                {stats.unanswered_communications ?? stats.recorded_communications}
                            </div>
                            <span className="text-[11px] text-muted-foreground">Pending reply</span>
                        </CardContent>
                    </Card>

                    <Card className="border shadow-xs hover:border-blue-500/40 transition-colors">
                        <CardHeader className="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
                            <CardDescription className="text-xs font-medium text-muted-foreground">Roster Staff</CardDescription>
                            <div className="h-7 w-7 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                                <Users className="h-3.5 w-3.5" />
                            </div>
                        </CardHeader>
                        <CardContent className="p-4 pt-1">
                            <div className="text-2xl font-bold tracking-tight">{stats.total_users}</div>
                            <span className="text-[11px] text-muted-foreground">Registered users</span>
                        </CardContent>
                    </Card>

                    <Card className="border shadow-xs hover:border-indigo-500/40 transition-colors col-span-2 sm:col-span-1">
                        <CardHeader className="p-4 pb-2 flex flex-row items-center justify-between space-y-0">
                            <CardDescription className="text-xs font-medium text-muted-foreground">Topic Bindings</CardDescription>
                            <div className="h-7 w-7 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                                <Layers className="h-3.5 w-3.5" />
                            </div>
                        </CardHeader>
                        <CardContent className="p-4 pt-1">
                            <div className="text-2xl font-bold tracking-tight">{stats.total_bindings}</div>
                            <span className="text-[11px] text-muted-foreground">Active thread links</span>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Tabs Container */}
                <Tabs defaultValue="communications" className="space-y-4">
                    <div className="w-full overflow-x-auto pb-1">
                        <TabsList className="bg-muted/60 p-1 rounded-xl h-auto inline-flex flex-wrap gap-1 border border-border/50">
                            <TabsTrigger value="communications" className="gap-2 px-3.5 py-2 text-xs font-medium rounded-lg data-[state=active]:bg-background data-[state=active]:shadow-xs transition-all">
                                <MessageSquare className="h-3.5 w-3.5 text-amber-600" />
                                Communications Tracker
                            </TabsTrigger>
                            <TabsTrigger value="topics" className="gap-2 px-3.5 py-2 text-xs font-medium rounded-lg data-[state=active]:bg-background data-[state=active]:shadow-xs transition-all">
                                <Layers className="h-3.5 w-3.5 text-indigo-600" />
                                Topic Mappings
                            </TabsTrigger>
                            <TabsTrigger value="roster" className="gap-2 px-3.5 py-2 text-xs font-medium rounded-lg data-[state=active]:bg-background data-[state=active]:shadow-xs transition-all">
                                <Users className="h-3.5 w-3.5 text-blue-600" />
                                Staff Roster
                            </TabsTrigger>
                            <TabsTrigger value="config" className="gap-2 px-3.5 py-2 text-xs font-medium rounded-lg data-[state=active]:bg-background data-[state=active]:shadow-xs transition-all">
                                <Bot className="h-3.5 w-3.5 text-purple-600" />
                                Bot Configuration
                            </TabsTrigger>
                            <TabsTrigger value="moderation" className="gap-2 px-3.5 py-2 text-xs font-medium rounded-lg data-[state=active]:bg-background data-[state=active]:shadow-xs transition-all">
                                <ShieldAlert className="h-3.5 w-3.5 text-rose-600" />
                                Group Moderation
                            </TabsTrigger>
                        </TabsList>
                    </div>

                    {/* Tab 1: Communications Tracker */}
                    <TabsContent value="communications" className="space-y-4">
                        <Card className="border shadow-xs overflow-hidden">
                            <CardHeader className="p-4 pb-3">
                                <div>
                                    <CardTitle className="text-base font-semibold flex items-center gap-2">
                                        <Activity className="h-4 w-4 text-amber-600" />
                                        Live Communication Log & Tracking
                                    </CardTitle>
                                    <CardDescription className="text-xs text-muted-foreground mt-0.5">
                                        Every message posted in Region 1 & Region 2 groups is tagged with a reference number, categorized, and tracked in real-time.
                                    </CardDescription>
                                </div>
                            </CardHeader>
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 p-3.5 bg-muted/20 border-y border-border/60">
                                <div className="relative">
                                    <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Search ref, branch, topic..."
                                        value={search}
                                        onChange={(e) => {
                                            setSearch(e.target.value);
                                            applyFilters(e.target.value, undefined, undefined, undefined);
                                        }}
                                        className="pl-9 h-9 text-xs bg-background"
                                    />
                                </div>
                                <Select
                                    value={regionFilter}
                                    onValueChange={(val) => {
                                        setRegionFilter(val);
                                        applyFilters(undefined, val, undefined, undefined);
                                    }}
                                >
                                    <SelectTrigger className="h-9 text-xs bg-background">
                                        <SelectValue placeholder="Region" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Regions</SelectItem>
                                        <SelectItem value="Region 1">Region 1</SelectItem>
                                        <SelectItem value="Region 2">Region 2</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select
                                    value={statusFilter}
                                    onValueChange={(val) => {
                                        setStatusFilter(val);
                                        applyFilters(undefined, undefined, val, undefined);
                                    }}
                                >
                                    <SelectTrigger className="h-9 text-xs bg-background">
                                        <SelectValue placeholder="Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Statuses</SelectItem>
                                        <SelectItem value="unanswered">⏳ Unanswered / Open</SelectItem>
                                        <SelectItem value="answered">✅ Responded / Answered</SelectItem>
                                        <SelectItem value="recorded">Recorded Only</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select
                                    value={topicFilter}
                                    onValueChange={(val) => {
                                        setTopicFilter(val);
                                        applyFilters(undefined, undefined, undefined, val);
                                    }}
                                >
                                    <SelectTrigger className="h-9 text-xs bg-background">
                                        <SelectValue placeholder="All Topics" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Department Topics</SelectItem>
                                        {getPresets(defaultTopicMapping).map((preset) => (
                                            <SelectItem key={preset.name} value={preset.name}>
                                                {preset.emoji} {preset.name} ({preset.department})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <CardContent className="p-0">
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead className="text-xs">Ref Number</TableHead>
                                                <TableHead className="text-xs">Region & Branch</TableHead>
                                                <TableHead className="text-xs">Topic & Department</TableHead>
                                                <TableHead className="text-xs">Sender</TableHead>
                                                <TableHead className="text-xs">Status</TableHead>
                                                <TableHead className="text-xs text-right">Timestamp</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {communications.length === 0 ? (
                                                <TableRow>
                                                    <TableCell colSpan={6} className="h-24 text-center text-neutral-500">
                                                        No communication logs found matching your filters.
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                communications.map((comm) => (
                                                    <TableRow key={comm.reference_no} className="hover:bg-neutral-50 dark:hover:bg-neutral-900/50">
                                                        <TableCell className="font-mono text-xs font-bold text-amber-700 dark:text-amber-400">
                                                            {comm.reference_no}
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex flex-col">
                                                                <span className="font-medium text-sm">{comm.branch_name || 'Branch N/A'}</span>
                                                                <span className="text-xs text-neutral-500">{comm.region}</span>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>
                                                            <div className="flex flex-col">
                                                                <span className="font-medium text-sm">{comm.topic_name}</span>
                                                                <span className="text-xs text-indigo-600 dark:text-indigo-400 font-medium">➡️ {comm.department}</span>
                                                            </div>
                                                        </TableCell>
                                                        <TableCell className="text-sm font-medium">
                                                            {comm.sender_display_name}
                                                        </TableCell>
                                                        <TableCell>
                                                            {getStatusBadge(comm.status)}
                                                        </TableCell>
                                                        <TableCell className="text-xs text-neutral-500">
                                                            {new Date(comm.created_at).toLocaleString()}
                                                        </TableCell>
                                                    </TableRow>
                                                ))
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Tab 2: Topic Mappings & Sync */}
                    <TabsContent value="topics" className="space-y-4">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div className="inline-flex rounded-lg bg-muted/50 p-1 text-muted-foreground">
                                <button
                                    type="button"
                                    onClick={() => setTopicSubTab('region1')}
                                    className={`px-3 py-1 rounded-md text-xs font-medium transition-colors ${
                                        topicSubTab === 'region1' ? 'bg-background text-foreground shadow-xs' : 'hover:text-foreground'
                                    }`}
                                >
                                    Region 1 Group
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setTopicSubTab('region2')}
                                    className={`px-3 py-1 rounded-md text-xs font-medium transition-colors ${
                                        topicSubTab === 'region2' ? 'bg-background text-foreground shadow-xs' : 'hover:text-foreground'
                                    }`}
                                >
                                    Region 2 Group
                                </button>
                            </div>

                            {canManage && selectedBindings.length > 0 && (
                                <div className="flex items-center gap-2 bg-muted/60 p-1.5 px-3 rounded-lg border">
                                    <span className="text-xs font-medium text-foreground">
                                        {selectedBindings.length} Selected
                                    </span>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() => handleBulkDelete(false)}
                                        className="h-7 text-xs"
                                    >
                                        Unbind System
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => handleBulkDelete(true)}
                                        className="h-7 text-xs"
                                    >
                                        Delete Both
                                    </Button>
                                </div>
                            )}
                        </div>

                        {/* Render Sub-Tab content based on topicSubTab */}
                        {['region1', 'region2'].map((groupTab) => {
                            if (topicSubTab !== groupTab) return null;

                            const groupKeyName = groupTab === 'region1' ? 'Region 1' : 'Region 2';
                            const filteredBindings = topicBindings.filter((b) => {
                                if (groupTab === 'region1') return b.group_key === 'Region 1';
                                return b.group_key === 'Region 2';
                            });

                            return (
                                <div key={groupTab} className="grid gap-5 md:grid-cols-3">
                                    <Card className="md:col-span-2 border shadow-none">
                                        <CardHeader className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b">
                                            <div>
                                                <CardTitle className="text-sm font-medium">
                                                    {groupKeyName} Topic Thread Mappings
                                                </CardTitle>
                                                <CardDescription className="text-xs">
                                                    Active Telegram topic threads and department routes for {groupKeyName}
                                                </CardDescription>
                                            </div>
                                            {canManage && (
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => handleSyncTopics(false, groupKeyName)}
                                                        disabled={isSyncingTopics}
                                                        className="h-8 text-xs gap-1.5"
                                                    >
                                                        <Zap className="h-3.5 w-3.5" />
                                                        {isSyncingTopics ? 'Syncing...' : `Sync ${groupKeyName}`}
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => handleSyncTopics(true, groupKeyName)}
                                                        disabled={isSyncingTopics}
                                                        className="h-8 text-xs gap-1.5"
                                                        title="Force re-creation with custom Emojis and post welcome message"
                                                    >
                                                        <RefreshCw className="h-3.5 w-3.5" />
                                                        Force Sync
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => handlePurgeUnlisted(true)}
                                                        className="h-8 text-xs gap-1.5 text-muted-foreground hover:text-destructive"
                                                        title="Remove all unlisted topics from Telegram groups & system database"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                        Purge
                                                    </Button>
                                                    <Button size="sm" onClick={() => setIsAddBindingOpen(true)} className="h-8 text-xs gap-1.5">
                                                        <Plus className="h-3.5 w-3.5" />
                                                        Add Binding
                                                    </Button>
                                                </div>
                                            )}
                                        </CardHeader>
                                        <CardContent>
                                            <div className="rounded-md border">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            {canManage && (
                                                                <TableHead className="w-10 text-center">
                                                                    <input
                                                                        type="checkbox"
                                                                        checked={filteredBindings.length > 0 && filteredBindings.every(b => selectedBindings.includes(`${b.group_key}:${b.thread_id}`))}
                                                                        onChange={() => toggleSelectAllGroup(filteredBindings)}
                                                                        className="h-4 w-4 rounded border-input"
                                                                    />
                                                                </TableHead>
                                                            )}
                                                            <TableHead>Group</TableHead>
                                                            <TableHead>Thread ID</TableHead>
                                                            <TableHead>Topic Name & Emoji</TableHead>
                                                            <TableHead>Target HO Dept</TableHead>
                                                            {canManage && <TableHead className="w-28 text-right">Actions</TableHead>}
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {filteredBindings.length === 0 ? (
                                                            <TableRow>
                                                                <TableCell colSpan={canManage ? 6 : 4} className="h-24 text-center text-muted-foreground">
                                                                    No topic thread bindings created for {groupKeyName} yet. Click <b>Sync {groupKeyName}</b> or <b>Force Sync</b> above.
                                                                </TableCell>
                                                            </TableRow>
                                                        ) : (
                                                            filteredBindings.map((b) => {
                                                                const bindingKey = `${b.group_key}:${b.thread_id}`;
                                                                const isSelected = selectedBindings.includes(bindingKey);
                                                                const emoji = getTopicEmoji(b.topic_name, defaultTopicMapping);
                                                                return (
                                                                    <TableRow key={bindingKey} className={isSelected ? 'bg-muted/50' : ''}>
                                                                        {canManage && (
                                                                            <TableCell className="text-center">
                                                                                <input
                                                                                    type="checkbox"
                                                                                    checked={isSelected}
                                                                                    onChange={() => toggleSelectBinding(bindingKey)}
                                                                                    className="h-4 w-4 rounded border-input"
                                                                                />
                                                                            </TableCell>
                                                                        )}
                                                                        <TableCell className="font-medium text-sm">{b.group_key}</TableCell>
                                                                        <TableCell className="font-mono text-xs">{b.thread_id}</TableCell>
                                                                        <TableCell className="font-medium flex items-center gap-2">
                                                                            <span>{emoji}</span>
                                                                            <span>{b.topic_name}</span>
                                                                        </TableCell>
                                                                        <TableCell>
                                                                            <Badge variant="outline">
                                                                                {b.department}
                                                                            </Badge>
                                                                        </TableCell>
                                                                        {canManage && (
                                                                            <TableCell className="text-right">
                                                                                <div className="flex items-center justify-end gap-1">
                                                                                    <Button
                                                                                        variant="ghost"
                                                                                        size="icon"
                                                                                        title="Edit topic binding"
                                                                                        onClick={() => openEditBinding(b)}
                                                                                        className="h-8 w-8 text-muted-foreground hover:text-foreground"
                                                                                    >
                                                                                        <Edit3 className="h-4 w-4" />
                                                                                    </Button>
                                                                                    <Button
                                                                                        variant="ghost"
                                                                                        size="icon"
                                                                                        title="Unbind from system only"
                                                                                        onClick={() => handleDeleteBinding(b.group_key, b.thread_id, false)}
                                                                                        className="h-8 w-8 text-muted-foreground hover:text-foreground"
                                                                                    >
                                                                                        <Trash2 className="h-4 w-4" />
                                                                                    </Button>
                                                                                    <Button
                                                                                        variant="ghost"
                                                                                        size="icon"
                                                                                        title="Delete topic from Telegram Group & System"
                                                                                        onClick={() => handleDeleteBinding(b.group_key, b.thread_id, true)}
                                                                                        className="h-8 w-8 text-muted-foreground hover:text-destructive"
                                                                                    >
                                                                                        <XCircle className="h-4 w-4" />
                                                                                    </Button>
                                                                                </div>
                                                                            </TableCell>
                                                                        )}
                                                                    </TableRow>
                                                                );
                                                            })
                                                        )}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        </CardContent>
                                    </Card>

                                    <Card className="border shadow-none">
                                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3 border-b">
                                            <div>
                                                <CardTitle className="text-sm font-medium flex items-center gap-2">
                                                    <Sparkles className="h-4 w-4 text-muted-foreground" />
                                                    Standard Topics Scope
                                                </CardTitle>
                                                <CardDescription className="text-xs">
                                                    Proposal topics with emojis & target HO departments.
                                                </CardDescription>
                                            </div>
                                            {canManage && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    className="h-8 text-xs gap-1.5"
                                                    onClick={() => setIsAddStandardTopicOpen(true)}
                                                >
                                                    <Plus className="h-3.5 w-3.5" />
                                                    Add Topic
                                                </Button>
                                            )}
                                        </CardHeader>
                                        <CardContent className="space-y-3 text-sm pt-3">
                                            <div className="rounded-md border p-2 space-y-1 max-h-[350px] overflow-y-auto">
                                                {getPresets(defaultTopicMapping).map((preset) => (
                                                    <div key={preset.name} className="flex items-center justify-between text-xs py-1.5 px-2 rounded hover:bg-muted/40 transition-colors">
                                                        <span className="font-medium flex items-center gap-2">
                                                            <span className="text-sm">{preset.emoji}</span>
                                                            <span>{preset.name}</span>
                                                        </span>
                                                        <div className="flex items-center gap-2">
                                                            <span className="text-muted-foreground text-[11px]">{preset.department}</span>
                                                            {canManage && (
                                                                <div className="flex items-center gap-0.5">
                                                                    <Button
                                                                        size="icon"
                                                                        variant="ghost"
                                                                        className="h-6 w-6 text-muted-foreground hover:text-foreground"
                                                                        title={`Edit preset ${preset.name}`}
                                                                        onClick={() => openEditStandardTopic(preset)}
                                                                    >
                                                                        <Edit3 className="h-3 w-3" />
                                                                    </Button>
                                                                    <Button
                                                                        size="icon"
                                                                        variant="ghost"
                                                                        className="h-6 w-6 text-muted-foreground hover:text-destructive"
                                                                        title={`Remove preset ${preset.name}`}
                                                                        onClick={() => handleDeleteStandardTopic(preset.name)}
                                                                    >
                                                                        <Trash2 className="h-3 w-3" />
                                                                    </Button>
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>
                            );
                        })}
                    </TabsContent>

                    {/* Tab 5: Group Moderation */}
                    <TabsContent value="moderation" className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-3">
                            {/* Card 1: Anti-Spam & Auto Welcome */}
                            <Card className="md:col-span-1 border shadow-none">
                                <CardHeader className="pb-3 border-b">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <ShieldAlert className="h-4 w-4" />
                                        Security & Protection
                                    </CardTitle>
                                    <CardDescription className="text-xs">
                                        Anti-spam link auto-deletion and welcome messages.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="pt-4">
                                    <form onSubmit={handleSaveModeration} className="space-y-4">
                                        <div className="flex items-center justify-between p-3 border rounded-lg bg-muted/20">
                                            <div className="space-y-0.5">
                                                <Label htmlFor="anti_link_protection" className="font-medium text-xs flex items-center gap-1.5">
                                                    <Link2Off className="h-3.5 w-3.5" />
                                                    Anti-Link Protection
                                                </Label>
                                                <p className="text-[11px] text-muted-foreground">Auto-delete unverified links from non-admins.</p>
                                            </div>
                                            <input
                                                type="checkbox"
                                                id="anti_link_protection"
                                                checked={moderationForm.data.anti_link_protection}
                                                onChange={(e) => moderationForm.setData('anti_link_protection', e.target.checked)}
                                                className="h-4 w-4 rounded border-input"
                                            />
                                        </div>

                                        <div className="flex items-center justify-between p-3 border rounded-lg bg-muted/20">
                                            <div className="space-y-0.5">
                                                <Label htmlFor="auto_welcome" className="font-medium text-xs flex items-center gap-1.5">
                                                    <Volume2 className="h-3.5 w-3.5" />
                                                    Auto Welcome Message
                                                </Label>
                                                <p className="text-[11px] text-muted-foreground">Greet new members when joining group.</p>
                                            </div>
                                            <input
                                                type="checkbox"
                                                id="auto_welcome"
                                                checked={moderationForm.data.auto_welcome}
                                                onChange={(e) => moderationForm.setData('auto_welcome', e.target.checked)}
                                                className="h-4 w-4 rounded border-input"
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="welcome_message" className="text-xs">Welcome Message Template</Label>
                                            <textarea
                                                id="welcome_message"
                                                rows={3}
                                                value={moderationForm.data.welcome_message}
                                                onChange={(e) => moderationForm.setData('welcome_message', e.target.value)}
                                                className="w-full text-xs p-2.5 rounded-md border bg-background font-mono"
                                                placeholder="Welcome {name} to {group}!"
                                            />
                                            <p className="text-[10px] text-muted-foreground">Placeholders: <code>{'{name}'}</code>, <code>{'{group}'}</code></p>
                                        </div>

                                        <Button type="submit" size="sm" className="w-full h-8 text-xs" disabled={moderationForm.processing}>
                                            Save Protection Settings
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>

                            {/* Card 2: Member Management Console (Ban/Unban/Kick) */}
                            <Card className="md:col-span-1 border shadow-none">
                                <CardHeader className="pb-3 border-b">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <UserX className="h-4 w-4" />
                                        Member Action Console
                                    </CardTitle>
                                    <CardDescription className="text-xs">
                                        Ban, unban, or kick members across connected groups.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="pt-4">
                                    <form onSubmit={handleMemberAction} className="space-y-3">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="mod_chat_id" className="text-xs">Target Group</Label>
                                            <Select
                                                value={memberActionForm.data.chat_id}
                                                onValueChange={(val) => memberActionForm.setData('chat_id', val)}
                                            >
                                                <SelectTrigger className="h-8 text-xs">
                                                    <SelectValue placeholder="Select Group" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {config.region_groups?.['Region 1'] && (
                                                        <SelectItem value={String(config.region_groups['Region 1'])}>
                                                            Region 1 Group
                                                        </SelectItem>
                                                    )}
                                                    {config.region_groups?.['Region 2'] && (
                                                        <SelectItem value={String(config.region_groups['Region 2'])}>
                                                            Region 2 Group
                                                        </SelectItem>
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="mod_user_id" className="text-xs">Telegram User ID</Label>
                                            <Input
                                                id="mod_user_id"
                                                placeholder="e.g. 123456789"
                                                value={memberActionForm.data.telegram_user_id}
                                                onChange={(e) => memberActionForm.setData('telegram_user_id', e.target.value)}
                                                className="h-8 text-xs"
                                                required
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="mod_action" className="text-xs">Action</Label>
                                            <Select
                                                value={memberActionForm.data.action}
                                                onValueChange={(val) => memberActionForm.setData('action', val)}
                                            >
                                                <SelectTrigger className="h-8 text-xs">
                                                    <SelectValue placeholder="Select Action" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="ban">Ban Member</SelectItem>
                                                    <SelectItem value="kick">Kick Member</SelectItem>
                                                    <SelectItem value="unban">Unban Member</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <Button
                                            type="submit"
                                            size="sm"
                                            variant={memberActionForm.data.action === 'ban' ? 'destructive' : 'default'}
                                            className="w-full h-8 text-xs gap-2"
                                            disabled={memberActionForm.processing}
                                        >
                                            Execute {memberActionForm.data.action.toUpperCase()}
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>

                            {/* Card 3: Channel Broadcast & Pin */}
                            <Card className="md:col-span-1 border shadow-none">
                                <CardHeader className="pb-3 border-b">
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <Megaphone className="h-4 w-4" />
                                        Channel Broadcast
                                    </CardTitle>
                                    <CardDescription className="text-xs">
                                        Broadcast announcement to target group or channel.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="pt-4">
                                    <form onSubmit={handleBroadcast} className="space-y-3">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="bc_chat_id" className="text-xs">Target Group</Label>
                                            <Select
                                                value={broadcastForm.data.chat_id}
                                                onValueChange={(val) => broadcastForm.setData('chat_id', val)}
                                            >
                                                <SelectTrigger className="h-8 text-xs">
                                                    <SelectValue placeholder="Select Target" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {config.region_groups?.['Region 1'] && (
                                                        <SelectItem value={String(config.region_groups['Region 1'])}>
                                                            Region 1 Group
                                                        </SelectItem>
                                                    )}
                                                    {config.region_groups?.['Region 2'] && (
                                                        <SelectItem value={String(config.region_groups['Region 2'])}>
                                                            Region 2 Group
                                                        </SelectItem>
                                                    )}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="bc_message" className="text-xs">Broadcast Message (HTML)</Label>
                                            <textarea
                                                id="bc_message"
                                                rows={3}
                                                value={broadcastForm.data.message}
                                                onChange={(e) => broadcastForm.setData('message', e.target.value)}
                                                className="w-full text-xs p-2.5 rounded-md border bg-background font-mono"
                                                placeholder="<b>ANNOUNCEMENT:</b> Message..."
                                                required
                                            />
                                        </div>

                                        <div className="flex items-center space-x-2">
                                            <input
                                                type="checkbox"
                                                id="bc_pin"
                                                checked={broadcastForm.data.pin}
                                                onChange={(e) => broadcastForm.setData('pin', e.target.checked)}
                                                className="h-4 w-4 rounded border-input"
                                            />
                                            <Label htmlFor="bc_pin" className="text-xs text-muted-foreground">
                                                Pin announcement at top of group
                                            </Label>
                                        </div>

                                        <Button type="submit" size="sm" className="w-full h-8 text-xs gap-1.5" disabled={broadcastForm.processing}>
                                            <Send className="h-3.5 w-3.5" />
                                            Post Broadcast
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>
                        </div>
                    </TabsContent>

                    {/* Tab 3: Staff Roster */}
                    <TabsContent value="roster" className="space-y-4">
                        <Card className="border shadow-none">
                            <CardHeader className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b">
                                <div>
                                    <CardTitle className="text-sm font-medium flex items-center gap-2">
                                        <Users className="h-4 w-4" />
                                        Staff Roster
                                    </CardTitle>
                                    <CardDescription className="text-xs">
                                        Branch Managers, Regional Managers, and Department Heads.
                                    </CardDescription>
                                </div>
                                {canManage && (
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={handleStandardizeNames}
                                            disabled={isStandardizingNames}
                                            className="h-8 text-xs gap-1.5"
                                            title="Clean and format all member names"
                                        >
                                            <Sparkles className={`h-3.5 w-3.5 ${isStandardizingNames ? 'animate-spin' : ''}`} />
                                            {isStandardizingNames ? 'Standardizing...' : 'Standardize Names'}
                                        </Button>
                                        <Button size="sm" onClick={() => setIsAddUserOpen(true)} className="h-8 text-xs gap-1.5">
                                            <UserPlus className="h-3.5 w-3.5" />
                                            Register User
                                        </Button>
                                    </div>
                                )}
                            </CardHeader>
                            <CardContent className="pt-4">
                                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
                                    <div className="relative w-full sm:w-80">
                                        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                        <Input
                                            type="text"
                                            placeholder="Search roster by name, ID, role, branch, dept..."
                                            value={rosterSearch}
                                            onChange={(e) => setRosterSearch(e.target.value)}
                                            className="pl-9 pr-8 text-xs h-8"
                                        />
                                        {rosterSearch && (
                                            <button
                                                type="button"
                                                onClick={() => setRosterSearch('')}
                                                className="absolute right-2.5 top-2 text-muted-foreground hover:text-foreground"
                                            >
                                                <X className="h-3.5 w-3.5" />
                                            </button>
                                        )}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        Showing <span className="font-semibold text-foreground">{filteredRosterUsers.length}</span> of {rosterUsers.length} staff
                                    </div>
                                </div>

                                <div className="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Telegram User ID</TableHead>
                                                <TableHead>Display Name</TableHead>
                                                <TableHead>Role</TableHead>
                                                <TableHead>Region / Branch</TableHead>
                                                <TableHead>HO Department</TableHead>
                                                <TableHead>Topic Permission</TableHead>
                                                {canManage && <TableHead className="w-24 text-right">Action</TableHead>}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {filteredRosterUsers.length === 0 ? (
                                                <TableRow>
                                                    <TableCell colSpan={canManage ? 8 : 7} className="h-20 text-center text-muted-foreground py-6">
                                                        {rosterSearch ? (
                                                            <span>No staff members found matching "{rosterSearch}".</span>
                                                        ) : (
                                                            <span>No staff registered in the roster yet.</span>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ) : (
                                                filteredRosterUsers.map((u) => (
                                                    <TableRow key={u.telegram_user_id}>
                                                        <TableCell className="font-mono text-xs">{u.telegram_user_id}</TableCell>
                                                        <TableCell className="font-medium text-sm">
                                                            <div className="flex items-center gap-2">
                                                                <span>{u.display_name}</span>
                                                                {u.display_name && u.display_name.trim().split(/\s+/).length < 2 && (
                                                                    <Badge variant="outline" className="text-[10px]">
                                                                        Needs Last Name
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        </TableCell>
                                                        <TableCell>{getRoleBadge(u.role)}</TableCell>
                                                        <TableCell className="text-xs text-muted-foreground">
                                                            {u.region ? u.region : ''} {u.branch_name ? `• ${u.branch_name}` : ''}
                                                            {!u.region && !u.branch_name ? '—' : ''}
                                                        </TableCell>
                                                        <TableCell className="text-xs font-medium">
                                                            {u.department || '—'}
                                                        </TableCell>
                                                        <TableCell>
                                                            {u.role === 'department_head' ? (
                                                                <Badge variant="secondary" className="text-[11px]">
                                                                    {u.department || 'Unassigned'} Only
                                                                </Badge>
                                                            ) : (
                                                                <Badge variant="outline" className="text-[11px]">
                                                                    Full Access
                                                                </Badge>
                                                            )}
                                                        </TableCell>

                                                        {canManage && (
                                                            <TableCell className="text-right">
                                                                <div className="flex items-center justify-end gap-1">
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        title="Edit Member Profile"
                                                                        onClick={() => openEditUser(u)}
                                                                        className="h-8 w-8 text-muted-foreground hover:text-foreground"
                                                                    >
                                                                        <Edit3 className="h-4 w-4" />
                                                                    </Button>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        title="Generate Invite Link"
                                                                        onClick={() => handleGenerateInviteLink(u)}
                                                                        className="h-8 w-8 text-muted-foreground hover:text-foreground"
                                                                    >
                                                                        <Send className="h-4 w-4" />
                                                                    </Button>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="icon"
                                                                        title="Remove user"
                                                                        onClick={() => handleDeleteUser(u.telegram_user_id, u.display_name)}
                                                                        className="h-8 w-8 text-muted-foreground hover:text-destructive"
                                                                    >
                                                                        <Trash2 className="h-4 w-4" />
                                                                    </Button>
                                                                </div>
                                                            </TableCell>
                                                        )}
                                                    </TableRow>
                                                ))
                                            )}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {/* Tab 4: Bot Configuration */}
                    <TabsContent value="config" className="space-y-4">
                        <Card className="border shadow-none">
                            <CardHeader className="pb-3 border-b">
                                <CardTitle className="text-sm font-medium flex items-center gap-2">
                                    <Bot className="h-4 w-4" />
                                    Telegram Groups & Bot API Configuration
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    Configure Telegram Bot API token, Group Chat IDs, and Operations Director Telegram User ID.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="pt-4">
                                <form onSubmit={handleSaveConfig} className="space-y-4 max-w-2xl">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="bot_token" className="text-xs">Telegram Bot Token (from @BotFather)</Label>
                                        <div className="relative">
                                            <Input
                                                id="bot_token"
                                                type={showBotToken ? 'text' : 'password'}
                                                placeholder="7123456789:ABC..."
                                                value={configForm.data.bot_token}
                                                onChange={(e) => configForm.setData('bot_token', e.target.value)}
                                                disabled={!canManage}
                                                className="pr-10 font-mono h-8 text-xs"
                                            />
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                tabIndex={-1}
                                                onClick={() => setShowBotToken(!showBotToken)}
                                                className="absolute right-0 top-0 h-full px-3 text-muted-foreground hover:text-foreground"
                                                title={showBotToken ? 'Hide Bot Token' : 'Show Bot Token'}
                                            >
                                                {showBotToken ? (
                                                    <EyeOff className="h-3.5 w-3.5" />
                                                ) : (
                                                    <Eye className="h-3.5 w-3.5" />
                                                )}
                                            </Button>
                                        </div>
                                    </div>

                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="region_1_chat_id" className="text-xs">Region 1 Group Chat ID</Label>
                                            <Input
                                                id="region_1_chat_id"
                                                placeholder="-1001234567890"
                                                value={configForm.data.region_1_chat_id}
                                                onChange={(e) => configForm.setData('region_1_chat_id', e.target.value)}
                                                disabled={!canManage}
                                                className="h-8 text-xs font-mono"
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label htmlFor="region_2_chat_id" className="text-xs">Region 2 Group Chat ID</Label>
                                            <Input
                                                id="region_2_chat_id"
                                                placeholder="-1001234567891"
                                                value={configForm.data.region_2_chat_id}
                                                onChange={(e) => configForm.setData('region_2_chat_id', e.target.value)}
                                                disabled={!canManage}
                                                className="h-8 text-xs font-mono"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-3 sm:grid-cols-2">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="operations_director_user_id" className="text-xs">Operations Director User ID</Label>
                                            <Input
                                                id="operations_director_user_id"
                                                placeholder="987654321"
                                                value={configForm.data.operations_director_user_id}
                                                onChange={(e) => configForm.setData('operations_director_user_id', e.target.value)}
                                                disabled={!canManage}
                                                className="h-8 text-xs font-mono"
                                            />
                                        </div>
                                    </div>

                                    {canManage && (
                                        <div className="pt-3 border-t space-y-3">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Button type="submit" size="sm" className="h-8 text-xs gap-1.5" disabled={configForm.processing}>
                                                    <Save className="h-3.5 w-3.5" />
                                                    Save Settings
                                                </Button>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={handleRegisterCommands}
                                                    disabled={isRegisteringCmds}
                                                    className="h-8 text-xs gap-1.5"
                                                    title="Register slash commands (/it, /hr, /topics) with Telegram"
                                                >
                                                    <Zap className="h-3.5 w-3.5" />
                                                    {isRegisteringCmds ? 'Registering...' : 'Register Commands'}
                                                </Button>
                                            </div>

                                            <div className="pt-2 space-y-2 bg-muted/20 p-3 rounded-lg border">
                                                <Label htmlFor="webhook_url" className="text-xs font-medium flex items-center gap-1.5">
                                                    <Activity className="h-3.5 w-3.5" />
                                                    Telegram Webhook URL (HTTPS Required)
                                                </Label>
                                                <p className="text-[11px] text-muted-foreground">
                                                    Enter your production HTTPS domain or tunnel URL (e.g. <code>https://your-domain.com</code>):
                                                </p>
                                                <div className="flex gap-2">
                                                    <Input
                                                        id="webhook_url"
                                                        placeholder="https://your-domain.com"
                                                        value={customWebhookUrl}
                                                        onChange={(e) => setCustomWebhookUrl(e.target.value)}
                                                        className="text-xs font-mono h-8"
                                                    />
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        onClick={handleSetWebhook}
                                                        disabled={isSettingWebhook}
                                                        className="shrink-0 h-8 text-xs gap-1.5"
                                                    >
                                                        {isSettingWebhook ? 'Connecting...' : 'Set Webhook'}
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </form>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>

            {/* Modal: Add Roster Member */}
            <Dialog open={isAddUserOpen} onOpenChange={setIsAddUserOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Register Staff Member</DialogTitle>
                        <DialogDescription>
                            Add a Branch Manager, Regional Manager, Department Head, or Operations Director.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleAddUser} className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label htmlFor="telegram_user_id">Telegram User ID</Label>
                            <div className="flex gap-2">
                                <Input
                                    id="telegram_user_id"
                                    placeholder="123456789"
                                    value={userForm.data.telegram_user_id}
                                    onChange={(e) => userForm.setData('telegram_user_id', e.target.value)}
                                    required
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleFetchMember}
                                    disabled={isFetchingMember}
                                    className="shrink-0 text-xs"
                                    title="Auto-fetch member name and profile from Telegram groups API"
                                >
                                    <Sparkles className="h-3.5 w-3.5 mr-1" />
                                    {isFetchingMember ? 'Fetching...' : 'Fetch Info'}
                                </Button>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="display_name">Display Name / Title</Label>
                            <Input
                                id="display_name"
                                placeholder="e.g. Abebe Kebede"
                                value={userForm.data.display_name}
                                onChange={(e) => userForm.setData('display_name', e.target.value)}
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="role">Role</Label>
                            <Select
                                value={userForm.data.role}
                                onValueChange={(val) => {
                                    userForm.setData({
                                        ...userForm.data,
                                        role: val,
                                        branch_name: val === 'branch_manager' ? userForm.data.branch_name : '',
                                        department: val === 'department_head' ? userForm.data.department : '',
                                        region: (val === 'branch_manager' || val === 'regional_manager') ? (userForm.data.region || 'Region 1') : '',
                                    });
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="branch_manager">Branch Manager</SelectItem>
                                    <SelectItem value="regional_manager">Regional Manager</SelectItem>
                                    <SelectItem value="department_head">Department Head</SelectItem>
                                    <SelectItem value="operations_director">Operations Director</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {(userForm.data.role === 'branch_manager' || userForm.data.role === 'regional_manager') && (
                            <div className="space-y-2">
                                <Label htmlFor="region">Region</Label>
                                <Select
                                    value={userForm.data.region}
                                    onValueChange={(val) => userForm.setData('region', val)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Region" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Region 1">Region 1</SelectItem>
                                        <SelectItem value="Region 2">Region 2</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        {userForm.data.role === 'branch_manager' && (
                            <div className="space-y-2">
                                <Label htmlFor="branch_name">Branch Name</Label>
                                <SearchableSelect
                                    options={branchOptions}
                                    value={userForm.data.branch_name}
                                    onValueChange={(val) => userForm.setData('branch_name', val)}
                                    placeholder="Select Branch"
                                    searchPlaceholder="Search branch..."
                                    emptyText="No branch found."
                                    className="w-full"
                                />
                            </div>
                        )}

                        {userForm.data.role === 'department_head' && (
                            <div className="space-y-2">
                                <Label htmlFor="department">HO Department</Label>
                                <SearchableSelect
                                    options={departmentOptions}
                                    value={userForm.data.department}
                                    onValueChange={(val) => userForm.setData('department', val)}
                                    placeholder="Select Department"
                                    searchPlaceholder="Search department..."
                                    emptyText="No department found."
                                    className="w-full"
                                />
                            </div>
                        )}

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setIsAddUserOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={userForm.processing}>
                                Save User
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal: Add Topic Binding */}
            <Dialog open={isAddBindingOpen} onOpenChange={setIsAddBindingOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Add Topic Thread</DialogTitle>
                        <DialogDescription>
                            Create a topic mapping or enter existing Telegram Thread ID.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleAddBinding} className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label htmlFor="preset_topic">Standard Preset (Optional)</Label>
                            <Select
                                value={getPresets(defaultTopicMapping).some(p => p.name === bindingForm.data.topic_name) ? bindingForm.data.topic_name : 'custom'}
                                onValueChange={(val) => {
                                    if (val !== 'custom') {
                                        const preset = getPresets(defaultTopicMapping).find(p => p.name === val);
                                        if (preset) {
                                            bindingForm.setData({
                                                ...bindingForm.data,
                                                topic_name: preset.name,
                                                department: preset.department,
                                                emoji: preset.emoji,
                                            });
                                        }
                                    }
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Choose a preset..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="custom">Custom Topic...</SelectItem>
                                    {getPresets(defaultTopicMapping).map((preset) => (
                                        <SelectItem key={preset.name} value={preset.name}>
                                            {preset.emoji} {preset.name} ({preset.department})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="group_key">Target Telegram Group</Label>
                            <Select
                                value={bindingForm.data.group_key}
                                onValueChange={(val) => bindingForm.setData('group_key', val)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Group" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Region 1">Region 1 Group</SelectItem>
                                    <SelectItem value="Region 2">Region 2 Group</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="grid grid-cols-4 gap-2">
                            <div className="col-span-1 space-y-2">
                                <Label htmlFor="emoji">Emoji</Label>
                                <Input
                                    id="emoji"
                                    placeholder="📢"
                                    value={bindingForm.data.emoji}
                                    onChange={(e) => bindingForm.setData('emoji', e.target.value)}
                                    className="text-center text-lg"
                                />
                            </div>
                            <div className="col-span-3 space-y-2">
                                <Label htmlFor="topic_name">Topic Name</Label>
                                <Input
                                    id="topic_name"
                                    placeholder="e.g. HR, IT, Operations"
                                    value={bindingForm.data.topic_name}
                                    onChange={(e) => bindingForm.setData('topic_name', e.target.value)}
                                    required
                                />
                            </div>
                        </div>

                        {/* Quick Emoji Buttons */}
                        <div className="flex flex-wrap items-center gap-1 p-2 bg-muted/30 rounded border text-xs">
                            <span className="text-[10px] text-muted-foreground font-medium">Quick Emojis:</span>
                            {['📢', '⚙️', '💼', '💰', '📦', '💻', '🔧', '☕', '🎓', '🛡️', '🚚', '💡', '📌', '🚀', '💬'].map((e) => (
                                <button
                                    key={e}
                                    type="button"
                                    onClick={() => bindingForm.setData('emoji', e)}
                                    className={`h-6 w-6 rounded hover:bg-muted text-sm flex items-center justify-center transition-colors ${bindingForm.data.emoji === e ? 'bg-primary/20 ring-1 ring-primary' : ''}`}
                                >
                                    {e}
                                </button>
                            ))}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="department">Target HO Department</Label>
                            <SearchableSelect
                                options={departmentOptions}
                                value={bindingForm.data.department}
                                onValueChange={(val) => bindingForm.setData('department', val)}
                                placeholder="Select Department"
                                searchPlaceholder="Search department..."
                                emptyText="No department found."
                                className="w-full"
                            />
                        </div>

                        <div className="space-y-2 pt-1 border-t">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="thread_id" className="text-xs font-medium">
                                    Telegram Topic Thread ID <span className="text-muted-foreground font-normal">(Optional)</span>
                                </Label>
                                <span className="text-[11px] text-muted-foreground">Auto-creates if blank</span>
                            </div>
                            <Input
                                id="thread_id"
                                placeholder="Leave blank to auto-create topic"
                                value={bindingForm.data.thread_id}
                                onChange={(e) => bindingForm.setData('thread_id', e.target.value)}
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setIsAddBindingOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={bindingForm.processing}>
                                {!bindingForm.data.thread_id ? 'Auto-Create Topic' : 'Save Binding'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal: Edit Topic Binding */}
            <Dialog open={isEditBindingOpen} onOpenChange={setIsEditBindingOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Edit Topic Binding</DialogTitle>
                        <DialogDescription>
                            Update topic name, target department, or sync to Telegram group.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleUpdateBinding} className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label>Group & Thread ID</Label>
                            <div className="text-xs font-mono p-2 bg-muted/40 rounded">
                                {editBindingForm.data.group_key} • Thread ID: {editBindingForm.data.thread_id}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="edit_topic_name">Topic Name</Label>
                            <Input
                                id="edit_topic_name"
                                value={editBindingForm.data.topic_name}
                                onChange={(e) => editBindingForm.setData('topic_name', e.target.value)}
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="edit_department">Target HO Department</Label>
                            <SearchableSelect
                                options={departmentOptions}
                                value={editBindingForm.data.department}
                                onValueChange={(val) => editBindingForm.setData('department', val)}
                                placeholder="Select Department"
                                searchPlaceholder="Search department..."
                                emptyText="No department found."
                                className="w-full"
                            />
                        </div>

                        <div className="flex items-center space-x-2 pt-1">
                            <input
                                type="checkbox"
                                id="sync_to_telegram"
                                checked={editBindingForm.data.sync_to_telegram}
                                onChange={(e) => editBindingForm.setData('sync_to_telegram', e.target.checked)}
                                className="h-4 w-4 rounded border-input"
                            />
                            <Label htmlFor="sync_to_telegram" className="text-xs text-muted-foreground">
                                Sync updated topic name to Telegram group
                            </Label>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setIsEditBindingOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={editBindingForm.processing}>
                                Save Changes
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal: Edit Roster Member */}
            <Dialog open={!!editingUser} onOpenChange={(open) => !open && setEditingUser(null)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Edit Staff Profile</DialogTitle>
                        <DialogDescription>
                            Update member's details and roles.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleUpdateUser} className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label htmlFor="edit_usr_id">Telegram User ID</Label>
                            <Input
                                id="edit_usr_id"
                                value={editUserForm.data.telegram_user_id}
                                disabled
                                className="font-mono bg-muted/40"
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="edit_usr_name">Display / Full Name</Label>
                            <Input
                                id="edit_usr_name"
                                value={editUserForm.data.display_name}
                                onChange={(e) => editUserForm.setData('display_name', e.target.value)}
                                placeholder="e.g., Abebe Bikila"
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="edit_usr_role">Role</Label>
                            <Select
                                value={editUserForm.data.role}
                                onValueChange={(val) => {
                                    editUserForm.setData({
                                        ...editUserForm.data,
                                        role: val,
                                        branch_name: val === 'branch_manager' ? editUserForm.data.branch_name : '',
                                        department: val === 'department_head' ? editUserForm.data.department : '',
                                        region: (val === 'branch_manager' || val === 'regional_manager') ? (editUserForm.data.region || 'Region 1') : '',
                                    });
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="branch_manager">Branch Manager</SelectItem>
                                    <SelectItem value="regional_manager">Regional Manager</SelectItem>
                                    <SelectItem value="department_head">HO Department Head</SelectItem>
                                    <SelectItem value="operations_director">Operations Director</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {(editUserForm.data.role === 'branch_manager' || editUserForm.data.role === 'regional_manager') && (
                            <div className="space-y-2">
                                <Label htmlFor="edit_usr_region">Region</Label>
                                <Select
                                    value={editUserForm.data.region}
                                    onValueChange={(val) => editUserForm.setData('region', val)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select Region" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Region 1">Region 1</SelectItem>
                                        <SelectItem value="Region 2">Region 2</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        {editUserForm.data.role === 'branch_manager' && (
                            <div className="space-y-2">
                                <Label htmlFor="edit_usr_branch">Branch Name</Label>
                                <SearchableSelect
                                    options={branchOptions}
                                    value={editUserForm.data.branch_name}
                                    onValueChange={(val) => editUserForm.setData('branch_name', val)}
                                    placeholder="Select Branch"
                                    searchPlaceholder="Search branch..."
                                    emptyText="No branch found."
                                    className="w-full"
                                />
                            </div>
                        )}

                        {editUserForm.data.role === 'department_head' && (
                            <div className="space-y-2">
                                <Label htmlFor="edit_usr_dept">HO Department</Label>
                                <SearchableSelect
                                    options={departmentOptions}
                                    value={editUserForm.data.department}
                                    onValueChange={(val) => editUserForm.setData('department', val)}
                                    placeholder="Select Department"
                                    searchPlaceholder="Search department..."
                                    emptyText="No department found."
                                    className="w-full"
                                />
                            </div>
                        )}

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setEditingUser(null)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={editUserForm.processing}>
                                Save Changes
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal: Add Standard Topic Preset */}
            <Dialog open={isAddStandardTopicOpen} onOpenChange={setIsAddStandardTopicOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Add Standard Topic Preset</DialogTitle>
                        <DialogDescription>
                            Create a standard topic preset with emoji and target department.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleAddStandardTopic} className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label htmlFor="std_topic_name">Topic Name</Label>
                            <Input
                                id="std_topic_name"
                                value={standardTopicForm.data.name}
                                onChange={(e) => standardTopicForm.setData('name', e.target.value)}
                                placeholder="e.g., Safety & Security"
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="std_topic_dept">Target HO Department</Label>
                            <SearchableSelect
                                options={departmentOptions}
                                value={standardTopicForm.data.department}
                                onValueChange={(val) => standardTopicForm.setData('department', val)}
                                placeholder="Select Department"
                                searchPlaceholder="Search department..."
                                emptyText="No department found."
                                className="w-full"
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="std_topic_emoji">Official Emoji</Label>
                            <div className="flex items-center gap-2">
                                <Input
                                    id="std_topic_emoji"
                                    value={standardTopicForm.data.emoji}
                                    onChange={(e) => standardTopicForm.setData('emoji', e.target.value)}
                                    placeholder="e.g., 🚨"
                                    className="w-20 text-center text-lg"
                                    maxLength={10}
                                    required
                                />
                                <div className="flex gap-1 flex-wrap">
                                    {['📢', '⚙️', '💼', '💰', '📦', '💻', '🔧', '☕', '🎓', '🛡️', '📊', '🚚', '🚨', '💡', '🌟'].map((em) => (
                                        <button
                                            key={em}
                                            type="button"
                                            onClick={() => standardTopicForm.setData('emoji', em)}
                                            className="h-8 w-8 rounded border text-sm hover:bg-muted flex items-center justify-center transition-colors"
                                        >
                                            {em}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setIsAddStandardTopicOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={standardTopicForm.processing}>
                                Save Preset
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Modal: Edit Standard Topic Preset */}
            <Dialog open={isEditStandardTopicOpen} onOpenChange={setIsEditStandardTopicOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Edit Standard Topic Preset</DialogTitle>
                        <DialogDescription>
                            Update standard topic name, department, and emoji.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleUpdateStandardTopic} className="space-y-4 py-2">
                        <div className="space-y-2">
                            <Label htmlFor="edit_std_topic_name">Topic Name</Label>
                            <Input
                                id="edit_std_topic_name"
                                value={editStandardTopicForm.data.name}
                                onChange={(e) => editStandardTopicForm.setData('name', e.target.value)}
                                placeholder="e.g., Safety & Security"
                                required
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="edit_std_topic_dept">Target HO Department</Label>
                            <SearchableSelect
                                options={departmentOptions}
                                value={editStandardTopicForm.data.department}
                                onValueChange={(val) => editStandardTopicForm.setData('department', val)}
                                placeholder="Select Department"
                                searchPlaceholder="Search department..."
                                emptyText="No department found."
                                className="w-full"
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="edit_std_topic_emoji">Official Emoji</Label>
                            <div className="flex items-center gap-2">
                                <Input
                                    id="edit_std_topic_emoji"
                                    value={editStandardTopicForm.data.emoji}
                                    onChange={(e) => editStandardTopicForm.setData('emoji', e.target.value)}
                                    placeholder="e.g., 🚨"
                                    className="w-20 text-center text-lg"
                                    maxLength={10}
                                    required
                                />
                                <div className="flex gap-1 flex-wrap">
                                    {['📢', '⚙️', '💼', '💰', '📦', '💻', '🔧', '☕', '🎓', '🛡️', '📊', '🚚', '🚨', '💡', '🌟'].map((em) => (
                                        <button
                                            key={em}
                                            type="button"
                                            onClick={() => editStandardTopicForm.setData('emoji', em)}
                                            className="h-8 w-8 rounded border text-sm hover:bg-muted flex items-center justify-center transition-colors"
                                        >
                                            {em}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </div>

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setIsEditStandardTopicOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={editStandardTopicForm.processing}>
                                Save Changes
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
