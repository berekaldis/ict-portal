import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/searchable-select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    Calendar,
    Clock,
    FileEdit,
    FileText,
    Info,
    Lock,
    Plus,
    PlusCircle,
    Store,
    Trash2,
    UserCheck,
    Search,
    X,
} from 'lucide-react';
import React, { useState } from 'react';

interface Props {
    branches: string[];
    voucherTypes: string[];
    editTypes: string[];
    targetDepartments?: string[];
    shifts?: string[];
    storeDepartments?: string[];
    userBranch?: string | null;
    canChangeBranch?: boolean;
}

export default function EditRequestCreate({
    branches = [],
    voucherTypes = [],
    editTypes = [],
    targetDepartments = ['Finance', 'F&B'],
    shifts = ['shft 1', 'shft 2'],
    storeDepartments = [],
    userBranch = null,
    canChangeBranch = false,
}: Props) {
    const { props } = usePage<any>();
    const user = props.auth?.user;

    const lockedBranch = userBranch || user?.branch || '';
    const hasLockedBranch = Boolean(lockedBranch) && !canChangeBranch;

    const [activeTab, setActiveTab] = useState<'voucher' | 'onhand'>('voucher');
    const [voucherCodes, setVoucherCodes] = useState<string[]>(['']);
    const [storeSearch, setStoreSearch] = useState('');

    const { data, setData, post, processing, errors } = useForm({
        action_type: 'Edit' as 'Edit' | 'Void' | 'On Hand Delete',
        branch: lockedBranch || branches[0] || '',
        user_name: user?.name || user?.username || '',
        target_dept: 'Finance',
        voucher_code: '',
        voucher_codes: [''],
        voucher_type: voucherTypes[0] || '',
        edit_type: editTypes[0] || 'Date Change',
        store_user: '',
        shift: shifts[0] || 'shft 1',
        store_departments: [] as string[],
        incorrect_date: '',
        correct_date: '',
        reason: '',
    });

    const isOHD = activeTab === 'onhand';

    const handleTabSwitch = (tab: 'voucher' | 'onhand') => {
        setActiveTab(tab);
        if (tab === 'onhand') {
            setData((prev) => ({
                ...prev,
                action_type: 'On Hand Delete',
                target_dept: 'F&B',
                shift: prev.shift || shifts[0] || 'shft 1',
            }));
        } else {
            setData((prev) => ({
                ...prev,
                action_type: 'Edit',
                target_dept: 'Finance',
            }));
        }
    };

    const toggleStoreDepartment = (dept: string) => {
        setData((prev) => {
            const current = prev.store_departments || [];
            if (current.includes(dept)) {
                return { ...prev, store_departments: current.filter((d) => d !== dept) };
            } else {
                return { ...prev, store_departments: [...current, dept] };
            }
        });
    };

    const filteredStoreDepartments = storeDepartments.filter((dept) =>
        dept.toLowerCase().includes(storeSearch.toLowerCase().trim())
    );

    const selectAllStoreDepartments = () => {
        if (storeSearch.trim()) {
            const toAdd = filteredStoreDepartments.filter((d) => !data.store_departments.includes(d));
            setData('store_departments', [...data.store_departments, ...toAdd]);
        } else {
            setData('store_departments', [...storeDepartments]);
        }
    };

    const clearStoreDepartments = () => {
        if (storeSearch.trim()) {
            setData('store_departments', data.store_departments.filter((d) => !filteredStoreDepartments.includes(d)));
        } else {
            setData('store_departments', []);
        }
    };

    const addVoucherCode = () => {
        setVoucherCodes((prev) => [...prev, '']);
    };

    const removeVoucherCode = (index: number) => {
        if (voucherCodes.length <= 1) return;
        setVoucherCodes((prev) => prev.filter((_, i) => i !== index));
    };

    const updateVoucherCode = (index: number, val: string) => {
        const updated = [...voucherCodes];
        updated[index] = val;
        setVoucherCodes(updated);
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isOHD) {
            if (!data.store_departments || data.store_departments.length === 0) {
                alert('Please select at least one Department Store.');
                return;
            }
        } else {
            // Synchronize voucher codes array if in voucher mode
            const cleanedCodes = voucherCodes.map((c) => c.trim()).filter(Boolean);
            const codeString = cleanedCodes.join(', ');

            data.voucher_codes = cleanedCodes;
            data.voucher_code = codeString || data.voucher_code;
        }

        post(route('edit-requests.store'));
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Voucher Requests', href: '/edit-requests' }, { title: 'Submit Request' }]}>
            <Head title="Submit Voucher / On Hand Delete Request" />

            <div className="max-w-3xl mx-auto space-y-4">
                {/* Header Section */}
                <div className="flex items-center gap-3">
                    <Button asChild variant="outline" size="icon" className="h-9 w-9">
                        <Link href={route('edit-requests.index')}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <FileEdit className="h-5 w-5 text-primary" /> Submit Correction Request
                        </h1>
                        <p className="text-xs text-muted-foreground">
                            Create a voucher edit, void, or store on-hand deletion request with searchable dropdowns
                        </p>
                    </div>
                </div>

                {/* Tab Navigation (Voucher Edit/Void vs On Hand Delete) */}
                <div className="flex items-center gap-2 bg-muted/50 p-1 rounded-xl border border-border/60">
                    <button
                        type="button"
                        onClick={() => handleTabSwitch('voucher')}
                        className={`flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-xs font-semibold transition-all ${
                            activeTab === 'voucher'
                                ? 'bg-background text-foreground shadow-xs border border-border/80'
                                : 'text-muted-foreground hover:text-foreground hover:bg-muted/80'
                        }`}
                    >
                        <FileText className="h-4 w-4 text-primary" />
                        <span>Voucher Edit / Void</span>
                    </button>
                    <button
                        type="button"
                        onClick={() => handleTabSwitch('onhand')}
                        className={`flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-xs font-semibold transition-all ${
                            activeTab === 'onhand'
                                ? 'bg-background text-foreground shadow-xs border border-border/80'
                                : 'text-muted-foreground hover:text-foreground hover:bg-muted/80'
                        }`}
                    >
                        <Trash2 className="h-4 w-4 text-amber-600 dark:text-amber-500" />
                        <span>On Hand Delete</span>
                    </button>
                </div>

                <Card className="border-border/60 shadow-xs">
                    <CardHeader className="py-4 px-6 border-b border-border/40">
                        <CardTitle className="text-sm font-semibold flex items-center gap-2">
                            {isOHD ? (
                                <>
                                    <Trash2 className="h-4 w-4 text-amber-600" />
                                    <span>New On Hand Delete Request</span>
                                </>
                            ) : (
                                <>
                                    <FileEdit className="h-4 w-4 text-primary" />
                                    <span>New Edit / Void Voucher Request</span>
                                </>
                            )}
                        </CardTitle>
                        <CardDescription className="text-xs">
                            {isOHD
                                ? 'Inventory on-hand deletion request routed to F&B and Store managers.'
                                : 'Voucher correction and void request routed to department managers for approval.'}
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="p-6">
                        <form onSubmit={submit} className="space-y-5">
                            {/* Branch & Requester Info */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <div className="flex items-center justify-between">
                                        <Label htmlFor="branch" className="text-xs font-semibold">
                                            Branch 🏢 <span className="text-destructive">*</span>
                                        </Label>
                                        {hasLockedBranch && (
                                            <span className="text-[11px] font-medium text-amber-600 dark:text-amber-500 flex items-center gap-1">
                                                <Lock className="h-3 w-3" /> Locked to your branch
                                            </span>
                                        )}
                                    </div>
                                    {hasLockedBranch ? (
                                        <div className="relative">
                                            <Input
                                                id="branch"
                                                value={data.branch}
                                                readOnly
                                                disabled
                                                className="h-10 text-sm font-semibold bg-muted/60 text-foreground cursor-not-allowed border-amber-500/30 pl-9"
                                            />
                                            <Building2 className="absolute left-3 top-3 h-4 w-4 text-amber-600 dark:text-amber-500" />
                                        </div>
                                    ) : (
                                        <SearchableSelect
                                            id="branch"
                                            options={branches}
                                            value={data.branch}
                                            onValueChange={(val) => setData('branch', val)}
                                            placeholder="Search or select branch..."
                                            searchPlaceholder="Type branch name..."
                                            emptyText="No matching active branch found."
                                        />
                                    )}
                                    {errors.branch && <p className="text-xs text-destructive">{errors.branch}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="user_name" className="text-xs font-semibold">
                                        Requester User Name <span className="text-destructive">*</span>
                                    </Label>
                                    <Input
                                        id="user_name"
                                        placeholder="e.g. John Doe"
                                        value={data.user_name}
                                        onChange={(e) => setData('user_name', e.target.value)}
                                        required
                                        className="h-10 text-sm"
                                    />
                                    {errors.user_name && <p className="text-xs text-destructive">{errors.user_name}</p>}
                                </div>
                            </div>

                            {/* TAB 1: VOUCHER EDIT / VOID FORM */}
                            {!isOHD && (
                                <div className="space-y-4 pt-1">
                                    {/* Action Required & Target Department */}
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div className="space-y-1.5">
                                            <Label className="text-xs font-semibold">
                                                Action Required <span className="text-destructive">*</span>
                                            </Label>
                                            <SearchableSelect
                                                options={['Edit', 'Void']}
                                                value={data.action_type}
                                                onValueChange={(val) => setData('action_type', val as 'Edit' | 'Void')}
                                                placeholder="Select Action..."
                                                searchPlaceholder="Search action (Edit or Void)..."
                                            />
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label className="text-xs font-semibold">
                                                Target Department <span className="text-destructive">*</span>
                                            </Label>
                                            <SearchableSelect
                                                options={targetDepartments}
                                                value={data.target_dept}
                                                onValueChange={(val) => setData('target_dept', val)}
                                                placeholder="Select Target Department..."
                                                searchPlaceholder="Search department..."
                                            />
                                            {errors.target_dept && <p className="text-xs text-destructive">{errors.target_dept}</p>}
                                        </div>
                                    </div>

                                    {/* Voucher Codes (Supports multiple codes like edit_request) */}
                                    <div className="space-y-2 p-3.5 rounded-xl bg-muted/20 border border-border/50">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-semibold flex items-center gap-1.5">
                                                <span>Voucher Codes</span>
                                                <Badge variant="secondary" className="text-[10px] h-5 px-1.5">
                                                    {voucherCodes.length}
                                                </Badge>
                                            </Label>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={addVoucherCode}
                                                className="h-7 text-xs text-primary hover:text-primary hover:bg-primary/10"
                                            >
                                                <Plus className="h-3.5 w-3.5 mr-1" /> Add Another Voucher Code
                                            </Button>
                                        </div>

                                        <div className="space-y-2">
                                            {voucherCodes.map((code, index) => (
                                                <div key={index} className="flex items-center gap-2">
                                                    <Input
                                                        placeholder={`Voucher Code #${index + 1} (e.g. VCH-001)`}
                                                        value={code}
                                                        onChange={(e) => updateVoucherCode(index, e.target.value)}
                                                        required={index === 0}
                                                        className="h-9 text-sm"
                                                    />
                                                    {voucherCodes.length > 1 && (
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => removeVoucherCode(index)}
                                                            className="h-9 w-9 text-destructive hover:bg-destructive/10 shrink-0"
                                                            title="Remove Voucher Code"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                        {errors.voucher_code && (
                                            <p className="text-xs text-destructive">{errors.voucher_code}</p>
                                        )}
                                    </div>

                                    {/* Voucher Type & Edit Type */}
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div className="space-y-1.5">
                                            <Label className="text-xs font-semibold">
                                                Voucher Type <span className="text-destructive">*</span>
                                            </Label>
                                            <SearchableSelect
                                                options={voucherTypes}
                                                value={data.voucher_type}
                                                onValueChange={(val) => setData('voucher_type', val)}
                                                placeholder="Search or select voucher type..."
                                                searchPlaceholder="Type voucher type name..."
                                                emptyText="No matching voucher type found."
                                            />
                                            {errors.voucher_type && <p className="text-xs text-destructive">{errors.voucher_type}</p>}
                                        </div>

                                        {data.action_type === 'Edit' && (
                                            <div className="space-y-1.5">
                                                <Label className="text-xs font-semibold">
                                                    Edit Type <span className="text-destructive">*</span>
                                                </Label>
                                                <SearchableSelect
                                                    options={editTypes}
                                                    value={data.edit_type}
                                                    onValueChange={(val) => setData('edit_type', val)}
                                                    placeholder="Search or select edit type..."
                                                    searchPlaceholder="Type edit type..."
                                                    emptyText="No matching edit type found."
                                                />
                                                {errors.edit_type && <p className="text-xs text-destructive">{errors.edit_type}</p>}
                                            </div>
                                        )}
                                    </div>

                                    {/* Date Fields if Date Change */}
                                    {data.action_type === 'Edit' && data.edit_type.toLowerCase().includes('date') && (
                                        <div className="p-3.5 rounded-xl bg-amber-500/5 border border-amber-500/20 space-y-2">
                                            <div className="text-xs font-semibold text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
                                                <Calendar className="h-3.5 w-3.5" /> Date Change Specification
                                            </div>
                                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div className="space-y-1">
                                                    <Label htmlFor="incorrect_date" className="text-[11px] font-medium text-muted-foreground">
                                                        Incorrect Date 📅 <span className="text-destructive">*</span>
                                                    </Label>
                                                    <Input
                                                        id="incorrect_date"
                                                        type="date"
                                                        value={data.incorrect_date}
                                                        onChange={(e) => setData('incorrect_date', e.target.value)}
                                                        required
                                                        className="h-9 text-xs"
                                                    />
                                                </div>
                                                <div className="space-y-1">
                                                    <Label htmlFor="correct_date" className="text-[11px] font-medium text-muted-foreground">
                                                        Correct Date 📅 <span className="text-destructive">*</span>
                                                    </Label>
                                                    <Input
                                                        id="correct_date"
                                                        type="date"
                                                        value={data.correct_date}
                                                        onChange={(e) => setData('correct_date', e.target.value)}
                                                        required
                                                        className="h-9 text-xs"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* TAB 2: ON HAND DELETE FORM */}
                            {isOHD && (
                                <div className="space-y-4 pt-1">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="store_user" className="text-xs font-semibold">
                                                Department Store User 👤 <span className="text-destructive">*</span>
                                            </Label>
                                            <Input
                                                id="store_user"
                                                placeholder="e.g. Abebech (Kitchen Store)"
                                                value={data.store_user}
                                                onChange={(e) => setData('store_user', e.target.value)}
                                                required
                                                className="h-10 text-sm"
                                            />
                                            {errors.store_user && <p className="text-xs text-destructive">{errors.store_user}</p>}
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label className="text-xs font-semibold">
                                                Target Department 🏦 <span className="text-destructive">*</span>
                                            </Label>
                                            <SearchableSelect
                                                options={targetDepartments}
                                                value={data.target_dept}
                                                onValueChange={(val) => setData('target_dept', val)}
                                                placeholder="Select Department..."
                                                searchPlaceholder="Search department..."
                                            />
                                            {errors.target_dept && <p className="text-xs text-destructive">{errors.target_dept}</p>}
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div className="space-y-1.5">
                                            <Label htmlFor="correct_date" className="text-xs font-semibold">
                                                On Hand Delete Date 📅 <span className="text-destructive">*</span>
                                            </Label>
                                            <Input
                                                id="correct_date"
                                                type="date"
                                                value={data.correct_date}
                                                onChange={(e) => setData('correct_date', e.target.value)}
                                                required
                                                className="h-10 text-sm"
                                            />
                                            {errors.correct_date && <p className="text-xs text-destructive">{errors.correct_date}</p>}
                                        </div>

                                        <div className="space-y-1.5">
                                            <Label className="text-xs font-semibold">
                                                Shift 🕒 <span className="text-destructive">*</span>
                                            </Label>
                                            <SearchableSelect
                                                options={shifts}
                                                value={data.shift}
                                                onValueChange={(val) => setData('shift', val)}
                                                placeholder="Select Shift..."
                                                searchPlaceholder="Search shift (shft 1, shft 2)..."
                                            />
                                        </div>
                                    </div>

                                    {/* Department Stores (Searchable & Multi-Select with Badges) */}
                                    <div className="space-y-2 pt-1">
                                        <div className="flex items-center justify-between">
                                            <Label className="text-xs font-semibold flex items-center gap-1.5">
                                                <span>Department Stores (Select one or more) 🏢</span>
                                                <span className="text-destructive">*</span>
                                                {data.store_departments.length > 0 && (
                                                    <Badge variant="secondary" className="text-[10px] h-5 px-1.5 font-semibold">
                                                        {data.store_departments.length} Selected
                                                    </Badge>
                                                )}
                                            </Label>
                                            <div className="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    onClick={selectAllStoreDepartments}
                                                    className="text-[11px] text-primary hover:underline font-medium"
                                                >
                                                    {storeSearch.trim() ? 'Select Filtered' : 'Select All'}
                                                </button>
                                                <span className="text-muted-foreground text-[10px]">•</span>
                                                <button
                                                    type="button"
                                                    onClick={clearStoreDepartments}
                                                    className="text-[11px] text-muted-foreground hover:text-foreground"
                                                >
                                                    {storeSearch.trim() ? 'Clear Filtered' : 'Clear All'}
                                                </button>
                                            </div>
                                        </div>

                                        {/* Selected Store Badges with Remove 'X' */}
                                        {data.store_departments.length > 0 && (
                                            <div className="flex flex-wrap gap-1.5 p-2 rounded-lg bg-background border border-border/60 shadow-xs max-h-28 overflow-y-auto">
                                                {data.store_departments.map((dept) => (
                                                    <Badge
                                                        key={dept}
                                                        variant="secondary"
                                                        className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs bg-primary/10 text-primary border border-primary/25 rounded-md hover:bg-destructive/10 hover:text-destructive hover:border-destructive/30 transition-all cursor-pointer group select-none"
                                                        onClick={() => toggleStoreDepartment(dept)}
                                                        title={`Click to remove ${dept}`}
                                                    >
                                                        <span className="font-medium">{dept}</span>
                                                        <X className="h-3 w-3 text-muted-foreground group-hover:text-destructive group-hover:scale-115 transition-transform" />
                                                    </Badge>
                                                ))}
                                            </div>
                                        )}

                                        {/* Search Filter Input */}
                                        <div className="relative">
                                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                                            <Input
                                                type="text"
                                                value={storeSearch}
                                                onChange={(e) => setStoreSearch(e.target.value)}
                                                placeholder="Search department stores (e.g. Pastry, Juice, Milk, Uniform)..."
                                                className="pl-9 pr-8 text-xs h-9 bg-background border-border/70 focus-visible:ring-primary"
                                            />
                                            {storeSearch && (
                                                <button
                                                    type="button"
                                                    onClick={() => setStoreSearch('')}
                                                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground p-0.5"
                                                    title="Clear search"
                                                >
                                                    <X className="h-3.5 w-3.5" />
                                                </button>
                                            )}
                                        </div>

                                        {/* Department Stores Grid */}
                                        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 p-3 rounded-xl bg-muted/20 border border-border/50 max-h-56 overflow-y-auto">
                                            {filteredStoreDepartments.length > 0 ? (
                                                filteredStoreDepartments.map((dept) => {
                                                    const isChecked = data.store_departments.includes(dept);
                                                    return (
                                                        <label
                                                            key={dept}
                                                            className={`flex items-center gap-2 p-2 rounded-lg border text-xs cursor-pointer transition-colors select-none ${
                                                                isChecked
                                                                    ? 'bg-primary/10 border-primary/40 font-medium text-foreground'
                                                                    : 'bg-background hover:bg-muted/50 border-border/60 text-muted-foreground'
                                                            }`}
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                checked={isChecked}
                                                                onChange={() => toggleStoreDepartment(dept)}
                                                                className="h-4 w-4 rounded border-input text-primary focus:ring-primary"
                                                            />
                                                            <span className="truncate" title={dept}>
                                                                {dept}
                                                            </span>
                                                        </label>
                                                    );
                                                })
                                            ) : (
                                                <div className="col-span-full py-4 text-center text-xs text-muted-foreground">
                                                    No department stores found matching "{storeSearch}"
                                                </div>
                                            )}
                                        </div>
                                        {errors.store_departments && (
                                            <p className="text-xs text-destructive">{errors.store_departments}</p>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Reason for Request */}
                            <div className="space-y-1.5">
                                <Label htmlFor="reason" className="text-xs font-semibold">
                                    Reason for Request 📝 {!isOHD && <span className="text-destructive">*</span>}
                                </Label>
                                <Textarea
                                    id="reason"
                                    placeholder={
                                        isOHD
                                            ? 'Optional notes or reason for on-hand deletion...'
                                            : 'Explain why this voucher edit or void is required...'
                                    }
                                    rows={3}
                                    value={data.reason}
                                    onChange={(e) => setData('reason', e.target.value)}
                                    required={!isOHD}
                                    className="text-sm resize-y"
                                />
                                {errors.reason && <p className="text-xs text-destructive">{errors.reason}</p>}
                            </div>

                            {/* Action Buttons */}
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-border/50">
                                <Button asChild variant="outline" type="button">
                                    <Link href={route('edit-requests.index')}>Cancel</Link>
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="font-medium bg-primary hover:bg-primary/90 text-primary-foreground shadow-xs"
                                >
                                    <PlusCircle className="mr-1.5 h-4 w-4" />
                                    {processing ? 'Submitting Request...' : 'Submit Request'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
