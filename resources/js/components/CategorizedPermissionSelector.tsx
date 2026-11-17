import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatPermissionLabel, groupPermissionsList, GroupedPermissions } from '@/lib/permission-categories';
import {
    Briefcase,
    CheckSquare,
    ChevronDown,
    ChevronUp,
    ClipboardCheck,
    FileEdit,
    GraduationCap,
    Receipt,
    Search,
    Send,
    Settings,
    Shield,
    Signal,
    Square,
    Users,
} from 'lucide-react';
import React, { useMemo, useState } from 'react';

interface Props {
    allPermissions: string[];
    groupedPermissions?: GroupedPermissions;
    selectedPermissions: string[];
    onChange: (permissions: string[]) => void;
}

const CATEGORY_ICONS: Record<string, React.ReactNode> = {
    'Kaldis Communication': <Send className="h-4 w-4 text-sky-600" />,
    'Telecom Management': <Signal className="h-4 w-4 text-teal-600" />,
    'IT Survey & Audit': <ClipboardCheck className="h-4 w-4 text-indigo-600" />,
    'Sales Reconciliation': <Receipt className="h-4 w-4 text-emerald-600" />,
    'HR Management': <GraduationCap className="h-4 w-4 text-pink-600" />,
    'Case Management (IT Only)': <Briefcase className="h-4 w-4 text-amber-600" />,
    'Edit & Void Requests': <FileEdit className="h-4 w-4 text-blue-600" />,
    'User & Organization': <Users className="h-4 w-4 text-emerald-600" />,
    'Roles & Access Control': <Shield className="h-4 w-4 text-purple-600" />,
    'System & General': <Settings className="h-4 w-4 text-zinc-600" />,
};

