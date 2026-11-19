import { SearchableSelect } from '@/components/ui/searchable-select';
import PhoneNumberModal, { EmployeeItem, OptionItem, PhoneNumberRecord } from '@/components/telecom/PhoneNumberModal';
import TelecomHeaderNav from '@/components/telecom/TelecomHeaderNav';
import TablePagination from '@/components/table-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePermission } from '@/hooks/user-permissions';
import AppLayout from '@/layouts/app-layout';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeftRight, Building, Building2, Download, Edit, Paperclip, Plus, Trash2, User } from 'lucide-react';
import React, { useEffect, useState } from 'react';
import { toast } from 'sonner';

type PhoneNumber = {
    id: number;
    phone_number: string;
    account_number?: string | null;
    sim_card_number?: string | null;
    service_type: string;
    package_type?: string | null;
    package_start_date?: string | null;
    package_expiry_date?: string | null;
    monthly_cost: number;
    billing_type: string;
    assigned_type: string;
    status: string;
    issue_date?: string | null;
    renewal_date?: string | null;
    notes?: string | null;
    attachment_path?: string | null;
    attachment_url?: string | null;
    provider?: { id: number; name: string } | null;
    employee?: { id: number; first_name: string; last_name: string; employee_code: string } | null;
    branch?: { id: number; name: string } | null;
    department?: { id: number; name: string } | null;
};

type Paginated<T> = {
    data: T[];
    total: number;
    from: number;
    to: number;
    links: any[];
};

type PageProps = {
    phoneNumbers: Paginated<PhoneNumber>;
    providers: OptionItem[];
    branches: OptionItem[];
    departments: OptionItem[];
    employees?: EmployeeItem[];
    transfers?: any[];
    filters: {
        search?: string;
        telecom_provider_id?: string;
        service_type?: string;
        status?: string;
        assigned_type?: string;
        branch_id?: string;
    };
    flash?: { success?: string; error?: string };
};

