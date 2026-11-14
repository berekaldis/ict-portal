import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import LetterModal, { LetterData } from '@/components/hr/letter-modal';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    Clock,
    FileText,
    Filter,
    GraduationCap,
    Plus,
    Printer,
    Search,
    Trash2,
    XCircle,
} from 'lucide-react';
import React, { useState } from 'react';

interface InternshipItem {
    id: number;
    request_id: string;
    name: string;
    gender: string;
    to_branch: string;
    work_branch: string;
    job: string;
    start_date: string;
    reference_status: string;
    created_at: string;
}

interface Props {
    internships: {
        data: InternshipItem[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    branches: string[];
    jobs: string[];
    filters: {
        search: string;
        branch: string;
        status: string;
    };
}

export default function InternshipIndex({ internships, branches, jobs, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [createOpen, setCreateOpen] = useState(false);
    const [selectedLetter, setSelectedLetter] = useState<LetterData | null>(null);

    const { data, setData, post, processing, reset, errors } = useForm({
        name: '',
        gender: 'አቶ',
        to_branch: branches[0] || 'Bole',
        work_branch: branches[0] || 'Bole',
        job: jobs[0] || 'Barista',
        start_date: new Date().toISOString().split('T')[0],
    });

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hr.internships.store'), {
            onSuccess: () => {
                setCreateOpen(false);
                reset();
            },
        });
    };

    const handleStatusChange = (id: number, status: string) => {
        router.put(route('hr.internships.status', id), { reference_status: status });
    };

    const handleDelete = (id: number) => {
        if (confirm('Delete this internship request?')) {
            router.delete(route('hr.internships.destroy', id));
        }
    };

    const viewLetter = async (id: number) => {
        try {
            const res = await fetch(`/hr/internships/${id}/letter`);
            const json = await res.json();
            setSelectedLetter(json);
        } catch (e) {
            alert('Failed to load letter.');
        }
    };

