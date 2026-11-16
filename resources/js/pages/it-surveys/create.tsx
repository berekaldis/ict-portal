import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import React, { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    AlertCircle,
    AlertTriangle,
    ArrowLeft,
    Camera,
    Check,
    CheckCircle2,
    HardDrive,
    Laptop,
    Send,
    SkipForward,
    Trash2,
    Upload,
    X,
} from 'lucide-react';

interface ChecklistItem {
    id: number;
    question: string;
    help_text: string | null;
}

interface Category {
    id: number;
    name: string;
    icon: string;
    type: 'hardware' | 'software';
    has_photo: boolean;
    description: string | null;
    items: ChecklistItem[];
}

interface Branch {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
}

interface Props {
    type: 'hardware' | 'software';
    categories: Category[];
    branches: Branch[];
    technicians: User[];
    defaultTechnician: string;
}

interface ItemState {
    status: 'OK' | 'Issue' | 'Skip';
    detail: string;
    skipReason: string;
}

export default function ItSurveyCreate({
    type,
    categories,
    branches,
    technicians,
    defaultTechnician,
}: Props) {
    const [surveyType, setSurveyType] = useState<'hardware' | 'software'>(type);
    const [selectedBranch, setSelectedBranch] = useState(branches[0]?.name || '');
    const [technicianName, setTechnicianName] = useState(defaultTechnician || '');
    const [branchManager, setBranchManager] = useState('');
    const [issuesSummary, setIssuesSummary] = useState('');
    const [actionsTaken, setActionsTaken] = useState('');
    const [recommendations, setRecommendations] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Initial state for checklist items
    const [checklistState, setChecklistState] = useState<Record<number, ItemState>>(() => {
        const initial: Record<number, ItemState> = {};
        categories.forEach((cat) => {
            cat.items.forEach((item) => {
                initial[item.id] = {
                    status: 'OK',
                    detail: '',
                    skipReason: '',
                };
            });
        });
        return initial;
    });

    // Category photos (base64)
    const [photos, setPhotos] = useState<Record<number, string>>({});

    const handleStatusChange = (itemId: number, status: 'OK' | 'Issue' | 'Skip') => {
        setChecklistState((prev) => ({
            ...prev,
            [itemId]: {
                ...prev[itemId],
                status,
            },
        }));
    };

    const handleDetailChange = (itemId: number, detail: string) => {
        setChecklistState((prev) => ({
            ...prev,
            [itemId]: {
                ...prev[itemId],
                detail,
            },
        }));
    };

    const handleSkipReasonChange = (itemId: number, skipReason: string) => {
        setChecklistState((prev) => ({
            ...prev,
            [itemId]: {
                ...prev[itemId],
                skipReason,
            },
        }));
    };

    const handlePhotoUpload = (catId: number, e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            const reader = new FileReader();
            reader.onloadend = () => {
                setPhotos((prev) => ({
                    ...prev,
                    [catId]: reader.result as string,
                }));
            };
            reader.readAsDataURL(file);
        }
    };

    const removePhoto = (catId: number) => {
        setPhotos((prev) => {
            const copy = { ...prev };
            delete copy[catId];
            return copy;
        });
    };

    // Health Score calculation
    const { healthScore, passedCount, issueCount, skipCount, totalCount, healthStatus } = useMemo(() => {
        let passed = 0;
        let issue = 0;
        let skip = 0;
        let total = 0;

        Object.values(checklistState).forEach((item) => {
            total++;
            if (item.status === 'OK') passed++;
            else if (item.status === 'Issue') issue++;
            else if (item.status === 'Skip') skip++;
        });

        const evaluated = passed + issue;
        const score = evaluated > 0 ? Math.round((passed / evaluated) * 100) : 100;

        let status: 'Good' | 'Warning' | 'Critical' = 'Good';
        if (score < 60) status = 'Critical';
        else if (score < 85) status = 'Warning';

        return {
            healthScore: score,
            passedCount: passed,
            issueCount: issue,
            skipCount: skip,
            totalCount: total,
            healthStatus: status,
        };
    }, [checklistState]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!selectedBranch) {
            alert('Please select a branch.');
            return;
        }
        if (!branchManager.trim()) {
            alert('Please provide the Branch Manager name.');
            return;
        }

        setIsSubmitting(true);

        const formattedChecklist = categories.flatMap((cat) =>
            cat.items.map((item) => {
                const st = checklistState[item.id] || { status: 'OK', detail: '', skipReason: '' };
                return {
                    category_id: cat.id,
                    category_name: cat.name,
                    item_id: item.id,
                    question: item.question,
                    status: st.status,
                    detail: st.detail,
                    skip_reason: st.skipReason,
                };
            })
        );

        router.post('/it-surveys', {
            branch_name: selectedBranch,
            technician_name: technicianName,
            branch_manager: branchManager,
            type: surveyType,
            checklist_data: formattedChecklist,
            issues_summary: issuesSummary,
            actions_taken: actionsTaken,
            recommendations: recommendations,
            photos: Object.values(photos),
        }, {
            onError: () => setIsSubmitting(false),
        });
    };

    return (
        <AppLayout breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'IT Survey & Audit', href: '/it-surveys' },
            { title: 'New Inspection', href: '/it-surveys/create' },
        ]}>
            <Head title={`New ${surveyType === 'hardware' ? 'Hardware' : 'Software'} Survey Inspection`} />

            <div className="flex h-full flex-1 flex-col gap-5 p-4 md:p-6 max-w-4xl mx-auto w-full">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-2 border-b border-slate-200">
                    <div>
                        <Link
                            href="/it-surveys"
                            className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-slate-900 mb-1 transition"
                        >
                            <ArrowLeft className="w-3.5 h-3.5" />
                            Back to Audits List
                        </Link>
                        <h1 className="text-xl font-bold tracking-tight text-slate-900">
                            New Branch Inspection
                        </h1>
                    </div>

                    {/* Hardware vs Software Toggle */}
                    <div className="flex bg-slate-100 p-0.5 rounded-lg border border-slate-200">
                        <button
                            type="button"
                            onClick={() => {
                                setSurveyType('hardware');
                                router.get('/it-surveys/create?type=hardware');
                            }}
                            className={`flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition ${
                                surveyType === 'hardware'
                                    ? 'bg-white text-indigo-700 shadow-sm'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            <HardDrive className="w-3.5 h-3.5" />
                            Hardware Audit
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                setSurveyType('software');
                                router.get('/it-surveys/create?type=software');
                            }}
                            className={`flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md transition ${
                                surveyType === 'software'
                                    ? 'bg-white text-cyan-700 shadow-sm'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            <Laptop className="w-3.5 h-3.5" />
                            Software Audit
                        </button>
                    </div>
                </div>

                {/* Score Summary Bar */}
                <Card className="border shadow-none">
                    <CardContent className="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="text-xs font-medium text-muted-foreground">Real-time Score:</span>
                                <span className="text-xl font-bold text-slate-900">{healthScore}%</span>
                                <Badge variant="outline" className={`text-xs ${
                                    healthStatus === 'Good'
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                                        : healthStatus === 'Warning'
                                        ? 'bg-amber-50 text-amber-700 border-amber-300'
                                        : 'bg-rose-50 text-rose-700 border-rose-300'
                                }`}>
                                    {healthStatus}
                                </Badge>
                            </div>
                            <div className="text-xs text-muted-foreground mt-0.5">
                                {passedCount} OK • {issueCount} Issues • {skipCount} Skipped ({totalCount} items evaluated)
                            </div>
                        </div>

                        {/* Visual Progress Meter */}
                        <div className="w-full sm:w-56">
                            <div className="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden border border-slate-200">
                                <div
                                    className={`h-full transition-all duration-300 ${
                                        healthStatus === 'Good'
                                            ? 'bg-emerald-500'
                                            : healthStatus === 'Warning'
                                            ? 'bg-amber-500'
                                            : 'bg-rose-500'
                                    }`}
                                    style={{ width: `${healthScore}%` }}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Branch & Inspector Details Card */}
                    <Card className="border shadow-none">
                        <CardHeader className="py-3 px-4 border-b border-slate-100">
                            <CardTitle className="text-xs font-semibold text-slate-900">
                                Inspection Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div className="space-y-1">
                                    <Label className="text-xs">Branch *</Label>
                                    <Select value={selectedBranch} onValueChange={setSelectedBranch}>
                                        <SelectTrigger className="h-8 text-xs">
                                            <SelectValue placeholder="Select Branch" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {branches.map((b) => (
                                                <SelectItem key={b.id} value={b.name}>{b.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs">Assigned Technician *</Label>
                                    <Input
                                        type="text"
                                        value={technicianName}
                                        onChange={(e) => setTechnicianName(e.target.value)}
                                        className="h-8 text-xs"
                                        required
                                    />
                                </div>

                                <div className="space-y-1">
                                    <Label className="text-xs">Branch Manager *</Label>
                                    <Input
                                        type="text"
                                        placeholder="e.g. Abebe Kebede"
                                        value={branchManager}
                                        onChange={(e) => setBranchManager(e.target.value)}
                                        className="h-8 text-xs"
                                        required
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Checklist Categories & Questions */}
                    <div className="space-y-3">
                        {categories.map((cat, catIdx) => (
                            <Card key={cat.id} className="border shadow-none">
                                <CardHeader className="py-2.5 px-4 bg-slate-50/60 border-b border-slate-100 flex flex-row items-center justify-between">
                                    <div>
                                        <CardTitle className="text-xs font-semibold text-slate-900 flex items-center gap-1.5">
                                            <span className="w-4 h-4 rounded-full bg-slate-200 text-slate-700 text-[10px] flex items-center justify-center font-bold">
                                                {catIdx + 1}
                                            </span>
                                            {cat.name}
                                        </CardTitle>
                                        {cat.description && (
                                            <p className="text-[11px] text-muted-foreground mt-0.5">{cat.description}</p>
                                        )}
                                    </div>

                                    {cat.has_photo && (
                                        <div>
                                            {photos[cat.id] ? (
                                                <div className="flex items-center gap-2">
                                                    <span className="text-xs text-emerald-600 font-medium flex items-center gap-1">
                                                        <Check className="w-3.5 h-3.5" /> Photo Attached
                                                    </span>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => removePhoto(cat.id)}
                                                        className="h-6 px-1.5 text-xs text-rose-600"
                                                    >
                                                        Remove
                                                    </Button>
                                                </div>
                                            ) : (
                                                <label className="cursor-pointer inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">
                                                    <Camera className="w-3.5 h-3.5 text-slate-500" />
                                                    Add Photo
                                                    <input
                                                        type="file"
                                                        accept="image/*"
                                                        className="hidden"
                                                        onChange={(e) => handlePhotoUpload(cat.id, e)}
                                                    />
                                                </label>
                                            )}
                                        </div>
                                    )}
                                </CardHeader>

                                <CardContent className="p-0 divide-y divide-slate-100">
                                    {cat.items.map((item) => {
                                        const current = checklistState[item.id] || { status: 'OK', detail: '', skipReason: '' };
                                        return (
                                            <div key={item.id} className="p-3 space-y-2">
                                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                    <div>
                                                        <div className="text-xs font-medium text-slate-800">{item.question}</div>
                                                        {item.help_text && (
                                                            <div className="text-[11px] text-muted-foreground">{item.help_text}</div>
                                                        )}
                                                    </div>

                                                    {/* Status Selection Buttons */}
                                                    <div className="flex items-center gap-1 self-start sm:self-center">
                                                        <button
                                                            type="button"
                                                            onClick={() => handleStatusChange(item.id, 'OK')}
                                                            className={`px-2.5 py-1 text-xs font-medium rounded-md border transition ${
                                                                current.status === 'OK'
                                                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-300 font-semibold'
                                                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            OK / Pass
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleStatusChange(item.id, 'Issue')}
                                                            className={`px-2.5 py-1 text-xs font-medium rounded-md border transition ${
                                                                current.status === 'Issue'
                                                                    ? 'bg-rose-50 text-rose-700 border-rose-300 font-semibold'
                                                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            Issue / Defect
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleStatusChange(item.id, 'Skip')}
                                                            className={`px-2.5 py-1 text-xs font-medium rounded-md border transition ${
                                                                current.status === 'Skip'
                                                                    ? 'bg-slate-100 text-slate-800 border-slate-300 font-semibold'
                                                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            N/A / Skip
                                                        </button>
                                                    </div>
                                                </div>

                                                {/* If Issue is chosen, prompt for details */}
                                                {current.status === 'Issue' && (
                                                    <div className="pt-1">
                                                        <Input
                                                            value={current.detail}
                                                            onChange={(e) => handleDetailChange(item.id, e.target.value)}
                                                            placeholder="Specify issue description or defect detail..."
                                                            className="h-7 text-xs border-rose-200 focus-visible:ring-rose-500"
                                                        />
                                                    </div>
                                                )}

                                                {/* If Skip is chosen, prompt for reason */}
                                                {current.status === 'Skip' && (
                                                    <div className="pt-1">
                                                        <Input
                                                            value={current.skipReason}
                                                            onChange={(e) => handleSkipReasonChange(item.id, e.target.value)}
                                                            placeholder="Reason for skipping (e.g. Not applicable at this branch)..."
                                                            className="h-7 text-xs border-slate-200"
                                                        />
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })}
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    {/* Summary & Recommendations Card */}
                    <Card className="border shadow-none">
                        <CardHeader className="py-3 px-4 border-b border-slate-100">
                            <CardTitle className="text-xs font-semibold text-slate-900">
                                Findings & Actions Summary
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="p-4 space-y-3">
                            <div className="space-y-1">
                                <Label className="text-xs">Identified Issues Summary</Label>
                                <Textarea
                                    rows={2}
                                    value={issuesSummary}
                                    onChange={(e) => setIssuesSummary(e.target.value)}
                                    placeholder="Summary of critical or pending defects identified during the audit..."
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Immediate Actions Taken</Label>
                                <Textarea
                                    rows={2}
                                    value={actionsTaken}
                                    onChange={(e) => setActionsTaken(e.target.value)}
                                    placeholder="Repairs, configuration changes, or cables replaced on site..."
                                    className="text-xs"
                                />
                            </div>

                            <div className="space-y-1">
                                <Label className="text-xs">Recommendations & Next Steps</Label>
                                <Textarea
                                    rows={2}
                                    value={recommendations}
                                    onChange={(e) => setRecommendations(e.target.value)}
                                    placeholder="Equipment replacements needed, follow-up visits, or upgrades..."
                                    className="text-xs"
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Submit Button */}
                    <div className="flex justify-end gap-2 pt-2">
                        <Link href="/it-surveys">
                            <Button type="button" variant="outline" size="sm" className="h-8 text-xs">
                                Cancel
                            </Button>
                        </Link>
                        <Button type="submit" size="sm" disabled={isSubmitting} className="h-8 text-xs gap-1.5">
                            <Send className="w-3.5 h-3.5" />
                            {isSubmitting ? 'Submitting...' : 'Submit Inspection Report'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
