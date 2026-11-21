import { Head } from '@inertiajs/react';
import React, { useEffect, useMemo, useState } from 'react';
import {
    AlertCircle,
    AlertTriangle,
    Camera,
    Check,
    CheckCircle2,
    Clock,
    HardDrive,
    Laptop,
    Send,
    ShieldAlert,
    SkipForward,
    Video,
    X,
    XCircle,
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
    type: string;
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

export type SurveyType = 'hardware' | 'software' | 'cctv_setup' | 'cctv_daily';

interface Props {
    type?: string;
    categories: Category[];
    branches: Branch[];
    technicians: User[];
    initialBranch?: string;
}

interface ItemState {
    status: 'OK' | 'Issue' | 'Skip';
    detail: string;
    skipReason: string;
}

export default function ItSurveyMiniApp({
    type = 'hardware',
    categories,
    branches,
    technicians,
    initialBranch = '',
}: Props) {
    const getInitialType = (t: string): SurveyType => {
        if (t === 'cctv_setup' || t === 'cctv_onetime' || t === 'setup') return 'cctv_setup';
        if (t === 'cctv_daily' || t === 'daily') return 'cctv_daily';
        if (t === 'cctv') return 'cctv_setup';
        if (t === 'software') return 'software';
        return 'hardware';
    };

    const [surveyType, setSurveyType] = useState<SurveyType>(() => getInitialType(type));
    const [selectedBranch, setSelectedBranch] = useState(
        initialBranch || branches[0]?.name || 'Bole Medhanialem'
    );
    const [technicianName, setTechnicianName] = useState('');
    const [branchManager, setBranchManager] = useState('');
    const [cameraInstallReason, setCameraInstallReason] = useState('New Camera Installed');
    const [newCameraCount, setNewCameraCount] = useState<number>(1);
    const [cameraLocation, setCameraLocation] = useState('');
    const [issuesSummary, setIssuesSummary] = useState('');
    const [actionsTaken, setActionsTaken] = useState('');
    const [recommendations, setRecommendations] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submittedData, setSubmittedData] = useState<{
        uuid: string;
        score: number;
        status: string;
    } | null>(null);

    // Filter categories dynamically by selected audit type
    const activeCategories = useMemo(() => {
        if (surveyType === 'cctv_setup') {
            return categories.filter(
                (cat) => cat.type === 'cctv_setup' || (cat.type === 'cctv' && cat.name.toLowerCase().includes('one-time'))
            );
        }
        if (surveyType === 'cctv_daily') {
            return categories.filter(
                (cat) => cat.type === 'cctv_daily' || (cat.type === 'cctv' && cat.name.toLowerCase().includes('daily'))
            );
        }
        return categories.filter((cat) => (cat.type || 'hardware') === surveyType);
    }, [categories, surveyType]);

    // Switch survey type and sync URL param
    const handleTypeSwitch = (newType: SurveyType) => {
        setSurveyType(newType);
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('type', newType);
            window.history.replaceState({}, '', url.toString());
        } catch {}
    };

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

    // Telegram Web App auto-detection & initialization
    useEffect(() => {
        const tg = (window as any).Telegram?.WebApp;
        if (tg) {
            try {
                tg.ready();
                tg.expand();

                if (tg.initDataUnsafe?.user) {
                    const u = tg.initDataUnsafe.user;
                    const fullName = [u.first_name, u.last_name].filter(Boolean).join(' ');
                    if (fullName) {
                        setTechnicianName(fullName);
                    } else if (u.username) {
                        setTechnicianName('@' + u.username);
                    }
                }
            } catch (err) {
                console.error('Telegram WebApp error', err);
            }
        }
    }, []);

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

    // Real-time Health Score computed specifically for activeCategories
    const { healthScore, passedCount, issueCount, skipCount, totalCount, healthStatus } = useMemo(() => {
        let passed = 0;
        let issue = 0;
        let skip = 0;
        let total = 0;

        activeCategories.forEach((cat) => {
            cat.items.forEach((item) => {
                total++;
                const itemSt = checklistState[item.id]?.status || 'OK';
                if (itemSt === 'OK') passed++;
                else if (itemSt === 'Issue') issue++;
                else if (itemSt === 'Skip') skip++;
            });
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
    }, [activeCategories, checklistState]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!selectedBranch) {
            alert('Please select a branch.');
            return;
        }
        if (!technicianName.trim()) {
            alert('Please provide technician / inspector name.');
            return;
        }
        if (!branchManager.trim()) {
            alert('Please provide branch manager name.');
            return;
        }

        setIsSubmitting(true);

        const formattedChecklist = activeCategories.flatMap((cat) =>
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

        try {
            const res = await fetch('/it-surveys/mini-app', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    branch_name: selectedBranch,
                    technician_name: technicianName,
                    branch_manager: branchManager,
                    type: surveyType,
                    camera_install_reason: surveyType === 'cctv_setup' ? cameraInstallReason : null,
                    new_camera_count: surveyType === 'cctv_setup' ? newCameraCount : null,
                    camera_location: surveyType === 'cctv_setup' ? cameraLocation : null,
                    checklist_data: formattedChecklist,
                    issues_summary: issuesSummary,
                    actions_taken: surveyType === 'cctv_setup'
                        ? `[Camera Installation: ${cameraInstallReason} (${newCameraCount} camera(s) at ${cameraLocation || 'Not specified'})] ${actionsTaken}`.trim()
                        : actionsTaken,
                    recommendations: recommendations,
                    photos: Object.values(photos),
                }),
            });

            const data = await res.json();
            if (data.success) {
                setSubmittedData({
                    uuid: data.report_uuid,
                    score: data.health_score,
                    status: data.health_status,
                });
            } else {
                alert(data.message || 'Submission failed. Please check required fields.');
            }
        } catch (err: any) {
            alert('Network or submission error: ' + err.message);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleCloseTelegram = () => {
        const tg = (window as any).Telegram?.WebApp;
        if (tg && typeof tg.close === 'function') {
            tg.close();
        } else {
            window.close();
        }
    };

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900 pb-12 antialiased selection:bg-indigo-500 selection:text-white">
            <Head>
                <title>Kaldis IT Branch Survey | Telegram Mini App</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
                <script src="https://telegram.org/js/telegram-web-app.js"></script>
            </Head>

            <div className="max-w-md mx-auto p-4 space-y-4">
                {/* Telegram Brand Header */}
                <div className="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <img
                            src="/images/logo.png"
                            alt="Kaldis Coffee"
                            className="w-10 h-10 object-contain rounded-xl border border-slate-100"
                            onError={(e) => {
                                (e.target as HTMLElement).style.display = 'none';
                            }}
                        />
                        <div>
                            <div className="flex items-center gap-1.5">
                                <h1 className="text-sm font-bold text-slate-900 leading-tight">IT Branch Survey</h1>
                                <span className="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    Mini App
                                </span>
                            </div>
                            <p className="text-[11px] text-slate-500">Kaldis Coffee IT Infrastructure Audit</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        onClick={handleCloseTelegram}
                        className="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition"
                        title="Close Mini App"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Success View Screen */}
                {submittedData ? (
                    <div className="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm text-center space-y-4 animate-in fade-in zoom-in-95 duration-200">
                        <div className="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto">
                            <CheckCircle2 className="w-8 h-8" />
                        </div>

                        <div>
                            <h2 className="text-lg font-bold text-slate-900">Inspection Submitted!</h2>
                            <p className="text-xs text-slate-500 mt-1">
                                Audit report for <span className="font-semibold text-slate-800">{selectedBranch}</span> was saved and notified to Telegram.
                            </p>
                        </div>

                        <div className="bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs space-y-1.5 text-left">
                            <div className="flex justify-between">
                                <span className="text-slate-500">Health Score:</span>
                                <span className="font-bold text-slate-900">{submittedData.score}% ({submittedData.status})</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-slate-500">Report UUID:</span>
                                <span className="font-mono text-[11px] text-slate-700 truncate max-w-[170px]">{submittedData.uuid}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-slate-500">Inspector:</span>
                                <span className="font-medium text-slate-800">{technicianName}</span>
                            </div>
                        </div>

                        <div className="space-y-2 pt-2">
                            <button
                                type="button"
                                onClick={handleCloseTelegram}
                                className="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm transition"
                            >
                                Close Telegram Mini App
                            </button>

                            <button
                                type="button"
                                onClick={() => setSubmittedData(null)}
                                className="w-full py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-medium transition"
                            >
                                Start New Inspection
                            </button>
                        </div>
                    </div>
                ) : (
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {/* Audit Type Selector - 4 Separated Types */}
                        <div className="grid grid-cols-2 gap-1.5 bg-slate-200/70 p-1.5 rounded-2xl">
                            <button
                                type="button"
                                onClick={() => handleTypeSwitch('hardware')}
                                className={`flex items-center justify-center gap-1.5 py-2.5 px-2 text-xs font-bold rounded-xl transition ${
                                    surveyType === 'hardware'
                                        ? 'bg-white text-indigo-700 shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                <HardDrive className="w-3.5 h-3.5" />
                                Hardware Audit
                            </button>
                            <button
                                type="button"
                                onClick={() => handleTypeSwitch('software')}
                                className={`flex items-center justify-center gap-1.5 py-2.5 px-2 text-xs font-bold rounded-xl transition ${
                                    surveyType === 'software'
                                        ? 'bg-white text-cyan-700 shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                <Laptop className="w-3.5 h-3.5" />
                                Software Audit
                            </button>
                            <button
                                type="button"
                                onClick={() => handleTypeSwitch('cctv_setup')}
                                className={`flex items-center justify-center gap-1.5 py-2.5 px-2 text-xs font-bold rounded-xl transition ${
                                    surveyType === 'cctv_setup'
                                        ? 'bg-white text-rose-700 shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                <ShieldAlert className="w-3.5 h-3.5" />
                                CCTV 1. One-Time
                            </button>
                            <button
                                type="button"
                                onClick={() => handleTypeSwitch('cctv_daily')}
                                className={`flex items-center justify-center gap-1.5 py-2.5 px-2 text-xs font-bold rounded-xl transition ${
                                    surveyType === 'cctv_daily'
                                        ? 'bg-white text-emerald-700 shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                <Clock className="w-3.5 h-3.5" />
                                CCTV 2. Daily
                            </button>
                        </div>

                        {/* Real-time Health Score Card */}
                        <div className="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm space-y-2">
                            <div className="flex items-center justify-between">
                                <div>
                                    <div className="text-[11px] text-slate-500 font-medium uppercase tracking-wider">Compliance Health</div>
                                    <div className="text-xl font-black text-slate-900">{healthScore}%</div>
                                </div>
                                <span className={`px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider border ${
                                    healthStatus === 'Good'
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : healthStatus === 'Warning'
                                        ? 'bg-amber-50 text-amber-700 border-amber-200'
                                        : 'bg-rose-50 text-rose-700 border-rose-200'
                                }`}>
                                    {healthStatus}
                                </span>
                            </div>

                            {/* Progress bar */}
                            <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden border border-slate-200">
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

                            <div className="text-[11px] text-slate-500 flex justify-between pt-0.5">
                                <span>{passedCount} OK</span>
                                <span>{issueCount} Issues</span>
                                <span>{skipCount} Skipped</span>
                            </div>
                        </div>

                        {/* Branch & Inspector Info */}
                        <div className="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm space-y-3">
                            <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">Branch Details</h3>

                            <div className="space-y-1">
                                <label className="text-xs font-medium text-slate-700">Select Branch *</label>
                                <select
                                    value={selectedBranch}
                                    onChange={(e) => setSelectedBranch(e.target.value)}
                                    className="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    required
                                >
                                    {branches.map((b) => (
                                        <option key={b.id} value={b.name}>{b.name}</option>
                                    ))}
                                </select>
                            </div>

                            <div className="space-y-1">
                                <label className="text-xs font-medium text-slate-700">Technician / Inspector Name *</label>
                                <input
                                    type="text"
                                    value={technicianName}
                                    onChange={(e) => setTechnicianName(e.target.value)}
                                    placeholder="Your full name"
                                    className="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    required
                                />
                            </div>

                            <div className="space-y-1">
                                <label className="text-xs font-medium text-slate-700">Branch Manager *</label>
                                <input
                                    type="text"
                                    value={branchManager}
                                    onChange={(e) => setBranchManager(e.target.value)}
                                    placeholder="Manager on duty"
                                    className="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    required
                                />
                            </div>
                        </div>

                        {/* New Camera Installation Context (Only for One-Time CCTV Setup) */}
                        {surveyType === 'cctv_setup' && (
                            <div className="bg-gradient-to-br from-rose-50/90 to-amber-50/60 border border-rose-200 rounded-2xl p-4 shadow-sm space-y-3">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <div className="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0">
                                            <Video className="w-4 h-4" />
                                        </div>
                                        <div>
                                            <h4 className="text-xs font-bold text-slate-900">New Camera Installation Details</h4>
                                            <p className="text-[11px] text-slate-500">Record camera details installed at {selectedBranch}</p>
                                        </div>
                                    </div>
                                    <span className="text-[10px] bg-rose-100/90 text-rose-700 font-bold px-2 py-0.5 rounded-full border border-rose-200">
                                        Setup Audit
                                    </span>
                                </div>

                                <div className="grid grid-cols-2 gap-2.5 pt-1">
                                    <div className="space-y-1">
                                        <label className="text-[11px] font-semibold text-slate-700">Installation Reason</label>
                                        <select
                                            value={cameraInstallReason}
                                            onChange={(e) => setCameraInstallReason(e.target.value)}
                                            className="w-full px-2.5 py-2 text-xs border border-rose-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-rose-500 font-medium"
                                        >
                                            <option value="New Camera Installed">New Camera Installed</option>
                                            <option value="Camera Replacement">Camera Replacement</option>
                                            <option value="New Branch Setup">New Branch Setup</option>
                                            <option value="Routine Hardening">Routine Hardening</option>
                                        </select>
                                    </div>

                                    <div className="space-y-1">
                                        <label className="text-[11px] font-semibold text-slate-700"># Cameras Installed</label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="32"
                                            value={newCameraCount}
                                            onChange={(e) => setNewCameraCount(parseInt(e.target.value) || 1)}
                                            className="w-full px-2.5 py-2 text-xs border border-rose-200 rounded-xl bg-white font-bold text-center focus:outline-none focus:ring-2 focus:ring-rose-500"
                                            required
                                        />
                                    </div>
                                </div>

                                <div className="space-y-1">
                                    <label className="text-[11px] font-semibold text-slate-700">Camera Location / Area Covered</label>
                                    <input
                                        type="text"
                                        value={cameraLocation}
                                        onChange={(e) => setCameraLocation(e.target.value)}
                                        placeholder="e.g. Cashier Counter 1, Store Room, Entrance Gate, Espresso Bar"
                                        className="w-full px-3 py-2 text-xs border border-rose-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-rose-500 placeholder:text-slate-400"
                                    />
                                </div>
                            </div>
                        )}

                        {/* Checklist Categories & Questions */}
                        <div className="space-y-3">
                            {activeCategories.length > 0 ? (
                                activeCategories.map((cat, catIdx) => (
                                <div key={cat.id} className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                                    <div className="p-3 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <span className="w-5 h-5 rounded-md bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center">
                                                {catIdx + 1}
                                            </span>
                                            <span className="text-xs font-bold text-slate-800">{cat.name}</span>
                                        </div>

                                        {cat.has_photo && (
                                            <div>
                                                {photos[cat.id] ? (
                                                    <div className="flex items-center gap-1.5">
                                                        <span className="text-[11px] text-emerald-600 font-medium flex items-center gap-0.5">
                                                            <Check className="w-3 h-3" /> Photo
                                                        </span>
                                                        <button
                                                            type="button"
                                                            onClick={() => removePhoto(cat.id)}
                                                            className="text-[11px] text-rose-600 hover:underline"
                                                        >
                                                            Remove
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <label className="cursor-pointer inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-medium text-slate-700 bg-white border border-slate-200 rounded-md hover:bg-slate-50 transition">
                                                        <Camera className="w-3 h-3 text-slate-500" />
                                                        Snap
                                                        <input
                                                            type="file"
                                                            accept="image/*"
                                                            capture="environment"
                                                            className="hidden"
                                                            onChange={(e) => handlePhotoUpload(cat.id, e)}
                                                        />
                                                    </label>
                                                )}
                                            </div>
                                        )}
                                    </div>

                                    <div className="divide-y divide-slate-100 p-2 space-y-2">
                                        {cat.items.map((item) => {
                                            const current = checklistState[item.id] || { status: 'OK', detail: '', skipReason: '' };
                                            return (
                                                <div key={item.id} className="p-2 space-y-1.5">
                                                    <div className="text-xs font-medium text-slate-800 leading-snug">
                                                        {item.question}
                                                    </div>
                                                    {item.help_text && (
                                                        <div className="text-[11px] text-slate-400">{item.help_text}</div>
                                                    )}

                                                    {/* Touch 3-state buttons */}
                                                    <div className="grid grid-cols-3 gap-1.5 pt-1">
                                                        <button
                                                            type="button"
                                                            onClick={() => handleStatusChange(item.id, 'OK')}
                                                            className={`py-1.5 text-xs font-semibold rounded-lg border transition ${
                                                                current.status === 'OK'
                                                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                                                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            OK / Pass
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleStatusChange(item.id, 'Issue')}
                                                            className={`py-1.5 text-xs font-semibold rounded-lg border transition ${
                                                                current.status === 'Issue'
                                                                    ? 'bg-rose-50 text-rose-700 border-rose-300'
                                                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            Issue
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => handleStatusChange(item.id, 'Skip')}
                                                            className={`py-1.5 text-xs font-semibold rounded-lg border transition ${
                                                                current.status === 'Skip'
                                                                    ? 'bg-slate-100 text-slate-800 border-slate-300'
                                                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            Skip / NA
                                                        </button>
                                                    </div>

                                                    {/* Issue Details input */}
                                                    {current.status === 'Issue' && (
                                                        <div className="pt-1">
                                                            <input
                                                                type="text"
                                                                value={current.detail}
                                                                onChange={(e) => handleDetailChange(item.id, e.target.value)}
                                                                placeholder="Describe defect, error, or fault..."
                                                                className="w-full px-2.5 py-1.5 text-xs border border-rose-200 rounded-lg bg-rose-50/40 focus:outline-none focus:ring-1 focus:ring-rose-500"
                                                            />
                                                        </div>
                                                    )}

                                                    {/* Skip Reason input */}
                                                    {current.status === 'Skip' && (
                                                        <div className="pt-1">
                                                            <input
                                                                type="text"
                                                                value={current.skipReason}
                                                                onChange={(e) => handleSkipReasonChange(item.id, e.target.value)}
                                                                placeholder="Reason for skipping (e.g. Device not installed)..."
                                                                className="w-full px-2.5 py-1.5 text-xs border border-slate-200 rounded-lg bg-slate-50 focus:outline-none"
                                                            />
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="bg-white rounded-2xl p-6 text-center border border-slate-200 text-xs text-slate-500">
                                No checklist items found for this audit type.
                            </div>
                        )}
                        </div>

                        {/* Summary & Recommendations */}
                        <div className="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm space-y-3">
                            <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">Findings & Actions</h3>

                            <div className="space-y-1">
                                <label className="text-xs font-medium text-slate-700">Issues Summary</label>
                                <textarea
                                    rows={2}
                                    value={issuesSummary}
                                    onChange={(e) => setIssuesSummary(e.target.value)}
                                    placeholder="Summary of critical issues..."
                                    className="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>

                            <div className="space-y-1">
                                <label className="text-xs font-medium text-slate-700">Actions Taken On-Site</label>
                                <textarea
                                    rows={2}
                                    value={actionsTaken}
                                    onChange={(e) => setActionsTaken(e.target.value)}
                                    placeholder="Repairs, cable replacement, software restarts..."
                                    className="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>

                            <div className="space-y-1">
                                <label className="text-xs font-medium text-slate-700">Recommendations</label>
                                <textarea
                                    rows={2}
                                    value={recommendations}
                                    onChange={(e) => setRecommendations(e.target.value)}
                                    placeholder="Preventative recommendations..."
                                    className="w-full px-3 py-2 text-xs border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        {/* Submit Button */}
                        <div className="pt-2 sticky bottom-4">
                            <button
                                type="submit"
                                disabled={isSubmitting}
                                className="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white rounded-xl text-xs font-bold shadow-md transition flex items-center justify-center gap-2 disabled:opacity-50"
                            >
                                <Send className="w-4 h-4" />
                                {isSubmitting ? 'Submitting Report...' : 'Submit Inspection Report'}
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
}