export default function PhoneNumbersIndex({
    phoneNumbers,
    providers = [],
    branches = [],
    departments = [],
    employees = [],
    transfers = [],
    filters = {},
    flash,
}: PageProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [providerFilter, setProviderFilter] = useState(filters.telecom_provider_id ?? 'all');
    const [serviceTypeFilter, setServiceTypeFilter] = useState(filters.service_type ?? 'all');
    const [statusFilter, setStatusFilter] = useState(filters.status ?? 'all');
    const [assignedTypeFilter, setAssignedTypeFilter] = useState(filters.assigned_type ?? 'all');

    const [openAddModal, setOpenAddModal] = useState(false);
    const [editingRecord, setEditingRecord] = useState<PhoneNumberRecord | null>(null);
    const [transferringItem, setTransferringItem] = useState<PhoneNumber | null>(null);

    const transferForm = useForm({
        assigned_type: 'Employee',
        employee_id: '',
        branch_id: '',
        department_id: '',
        transfer_reason: '',
    });

    const { can } = usePermission();

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash?.success, flash?.error]);

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        const params: Record<string, string> = {};
        if (search) params.search = search;
        if (providerFilter !== 'all') params.telecom_provider_id = providerFilter;
        if (serviceTypeFilter !== 'all') params.service_type = serviceTypeFilter;
        if (statusFilter !== 'all') params.status = statusFilter;
        if (assignedTypeFilter !== 'all') params.assigned_type = assignedTypeFilter;

        router.get('/telecom/phone-numbers', params, { preserveState: true, preserveScroll: true });
    }

    function handleDelete(id: number, phone: string) {
        if (confirm(`Are you sure you want to delete phone number "${phone}"?`)) {
            router.delete(`/telecom/phone-numbers/${id}`, {
                onSuccess: () => toast.success('Phone number deleted.'),
                onError: () => toast.error('Failed to delete.'),
            });
        }
    }

    function getAssignedLabel(item: PhoneNumber) {
        if (item.assigned_type === 'Employee' && item.employee) {
            return (
                <div className="flex items-center gap-1.5 font-medium">
                    <User className="h-3.5 w-3.5 text-blue-500" />
                    <span>{item.employee.first_name} {item.employee.last_name}</span>
                </div>
            );
        }
        if (item.assigned_type === 'Branch' && item.branch) {
            return (
                <div className="flex items-center gap-1.5 font-medium">
                    <Building className="h-3.5 w-3.5 text-amber-500" />
                    <span>{item.branch.name}</span>
                </div>
            );
        }
        if (item.assigned_type === 'Department' && item.department) {
            return (
                <div className="flex items-center gap-1.5 font-medium">
                    <Building2 className="h-3.5 w-3.5 text-purple-500" />
                    <span>{item.department.name}</span>
                </div>
            );
        }
        return <span className="text-muted-foreground italic text-xs">Unassigned</span>;
    }

    function getStatusBadge(status: string) {
        switch (status) {
            case 'Active':
                return <Badge variant="default" className="text-xs font-normal">Active</Badge>;
            case 'Inactive':
                return <Badge variant="secondary" className="text-xs font-normal">Inactive</Badge>;
            case 'Suspended':
                return <Badge variant="outline" className="text-xs font-normal text-amber-600 dark:text-amber-400 border-amber-300">Suspended</Badge>;
            case 'Cancelled':
                return <Badge variant="destructive" className="text-xs font-normal">Cancelled</Badge>;
            default:
                return <Badge variant="outline" className="text-xs font-normal">{status}</Badge>;
        }
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Telecom Management', href: '/telecom/dashboard' },
                { title: 'Phone Numbers', href: '/telecom/phone-numbers' },
            ]}
        >
            <Head title="Company Phone Numbers & Packages" />

            <div className="flex h-full flex-1 flex-col gap-5 p-4 md:p-6 max-w-7xl">
                {/* Unified Header Navigation */}
                <TelecomHeaderNav
                    title="Phone Lines & SIMs"
                    description="Manage company voice lines, SIM cards, monthly plans, and user allocations"
                >
                    <div className="flex items-center gap-2">
                        {can('telecom.export') && (
                            <a href={`/telecom/phone-numbers/export?search=${encodeURIComponent(search)}`}>
                                <Button variant="outline" size="sm" className="gap-1.5 h-8 text-xs">
                                    <Download className="h-3.5 w-3.5" /> Export CSV
                                </Button>
                            </a>
                        )}
                        {can('telecom.phone.create') && (
                            <Button size="sm" onClick={() => setOpenAddModal(true)} className="gap-1.5 h-8 text-xs">
                                <Plus className="h-3.5 w-3.5" /> Add Phone Line
                            </Button>
                        )}
                    </div>
                </TelecomHeaderNav>

                <Card className="border shadow-none">
                    {/* Clean filter controls */}
                    <div className="p-4 border-b">
                        <form className="grid gap-3 sm:grid-cols-2 md:grid-cols-5" onSubmit={handleSearch}>
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search number, SIM, user..."
                                className="h-9 text-xs"
                            />
                            <Select value={providerFilter} onValueChange={setProviderFilter}>
                                <SelectTrigger className="h-9 text-xs">
                                    <SelectValue placeholder="All Providers" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Providers</SelectItem>
                                    {providers.map((p) => (
                                        <SelectItem key={p.id} value={String(p.id)}>{p.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select value={serviceTypeFilter} onValueChange={setServiceTypeFilter}>
                                <SelectTrigger className="h-9 text-xs">
                                    <SelectValue placeholder="All Service Types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Service Types</SelectItem>
                                    <SelectItem value="Mobile Voice">Mobile Voice</SelectItem>
                                    <SelectItem value="Mobile Data">Mobile Data</SelectItem>
                                    <SelectItem value="Fixed Line">Fixed Line</SelectItem>
                                    <SelectItem value="CUG">CUG (Group)</SelectItem>
                                    <SelectItem value="Shortcode">Shortcode</SelectItem>
                                </SelectContent>
                            </Select>

                            <Select value={statusFilter} onValueChange={setStatusFilter}>
                                <SelectTrigger className="h-9 text-xs">
                                    <SelectValue placeholder="All Statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="Active">Active</SelectItem>
                                    <SelectItem value="Suspended">Suspended</SelectItem>
                                    <SelectItem value="Inactive">Inactive</SelectItem>
                                    <SelectItem value="Cancelled">Cancelled</SelectItem>
                                </SelectContent>
                            </Select>

                            <Button type="submit" variant="secondary" className="h-9 text-xs">Filter</Button>
                        </form>
                    </div>

                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="text-xs">#</TableHead>
                                    <TableHead className="text-xs">Phone Number</TableHead>
                                    <TableHead className="text-xs">Provider</TableHead>
                                    <TableHead className="text-xs">Service & Package</TableHead>
                                    <TableHead className="text-xs">Billing & Cost</TableHead>
                                    <TableHead className="text-xs">Assigned To</TableHead>
                                    <TableHead className="text-xs">Status</TableHead>
                                    <TableHead className="text-xs text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {phoneNumbers.data.map((item, idx) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="text-xs font-mono text-muted-foreground">{(phoneNumbers.from ?? 0) + idx}</TableCell>
                                        <TableCell className="text-xs font-medium font-mono">
                                            <div>{item.phone_number}</div>
                                            {item.account_number && (
                                                <div className="text-[11px] text-muted-foreground font-sans">Acc: {item.account_number}</div>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-xs">{item.provider?.name ?? 'N/A'}</TableCell>
                                        <TableCell className="text-xs">
                                            <div className="font-medium">{item.service_type}</div>
                                            {item.package_type && (
                                                <div className="text-[11px] text-muted-foreground">{item.package_type}</div>
                                            )}
                                            {(item.package_start_date || item.package_expiry_date || item.renewal_date) && (
                                                <div className="text-[11px] text-muted-foreground mt-0.5 font-mono">
                                                    {item.package_start_date ?? item.issue_date ?? 'N/A'} &rarr; {item.package_expiry_date ?? item.renewal_date ?? 'N/A'}
                                                </div>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-xs">
                                            <div className="font-mono font-medium">{Number(item.monthly_cost).toFixed(2)} ETB</div>
                                            <div className="text-[11px] text-muted-foreground">{item.billing_type}</div>
                                        </TableCell>
                                        <TableCell className="text-xs max-w-xs truncate">
                                            {getAssignedLabel(item)}
                                        </TableCell>
                                        <TableCell className="text-xs">{getStatusBadge(item.status)}</TableCell>
                                        <TableCell className="text-right text-xs">
                                            <div className="flex items-center justify-end gap-1">
                                                {item.attachment_url && (
                                                    <a
                                                        href={item.attachment_url}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        title="View Attached Document"
                                                        className="h-7 w-7 inline-flex items-center justify-center rounded-md text-emerald-600 hover:text-emerald-700 hover:bg-emerald-500/10 transition-colors"
                                                    >
                                                        <Paperclip className="h-3.5 w-3.5" />
                                                    </a>
                                                )}
                                                {can('telecom.phone.transfer') && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Transfer Line"
                                                        className="h-7 w-7 text-muted-foreground hover:text-foreground"
                                                        onClick={() => {
                                                            setTransferringItem(item);
                                                            transferForm.setData({
                                                                assigned_type: item.assigned_type || 'Employee',
                                                                employee_id: item.employee?.id ? String(item.employee.id) : '',
                                                                branch_id: item.branch?.id ? String(item.branch.id) : '',
                                                                department_id: item.department?.id ? String(item.department.id) : '',
                                                                transfer_reason: '',
                                                            });
                                                        }}
                                                    >
                                                        <ArrowLeftRight className="h-3.5 w-3.5" />
                                                    </Button>
                                                )}
                                                {can('telecom.phone.edit') && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Edit"
                                                        className="h-7 w-7 text-muted-foreground hover:text-foreground"
                                                        onClick={() => setEditingRecord({
                                                            id: item.id,
                                                            phone_number: item.phone_number,
                                                            account_number: item.account_number || '',
                                                            sim_card_number: item.sim_card_number || '',
                                                            telecom_provider_id: item.provider?.id || '',
                                                            service_type: item.service_type,
                                                            package_type: item.package_type || '',
                                                            package_start_date: item.package_start_date || '',
                                                            package_expiry_date: item.package_expiry_date || item.renewal_date || '',
                                                            monthly_cost: item.monthly_cost,
                                                            billing_type: item.billing_type,
                                                            assigned_type: item.assigned_type,
                                                            employee_id: item.employee?.id || '',
                                                            branch_id: item.branch?.id || '',
                                                            department_id: item.department?.id || '',
                                                            status: item.status,
                                                            issue_date: item.issue_date || '',
                                                            renewal_date: item.renewal_date || '',
                                                            notes: item.notes || '',
                                                            attachment_path: item.attachment_path || null,
                                                            attachment_url: item.attachment_url || null,
                                                        })}
                                                    >
                                                        <Edit className="h-3.5 w-3.5" />
                                                    </Button>
                                                )}
                                                {can('telecom.phone.delete') && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7 text-muted-foreground hover:text-destructive"
                                                        onClick={() => handleDelete(item.id, item.phone_number)}
                                                        title="Delete"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {phoneNumbers.data.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-center py-8 text-xs text-muted-foreground">
                                            No company phone numbers found matching the criteria.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                    <TablePagination from={phoneNumbers.from} to={phoneNumbers.to} total={phoneNumbers.total} links={phoneNumbers.links} />
                </Card>

                {/* Transfer History Log Card */}
                {transfers && transfers.length > 0 && (
                    <Card className="border shadow-sm bg-card">
                        <CardHeader className="py-3 bg-amber-50/50 dark:bg-amber-950/20 border-b">
                            <CardTitle className="text-sm font-bold flex items-center gap-2 text-amber-900 dark:text-amber-300">
                                <ArrowLeftRight className="h-4 w-4 text-amber-600" />
                                Recent SIM Card & Line Transfer History Log
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/30 text-xs">
                                        <TableHead>Line / SIM Reference</TableHead>
                                        <TableHead>Transferred To</TableHead>
                                        <TableHead>Reason</TableHead>
                                        <TableHead>Transferred By</TableHead>
                                        <TableHead>Date & Time</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transfers.map((tr: any) => (
                                        <TableRow key={tr.id} className="text-xs">
                                            <TableCell className="font-semibold text-amber-700 dark:text-amber-400">{tr.reference_number}</TableCell>
                                            <TableCell>
                                                {tr.to_employee ? `${tr.to_employee.first_name} ${tr.to_employee.last_name} (Employee)` :
                                                 tr.to_branch ? `${tr.to_branch.name} (Branch)` :
                                                 tr.to_department ? `${tr.to_department.name} (Department)` : 'Unassigned'}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground max-w-xs truncate">{tr.transfer_reason || '-'}</TableCell>
                                            <TableCell>{tr.transferred_by_user?.name || 'System User'}</TableCell>
                                            <TableCell className="text-muted-foreground font-mono">{new Date(tr.created_at).toLocaleString()}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Enhanced Transfer SIM Card / Line Dialog */}
            <Dialog open={!!transferringItem} onOpenChange={(val) => !val && setTransferringItem(null)}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2 text-amber-800 dark:text-amber-300">
                            <ArrowLeftRight className="h-5 w-5 text-amber-600" />
                            Transfer SIM Card / Phone Line
                        </DialogTitle>
                        <DialogDescription>
                            Reassign line or SIM card to a new employee, branch, or department.
                        </DialogDescription>
                    </DialogHeader>

                    {transferringItem && (
                        <div className="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 rounded-lg space-y-1 text-xs">
                            <div className="font-bold text-amber-900 dark:text-amber-300 flex items-center justify-between">
                                <span className="font-mono text-sm">{transferringItem.phone_number}</span>
                                {transferringItem.account_number && (
                                    <span className="font-mono text-[11px] text-amber-700 dark:text-amber-400">Acc: {transferringItem.account_number}</span>
                                )}
                            </div>
                            <div className="flex items-center gap-2 text-muted-foreground pt-1">
                                <span>
                                    Current: <strong className="text-foreground">{transferringItem.employee ? `${transferringItem.employee.first_name} ${transferringItem.employee.last_name} (Employee)` : transferringItem.branch?.name ? `${transferringItem.branch.name} (Branch)` : transferringItem.department?.name ? `${transferringItem.department.name} (Department)` : 'Unassigned'}</strong>
                                </span>
                                <ArrowLeftRight className="h-3.5 w-3.5 text-amber-600 shrink-0" />
                                <span className="font-semibold text-amber-700 dark:text-amber-300">New Target</span>
                            </div>
                        </div>
                    )}

                    <form onSubmit={(e) => {
                        e.preventDefault();
                        if (!transferringItem) return;
                        transferForm.post(route('telecom.phone-numbers.transfer', transferringItem.id), {
                            onSuccess: () => {
                                toast.success('SIM Card / Phone line transferred successfully!');
                                setTransferringItem(null);
                                transferForm.reset();
                            },
                            onError: () => toast.error('Failed to transfer line.'),
                        });
                    }} className="space-y-4 py-2">
                        <div className="space-y-1.5">
                            <Label>Assignment Category</Label>
                            <Select
                                value={transferForm.data.assigned_type}
                                onValueChange={(val) => transferForm.setData('assigned_type', val)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select Category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Employee">Specific Employee</SelectItem>
                                    <SelectItem value="Branch">Branch / Office</SelectItem>
                                    <SelectItem value="Department">Department</SelectItem>
                                    <SelectItem value="Unassigned">Unassigned / Reserve</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {transferForm.data.assigned_type === 'Employee' && (
                            <div className="space-y-1.5">
                                <Label>Search & Select Target Employee</Label>
                                <SearchableSelect
                                    options={(employees || []).map((emp) => ({
                                        id: String(emp.id),
                                        name: `${emp.first_name} ${emp.last_name} ${emp.employee_code ? `(${emp.employee_code})` : ''}`,
                                    }))}
                                    value={transferForm.data.employee_id}
                                    onValueChange={(val) => transferForm.setData('employee_id', val)}
                                    placeholder="Search target employee..."
                                    searchPlaceholder="Type employee name or code..."
                                />
                            </div>
                        )}

                        {transferForm.data.assigned_type === 'Branch' && (
                            <div className="space-y-1.5">
                                <Label>Search & Select Target Branch</Label>
                                <SearchableSelect
                                    options={branches.map((b) => ({ id: String(b.id), name: b.name }))}
                                    value={transferForm.data.branch_id}
                                    onValueChange={(val) => transferForm.setData('branch_id', val)}
                                    placeholder="Search target branch..."
                                    searchPlaceholder="Type branch name..."
                                />
                            </div>
                        )}

                        {transferForm.data.assigned_type === 'Department' && (
                            <div className="space-y-1.5">
                                <Label>Search & Select Target Department</Label>
                                <SearchableSelect
                                    options={departments.map((d) => ({ id: String(d.id), name: d.name }))}
                                    value={transferForm.data.department_id}
                                    onValueChange={(val) => transferForm.setData('department_id', val)}
                                    placeholder="Search target department..."
                                    searchPlaceholder="Type department name..."
                                />
                            </div>
                        )}

                        <div className="space-y-1.5">
                            <Label>Reason for Transfer (Optional)</Label>
                            <Input
                                value={transferForm.data.transfer_reason}
                                onChange={(e) => transferForm.setData('transfer_reason', e.target.value)}
                                placeholder="e.g. Employee reassignment or device swap"
                            />
                        </div>

                        <DialogFooter className="pt-2">
                            <Button type="button" variant="outline" onClick={() => setTransferringItem(null)}>
                                Cancel
                            </Button>
                            <Button type="submit" className="bg-amber-600 hover:bg-amber-700 text-white font-semibold" disabled={transferForm.processing}>
                                Confirm Transfer
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Quick Add / Edit Phone Number Modal */}
            <PhoneNumberModal
                open={openAddModal || !!editingRecord}
                onOpenChange={(val) => {
                    if (!val) {
                        setOpenAddModal(false);
                        setEditingRecord(null);
                    }
                }}
                initialData={editingRecord}
                providers={providers}
                branches={branches}
                departments={departments}
                employees={employees}
            />
        </AppLayout>
    );
}
