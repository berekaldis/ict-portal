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
    Briefcase,
    Filter,
    Plus,
    Printer,
    Search,
    Trash2,
} from 'lucide-react';
import React, { useState } from 'react';

interface RecruitmentItem {
    id: number;
    request_id: string;
    name: string;
    gender: string;
    branch: string;
    requested_job: string;
    assigned_job: string;
    start_date: string;
    qualification_ref_id: string | null;
    created_at: string;
}

interface Props {
    recruitments: {
        data: RecruitmentItem[];
        current_page: number;
        last_page: number;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    branches: string[];
    jobs: string[];
    qualifications: { request_id: string; name: string; gender: string; branch: string; job: string }[];
    filters: {
        search: string;
        branch: string;
    };
}

export default function RecruitmentIndex({
    recruitments,
    branches,
    jobs,
    qualifications,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [createOpen, setCreateOpen] = useState(false);
    const [selectedLetter, setSelectedLetter] = useState<LetterData | null>(null);

    const { data, setData, post, processing, reset } = useForm({
        name: '',
        gender: 'አቶ',
        branch: branches[0] || '',
        requested_job: jobs[0] || 'Barista',
        assigned_job: jobs[0] || 'Barista',
        start_date: new Date().toISOString().split('T')[0],
        destination: '',
        qualification_ref_id: '',
    });

    useEffect(() => {
        if (!data.branch && branches.length > 0) {
            setData('branch', branches[0]);
        }
    }, [branches]);

    const handleSelectQualification = (refId: string) => {
        setData('qualification_ref_id', refId);
        const match = qualifications.find((q) => q.request_id === refId);
        if (match) {
            setData((prev) => ({
                ...prev,
                qualification_ref_id: refId,
                name: match.name,
                gender: match.gender,
                branch: match.branch,
                requested_job: match.job,
                assigned_job: match.job,
            }));
        }
    };

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('hr.recruitments.store'), {
            onSuccess: () => {
                setCreateOpen(false);
                reset();
            },
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Delete this recruitment placement record?')) {
            router.delete(route('hr.recruitments.destroy', id));
        }
    };

    const viewLetter = async (id: number) => {
        try {
            const res = await fetch(`/hr/recruitments/${id}/letter`);
            const json = await res.json();
            setSelectedLetter(json);
        } catch (e) {
            alert('Failed to load letter.');
        }
    };

    const applyFilter = (params: Record<string, string>) => {
        router.get(route('hr.recruitments.index'), { ...filters, search, ...params }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'HR Management', href: '/hr' }, { title: 'Recruitments' }]}>
            <Head title="Permanent Recruitments | Kaldis HR" />

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
                                <Briefcase className="h-5 w-5 text-blue-600" /> Permanent Recruitments (የቋሚ ቅጥር ምደባ)
                            </h1>
                            <p className="text-xs text-muted-foreground">
                                Record permanent staff placement, job assignment, and dispatch official placement letters
                            </p>
                        </div>
                    </div>

                    <Button onClick={() => setCreateOpen(true)} size="sm" className="h-9 bg-blue-700 hover:bg-blue-800 text-white font-medium">
                        <Plus className="h-4 w-4 mr-1.5" /> Confirm Recruitment
                    </Button>
                </div>