    const applyFilter = (params: Record<string, string>) => {
        router.get(route('hr.internships.index'), { ...filters, search, ...params }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'HR Management', href: '/hr' }, { title: 'Internship Requests' }]}>
            <Head title="Internship Requests | Kaldis HR" />

            <div className="space-y-6 max-w-7xl mx-auto pb-12">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-border/40">
                    <div className="flex items-center gap-3">
                        <Button asChild variant="outline" size="icon" className="h-9 w-9">
                            <Link href={route('hr.dashboard')}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                                <GraduationCap className="h-5 w-5 text-amber-600" /> Internship Requests (የሥራ ላይ ልምምድ)
                            </h1>
                            <p className="text-xs text-muted-foreground">
                                Manage practical training dispatches, official assignment letters, and branch confirmation
                            </p>
                        </div>
                    </div>

                    <Button onClick={() => setCreateOpen(true)} size="sm" className="h-9 bg-amber-700 hover:bg-amber-800 text-white font-medium">
                        <Plus className="h-4 w-4 mr-1.5" /> New Internship Request
                    </Button>
                </div>

                {/* Filter Bar */}
                <div className="bg-card rounded-xl p-3 border border-border/50 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-xs">
                    <div className="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                        <select
                            value={filters.branch}
                            onChange={(e) => applyFilter({ branch: e.target.value })}
                            className="h-8 text-xs rounded-lg border-border/60 bg-muted/20 px-2.5 font-medium"
                        >
                            <option value="all">All Branches</option>
                            {branches.map((b) => (
                                <option key={b} value={b}>{b}</option>
                            ))}
                        </select>

                        <select
                            value={filters.status}
                            onChange={(e) => applyFilter({ status: e.target.value })}
                            className="h-8 text-xs rounded-lg border-border/60 bg-muted/20 px-2.5 font-medium"
                        >
                            <option value="all">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Completed">Completed</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>

                    <div className="relative w-full sm:w-72">
                        <Search className="w-3.5 h-3.5 text-muted-foreground absolute left-2.5 top-2.5" />
                        <Input
                            placeholder="Search by ID, name, or job..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && applyFilter({ search })}
                            className="h-8 text-xs pl-8"
                        />
                    </div>
                </div>

                {/* Table */}
                <Card className="border-border/60 shadow-xs overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr className="border-b border-border/60 bg-muted/40 font-semibold text-muted-foreground">
                                    <th className="p-3">Request ID</th>
                                    <th className="p-3">Staff Name</th>
                                    <th className="p-3">Position</th>
                                    <th className="p-3">To Branch</th>
                                    <th className="p-3">Work Branch</th>
                                    <th className="p-3">Start Date</th>
                                    <th className="p-3">Status</th>
                                    <th className="p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border/30">
                                {internships.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="p-8 text-center text-muted-foreground">
                                            No internship requests found.
                                        </td>
                                    </tr>
                                ) : (
                                    internships.data.map((item) => (
                                        <tr key={item.id} className="hover:bg-muted/20 transition-colors">
                                            <td className="p-3 font-mono font-bold text-foreground">{item.request_id}</td>
                                            <td className="p-3 font-medium text-foreground">
                                                {item.gender} {item.name}
                                            </td>
                                            <td className="p-3 text-muted-foreground">{item.job}</td>
                                            <td className="p-3 font-medium text-foreground">{item.to_branch}</td>
                                            <td className="p-3 text-muted-foreground">{item.work_branch}</td>
                                            <td className="p-3 text-muted-foreground">{item.start_date}</td>
                                            <td className="p-3">
                                                <Badge
                                                    variant="outline"
                                                    className={`text-[10px] ${
                                                        item.reference_status === 'Approved' || item.reference_status === 'Completed'
                                                            ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/30'
                                                            : item.reference_status === 'Rejected'
                                                            ? 'bg-red-500/10 text-red-600 border-red-500/30'
                                                            : 'bg-amber-500/10 text-amber-600 border-amber-500/30'
                                                    }`}
                                                >
                                                    {item.reference_status}
                                                </Badge>
                                            </td>
                                            <td className="p-3 text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => viewLetter(item.id)}
                                                        className="h-7 text-[10px] px-2 text-foreground"
                                                    >
                                                        <Printer className="w-3 h-3 mr-1" /> Letter
                                                    </Button>
                                                    <select
                                                        value={item.reference_status}
                                                        onChange={(e) => handleStatusChange(item.id, e.target.value)}
                                                        className="h-7 text-[10px] rounded border border-border/50 bg-background px-1"
                                                    >
                                                        <option value="Pending">Pending</option>
                                                        <option value="Approved">Approve</option>
                                                        <option value="Completed">Complete</option>
                                                        <option value="Rejected">Reject</option>
                                                    </select>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="h-7 w-7 text-destructive hover:text-destructive"
                                                        onClick={() => handleDelete(item.id)}
                                                    >
                                                        <Trash2 className="w-3.5 h-3.5" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {internships.last_page > 1 && (
                        <div className="p-3 border-t border-border/40 flex items-center justify-between text-xs text-muted-foreground">
                            <div>Showing page {internships.current_page} of {internships.last_page}</div>
                            <div className="flex gap-1">
                                {internships.links.map((link, idx) => (
                                    <Button
                                        key={idx}
                                        asChild={!!link.url}
                                        disabled={!link.url}
                                        variant={link.active ? 'default' : 'outline'}
                                        size="sm"
                                        className="h-7 text-xs px-2.5"
                                    >
                                        {link.url ? (
                                            <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                                        ) : (
                                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                        )}
                                    </Button>
                                ))}
                            </div>
                        </div>
                    )}
                </Card>

                {/* Create Modal */}
                <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                    <DialogContent className="max-w-lg">
                        <DialogHeader>
                            <DialogTitle className="text-base font-bold flex items-center gap-2">
                                <GraduationCap className="w-5 h-5 text-amber-600" /> New Internship Request
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                Creates an official internship dispatch record and dispatches Telegram notification to the target branch.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleCreate} className="space-y-4 pt-2">
                            <div className="grid grid-cols-3 gap-3">
                                <div>
                                    <Label className="text-xs font-semibold">Title</Label>
                                    <select
                                        value={data.gender}
                                        onChange={(e) => setData('gender', e.target.value)}
                                        className="w-full text-xs rounded-lg border-border/60 bg-muted/20 py-2 px-2.5 mt-1 font-medium"
                                    >
                                        <option value="አቶ">አቶ</option>
                                        <option value="ወ/ሪት">ወ/ሪት</option>
                                        <option value="ወ/ሮ">ወ/ሮ</option>
                                        <option value="Mr.">Mr.</option>
                                        <option value="Ms.">Ms.</option>
                                    </select>
                                </div>
                                <div className="col-span-2">
                                    <Label className="text-xs font-semibold">Full Name</Label>
                                    <Input
                                        placeholder="Candidate Full Name..."
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="text-xs h-9 mt-1"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label className="text-xs font-semibold">To Branch (Dispatch)</Label>
                                    <select
                                        value={data.to_branch}
                                        onChange={(e) => setData('to_branch', e.target.value)}
                                        className="w-full text-xs rounded-lg border-border/60 bg-muted/20 py-2 px-2.5 mt-1 font-medium"
                                    >
                                        {branches.map((b) => (
                                            <option key={b} value={b}>{b}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <Label className="text-xs font-semibold">Work Branch (Placement)</Label>
                                    <select
                                        value={data.work_branch}
                                        onChange={(e) => setData('work_branch', e.target.value)}
                                        className="w-full text-xs rounded-lg border-border/60 bg-muted/20 py-2 px-2.5 mt-1 font-medium"
                                    >
                                        {branches.map((b) => (
                                            <option key={b} value={b}>{b}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label className="text-xs font-semibold">Job / Position</Label>
                                    <select
                                        value={data.job}
                                        onChange={(e) => setData('job', e.target.value)}
                                        className="w-full text-xs rounded-lg border-border/60 bg-muted/20 py-2 px-2.5 mt-1 font-medium"
                                    >
                                        {jobs.map((j) => (
                                            <option key={j} value={j}>{j}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <Label className="text-xs font-semibold">Start Date</Label>
                                    <Input
                                        type="date"
                                        value={data.start_date}
                                        onChange={(e) => setData('start_date', e.target.value)}
                                        className="text-xs h-9 mt-1"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="flex justify-end gap-2 pt-3 border-t border-border/40">
                                <Button type="button" variant="outline" size="sm" onClick={() => setCreateOpen(false)}>
                                    Cancel
                                </Button>
                                <Button type="submit" size="sm" disabled={processing} className="bg-amber-700 hover:bg-amber-800 text-white">
                                    {processing ? 'Saving...' : 'Create & Generate Letter'}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Printable Letter Modal */}
                <LetterModal
                    open={!!selectedLetter}
                    onClose={() => setSelectedLetter(null)}
                    letter={selectedLetter}
                />
            </div>
        </AppLayout>
    );
}
