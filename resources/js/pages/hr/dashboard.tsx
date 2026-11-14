import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import LetterModal, { LetterData } from '@/components/hr/letter-modal';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import {
    Briefcase,
    Building2,
    CheckCircle2,
    FileText,
    GraduationCap,
    Plus,
    Printer,
    Settings,
    UserCheck,
    Users,
} from 'lucide-react';
import React, { useState } from 'react';

interface Props {
    stats: {
        internships: number;
        qualifications: number;
        recruitments: number;
        totalBranches: number;
    };
    recentInternships: any[];
    recentQualifications: any[];
    recentRecruitments: any[];
    branches: string[];
    jobs: string[];
    filters: {
        branch: string;
        job: string;
    };
}

export default function HrDashboard({
    stats,
    recentInternships,
    recentQualifications,
    recentRecruitments,
    branches,
    jobs,
    filters,
}: Props) {
    const [selectedLetter, setSelectedLetter] = useState<LetterData | null>(null);

    const handleFilterChange = (params: Record<string, string>) => {
        router.get(route('hr.dashboard'), { ...filters, ...params }, { preserveState: true });
    };

    const viewLetter = async (url: string) => {
        try {
            const res = await fetch(url);
            const data = await res.json();
            setSelectedLetter(data);
        } catch (e) {
            alert('Failed to load official letter.');
        }
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'HR Management', href: '/hr' }, { title: 'Dashboard' }]}>
            <Head title="HR Management Dashboard | Kaldis Coffee" />

            <div className="space-y-6 max-w-7xl mx-auto pb-12">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-border/40">
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <Users className="h-5 w-5 text-amber-600" /> Kaldis HR Management Dashboard
                        </h1>
                        <p className="text-xs text-muted-foreground">
                            Oversee internship assignments, probation qualifications, recruitments, and branch dispatches
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button asChild variant="outline" size="sm" className="h-8 text-xs">
                            <Link href={route('hr.settings.index')}>
                                <Settings className="h-3.5 w-3.5 mr-1" /> HR Settings & Telegram
                            </Link>
                        </Button>
                        <Button asChild size="sm" className="h-8 text-xs bg-amber-700 hover:bg-amber-800 text-white">
                            <Link href={route('hr.internships.index')}>
                                <Plus className="h-3.5 w-3.5 mr-1" /> New Request
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* KPI Metrics */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="border-border/60 shadow-xs">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">Internship Requests</p>
                                <p className="text-2xl font-bold text-amber-600 mt-0.5">{stats.internships}</p>
                                <span className="text-[10px] text-muted-foreground">Practical training assignments</span>
                            </div>
                            <div className="w-10 h-10 rounded-full bg-amber-500/10 text-amber-600 flex items-center justify-center">
                                <GraduationCap className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 shadow-xs">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">Qualifications</p>
                                <p className="text-2xl font-bold text-emerald-600 mt-0.5">{stats.qualifications}</p>
                                <span className="text-[10px] text-emerald-600/80 font-medium">Completed probation evaluations</span>
                            </div>
                            <div className="w-10 h-10 rounded-full bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                                <UserCheck className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 shadow-xs">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">Recruitments</p>
                                <p className="text-2xl font-bold text-blue-600 mt-0.5">{stats.recruitments}</p>
                                <span className="text-[10px] text-blue-600/80 font-medium">Permanent placements confirmed</span>
                            </div>
                            <div className="w-10 h-10 rounded-full bg-blue-500/10 text-blue-600 flex items-center justify-center">
                                <Briefcase className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border/60 shadow-xs">
                        <CardContent className="p-4 flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium text-muted-foreground">Active Branches</p>
                                <p className="text-2xl font-bold text-purple-600 mt-0.5">{stats.totalBranches}</p>
                                <span className="text-[10px] text-muted-foreground">With HR dispatch routing</span>
                            </div>
                            <div className="w-10 h-10 rounded-full bg-purple-500/10 text-purple-600 flex items-center justify-center">
                                <Building2 className="w-5 h-5" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <div className="bg-card rounded-xl p-3 border border-border/50 flex flex-wrap items-center gap-3 shadow-xs">
                    <div className="flex items-center gap-2 text-xs">
                        <span className="text-muted-foreground font-medium">Filter Branch:</span>
                        <select
                            value={filters.branch}
                            onChange={(e) => handleFilterChange({ branch: e.target.value })}
                            className="h-8 text-xs rounded-lg border-border/60 bg-muted/20 px-2.5"
                        >
                            <option value="all">All Branches</option>
                            {branches.map((b) => (
                                <option key={b} value={b}>{b}</option>
                            ))}
                        </select>
                    </div>

                    <div className="flex items-center gap-2 text-xs">
                        <span className="text-muted-foreground font-medium">Filter Position:</span>
                        <select
                            value={filters.job}
                            onChange={(e) => handleFilterChange({ job: e.target.value })}
                            className="h-8 text-xs rounded-lg border-border/60 bg-muted/20 px-2.5"
                        >
                            <option value="all">All Positions</option>
                            {jobs.map((j) => (
                                <option key={j} value={j}>{j}</option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* 3 Overview Tables */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Recent Internships */}
                    <Card className="border-border/60 shadow-xs">
                        <CardHeader className="py-3 px-4 border-b border-border/40 flex flex-row items-center justify-between">
                            <CardTitle className="text-xs font-bold flex items-center gap-1.5">
                                <GraduationCap className="w-4 h-4 text-amber-600" /> Recent Internships
                            </CardTitle>
                            <Button asChild variant="ghost" size="sm" className="h-6 text-[11px] px-1.5">
                                <Link href={route('hr.internships.index')}>View All</Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="p-3 divide-y divide-border/30 text-xs">
                            {recentInternships.length === 0 ? (
                                <p className="text-center text-muted-foreground py-4">No recent records</p>
                            ) : (
                                recentInternships.map((item) => (
                                    <div key={item.id} className="py-2.5 flex items-center justify-between hover:bg-muted/10">
                                        <div className="space-y-0.5">
                                            <p className="font-semibold text-foreground">{item.name}</p>
                                            <p className="text-[11px] text-muted-foreground">
                                                {item.job} • {item.work_branch}
                                            </p>
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => viewLetter(`/hr/internships/${item.id}/letter`)}
                                            className="h-6 text-[10px] px-2"
                                        >
                                            Letter
                                        </Button>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Qualifications */}
                    <Card className="border-border/60 shadow-xs">
                        <CardHeader className="py-3 px-4 border-b border-border/40 flex flex-row items-center justify-between">
                            <CardTitle className="text-xs font-bold flex items-center gap-1.5">
                                <UserCheck className="w-4 h-4 text-emerald-600" /> Recent Qualifications
                            </CardTitle>
                            <Button asChild variant="ghost" size="sm" className="h-6 text-[11px] px-1.5">
                                <Link href={route('hr.qualifications.index')}>View All</Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="p-3 divide-y divide-border/30 text-xs">
                            {recentQualifications.length === 0 ? (
                                <p className="text-center text-muted-foreground py-4">No recent records</p>
                            ) : (
                                recentQualifications.map((item) => (
                                    <div key={item.id} className="py-2.5 flex items-center justify-between hover:bg-muted/10">
                                        <div className="space-y-0.5">
                                            <p className="font-semibold text-foreground">{item.name}</p>
                                            <p className="text-[11px] text-muted-foreground">
                                                {item.job} • {item.branch}
                                            </p>
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => viewLetter(`/hr/qualifications/${item.id}/letter`)}
                                            className="h-6 text-[10px] px-2"
                                        >
                                            Letter
                                        </Button>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Recruitments */}
                    <Card className="border-border/60 shadow-xs">
                        <CardHeader className="py-3 px-4 border-b border-border/40 flex flex-row items-center justify-between">
                            <CardTitle className="text-xs font-bold flex items-center gap-1.5">
                                <Briefcase className="w-4 h-4 text-blue-600" /> Recent Recruitments
                            </CardTitle>
                            <Button asChild variant="ghost" size="sm" className="h-6 text-[11px] px-1.5">
                                <Link href={route('hr.recruitments.index')}>View All</Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="p-3 divide-y divide-border/30 text-xs">
                            {recentRecruitments.length === 0 ? (
                                <p className="text-center text-muted-foreground py-4">No recent records</p>
                            ) : (
                                recentRecruitments.map((item) => (
                                    <div key={item.id} className="py-2.5 flex items-center justify-between hover:bg-muted/10">
                                        <div className="space-y-0.5">
                                            <p className="font-semibold text-foreground">{item.name}</p>
                                            <p className="text-[11px] text-muted-foreground">
                                                {item.assigned_job} • {item.branch}
                                            </p>
                                        </div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => viewLetter(`/hr/recruitments/${item.id}/letter`)}
                                            className="h-6 text-[10px] px-2"
                                        >
                                            Letter
                                        </Button>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Letter Preview Modal */}
            <LetterModal
                open={!!selectedLetter}
                onClose={() => setSelectedLetter(null)}
                letter={selectedLetter}
            />
        </AppLayout>
    );
}
