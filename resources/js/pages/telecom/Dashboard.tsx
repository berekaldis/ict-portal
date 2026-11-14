import BroadbandModal from '@/components/telecom/BroadbandModal';
import PhoneNumberModal, { EmployeeItem, OptionItem } from '@/components/telecom/PhoneNumberModal';
import TelecomHeaderNav from '@/components/telecom/TelecomHeaderNav';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePermission } from '@/hooks/user-permissions';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import {
    Building2,
    DollarSign,
    Phone,
    Plus,
    Radio,
    Signal,
    Wifi,
} from 'lucide-react';
import React, { useState } from 'react';

type Stats = {
    total_phones: number;
    active_phones: number;
    total_phone_cost: number;
    total_broadbands: number;
    active_broadbands: number;
    wttx_count: number;
    total_broadband_cost: number;
    total_monthly_spend: number;
    total_providers: number;
};

type PhoneTypeStat = {
    service_type: string;
    count: number;
    total_cost: number;
};

type BroadbandTypeStat = {
    connection_type: string;
    count: number;
    total_cost: number;
};

type ProviderStat = {
    id: number;
    name: string;
    code?: string;
    phone_numbers_count: number;
    broadbands_count: number;
};

type BranchStat = {
    id: number;
    name: string;
    phone_count: number;
    broadband_count: number;
    total_cost: number;
};

type ExpiringBroadband = {
    id: number;
    connection_name: string;
    connection_type: string;
    contract_expiry_date: string;
    monthly_cost: number;
    provider?: { id: number; name: string };
    branch?: { id: number; name: string };
};

type PageProps = {
    stats: Stats;
    phone_types: PhoneTypeStat[];
    broadband_types: BroadbandTypeStat[];
    provider_stats: ProviderStat[];
    branch_stats?: BranchStat[];
    expiring_broadbands: ExpiringBroadband[];
    providers?: OptionItem[];
    branches?: OptionItem[];
    departments?: OptionItem[];
    employees?: EmployeeItem[];
};

