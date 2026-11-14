import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Briefcase, Paperclip, PlusCircle } from 'lucide-react';
import React from 'react';

interface Props {
    types: string[];
    assignees: string[];
}

export default function CaseCreate({ types, assignees }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        case_type: types[0] || 'Support',
        description: '',
        priority: 'Medium',
        due_date: '',
        assigned_to: '',
        remarks: '',
        attachment: null as File | null,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('cases.store'));
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Cases', href: '/cases' }, { title: 'Create Case' }]}>
            <Head title="Log New Support Case" />

            <div className="max-w-2xl mx-auto space-y-4">
                <div className="flex items-center gap-3">
                    <Button asChild variant="outline" size="icon" className="h-9 w-9">
                        <Link href={route('cases.index')}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-foreground flex items-center gap-2">
                            <Briefcase className="h-5 w-5 text-primary" /> Log New Support Case
                        </h1>
                        <p className="text-xs text-muted-foreground">
                            Create a ticket for CNET support, bug fix, or project milestone
                        </p>
                    </div>
                </div>

                <Card className="border-border/60 shadow-xs">
                    <CardHeader className="py-4 px-6 border-b border-border/40">
                        <CardTitle className="text-sm font-semibold">Case Details</CardTitle>
                        <CardDescription className="text-xs">
                            All cases automatically notify the assigned IT team members on Telegram.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="p-6">
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="case_type">Case Type <span className="text-destructive">*</span></Label>
                                    <Select
                                        value={data.case_type}
                                        onValueChange={(val) => setData('case_type', val)}
                                    >
                                        <SelectTrigger id="case_type">
                                            <SelectValue placeholder="Select type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {types.map((t) => (
                                                <SelectItem key={t} value={t}>{t}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.case_type && <p className="text-xs text-destructive">{errors.case_type}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="priority">Priority <span className="text-destructive">*</span></Label>
                                    <Select
                                        value={data.priority}
                                        onValueChange={(val) => setData('priority', val)}
                                    >
                                        <SelectTrigger id="priority">
                                            <SelectValue placeholder="Select priority" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Critical">🔴 Critical (High SLA)</SelectItem>
                                            <SelectItem value="Medium">🟡 Medium (Normal)</SelectItem>
                                            <SelectItem value="Low">🟢 Low (Minor / Enhancement)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.priority && <p className="text-xs text-destructive">{errors.priority}</p>}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-1.5">
                                    <Label htmlFor="assigned_to">Assignee (IT / CNET Support)</Label>
                                    <Select
                                        value={data.assigned_to}
                                        onValueChange={(val) => setData('assigned_to', val)}
                                    >
                                        <SelectTrigger id="assigned_to">
                                            <SelectValue placeholder="Select assignee" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Unassigned">Leave Unassigned</SelectItem>
                                            {assignees.map((a) => (
                                                <SelectItem key={a} value={a}>{a}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.assigned_to && <p className="text-xs text-destructive">{errors.assigned_to}</p>}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="due_date">Target Due Date</Label>
                                    <Input
                                        id="due_date"
                                        type="date"
                                        value={data.due_date}
                                        onChange={(e) => setData('due_date', e.target.value)}
                                    />
                                    {errors.due_date && <p className="text-xs text-destructive">{errors.due_date}</p>}
                                </div>
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="description">Case Description <span className="text-destructive">*</span></Label>
                                <Textarea
                                    id="description"
                                    placeholder="Provide detailed description of the error, voucher, store, or requirement..."
                                    rows={4}
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                                {errors.description && <p className="text-xs text-destructive">{errors.description}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="remarks">Initial Remarks / Steps Taken</Label>
                                <Input
                                    id="remarks"
                                    placeholder="Optional notes..."
                                    value={data.remarks}
                                    onChange={(e) => setData('remarks', e.target.value)}
                                />
                                {errors.remarks && <p className="text-xs text-destructive">{errors.remarks}</p>}
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="attachment">Attachment (Screenshot, Log, or Document)</Label>
                                <Input
                                    id="attachment"
                                    type="file"
                                    onChange={(e) => setData('attachment', e.target.files ? e.target.files[0] : null)}
                                />
                                {errors.attachment && <p className="text-xs text-destructive">{errors.attachment}</p>}
                            </div>

                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-border/50">
                                <Button asChild variant="outline" type="button">
                                    <Link href={route('cases.index')}>Cancel</Link>
                                </Button>
                                <Button type="submit" disabled={processing} className="font-medium">
                                    <PlusCircle className="mr-1.5 h-4 w-4" />
                                    {processing ? 'Logging Case...' : 'Submit Case'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
