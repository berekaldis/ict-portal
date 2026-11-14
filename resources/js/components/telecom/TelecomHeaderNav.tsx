import { Badge } from '@/components/ui/badge';
import { Link, usePage } from '@inertiajs/react';
import { Building2, LayoutDashboard, Phone, Wifi } from 'lucide-react';
import React from 'react';

interface TelecomHeaderNavProps {
    title?: string;
    description?: string;
    totalMonthlySpend?: number;
    children?: React.ReactNode;
}

export default function TelecomHeaderNav({
    title = 'Telecom Management',
    description = 'Corporate phone lines, broadband, WTTx connections, and service providers',
    totalMonthlySpend,
    children,
}: TelecomHeaderNavProps) {
    const { url } = usePage();

    const tabs = [
        {
            name: 'Overview',
            href: '/telecom/dashboard',
            icon: LayoutDashboard,
            active: url === '/telecom/dashboard' || url.startsWith('/telecom/dashboard'),
        },
        {
            name: 'Phone Lines & SIMs',
            href: '/telecom/phone-numbers',
            icon: Phone,
            active: url.startsWith('/telecom/phone-numbers'),
        },
        {
            name: 'Broadband & WTTx',
            href: '/telecom/broadbands',
            icon: Wifi,
            active: url.startsWith('/telecom/broadbands'),
        },
        {
            name: 'Service Providers',
            href: '/telecom/providers',
            icon: Building2,
            active: url.startsWith('/telecom/providers'),
        },
    ];

    const formatETB = (amount: number) => {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'ETB', minimumFractionDigits: 0 }).format(amount);
    };

    return (
        <div className="flex flex-col gap-3 border-b pb-4">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-xl font-semibold tracking-tight text-foreground">{title}</h1>
                    <p className="text-xs text-muted-foreground">{description}</p>
                </div>
                <div className="flex items-center gap-2">
                    {typeof totalMonthlySpend === 'number' && (
                        <div className="flex items-center gap-1.5 text-xs text-muted-foreground mr-1">
                            <span>Monthly Spend:</span>
                            <Badge variant="outline" className="font-mono text-xs font-medium">
                                {formatETB(totalMonthlySpend)}/mo
                            </Badge>
                        </div>
                    )}
                    {children}
                </div>
            </div>

            {/* Clean pill tabs */}
            <div className="inline-flex w-fit items-center gap-1 rounded-lg bg-muted/50 p-1 text-muted-foreground">
                {tabs.map((tab) => {
                    const Icon = tab.icon;
                    return (
                        <Link
                            key={tab.href}
                            href={tab.href}
                            className={`inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-colors ${
                                tab.active
                                    ? 'bg-background text-foreground shadow-xs'
                                    : 'hover:text-foreground'
                            }`}
                        >
                            <Icon className="h-3.5 w-3.5" />
                            <span>{tab.name}</span>
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}
