import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/searchable-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Calendar, ExternalLink, FileText, Paperclip, Save, Trash2, X } from 'lucide-react';
import { FormEvent, useState } from 'react';

type Option = { id: number; name: string };

type Broadband = {
    id: number;
    account_number?: string | null;
    service_number?: string | null;
    connection_name: string;
    connection_type: string;
    telecom_provider_id?: number | null;
    package_type?: string | null;
    package_start_date?: string | null;
    package_expiry_date?: string | null;
    bandwidth_speed?: string | null;
    monthly_cost: number;
    billing_type: string;
    branch_id?: number | null;
    department_id?: number | null;
    installation_address?: string | null;
    ip_address?: string | null;
    equipment_details?: string | null;
    contract_start_date?: string | null;
    contract_expiry_date?: string | null;
    status: string;
    notes?: string | null;
    attachment_path?: string | null;
    attachment_url?: string | null;
};

type PageProps = {
    broadband: Broadband;
    providers: Option[];
    branches: Option[];
    departments: Option[];
};

export default function BroadbandsEdit({ broadband, providers = [], branches = [], departments = [] }: PageProps) {
    const [durationMonths, setDurationMonths] = useState<number>(1);

    const { data, setData, put, processing, errors } = useForm({
        connection_name: broadband.connection_name ?? '',
        account_number: broadband.account_number ?? '',
        service_number: broadband.service_number ?? '',
        connection_type: broadband.connection_type ?? 'WTTx (Fixed Wireless)',
        telecom_provider_id: broadband.telecom_provider_id ? String(broadband.telecom_provider_id) : '',
        package_type: broadband.package_type ?? '',
        package_start_date: broadband.package_start_date ?? broadband.contract_start_date ?? '',
        package_expiry_date: broadband.package_expiry_date ?? broadband.contract_expiry_date ?? '',
        bandwidth_speed: broadband.bandwidth_speed ?? '',
        monthly_cost: String(broadband.monthly_cost ?? '0.00'),
        billing_type: broadband.billing_type ?? 'Postpaid',
        branch_id: broadband.branch_id ? String(broadband.branch_id) : '',
        department_id: broadband.department_id ? String(broadband.department_id) : '',
        installation_address: broadband.installation_address ?? '',
        ip_address: broadband.ip_address ?? '',
        equipment_details: broadband.equipment_details ?? '',
        contract_start_date: broadband.contract_start_date ?? '',
        contract_expiry_date: broadband.contract_expiry_date ?? '',
        status: broadband.status ?? 'Active',
        notes: broadband.notes ?? '',
        attachment: null as File | null,
        remove_attachment: false,
    });

    const calculateExpiryDate = (startDateStr: string, months: number) => {
        if (!startDateStr) return '';
        const d = new Date(startDateStr);
        if (isNaN(d.getTime())) return '';
        d.setMonth(d.getMonth() + months);
        return d.toISOString().split('T')[0];
    };

    const handleStartDateChange = (startDateStr: string) => {
        const calculatedExpiry = calculateExpiryDate(startDateStr, durationMonths);
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

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        router.post(`/telecom/broadbands/${broadband.id}`, {
            ...data,
            _method: 'PUT',
        }, { forceFormData: true });
    }

    const formattedBranches = branches.map((b) => ({
        id: String(b.id),
        name: b.name,
    }));

    const formattedDepartments = departments.map((d) => ({
        id: String(d.id),
        name: d.name,
    }));

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Telecom Management', href: '/telecom/dashboard' },
                { title: 'Broadband & WTTx', href: '/telecom/broadbands' },
                { title: 'Edit Broadband / WTTx', href: `/telecom/broadbands/${broadband.id}/edit` },
            ]}
        >
            <Head title={`Edit Connection - ${broadband.connection_name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto w-full">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Edit Broadband / WTTx / Data SIM: {broadband.connection_name}</h1>
                        <p className="text-sm text-muted-foreground">Update connection parameters, service number, speed, IP address, and contract info.</p>
                    </div>
                    <Link href="/telecom/broadbands">
                        <Button variant="outline" size="sm" className="gap-1.5">
                            <ArrowLeft className="h-4 w-4" /> Back to List
                        </Button>
                    </Link>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base font-semibold">Connection Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid gap-4 md:grid-cols-2">
                                {/* Connection Name */}
                                <div>
                                    <Label htmlFor="connection_name">Connection Name / Identifier <span className="text-rose-500">*</span></Label>
                                    <Input
                                        id="connection_name"
                                        value={data.connection_name}
                                        onChange={(e) => setData('connection_name', e.target.value)}
                                        required
                                    />
                                    {errors.connection_name && <p className="text-xs text-rose-500 mt-1">{errors.connection_name}</p>}
                                </div>

                                {/* Connection Type */}
                                <div>
                                    <Label htmlFor="connection_type">Connection Technology / Type <span className="text-rose-500">*</span></Label>
                                    <Select
                                        value={data.connection_type}
                                        onValueChange={(val) => setData('connection_type', val)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select Connection Type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="WTTx (Fixed Wireless)">WTTx (Fixed Wireless 4G/5G)</SelectItem>
                                            <SelectItem value="Data Sim Card">Data SIM Card</SelectItem>
                                            <SelectItem value="Fiber Broadband (FTTH/FTTB)">Fiber Broadband (FTTH/FTTB)</SelectItem>
                                            <SelectItem value="ADSL / Copper Broadband">ADSL / Copper Broadband</SelectItem>
                                            <SelectItem value="Dedicated Leased Line">Dedicated Leased Line</SelectItem>
                                            <SelectItem value="VSAT Satellite">VSAT Satellite</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Service Number */}
                                <div>
                                    <Label htmlFor="service_number">Service Number / SIM Phone Number</Label>
                                    <Input
                                        id="service_number"
                                        value={data.service_number}
                                        onChange={(e) => setData('service_number', e.target.value)}
                                        placeholder="e.g. 0911XXXXXX or SIM Line No."
                                    />
                                </div>

                                {/* Account / Service ID */}
                                <div>
                                    <Label htmlFor="account_number">Account / Circuit ID</Label>
                                    <Input
                                        id="account_number"
                                        value={data.account_number}
                                        onChange={(e) => setData('account_number', e.target.value)}
                                    />
                                </div>

                                {/* Provider */}
                                <div>
                                    <Label htmlFor="telecom_provider_id">Telecom Provider</Label>
                                    <Select
                                        value={data.telecom_provider_id}
                                        onValueChange={(val) => setData('telecom_provider_id', val)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select Provider" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {providers.map((p) => (
                                                <SelectItem key={p.id} value={String(p.id)}>{p.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Location / Branch (Searchable) */}
                                <div className="space-y-1">
                                    <Label htmlFor="branch_id">Search & Select Assigned Branch</Label>
                                    <SearchableSelect
                                        options={formattedBranches}
                                        value={data.branch_id}
                                        onValueChange={(val) => setData('branch_id', val)}
                                        placeholder="Search branch name..."
                                        searchPlaceholder="Search branch..."
                                        className="w-full bg-white dark:bg-slate-950"
                                    />
                                </div>

                                {/* Package / Plan Name */}
                                <div>
                                    <Label htmlFor="package_type">Package / Plan Name</Label>
                                    <Input
                                        id="package_type"
                                        value={data.package_type}
                                        onChange={(e) => setData('package_type', e.target.value)}
                                    />
                                </div>

                                {/* Bandwidth / Speed */}
                                <div>
                                    <Label htmlFor="bandwidth_speed">Bandwidth Speed</Label>
                                    <Input
                                        id="bandwidth_speed"
                                        value={data.bandwidth_speed}
                                        onChange={(e) => setData('bandwidth_speed', e.target.value)}
                                    />
                                </div>

                                {/* Package Start Date */}
                                <div>
                                    <Label htmlFor="package_start_date" className="flex items-center gap-1.5 font-semibold text-purple-700 dark:text-purple-400">
                                        <Calendar className="h-4 w-4" /> Package Start Date
                                    </Label>
                                    <Input
                                        id="package_start_date"
                                        type="date"
                                        value={data.package_start_date}
                                        onChange={(e) => handleStartDateChange(e.target.value)}
                                        className="mt-1 border-purple-300 dark:border-purple-800"
                                    />
                                </div>

                                {/* Package Duration Selector */}
                                <div>
                                    <Label className="font-semibold text-slate-700 dark:text-slate-300">Package Duration</Label>
                                    <Select
                                        value={String(durationMonths)}
                                        onValueChange={(val) => handleDurationChange(parseInt(val, 10))}
                                    >
                                        <SelectTrigger className="mt-1">
                                            <SelectValue placeholder="Select Duration" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">1 Month (+30 Days)</SelectItem>
                                            <SelectItem value="3">3 Months</SelectItem>
                                            <SelectItem value="6">6 Months</SelectItem>
                                            <SelectItem value="12">1 Year (12 Months)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Calculated Package Expire Date */}
                                <div>
                                    <Label htmlFor="package_expiry_date" className="flex items-center gap-1.5 font-semibold text-emerald-700 dark:text-emerald-400">
                                        <Calendar className="h-4 w-4" /> Package Expire Date
                                    </Label>
                                    <Input
                                        id="package_expiry_date"
                                        type="date"
                                        value={data.package_expiry_date}
                                        onChange={(e) => setData('package_expiry_date', e.target.value)}
                                        className="mt-1 border-emerald-300 dark:border-emerald-800 font-semibold"
                                    />
                                    <p className="text-[11px] text-muted-foreground mt-0.5">Calculated based on start date & duration</p>
                                </div>

                                {/* Billing Type */}
                                <div>
                                    <Label htmlFor="billing_type">Billing Type</Label>
                                    <Select
                                        value={data.billing_type}
                                        onValueChange={(val) => setData('billing_type', val)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Billing Type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Postpaid">Postpaid</SelectItem>
                                            <SelectItem value="Prepaid">Prepaid</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Monthly Cost */}
                                <div>
                                    <Label htmlFor="monthly_cost">Monthly Cost (ETB) <span className="text-rose-500">*</span></Label>
                                    <Input
                                        id="monthly_cost"
                                        type="number"
                                        step="0.01"
                                        value={data.monthly_cost}
                                        onChange={(e) => setData('monthly_cost', e.target.value)}
                                        required
                                    />
                                </div>

                                {/* Department (Searchable) */}
                                <div className="space-y-1">
                                    <Label htmlFor="department_id">Search & Select Department</Label>
                                    <SearchableSelect
                                        options={formattedDepartments}
                                        value={data.department_id}
                                        onValueChange={(val) => setData('department_id', val)}
                                        placeholder="Search department name..."
                                        searchPlaceholder="Search department..."
                                        className="w-full bg-white dark:bg-slate-950"
                                    />
                                </div>

                                {/* Static IP */}
                                <div>
                                    <Label htmlFor="ip_address">Static IP / Gateway IP</Label>
                                    <Input
                                        id="ip_address"
                                        value={data.ip_address}
                                        onChange={(e) => setData('ip_address', e.target.value)}
                                    />
                                </div>

                                {/* Installation Address */}
                                <div>
                                    <Label htmlFor="installation_address">Installation Site Address</Label>
                                    <Input
                                        id="installation_address"
                                        value={data.installation_address}
                                        onChange={(e) => setData('installation_address', e.target.value)}
                                    />
                                </div>

                                {/* Status */}
                                <div>
                                    <Label htmlFor="status">Status</Label>
                                    <Select
                                        value={data.status}
                                        onValueChange={(val) => setData('status', val)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Active">Active</SelectItem>
                                            <SelectItem value="Suspended">Suspended</SelectItem>
                                            <SelectItem value="Inactive">Inactive</SelectItem>
                                            <SelectItem value="Pending Installation">Pending Installation</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            {/* Equipment Details */}
                            <div>
                                <Label htmlFor="equipment_details">Router / Modem / CPE Details</Label>
                                <Textarea
                                    id="equipment_details"
                                    rows={2}
                                    value={data.equipment_details}
                                    onChange={(e) => setData('equipment_details', e.target.value)}
                                />
                            </div>

                            {/* Notes */}
                            <div>
                                <Label htmlFor="notes">Notes / Additional Remarks</Label>
                                <Textarea
                                    id="notes"
                                    rows={2}
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                />
                            </div>

                            {/* Attachment */}
                            <div className="space-y-2 p-3.5 rounded-lg border border-border/70 bg-muted/20">
                                <Label className="text-xs font-semibold flex items-center justify-between">
                                    <span className="flex items-center gap-1.5">
                                        <Paperclip className="h-4 w-4 text-primary" />
                                        Attachment / Contract Agreement (Broadband SLA, Invoice, Form)
                                    </span>
                                    <span className="text-[11px] text-muted-foreground font-normal">
                                        PDF, Images, Word up to 10MB
                                    </span>
                                </Label>

                                {broadband.attachment_url && !data.remove_attachment && (
                                    <div className="flex items-center justify-between p-2.5 rounded-md border border-border bg-background text-xs">
                                        <div className="flex items-center gap-2 truncate">
                                            <FileText className="h-4 w-4 text-emerald-600 shrink-0" />
                                            <span className="font-medium truncate">Current Attachment</span>
                                            <a
                                                href={broadband.attachment_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="text-primary underline hover:text-primary/80 inline-flex items-center gap-1 text-[11px]"
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
                                        id="attachment"
                                        type="file"
                                        accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx,.xls,.xlsx"
                                        onChange={(e) => setData('attachment', e.target.files?.[0] || null)}
                                        className="cursor-pointer file:cursor-pointer file:font-medium text-xs h-9 bg-background"
                                    />
                                    {data.attachment && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => {
                                                setData('attachment', null);
                                                const fileInput = document.getElementById('attachment') as HTMLInputElement;
                                                if (fileInput) fileInput.value = '';
                                            }}
                                            className="h-9 px-2 text-xs text-muted-foreground hover:text-foreground"
                                        >
                                            <X className="h-4 w-4" />
                                        </Button>
                                    )}
                                </div>
                                {errors.attachment && <p className="text-xs text-destructive">{errors.attachment}</p>}
                            </div>

                            <div className="flex items-center justify-end gap-2 pt-4">
                                <Link href="/telecom/broadbands">
                                    <Button variant="outline" type="button">Cancel</Button>
                                </Link>
                                <Button type="submit" disabled={processing} className="gap-1.5">
                                    <Save className="h-4 w-4" /> Update Connection
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
