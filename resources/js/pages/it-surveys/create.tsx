import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import {
    AlertCircle,
    ArrowLeft,
    Camera,
    Check,
    CheckCircle2,
    HardDrive,
    Info,
    Laptop,
    Send,
    SkipForward,
    Upload,
    X,
} from 'lucide-react';
import React, { useMemo, useState } from 'react';

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

export default function ItSurveyCreate({ type, categories, branches, technicians, defaultTechnician }: Props) {
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

    // Real-time Health Score calculation (matches original Servey logic: skip-aware)
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

        // Prepare checklist data array
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
            { title: 'IT Survey & Audit', href: '/it-surveys' },
            { title: 'New Inspection', href: '/it-surveys/create' },
        ]}>
            <Head title={`New ${surveyType === 'hardware' ? 'Hardware' : 'Software'} Survey Inspection`} />

            <div className="p-6 max-w-5xl mx-auto space-y-6">
                {/* Back Link & Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <a
                            href="/it-surveys"
                            className="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800 mb-2 transition"
                        >
                            <ArrowLeft className="w-4 h-4" />
                            Back to All Audits
                        </a>
                        <h1 className="text-2xl font-bold text-slate-900">Branch Infrastructure Inspection & Audit</h1>
                        <p className="text-sm text-slate-500">Conduct comprehensive on-site branch hardware & system evaluation</p>
                    </div>

                    {/* Hardware vs Software Switch */}
                    <div className="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
                        <button
                            type="button"
                            onClick={() => {
                                setSurveyType('hardware');
                                router.get('/it-surveys/create?type=hardware');
                            }}
                            className={`flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg transition ${
                                surveyType === 'hardware'
                                    ? 'bg-white text-indigo-700 shadow-sm'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            <HardDrive className="w-4 h-4" />
                            Hardware Survey
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                setSurveyType('software');
                                router.get('/it-surveys/create?type=software');
                            }}
                            className={`flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg transition ${
                                surveyType === 'software'
                                    ? 'bg-white text-cyan-700 shadow-sm'
                                    : 'text-slate-600 hover:text-slate-900'
                            }`}
                        >
                            <Laptop className="w-4 h-4" />
                            Software Survey
                        </button>
                    </div>
                </div>

                {/* Sticky Health Score Header */}
                <div className="sticky top-4 z-20 bg-white/95 backdrop-blur-md p-5 rounded-2xl border border-slate-200 shadow-md">
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div className="flex items-center gap-4">
                            <div className={`w-14 h-14 rounded-2xl flex items-center justify-center font-black text-xl border ${
                                healthStatus === 'Good'
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                    : healthStatus === 'Warning'
                                    ? 'bg-amber-50 text-amber-700 border-amber-200'
                                    : 'bg-rose-50 text-rose-700 border-rose-200'
                            }`}>
                                {healthScore}%
                            </div>
                            <div>
                                <div className="flex items-center gap-2">
                                    <span className="font-bold text-slate-900 text-lg">Overall Health Score</span>
                                    <span className={`px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider ${
                                        healthStatus === 'Good'
                                            ? 'bg-emerald-100 text-emerald-800'
                                            : healthStatus === 'Warning'
                                            ? 'bg-amber-100 text-amber-800'
                                            : 'bg-rose-100 text-rose-800'
                                    }`}>
                                        {healthStatus}
                                    </span>
                                </div>
                                <div className="text-xs text-slate-500 mt-0.5">
                                    {passedCount} OK • {issueCount} Issues • {skipCount} Skipped (out of {totalCount} total items)
                                </div>
                            </div>
                        </div>

                        {/* Visual Progress Bar */}
                        <div className="w-full sm:w-64">
                            <div className="w-full bg-slate-100 h-3 rounded-full overflow-hidden border border-slate-200">
                                <div
                                    className={`h-full transition-all duration-300 ${
                                        healthStatus === 'Good'
                                            ? 'bg-emerald-500'
                                            : healthStatus === 'Warning'
                                            ? 'bg-amber-500'
                                            : 'bg-rose-500'
                                    }`}
                                    style={{ width: `${healthScore}%` }}
                                ></div>
                            </div>
                            <div className="flex justify-between text-[11px] text-slate-400 mt-1">
                                <span>&lt;60% Critical</span>
                                <span>60-84% Warning</span>
                                <span>85%+ Good</span>
                            </div>
                        </div>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Branch & Inspector Details Card */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                        <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span className="w-2 h-2 rounded-full bg-indigo-600"></span>
                            Branch & Audit Details
                        </h2>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                    Branch Name <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    value={selectedBranch}
                                    onChange={(e) => setSelectedBranch(e.target.value)}
                                    className="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                                    required
                                >
                                    {branches.map((b) => (
                                        <option key={b.id} value={b.name}>{b.name}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                    Assigned IT Specialist <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={technicianName}
                                    onChange={(e) => setTechnicianName(e.target.value)}
                                    className="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                                    required
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                    Branch Manager Name <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    placeholder="e.g. Abebe Kebede"
                                    value={branchManager}
                                    onChange={(e) => setBranchManager(e.target.value)}
                                    className="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                                    required
                                />
                            </div>
                        </div>
                    </div>

                    {/* Checklist Categories & Questions */}
                    <div className="space-y-5">
                        {categories.map((cat, catIdx) => (
                            <div key={cat.id} className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                                {/* Category Header */}
                                <div className="bg-slate-50/70 p-5 border-b border-slate-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                                    <div>
                                        <h3 className="text-base font-bold text-slate-900 flex items-center gap-2">
                                            <span className="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 text-xs flex items-center justify-center font-bold">
                                                {catIdx + 1}
                                            </span>
                                            {cat.name}
                                        </h3>
                                        {cat.description && (
                                            <p className="text-xs text-slate-500 mt-0.5">{cat.description}</p>
                                        )}
                                    </div>

                                    {cat.has_photo && (
                                        <div>
                                            {photos[cat.id] ? (
                                                <div className="flex items-center gap-2">
                                                    <img
                                                        src={photos[cat.id]}
                                                        alt="Photo evidence"
                                                        className="w-10 h-10 object-cover rounded-lg border border-slate-200"
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => removePhoto(cat.id)}
                                                        className="text-xs text-rose-600 hover:text-rose-700 font-medium"
                                                    >
                                                        Remove
                                                    </button>
                                                </div>
                                            ) : (
                                                <label className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer shadow-sm transition">
                                                    <Camera className="w-3.5 h-3.5 text-indigo-600" />
                                                    Add Photo Evidence
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
                                </div>

                                {/* Items in this Category */}
                                <div className="p-5 divide-y divide-slate-100">
                                    {cat.items.map((item) => {
                                        const current = checklistState[item.id] || { status: 'OK', detail: '', skipReason: '' };
                                        const isOk = current.status === 'OK';
                                        const isIssue = current.status === 'Issue';
                                        const isSkip = current.status === 'Skip';

                                        return (
                                            <div key={item.id} className="py-4 first:pt-0 last:pb-0 space-y-3">
                                                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                                                    <div className="flex-1">
                                                        <div className="text-sm font-semibold text-slate-800 flex items-start gap-2">
                                                            <span className="mt-1">
                                                                {isOk ? (
                                                                    <span className="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                                                                ) : isIssue ? (
                                                                    <span className="w-2 h-2 rounded-full bg-rose-500 inline-block"></span>
                                                                ) : (
                                                                    <span className="w-2 h-2 rounded-full bg-purple-500 inline-block"></span>
                                                                )}
                                                            </span>
                                                            <span>{item.question}</span>
                                                        </div>
                                                        {item.help_text && (
                                                            <div className="text-xs text-slate-400 mt-0.5 ml-4 flex items-center gap-1">
                                                                <Info className="w-3 h-3 text-slate-400" />
                                                                {item.help_text}
                                                            </div>
                                                        )}
                                                    </div>

                                                    {/* Status Action Buttons */}
                                                    <div className="flex items-center gap-1.5 self-end sm:self-center bg-slate-50 p-1 rounded-xl border border-slate-200">
                                                        <button
                                                            type="button"
                                                            onClick={() => handleStatusChange(item.id, 'OK')}
                                                            className={`inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg transition ${
                                                                isOk
                                                                    ? 'bg-emerald-600 text-white shadow-sm'
                                                                    : 'text-slate-600 hover:text-emerald-700'
                                                            }`}
                                                        >
                                                            <Check className="w-3.5 h-3.5" />
                                                            OK
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleStatusChange(item.id, 'Issue')}
                                                            className={`inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg transition ${
                                                                isIssue
                                                                    ? 'bg-rose-600 text-white shadow-sm'
                                                                    : 'text-slate-600 hover:text-rose-700'
                                                            }`}
                                                        >
                                                            <AlertCircle className="w-3.5 h-3.5" />
                                                            Issue
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleStatusChange(item.id, 'Skip')}
                                                            className={`inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg transition ${
                                                                isSkip
                                                                    ? 'bg-purple-600 text-white shadow-sm'
                                                                    : 'text-slate-600 hover:text-purple-700'
                                                            }`}
                                                        >
                                                            <SkipForward className="w-3.5 h-3.5" />
                                                            Skip
                                                        </button>
                                                    </div>
                                                </div>

                                                {/* Issue Description Area */}
                                                {isIssue && (
                                                    <div className="bg-rose-50/60 p-3.5 rounded-xl border border-rose-200 ml-4 animate-fadeIn">
                                                        <label className="block text-xs font-semibold text-rose-800 mb-1">
                                                            Issue Description & Symptoms:
                                                        </label>
                                                        <textarea
                                                            rows={2}
                                                            placeholder="Describe the defect, error code, or physical fault..."
                                                            value={current.detail}
                                                            onChange={(e) => handleDetailChange(item.id, e.target.value)}
                                                            className="w-full text-xs p-2.5 border border-rose-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-rose-500"
                                                        ></textarea>
                                                    </div>
                                                )}

                                                {/* Skip Reason Area */}
                                                {isSkip && (
                                                    <div className="bg-purple-50/60 p-3.5 rounded-xl border border-purple-200 ml-4 animate-fadeIn">
                                                        <label className="block text-xs font-semibold text-purple-800 mb-1">
                                                            Reason for Skipping (Not applicable, hardware missing, etc.):
                                                        </label>
                                                        <textarea
                                                            rows={2}
                                                            placeholder="e.g. Branch does not have kitchen printer installed..."
                                                            value={current.skipReason}
                                                            onChange={(e) => handleSkipReasonChange(item.id, e.target.value)}
                                                            className="w-full text-xs p-2.5 border border-purple-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-purple-500"
                                                        ></textarea>
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Summary & Corrective Actions Card */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                        <h2 className="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span className="w-2 h-2 rounded-full bg-indigo-600"></span>
                            Audit Summary & Corrective Actions
                        </h2>

                        <div className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                    Overall Issues Summary
                                </label>
                                <textarea
                                    rows={3}
                                    placeholder="Summarize the key problems discovered during this branch audit..."
                                    value={issuesSummary}
                                    onChange={(e) => setIssuesSummary(e.target.value)}
                                    className="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                                ></textarea>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                        Immediate Actions Taken
                                    </label>
                                    <textarea
                                        rows={3}
                                        placeholder="Cables reconnected, printer roller cleaned, restarted service..."
                                        value={actionsTaken}
                                        onChange={(e) => setActionsTaken(e.target.value)}
                                        className="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                                    ></textarea>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-700 uppercase mb-1.5">
                                        Recommendations & Follow-up
                                    </label>
                                    <textarea
                                        rows={3}
                                        placeholder="Replace faulty UPS battery, dispatch network technician..."
                                        value={recommendations}
                                        onChange={(e) => setRecommendations(e.target.value)}
                                        className="w-full px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Submit Bar */}
                    <div className="flex items-center justify-end gap-3 pt-4">
                        <a
                            href="/it-surveys"
                            className="px-5 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-800 transition"
                        >
                            Cancel
                        </a>
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            className="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md transition disabled:opacity-50"
                        >
                            {isSubmitting ? (
                                <span>Submitting Audit...</span>
                            ) : (
                                <>
                                    <Send className="w-4 h-4" />
                                    Submit Inspection Report
                                </>
                            )}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