export default function CategorizedPermissionSelector({
    allPermissions,
    groupedPermissions: serverGrouped,
    selectedPermissions,
    onChange,
}: Props) {
    const [search, setSearch] = useState('');
    const [activeTab, setActiveTab] = useState<string>('ALL');

    const categoryGroups = useMemo(() => {
        if (serverGrouped && Object.keys(serverGrouped).length > 0) {
            return serverGrouped;
        }
        return groupPermissionsList(allPermissions);
    }, [allPermissions, serverGrouped]);

    const categories = useMemo(() => Object.keys(categoryGroups), [categoryGroups]);

    const [collapsed, setCollapsed] = useState<Record<string, boolean>>({});

    const toggleCollapse = (cat: string) => {
        setCollapsed((prev) => ({ ...prev, [cat]: !prev[cat] }));
    };

    const togglePermission = (perm: string) => {
        if (selectedPermissions.includes(perm)) {
            onChange(selectedPermissions.filter((p) => p !== perm));
        } else {
            onChange([...selectedPermissions, perm]);
        }
    };

    const toggleCategoryAll = (cat: string, items: string[]) => {
        const allSelected = items.every((i) => selectedPermissions.includes(i));
        if (allSelected) {
            onChange(selectedPermissions.filter((p) => !items.includes(p)));
        } else {
            const next = Array.from(new Set([...selectedPermissions, ...items]));
            onChange(next);
        }
    };

    const selectAll = () => onChange([...allPermissions]);
    const clearAll = () => onChange([]);

    const filteredCategories = useMemo(() => {
        const result: Record<string, string[]> = {};
        const query = search.trim().toLowerCase();

        categories.forEach((cat) => {
            if (activeTab !== 'ALL' && activeTab !== cat) return;

            const items = categoryGroups[cat] || [];
            const matched = query
                ? items.filter((p) => p.toLowerCase().includes(query) || formatPermissionLabel(p).toLowerCase().includes(query))
                : items;

            if (matched.length > 0) {
                result[cat] = matched;
            }
        });

        return result;
    }, [categories, categoryGroups, search, activeTab]);

    return (
        <div className="space-y-4">
            <div className="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
                <div className="relative w-full sm:w-72">
                    <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        type="text"
                        placeholder="Search permissions..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="pl-9 h-9"
                    />
                </div>
                <div className="flex items-center gap-2">
                    <Button type="button" variant="outline" size="sm" onClick={selectAll} className="text-xs h-8">
                        <CheckSquare className="h-3.5 w-3.5 mr-1" /> Select All ({allPermissions.length})
                    </Button>
                    <Button type="button" variant="ghost" size="sm" onClick={clearAll} className="text-xs h-8 text-destructive">
                        <Square className="h-3.5 w-3.5 mr-1" /> Clear All
                    </Button>
                </div>
            </div>

            {/* Quick Filter Badges */}
            <div className="flex flex-wrap gap-1.5 pb-1">
                <button
                    type="button"
                    onClick={() => setActiveTab('ALL')}
                    className={`px-2.5 py-1 text-xs rounded-full border transition-all ${
                        activeTab === 'ALL'
                            ? 'bg-primary text-primary-foreground border-primary font-medium'
                            : 'bg-muted/40 text-muted-foreground hover:bg-muted'
                    }`}
                >
                    All Modules ({categories.length})
                </button>
                {categories.map((cat) => (
                    <button
                        key={cat}
                        type="button"
                        onClick={() => setActiveTab(cat)}
                        className={`px-2.5 py-1 text-xs rounded-full border transition-all flex items-center gap-1.5 ${
                            activeTab === cat
                                ? 'bg-primary text-primary-foreground border-primary font-medium'
                                : 'bg-muted/40 text-muted-foreground hover:bg-muted'
                        }`}
                    >
                        {CATEGORY_ICONS[cat]}
                        <span>{cat}</span>
                        <span className="text-[10px] opacity-75 font-mono">({categoryGroups[cat]?.length || 0})</span>
                    </button>
                ))}
            </div>

            {/* Category Cards */}
            <div className="space-y-3">
                {Object.keys(filteredCategories).map((cat) => {
                    const items = filteredCategories[cat];
                    const selectedCount = items.filter((p) => selectedPermissions.includes(p)).length;
                    const isAll = items.length > 0 && selectedCount === items.length;
                    const isCollapsed = !!collapsed[cat];

                    return (
                        <Card key={cat} className="border-border/60 shadow-xs overflow-hidden">
                            <CardHeader className="py-2.5 px-4 bg-muted/20 border-b border-border/40">
                                <div className="flex items-center justify-between">
                                    <div
                                        className="flex items-center gap-2 cursor-pointer select-none"
                                        onClick={() => toggleCollapse(cat)}
                                    >
                                        {CATEGORY_ICONS[cat]}
                                        <CardTitle className="text-sm font-semibold tracking-tight">{cat}</CardTitle>
                                        <Badge variant="secondary" className="text-[11px] font-normal py-0 h-5">
                                            {selectedCount} / {items.length}
                                        </Badge>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => toggleCategoryAll(cat, items)}
                                            className="h-7 text-xs px-2 text-muted-foreground hover:text-foreground"
                                        >
                                            {isAll ? 'Deselect Category' : 'Select Category'}
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => toggleCollapse(cat)}
                                            className="h-7 w-7 text-muted-foreground"
                                        >
                                            {isCollapsed ? <ChevronDown className="h-4 w-4" /> : <ChevronUp className="h-4 w-4" />}
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                            {!isCollapsed && (
                                <CardContent className="p-4">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5">
                                        {items.map((perm) => {
                                            const isChecked = selectedPermissions.includes(perm);
                                            return (
                                                <div
                                                    key={perm}
                                                    role="button"
                                                    tabIndex={0}
                                                    onClick={() => togglePermission(perm)}
                                                    onKeyDown={(e) => {
                                                        if (e.key === ' ' || e.key === 'Enter') {
                                                            e.preventDefault();
                                                            togglePermission(perm);
                                                        }
                                                    }}
                                                    className={`flex items-start gap-2.5 p-2 rounded-md border text-left cursor-pointer transition-colors ${
                                                        isChecked
                                                            ? 'bg-primary/5 border-primary/40 text-foreground'
                                                            : 'bg-background hover:bg-muted/30 border-border/50 text-muted-foreground'
                                                    }`}
                                                >
                                                    <Checkbox
                                                        checked={isChecked}
                                                        tabIndex={-1}
                                                        className="mt-0.5 pointer-events-none"
                                                    />
                                                    <div className="text-xs leading-snug">
                                                        <div className="font-medium text-foreground">{formatPermissionLabel(perm)}</div>
                                                        <div className="font-mono text-[10px] text-muted-foreground">{perm}</div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </CardContent>
                            )}
                        </Card>
                    );
                })}
            </div>
        </div>
    );
}