                {/* Filters */}
                <div className="bg-card rounded-xl p-3 border border-border/50 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-xs">
                    <div className="flex items-center gap-2 w-full sm:w-auto text-xs">
                        <span className="text-muted-foreground font-medium">Branch:</span>
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
                                    <th className="p-3">Recruitment ID</th>
                                    <th className="p-3">Staff Name</th>
                                    <th className="p-3">Branch</th>
                                    <th className="p-3">Requested Position</th>
                                    <th className="p-3">Assigned Position</th>
                                    <th className="p-3">Start Date</th>
                                    <th className="p-3">Qualification Ref</th>
                                    <th className="p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border/30">
                                {recruitments.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="p-8 text-center text-muted-foreground">
                                            No recruitment placement records found.
                                        </td>
                                    </tr>
                                ) : (
                                    recruitments.data.map((item) => (
                                        <tr key={item.id} className="hover:bg-muted/20 transition-colors">
                                            <td className="p-3 font-mono font-bold text-foreground">{item.request_id}</td>
                                            <td className="p-3 font-medium text-foreground">
                                                {item.gender} {item.name}
                                            </td>
                                            <td className="p-3 font-medium text-foreground">{item.branch}</td>
                                            <td className="p-3 text-muted-foreground">{item.requested_job}</td>
                                            <td className="p-3 font-semibold text-blue-600">{item.assigned_job}</td>
                                            <td className="p-3 text-muted-foreground">{item.start_date}</td>
                                            <td className="p-3 font-mono text-muted-foreground">{item.qualification_ref_id || '-'}</td>
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
                    {recruitments.last_page > 1 && (
                        <div className="p-3 border-t border-border/40 flex items-center justify-between text-xs text-muted-foreground">
                            <div>Showing page {recruitments.current_page} of {recruitments.last_page}</div>
                            <div className="flex gap-1">
                                {recruitments.links.map((link, idx) => (
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
                                <Briefcase className="w-5 h-5 text-blue-600" /> Confirm Permanent Recruitment
                            </DialogTitle>
                            <DialogDescription className="text-xs">
                                Confirm the final permanent job placement and generate the official employment letter.
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={handleCreate} className="space-y-4 pt-2">
                            {/* Link to previous qualification */}
                            {qualifications.length > 0 && (
                                <div className="p-2.5 rounded-lg border border-border/50 bg-muted/20 space-y-1">
                                    <Label className="text-[11px] font-semibold text-muted-foreground">
                                        Import from Qualified Candidate (Optional):
                                    </Label>
                                    <select
                                        value={data.qualification_ref_id}
                                        onChange={(e) => handleSelectQualification(e.target.value)}
                                        className="w-full text-xs rounded border-border/60 bg-background py-1.5 px-2"
                                    >
                                        <option value="">-- Choose Qualified Candidate --</option>
                                        {qualifications.map((q) => (
                                            <option key={q.request_id} value={q.request_id}>
                                                {q.request_id}: {q.gender} {q.name} ({q.branch} - {q.job})
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            )}

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
                                    <Label className="text-xs font-semibold">Staff Full Name</Label>
                                    <Input
                                        placeholder="Full Name..."
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="text-xs h-9 mt-1"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label className="text-xs font-semibold">Assigned Branch</Label>
                                    <select
                                        value={data.branch}
                                        onChange={(e) => setData('branch', e.target.value)}
                                        className="w-full text-xs rounded-lg border-border/60 bg-muted/20 py-2 px-2.5 mt-1 font-medium"
                                    >
                                        {branches.map((b) => (
                                            <option key={b} value={b}>{b}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <Label className="text-xs font-semibold">Effective Start Date</Label>
                                    <Input
                                        type="date"
                                        value={data.start_date}
                                        onChange={(e) => setData('start_date', e.target.value)}
                                        className="text-xs h-9 mt-1"
                                        required
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label className="text-xs font-semibold">Requested Job Position</Label>
                                    <select
                                        value={data.requested_job}
                                        onChange={(e) => setData('requested_job', e.target.value)}
                                        className="w-full text-xs rounded-lg border-border/60 bg-muted/20 py-2 px-2.5 mt-1 font-medium"
                                    >
                                        {jobs.map((j) => (
                                            <option key={j} value={j}>{j}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <Label className="text-xs font-semibold">Final Assigned Position</Label>
                                    <select
                                        value={data.assigned_job}
                                        onChange={(e) => setData('assigned_job', e.target.value)}
                                        className="w-full text-xs rounded-lg border-border/60 bg-muted/20 py-2 px-2.5 mt-1 font-medium font-semibold text-blue-700"
                                    >
                                        {jobs.map((j) => (
                                            <option key={j} value={j}>{j}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="flex justify-end gap-2 pt-3 border-t border-border/40">
                                <Button type="button" variant="outline" size="sm" onClick={() => setCreateOpen(false)}>
                                    Cancel
                                </Button>
                                <Button type="submit" size="sm" disabled={processing} className="bg-blue-700 hover:bg-blue-800 text-white">
                                    {processing ? 'Saving...' : 'Confirm Placement & Print'}
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