export default function TelecomDashboard({
    stats,
    phone_types,
    broadband_types,
    provider_stats,
    branch_stats = [],
    expiring_broadbands,
    providers = [],
    branches = [],
    departments = [],
    employees = [],
}: PageProps) {
    const [openAddPhoneModal, setOpenAddPhoneModal] = useState(false);
    const [openAddBroadbandModal, setOpenAddBroadbandModal] = useState(false);
    const { can } = usePermission();

    const formatETB = (amount: number) => {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'ETB', minimumFractionDigits: 2 }).format(amount);
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Telecom Management', href: '/telecom/dashboard' },
                { title: 'Overview', href: '/telecom/dashboard' },
            ]}
        >
            <Head title="Telecom Management Overview" />

            <div className="flex h-full flex-1 flex-col gap-5 p-4 md:p-6 max-w-7xl">
                {/* Clean Header Navigation */}
                <TelecomHeaderNav
                    totalMonthlySpend={stats.total_monthly_spend}
                >
                    {can('telecom.phone.create') && (
                        <Button size="sm" variant="outline" onClick={() => setOpenAddPhoneModal(true)} className="gap-1.5 h-8 text-xs">
                            <Plus className="h-3.5 w-3.5" /> Add Phone Line
                        </Button>
                    )}
                    {can('telecom.broadband.create') && (
                        <Button size="sm" onClick={() => setOpenAddBroadbandModal(true)} className="gap-1.5 h-8 text-xs">
                            <Plus className="h-3.5 w-3.5" /> Add Broadband
                        </Button>
                    )}
                </TelecomHeaderNav>

                {/* Clean KPI Cards */}
                <div className="grid gap-4 grid-cols-2 lg:grid-cols-4">
                    <Card className="border shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-medium text-muted-foreground">Phone Numbers & SIMs</CardTitle>
                            <Phone className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_phones}</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {stats.active_phones} active • {formatETB(stats.total_phone_cost)}/mo
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-medium text-muted-foreground">Broadband & WTTx</CardTitle>
                            <Wifi className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_broadbands}</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {stats.wttx_count} WTTx • {formatETB(stats.total_broadband_cost)}/mo
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-medium text-muted-foreground">Total Monthly Spend</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold font-mono">
                                {formatETB(stats.total_monthly_spend)}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">
                                Combined recurring services
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-medium text-muted-foreground">Telecom Providers</CardTitle>
                            <Signal className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_providers}</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                Active service partners
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Section Breakdowns: Services & Connections */}
                <div className="grid gap-5 md:grid-cols-2">
                    {/* Phone Lines Breakdown */}
                    <Card className="border shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between py-3 border-b">
                            <CardTitle className="text-sm font-medium">Phone Numbers by Service Type</CardTitle>
                            <Link href="/telecom/phone-numbers" className="text-xs text-primary hover:underline">
                                View details &rarr;
                            </Link>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="text-xs">Service Type</TableHead>
                                        <TableHead className="text-center text-xs">Lines</TableHead>
                                        <TableHead className="text-right text-xs">Monthly Cost</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {phone_types.map((type) => (
                                        <TableRow key={type.service_type}>
                                            <TableCell className="text-xs font-medium">{type.service_type}</TableCell>
                                            <TableCell className="text-center text-xs">
                                                <Badge variant="secondary" className="font-mono text-[11px] font-normal">{type.count}</Badge>
                                            </TableCell>
                                            <TableCell className="text-right text-xs font-mono">{formatETB(type.total_cost)}</TableCell>
                                        </TableRow>
                                    ))}
                                    {phone_types.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={3} className="text-center text-xs text-muted-foreground py-4">No phone lines recorded yet.</TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    {/* Broadband & WTTx Breakdown */}
                    <Card className="border shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between py-3 border-b">
                            <CardTitle className="text-sm font-medium">Internet Connections by Type</CardTitle>
                            <Link href="/telecom/broadbands" className="text-xs text-primary hover:underline">
                                View details &rarr;
                            </Link>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="text-xs">Connection Type</TableHead>
                                        <TableHead className="text-center text-xs">Connections</TableHead>
                                        <TableHead className="text-right text-xs">Monthly Cost</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {broadband_types.map((type) => (
                                        <TableRow key={type.connection_type}>
                                            <TableCell className="text-xs font-medium">{type.connection_type}</TableCell>
                                            <TableCell className="text-center text-xs">
                                                <Badge variant="secondary" className="font-mono text-[11px] font-normal">{type.count}</Badge>
                                            </TableCell>
                                            <TableCell className="text-right text-xs font-mono">{formatETB(type.total_cost)}</TableCell>
                                        </TableRow>
                                    ))}
                                    {broadband_types.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={3} className="text-center text-xs text-muted-foreground py-4">No broadband / WTTx connections recorded yet.</TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                {/* Section Breakdowns: Providers & Branch Summary */}
                <div className="grid gap-5 md:grid-cols-2">
                    {/* Telecom Service Providers */}
                    <Card className="border shadow-none">
                        <CardHeader className="flex flex-row items-center justify-between py-3 border-b">
                            <CardTitle className="text-sm font-medium">Telecom Providers</CardTitle>
                            <Link href="/telecom/providers" className="text-xs text-primary hover:underline">
                                Manage providers &rarr;
                            </Link>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="text-xs">Provider</TableHead>
                                        <TableHead className="text-center text-xs">Phone Lines</TableHead>
                                        <TableHead className="text-center text-xs">Broadbands</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {provider_stats.map((p) => (
                                        <TableRow key={p.id}>
                                            <TableCell className="text-xs font-medium">{p.name}</TableCell>
                                            <TableCell className="text-center text-xs font-mono">{p.phone_numbers_count}</TableCell>
                                            <TableCell className="text-center text-xs font-mono">{p.broadbands_count}</TableCell>
                                        </TableRow>
                                    ))}
                                    {provider_stats.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={3} className="text-center text-xs text-muted-foreground py-4">No providers found.</TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    {/* Upcoming Contract Renewals */}
                    <Card className="border shadow-none">
                        <CardHeader className="py-3 border-b">
                            <CardTitle className="text-sm font-medium">Upcoming Contract Renewals</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="text-xs">Connection</TableHead>
                                        <TableHead className="text-xs">Branch</TableHead>
                                        <TableHead className="text-right text-xs">Expiry Date</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {expiring_broadbands.map((b) => (
                                        <TableRow key={b.id}>
                                            <TableCell className="text-xs font-medium">
                                                <div>{b.connection_name}</div>
                                                <div className="text-[11px] text-muted-foreground">{b.connection_type}</div>
                                            </TableCell>
                                            <TableCell className="text-xs">{b.branch?.name ?? '-'}</TableCell>
                                            <TableCell className="text-right text-xs">
                                                <Badge variant="outline" className="text-xs font-mono font-normal">{b.contract_expiry_date}</Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    {expiring_broadbands.length === 0 && (
                                        <TableRow>
                                            <TableCell colSpan={3} className="text-center text-xs text-muted-foreground py-4">No contracts expiring within 45 days.</TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                {/* Clean Branch Breakdown Table (instead of 20+ cluttered cards) */}
                {branch_stats.length > 0 && (
                    <Card className="border shadow-none">
                        <CardHeader className="py-3 border-b flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-sm font-medium">Branch Telecom Expenses</CardTitle>
                                <p className="text-xs text-muted-foreground">Breakdown of recurring lines and bandwidth by branch location</p>
                            </div>
                            <span className="text-xs text-muted-foreground">{branch_stats.length} Branches</span>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="text-xs">Branch Location</TableHead>
                                        <TableHead className="text-center text-xs">Phone Lines</TableHead>
                                        <TableHead className="text-center text-xs">Broadband / WTTx</TableHead>
                                        <TableHead className="text-right text-xs">Monthly Cost</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {branch_stats.map((b) => (
                                        <TableRow key={b.id}>
                                            <TableCell className="text-xs font-medium">{b.name}</TableCell>
                                            <TableCell className="text-center text-xs font-mono">{b.phone_count}</TableCell>
                                            <TableCell className="text-center text-xs font-mono">{b.broadband_count}</TableCell>
                                            <TableCell className="text-right text-xs font-mono font-medium">{formatETB(b.total_cost)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Quick Add Modals */}
            <PhoneNumberModal
                open={openAddPhoneModal}
                onOpenChange={setOpenAddPhoneModal}
                providers={providers}
                branches={branches}
                departments={departments}
                employees={employees}
            />

            <BroadbandModal
                open={openAddBroadbandModal}
                onOpenChange={setOpenAddBroadbandModal}
                providers={providers}
                branches={branches}
            />
        </AppLayout>
    );
}
