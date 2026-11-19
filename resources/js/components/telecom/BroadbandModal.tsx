import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/searchable-select';
import { router, useForm } from '@inertiajs/react';
import { Calendar, ExternalLink, FileText, Loader2, Paperclip, Trash2, Wifi, X } from 'lucide-react';
import React, { useEffect, useState } from 'react';
import { OptionItem } from './PhoneNumberModal';

export interface BroadbandRecord {
    id?: number;
    connection_name: string;
    account_number?: string;
    service_number?: string;
    telecom_provider_id?: number | string;
    connection_type: string;
    package_type?: string;
    package_start_date?: string;
    package_expiry_date?: string;
    bandwidth_speed?: string;
    speed_mbps?: number | string;
    monthly_cost: number | string;
    billing_type?: string;
    branch_id?: number | string;
    department_id?: number | string;
    status: string;
    ip_address?: string;
    equipment_details?: string;
    contract_start_date?: string;
    contract_expiry_date?: string;
    notes?: string;
    attachment_path?: string | null;
    attachment_url?: string | null;
}

interface BroadbandModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    initialData?: BroadbandRecord | null;
    providers: OptionItem[];
    branches: OptionItem[];
    onSuccessCallback?: () => void;
}

export default function BroadbandModal({
    open,
    onOpenChange,
    initialData,
    providers,
    branches,
    onSuccessCallback,
}: BroadbandModalProps) {
    const isEdit = !!initialData?.id;
    const [durationMonths, setDurationMonths] = useState<number>(1);

    const { data, setData, post, processing, errors, reset } = useForm({
        connection_name: '',
        account_number: '',
        service_number: '',
        telecom_provider_id: '',
        connection_type: 'WTTx (Fixed Wireless)',
        package_type: '',
        package_start_date: '',
        package_expiry_date: '',
        bandwidth_speed: '',
        speed_mbps: '',
        monthly_cost: 0,
        billing_type: 'Postpaid',
        branch_id: '',
        department_id: '',
        status: 'Active',
        ip_address: '',
        equipment_details: '',
        contract_start_date: '',
        contract_expiry_date: '',
        notes: '',
        attachment: null as File | null,
        remove_attachment: false,
    });

    useEffect(() => {
        if (initialData) {
            setData({
                connection_name: initialData.connection_name || '',
                account_number: initialData.account_number || '',
                service_number: initialData.service_number || '',
                telecom_provider_id: String(initialData.telecom_provider_id || ''),
                connection_type: initialData.connection_type || 'WTTx (Fixed Wireless)',
                package_type: initialData.package_type || '',
                package_start_date: initialData.package_start_date || initialData.contract_start_date || '',
                package_expiry_date: initialData.package_expiry_date || initialData.contract_expiry_date || '',
                bandwidth_speed: initialData.bandwidth_speed || (initialData.speed_mbps ? `${initialData.speed_mbps} Mbps` : ''),
                speed_mbps: String(initialData.speed_mbps || ''),
                monthly_cost: Number(initialData.monthly_cost) || 0,
                billing_type: initialData.billing_type || 'Postpaid',
                branch_id: String(initialData.branch_id || ''),
                department_id: String(initialData.department_id || ''),
                status: initialData.status || 'Active',
                ip_address: initialData.ip_address || '',
                equipment_details: initialData.equipment_details || '',
                contract_start_date: initialData.contract_start_date || '',
                contract_expiry_date: initialData.contract_expiry_date || '',
                notes: initialData.notes || '',
                attachment: null,
                remove_attachment: false,
            } as any);
        } else {
            reset();
        }
    }, [initialData, open]);

    // Automatically set package_expiry_date whenever package_start_date or durationMonths changes
    useEffect(() => {
        if (data.package_start_date) {
            const calculatedExpiry = calculateExpiryDate(data.package_start_date, durationMonths || 1);
            if (calculatedExpiry && calculatedExpiry !== data.package_expiry_date) {
                setData((prev) => ({
                    ...prev,
                    package_expiry_date: calculatedExpiry,
                    contract_expiry_date: calculatedExpiry,
                }));
            }
        }
    }, [data.package_start_date, durationMonths]);

    const calculateExpiryDate = (startDateStr: string, months: number = 1) => {
        if (!startDateStr) return '';
        const d = new Date(startDateStr);
        if (isNaN(d.getTime())) return '';
        d.setMonth(d.getMonth() + (months || 1));
        return d.toISOString().split('T')[0];
    };

    const handleStartDateChange = (startDateStr: string) => {
        const calculatedExpiry = calculateExpiryDate(startDateStr, durationMonths || 1);
        setData((prev) => ({
            ...prev,
            package_start_date: startDateStr,
            package_expiry_date: calculatedExpiry || prev.package_expiry_date,
            contract_start_date: startDateStr,
            contract_expiry_date: calculatedExpiry || prev.contract_expiry_date,
        }));
    };

    const handleDurationChange = (months: number) => {
        setDurationMonths(months);
        if (data.package_start_date) {
            const calculatedExpiry = calculateExpiryDate(data.package_start_date, months);
            setData((prev) => ({
                ...prev,
                package_expiry_date: calculatedExpiry,
                contract_expiry_date: calculatedExpiry,
            }));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const payload = {
            ...data,
            telecom_provider_id: data.telecom_provider_id || null,
            branch_id: data.branch_id || null,
            department_id: data.department_id || null,
            bandwidth_speed: data.bandwidth_speed || (data.speed_mbps ? `${data.speed_mbps} Mbps` : null),
        };

        const options = {
            forceFormData: true,
            onSuccess: () => {
                onOpenChange(false);
                reset();
                if (onSuccessCallback) onSuccessCallback();
            },
        };

        if (isEdit && initialData?.id) {
            router.post(`/telecom/broadbands/${initialData.id}`, {
                ...payload,
                _method: 'PUT',
            }, options);
        } else {
            router.post('/telecom/broadbands', payload, options);
        }
    };

    const formattedBranches = branches.map((b) => ({
        id: String(b.id),
        name: b.name,
    }));

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                className="sm:max-w-5xl max-w-5xl max-h-[92vh] overflow-y-auto p-6 sm:p-8 border-border/80 shadow-2xl"
                onPointerDownOutside={(e) => e.preventDefault()}
                onInteractOutside={(e) => e.preventDefault()}
                onEscapeKeyDown={(e) => e.preventDefault()}
            >
                <DialogHeader className="pb-2 border-b border-border/60">
                    <DialogTitle className="flex items-center gap-2 text-2xl font-bold text-purple-700 dark:text-purple-400">
                        <Wifi className="h-6 w-6 text-purple-600" />
                        {isEdit ? 'Edit Broadband / Data Connection' : 'Add Broadband / WTTx / Data SIM Connection'}
                    </DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-6 pt-3">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <Label htmlFor="connection_name" className="text-sm font-semibold">Connection Name / Identifier <span className="text-destructive">*</span></Label>
                            <Input
                                id="connection_name"
                                placeholder="e.g. HQ Bole Primary Fiber, Region 1 Data SIM"
                                value={data.connection_name}
                                onChange={(e) => setData('connection_name', e.target.value)}
                                className="mt-1 h-10"
                                required
                            />
                            <InputError message={errors.connection_name} />
                        </div>

                        <div>
                            <Label htmlFor="connection_type" className="text-sm font-semibold">Connection Type <span className="text-destructive">*</span></Label>
                            <select
                                id="connection_type"
                                value={data.connection_type}
                                onChange={(e) => setData('connection_type', e.target.value)}
                                className="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="WTTx (Fixed Wireless)">WTTx (Fixed Wireless 4G/5G)</option>
                                <option value="Data Sim Card">Data SIM Card</option>
                                <option value="Fiber Broadband (FTTH/FTTB)">Fiber Broadband (FTTH/FTTB)</option>
                                <option value="ADSL / Copper Broadband">ADSL / Copper Broadband</option>
                                <option value="Dedicated Leased Line">Dedicated Leased Line</option>
                                <option value="VSAT Satellite">VSAT Satellite</option>
                            </select>
                            <InputError message={errors.connection_type} />
                        </div>

                        <div>
                            <Label htmlFor="service_number" className="text-sm font-semibold">Service Number / SIM Phone Number</Label>
                            <Input
                                id="service_number"
                                placeholder="e.g. 0911XXXXXX or SIM Line No."
                                value={data.service_number}
                                onChange={(e) => setData('service_number', e.target.value)}
                                className="mt-1 h-10 font-mono"
                            />
                            <InputError message={errors.service_number} />
                        </div>

                        <div>
                            <Label htmlFor="account_number" className="text-sm font-semibold">Account / Circuit No.</Label>
                            <Input
                                id="account_number"
                                placeholder="e.g. CKT-99120 or Account No."
                                value={data.account_number}
                                onChange={(e) => setData('account_number', e.target.value)}
                                className="mt-1 h-10 font-mono"
                            />
                            <InputError message={errors.account_number} />
                        </div>

                        <div>
                            <Label htmlFor="telecom_provider_id" className="text-sm font-semibold">Telecom Provider</Label>
                            <select
                                id="telecom_provider_id"
                                value={data.telecom_provider_id}
                                onChange={(e) => setData('telecom_provider_id', e.target.value)}
                                className="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="">Select Provider...</option>
                                {providers.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.telecom_provider_id} />
                        </div>

                        {/* Searchable Branch Selector */}
                        <div className="space-y-1">
                            <Label htmlFor="branch_id" className="text-sm font-semibold">Search & Select Assigned Branch</Label>
                            <SearchableSelect
                                options={formattedBranches}
                                value={data.branch_id}
                                onValueChange={(val) => setData('branch_id', val)}
                                placeholder="Type to search branch..."
                                searchPlaceholder="Search branch..."
                                className="w-full bg-white dark:bg-slate-950"
                            />
                            <InputError message={errors.branch_id} />
                        </div>

                        <div>
                            <Label htmlFor="package_type" className="text-sm font-semibold">Package / Rate Plan</Label>
                            <Input
                                id="package_type"
                                placeholder="e.g. Business Fiber 50Mbps, WTTx Unlimited"
                                value={data.package_type}
                                onChange={(e) => setData('package_type', e.target.value)}
                                className="mt-1 h-10"
                            />
                        </div>

                        <div>
                            <Label htmlFor="bandwidth_speed" className="text-sm font-semibold">Bandwidth Speed</Label>
                            <Input
                                id="bandwidth_speed"
                                placeholder="e.g. 50 Mbps, Unlimited 4G/5G"
                                value={data.bandwidth_speed}
                                onChange={(e) => setData('bandwidth_speed', e.target.value)}
                                className="mt-1 h-10"
                            />
                        </div>

                        {/* Package Start Date */}
                        <div>
                            <Label htmlFor="package_start_date" className="flex items-center gap-1.5 text-sm font-semibold text-purple-700 dark:text-purple-400">
                                <Calendar className="h-4 w-4" /> Package Start Date
                            </Label>
                            <Input
                                id="package_start_date"
                                type="date"
                                value={data.package_start_date}
                                onChange={(e) => handleStartDateChange(e.target.value)}
                                className="mt-1 h-10 border-purple-300 dark:border-purple-800"
                            />
                            <InputError message={errors.package_start_date} />
                        </div>

                        {/* Package Duration Selector */}
                        <div>
                            <Label className="text-sm font-semibold text-slate-700 dark:text-slate-300">Package Duration</Label>
                            <select
                                value={durationMonths}
                                onChange={(e) => handleDurationChange(parseInt(e.target.value, 10))}
                                className="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value={1}>1 Month (+30 Days)</option>
                                <option value={3}>3 Months</option>
                                <option value={6}>6 Months</option>
                                <option value={12}>1 Year (12 Months)</option>
                            </select>
                        </div>

                        {/* Calculated Package Expire Date */}
                        <div>
                            <Label htmlFor="package_expiry_date" className="flex items-center gap-1.5 text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                                <Calendar className="h-4 w-4" /> Package Expire Date
                            </Label>
                            <Input
                                id="package_expiry_date"
                                type="date"
                                value={data.package_expiry_date}
                                onChange={(e) => setData('package_expiry_date', e.target.value)}
                                className="mt-1 h-10 border-emerald-300 dark:border-emerald-800 font-semibold"
                            />
                            <p className="text-[11px] text-muted-foreground mt-0.5">Calculated based on start date & duration</p>
                            <InputError message={errors.package_expiry_date} />
                        </div>

                        <div>
                            <Label htmlFor="monthly_cost" className="text-sm font-semibold">Monthly Cost (ETB) <span className="text-destructive">*</span></Label>
                            <Input
                                id="monthly_cost"
                                type="number"
                                step="0.01"
                                value={data.monthly_cost}
                                onChange={(e) => setData('monthly_cost', parseFloat(e.target.value) || 0)}
                                className="mt-1 h-10 font-mono"
                                required
                            />
                            <InputError message={errors.monthly_cost} />
                        </div>

                        <div>
                            <Label htmlFor="billing_type" className="text-sm font-semibold">Billing Type</Label>
                            <select
                                id="billing_type"
                                value={data.billing_type}
                                onChange={(e) => setData('billing_type', e.target.value)}
                                className="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="Postpaid">Postpaid</option>
                                <option value="Prepaid">Prepaid</option>
                            </select>
                            <InputError message={errors.billing_type} />
                        </div>

                        <div>
                            <Label htmlFor="status" className="text-sm font-semibold">Status <span className="text-destructive">*</span></Label>
                            <select
                                id="status"
                                value={data.status}
                                onChange={(e) => setData('status', e.target.value)}
                                className="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Suspended">Suspended</option>
                                <option value="Pending Installation">Pending Installation</option>
                            </select>
                            <InputError message={errors.status} />
                        </div>

                        <div>
                            <Label htmlFor="ip_address" className="text-sm font-semibold">Static IP Address</Label>
                            <Input
                                id="ip_address"
                                placeholder="e.g. 197.156.xx.xx"
                                value={data.ip_address}
                                onChange={(e) => setData('ip_address', e.target.value)}
                                className="mt-1 h-10 font-mono text-xs"
                            />
                        </div>

                        {/* Notes */}
                        <div className="col-span-1 md:col-span-2 space-y-1">
                            <Label htmlFor="notes" className="text-sm font-semibold">Notes / Remarks</Label>
                            <Input
                                id="notes"
                                value={data.notes}
                                onChange={(e) => setData('notes', e.target.value)}
                                placeholder="Additional details, router credentials, circuit notes..."
                            />
                            <InputError message={errors.notes} />
                        </div>

                        {/* Attachment Upload */}
                        <div className="col-span-1 md:col-span-2 space-y-2 p-3.5 rounded-lg border border-border/70 bg-muted/20">
                            <Label className="text-sm font-semibold flex items-center justify-between">
                                <span className="flex items-center gap-1.5">
                                    <Paperclip className="h-4 w-4 text-purple-600" />
                                    Attachment / Contract Agreement (Broadband SLA, Invoice, Form)
                                </span>
                                <span className="text-[11px] text-muted-foreground font-normal">
                                    PDF, Images, Word up to 10MB
                                </span>
                            </Label>

                            {initialData?.attachment_url && !data.remove_attachment && (
                                <div className="flex items-center justify-between p-2.5 rounded-md border border-border bg-background text-xs">
                                    <div className="flex items-center gap-2 truncate">
                                        <FileText className="h-4 w-4 text-emerald-600 shrink-0" />
                                        <span className="font-medium truncate">Current Attachment</span>
                                        <a
                                            href={initialData.attachment_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-purple-600 underline hover:text-purple-700 inline-flex items-center gap-1 text-[11px]"
                                        >
                                            <ExternalLink className="h-3 w-3" /> View / Download
                                        </a>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => setData('remove_attachment', true)}
                                        className="h-7 text-xs text-destructive hover:bg-destructive/10"
                                    >
                                        <Trash2 className="h-3.5 w-3.5 mr-1" /> Remove
                                    </Button>
                                </div>
                            )}

                            {data.remove_attachment && (
                                <div className="flex items-center justify-between p-2 rounded-md bg-destructive/10 border border-destructive/20 text-xs text-destructive">
                                    <span>Attachment will be removed upon saving.</span>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => setData('remove_attachment', false)}
                                        className="h-6 text-xs text-foreground"
                                    >
                                        Undo
                                    </Button>
                                </div>
                            )}

                            <div className="flex items-center gap-2">
                                <Input
                                    id="broadband_attachment"
                                    type="file"
                                    accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx,.xls,.xlsx"
                                    onChange={(e) => {
                                        const file = e.target.files?.[0] || null;
                                        setData('attachment', file);
                                    }}
                                    className="cursor-pointer file:cursor-pointer file:font-medium text-xs h-9 bg-background"
                                />
                                {data.attachment && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => {
                                            setData('attachment', null);
                                            const fileInput = document.getElementById('broadband_attachment') as HTMLInputElement;
                                            if (fileInput) fileInput.value = '';
                                        }}
                                        className="h-9 px-2 text-xs text-muted-foreground hover:text-foreground"
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                            <InputError message={errors.attachment} />
                        </div>
                    </div>

                    <DialogFooter className="pt-6 border-t border-border/60">
                        <DialogClose asChild>
                            <Button type="button" variant="outline" size="lg">Cancel</Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing} size="lg" className="gap-2 px-6 bg-purple-600 hover:bg-purple-700 text-white font-semibold">
                            {processing && <Loader2 className="h-4 w-4 animate-spin" />}
                            <span>{isEdit ? 'Save Changes' : 'Save Connection'}</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
