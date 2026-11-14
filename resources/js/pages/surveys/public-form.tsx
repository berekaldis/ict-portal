import { Button } from '@/components/ui/badge';
import { Head } from '@inertiajs/react';
import { CheckCircle2, Coffee, MapPin, Send, Sparkles } from 'lucide-react';
import React, { useState } from 'react';

interface Props {
    initialBranch: string;
    initialTable?: string;
    branches: string[];
}

export default function PublicSurveyForm({ initialBranch, initialTable = '', branches }: Props) {
    const [branch, setBranch] = useState(initialBranch || 'Bole Medhanialem');
    const [tableNumber, setTableNumber] = useState(initialTable || '');
    const [submitted, setSubmitted] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);

    // Form state matching 20 questions
    const [formData, setFormData] = useState<{
        q1_frequency: string;
        q2_reason: string;
        q2_reason_other: string;
        q3_spend: string;
        q4_orders: string[];
        q4_orders_other: string;
        q5_missing_menu: string;
        q5_missing_detail: string;
        q6_factors: string[];
        q6_factors_other: string;
        q7_location_rating: string;
        q8_price_rating: string;
        q9_improvements: string[];
        q9_improvements_other: string;
        q10_consider_other_cafe: string;
        q11_why_other_cafe: string;
        q11_why_other_other: string;
        q12_if_branch_closed: string;
        q12_if_branch_closed_other: string;
        q13_importance: string;
        q14_waiter_rating: string;
        q15_waiter_improvements: string[];
        q15_waiter_improvements_other: string;
        q16_interior_rating: string;
        q17_interior_improvements: string[];
        q17_interior_improvements_other: string;
        q18_waiter_recommendations: string;
        q19_service_suggestions: string;
        q20_visit_motivation: string;
    }>({
        q1_frequency: '',
        q2_reason: '',
        q2_reason_other: '',
        q3_spend: '',
        q4_orders: [],
        q4_orders_other: '',
        q5_missing_menu: '',
        q5_missing_detail: '',
        q6_factors: [],
        q6_factors_other: '',
        q7_location_rating: '',
        q8_price_rating: '',
        q9_improvements: [],
        q9_improvements_other: '',
        q10_consider_other_cafe: '',
        q11_why_other_cafe: '',
        q11_why_other_other: '',
        q12_if_branch_closed: '',
        q12_if_branch_closed_other: '',
        q13_importance: '',
        q14_waiter_rating: '',
        q15_waiter_improvements: [],
        q15_waiter_improvements_other: '',
        q16_interior_rating: '',
        q17_interior_improvements: [],
        q17_interior_improvements_other: '',
        q18_waiter_recommendations: '',
        q19_service_suggestions: '',
        q20_visit_motivation: '',
    });

    const handleCheckboxChange = (field: 'q4_orders' | 'q6_factors' | 'q9_improvements' | 'q15_waiter_improvements' | 'q17_interior_improvements', value: string) => {
        setFormData((prev) => {
            const list = prev[field];
            const nextList = list.includes(value) ? list.filter((item) => item !== value) : [...list, value];
            return { ...prev, [field]: nextList };
        });
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        setErrorMsg(null);

        try {
            const res = await fetch('/survey/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                body: JSON.stringify({
                    branch,
                    table_number: tableNumber,
                    ...formData,
                }),
            });

            const json = await res.json();
            if (res.ok && json.success) {
                setSubmitted(true);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                setErrorMsg(json.message || 'አስተያየትዎን ማስገባት አልተቻለም። እባክዎ እንደገና ይሞክሩ።');
            }
        } catch (err) {
            setErrorMsg('የኔትወርክ ስህተት አጋጥሟል። እባክዎ እንደገና ይሞክሩ።');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="min-h-screen bg-stone-100 text-stone-800 selection:bg-amber-600 selection:text-white pb-16 font-sans">
            <Head title="የደንበኞች አስተያየት መጠይቅ | Kaldis Coffee" />

            {/* Header */}
            <div className="bg-[#4a2618] text-white py-8 px-4 text-center shadow-md relative overflow-hidden">
                <div className="max-w-2xl mx-auto space-y-3 relative z-10">
                    <div className="inline-flex items-center justify-center w-14 h-14 rounded-full bg-amber-500/20 border border-amber-400/40 text-amber-400 shadow-inner">
                        <Coffee className="w-7 h-7" />
                    </div>
                    <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight text-amber-100">
                        የደንበኞች አስተያየት መጠይቅ
                    </h1>
                    <div className="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-amber-400/30 px-4 py-1.5 rounded-full text-xs sm:text-sm text-amber-200">
                        <MapPin className="w-3.5 h-3.5 text-amber-400" />
                        <span>ቅርንጫፍ፡ <strong className="text-white">{branch}</strong></span>
                        {tableNumber && (
                            <>
                                <span className="opacity-40">•</span>
                                <span>ጠረጴዛ፡ <strong className="text-white">{tableNumber}</strong></span>
                            </>
                        )}
                    </div>
                    <p className="text-xs sm:text-sm text-stone-200/90 leading-relaxed max-w-lg mx-auto">
                        የሚሰጡን አስተያየት የዚህን ቅርንጫፍ አገልግሎት እና የምርት አማራጮች ለማሻሻል ይረዳናል። እባክዎ ጥቂት ደቂቃዎች ወስደው የሚሰማዎትን እውነተኛ አስተያየት ይስጡን።
                    </p>
                </div>
            </div>

            <div className="max-w-2xl mx-auto px-4 mt-6">
                {submitted ? (
                    <div className="bg-white rounded-2xl shadow-xl p-8 sm:p-10 text-center border border-emerald-100 space-y-5 animate-in fade-in duration-500">
                        <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center">
                            <CheckCircle2 className="w-10 h-10" />
                        </div>
                        <div className="space-y-2">
                            <h2 className="text-2xl font-bold text-stone-900">እናመሰግናለን!</h2>
                            <p className="text-stone-600 text-sm leading-relaxed max-w-md mx-auto">
                                የሰጡን አስተያየት ለአገልግሎታችን ማሻሻያ በጣም ጠቃሚ ነው። ለካልዲስ ቡና ያለዎት ፍቅር እና ድጋፍ ምንጊዜም ይበረታታል።
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => {
                                setSubmitted(false);
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            }}
                            className="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-[#4a2618] text-white text-xs font-semibold hover:bg-[#381c12] transition-colors shadow-sm"
                        >
                            አዲስ መጠይቅ ሙላ
                        </button>
                    </div>
                ) : (
                    <form onSubmit={handleSubmit} className="space-y-5">
                        {errorMsg && (
                            <div className="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl text-center">
                                {errorMsg}
                            </div>
                        )}

                        {/* Branch & Table selector */}
                        <div className="bg-white rounded-2xl p-5 shadow-xs border border-stone-200/80 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-bold text-stone-700 mb-1.5">ቅርንጫፍ (Branch)</label>
                                <select
                                    value={branch}
                                    onChange={(e) => setBranch(e.target.value)}
                                    className="w-full text-xs rounded-lg border-stone-300 bg-stone-50/50 py-2 px-3 focus:ring-amber-500 focus:border-amber-500 font-medium"
                                >
                                    {branches.map((b) => (
                                        <option key={b} value={b}>{b}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-stone-700 mb-1.5">የጠረጴዛ ቁጥር (ከተገኘ)</label>
                                <input
                                    type="text"
                                    placeholder="ምሳሌ፡ 05 ወይም Table 1"
                                    value={tableNumber}
                                    onChange={(e) => setTableNumber(e.target.value)}
                                    className="w-full text-xs rounded-lg border-stone-300 bg-stone-50/50 py-2 px-3 focus:ring-amber-500 focus:border-amber-500"
                                />
                            </div>
                        </div>

                        {/* Q1: Frequency */}
                        <div className="bg-white rounded-2xl p-5 shadow-xs border border-stone-200/80 space-y-3">
                            <h3 className="text-sm font-bold text-stone-900">1. ይህን ቅርንጫፍ ምን ያህል ጊዜ ይጎበኛሉ?</h3>
                            <div className="space-y-2">
                                {['የመጀመሪያ ጊዜዬ ነው', 'አልፎ አልፎ', 'በወር 1–2 ጊዜ', 'በሳምንት 1–2 ጊዜ', 'በሳምንት 3 ጊዜ ወይም ከዚያ በላይ'].map((opt) => (
                                    <label key={opt} className="flex items-center gap-3 p-2.5 rounded-xl border border-stone-200/60 hover:bg-stone-50 cursor-pointer text-xs">
                                        <input
                                            type="radio"
                                            name="q1_frequency"
                                            value={opt}
                                            checked={formData.q1_frequency === opt}
                                            onChange={(e) => setFormData({ ...formData, q1_frequency: e.target.value })}
                                            className="text-amber-700 focus:ring-amber-600"
                                        />
                                        <span>{opt}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Q2: Primary reason */}
                        <div className="bg-white rounded-2xl p-5 shadow-xs border border-stone-200/80 space-y-3">
                            <h3 className="text-sm font-bold text-stone-900">2. በዋናነት ለምን ወደዚህ ቅርንጫፍ ይመጣሉ?</h3>
                            <div className="space-y-2">
                                {['ቡና/መጠጥ', 'ቁርስ', 'ምሳ', 'ስብሰባ/ስራ', 'ከጓደኞች ጋር ለመገናኘት', 'ተወስዶ ለመሄድ (Takeaway)', 'ሌላ'].map((opt) => (
                                    <label key={opt} className="flex items-center gap-3 p-2.5 rounded-xl border border-stone-200/60 hover:bg-stone-50 cursor-pointer text-xs">
                                        <input
                                            type="radio"
                                            name="q2_reason"
                                            value={opt}
                                            checked={formData.q2_reason === opt}
                                            onChange={(e) => setFormData({ ...formData, q2_reason: e.target.value })}
                                            className="text-amber-700 focus:ring-amber-600"
                                        />
                                        <span>{opt}</span>
                                    </label>
                                ))}
                            </div>
                            {formData.q2_reason === 'ሌላ' && (
                                <input
                                    type="text"
                                    placeholder="ሌላ ምክንያት ይግለጹ..."
                                    value={formData.q2_reason_other}
                                    onChange={(e) => setFormData({ ...formData, q2_reason_other: e.target.value })}
                                    className="w-full text-xs rounded-lg border-stone-300 py-2 px-3 mt-2 focus:ring-amber-500"
                                />
                            )}
                        </div>

                        {/* Q3: Average spend */}
                        <div className="bg-white rounded-2xl p-5 shadow-xs border border-stone-200/80 space-y-3">
                            <h3 className="text-sm font-bold text-stone-900">3. በአንድ ጉብኝት በአማካይ ምን ያህል ወጪ ያደርጋሉ?</h3>
                            <div className="space-y-2">
                                {['ከ150 ብር በታች', '150–250 ብር', '251–350 ብር', '351–500 ብር', 'ከ500 ብር በላይ'].map((opt) => (
                                    <label key={opt} className="flex items-center gap-3 p-2.5 rounded-xl border border-stone-200/60 hover:bg-stone-50 cursor-pointer text-xs">
                                        <input
                                            type="radio"
                                            name="q3_spend"
                                            value={opt}
                                            checked={formData.q3_spend === opt}
                                            onChange={(e) => setFormData({ ...formData, q3_spend: e.target.value })}
                                            className="text-amber-700 focus:ring-amber-600"
                                        />
                                        <span>{opt}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Q4: Top ordered items */}
                        <div className="bg-white rounded-2xl p-5 shadow-xs border border-stone-200/80 space-y-3">
                            <h3 className="text-sm font-bold text-stone-900">
                                4. ብዙ ጊዜ ምን ያዛሉ? <span className="text-xs font-normal text-stone-500">(ከአንድ በላይ መምረጥ ይቻላል)</span>
                            </h3>
                            <div className="grid grid-cols-2 gap-2">
                                {['ትኩስ ቡና', 'ቀዝቃዛ መጠጦች', 'ፍራፔ', 'ሻይ', 'ቁርስ', 'ምሳ', 'ጣፋጭ/ዴዘርት', 'ሌላ'].map((opt) => (
                                    <label key={opt} className="flex items-center gap-2.5 p-2 rounded-xl border border-stone-200/60 hover:bg-stone-50 cursor-pointer text-xs">
                                        <input
                                            type="checkbox"
                                            checked={formData.q4_orders.includes(opt)}
                                            onChange={() => handleCheckboxChange('q4_orders', opt)}
                                            className="rounded text-amber-700 focus:ring-amber-600"
                                        />
                                        <span>{opt}</span>
                                    </label>
                                ))}
                            </div>
                            {formData.q4_orders.includes('ሌላ') && (
                                <input
                                    type="text"
                                    placeholder="ሌላ የሚታዘዝ ነገር..."
                                    value={formData.q4_orders_other}
                                    onChange={(e) => setFormData({ ...formData, q4_orders_other: e.target.value })}
                                    className="w-full text-xs rounded-lg border-stone-300 py-2 px-3 mt-2 focus:ring-amber-500"
                                />
                            )}
                        </div>

                        {/* Q5: Missing menu items */}
                        <div className="bg-white rounded-2xl p-5 shadow-xs border border-stone-200/80 space-y-3">
                            <h3 className="text-sm font-bold text-stone-900">5. አሁን በምናቀርበው ሜኑ ላይ የሌለ እና እንዲኖር የሚፈልጉት ምርት አለ?</h3>
                            <div className="flex gap-4">
                                {['አዎ', 'የለም'].map((opt) => (
                                    <label key={opt} className="flex items-center gap-2 p-2.5 px-4 rounded-xl border border-stone-200/60 hover:bg-stone-50 cursor-pointer text-xs">
                                        <input
                                            type="radio"
                                            name="q5_missing_menu"
                                            value={opt}
                                            checked={formData.q5_missing_menu === opt}
                                            onChange={(e) => setFormData({ ...formData, q5_missing_menu: e.target.value })}
                                            className="text-amber-700 focus:ring-amber-600"
                                        />
                                        <span>{opt}</span>
                                    </label>
                                ))}
                            </div>
                            {formData.q5_missing_menu === 'አዎ' && (
                                <input
                                    type="text"
                                    placeholder="አዎ ከሆነ፣ እባክዎ ይግለጹ (ምሳሌ፡ Oat milk, Special Sandwich)..."
                                    value={formData.q5_missing_detail}
                                    onChange={(e) => setFormData({ ...formData, q5_missing_detail: e.target.value })}
                                    className="w-full text-xs rounded-lg border-stone-300 py-2 px-3 mt-2 focus:ring-amber-500"
                                />
                            )}
                        </div>

                        {/* Q8: Price rating */}
                        <div className="bg-white rounded-2xl p-5 shadow-xs border border-stone-200/80 space-y-3">
                            <h3 className="text-sm font-bold text-stone-900">6. ስለ ምርቶቻችን ዋጋ ምን ይላሉ?</h3>
                            <div className="space-y-2">
                                {['በጣም ተመጣጣኝ', 'ተመጣጣኝ', 'መካከለኛ/ተገቢ', 'ውድ', 'በጣም ውድ'].map((opt) => (
                                    <label key={opt} className="flex items-center gap-3 p-2.5 rounded-xl border border-stone-200/60 hover:bg-stone-50 cursor-pointer text-xs">
                                        <input
                                            type="radio"
                                            name="q8_price_rating"
                                            value={opt}
                                            checked={formData.q8_price_rating === opt}
                                            onChange={(e) => setFormData({ ...formData, q8_price_rating: e.target.value })}
                                            className="text-amber-700 focus:ring-amber-600"
                                        />
                                        <span>{opt}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Q14: Waiter Rating */}
                        <div className="bg-white rounded-2xl p-5 shadow-xs border border-stone-200/80 space-y-3">
                            <h3 className="text-sm font-bold text-stone-900">7. የአገልጋዮችን (waiters) አገልግሎት እንዴት ይገመግማሉ?</h3>
                            <div className="space-y-2">
                                {['በጣም ጥሩ', 'ጥሩ', 'መካከለኛ', 'ደካማ', 'በጣም ደካማ'].map((opt) => (
                                    <label key={opt} className="flex items-center gap-3 p-2.5 rounded-xl border border-stone-200/60 hover:bg-stone-50 cursor-pointer text-xs">
                                        <input
                                            type="radio"
                                            name="q14_waiter_rating"
                                            value={opt}
                                            checked={formData.q14_waiter_rating === opt}
                                            onChange={(e) => setFormData({ ...formData, q14_waiter_rating: e.target.value })}
                                            className="text-amber-700 focus:ring-amber-600"
                                        />
                                        <span>{opt}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Q16: Interior Rating */}
                        <div className="bg-white rounded-2xl p-5 shadow-xs border border-stone-200/80 space-y-3">
                            <h3 className="text-sm font-bold text-stone-900">8. ስለ ዚህ ቅርንጫፍ የውስጥ ዲዛይን (Interior Design) ምን ያስባሉ?</h3>
                            <div className="space-y-2">
                                {['በጣም ጥሩ', 'ጥሩ', 'መካከለኛ', 'ደካማ', 'በጣም ደካማ'].map((opt) => (
                                    <label key={opt} className="flex items-center gap-3 p-2.5 rounded-xl border border-stone-200/60 hover:bg-stone-50 cursor-pointer text-xs">
                                        <input
                                            type="radio"
                                            name="q16_interior_rating"
                                            value={opt}
                                            checked={formData.q16_interior_rating === opt}
                                            onChange={(e) => setFormData({ ...formData, q16_interior_rating: e.target.value })}
                                            className="text-amber-700 focus:ring-amber-600"
                                        />
                                        <span>{opt}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        {/* Q19: Service suggestions */}
                        <div className="bg-white rounded-2xl p-5 shadow-xs border border-stone-200/80 space-y-3">
                            <h3 className="text-sm font-bold text-stone-900">9. የዚህን ቅርንጫፍ አገልግሎት ለማሻሻል ምን አይነት ለውጥ ቢደረግ ይፈልጋሉ?</h3>
                            <textarea
                                rows={3}
                                placeholder="አስተያየትዎን እዚህ ይጻፉ..."
                                value={formData.q19_service_suggestions}
                                onChange={(e) => setFormData({ ...formData, q19_service_suggestions: e.target.value })}
                                className="w-full text-xs rounded-xl border-stone-300 py-2.5 px-3 focus:ring-amber-500 focus:border-amber-500"
                            />
                        </div>

                        {/* Q20: Visit motivation */}
                        <div className="bg-white rounded-2xl p-5 shadow-xs border border-stone-200/80 space-y-3">
                            <h3 className="text-sm font-bold text-stone-900">10. ይህን ቅርንጫፍ በተደጋጋሚ እንዲጎበኙ ምን ሊያደርግዎት ይችላል?</h3>
                            <textarea
                                rows={3}
                                placeholder="አስተያየትዎን እዚህ ይጻፉ..."
                                value={formData.q20_visit_motivation}
                                onChange={(e) => setFormData({ ...formData, q20_visit_motivation: e.target.value })}
                                className="w-full text-xs rounded-xl border-stone-300 py-2.5 px-3 focus:ring-amber-500 focus:border-amber-500"
                            />
                        </div>

                        {/* Submit Button */}
                        <div className="pt-3 text-center space-y-3">
                            <button
                                type="submit"
                                disabled={submitting}
                                className="w-full py-3.5 px-6 rounded-xl bg-[#4a2618] hover:bg-[#381c12] text-white font-bold text-sm shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                            >
                                <Send className="w-4 h-4" />
                                {submitting ? 'በመላክ ላይ...' : 'አስተያየት አስገባ (Submit)'}
                            </button>
                            <p className="text-[11px] text-stone-500">
                                እናመሰግናለን! የሰጡን አስተያየት ለአገልግሎታችን ማሻሻያ በጣም ጠቃሚ ነው።
                            </p>
                        </div>
                    </form>
                )}
            </div>

            {/* Footer */}
            <div className="mt-12 text-center text-xs text-stone-500 space-y-1">
                <p>© {new Date().getFullYear()} Kaldis Coffee • የደንበኞች አስተያየት መጠይቅ</p>
            </div>
        </div>
    );
}
