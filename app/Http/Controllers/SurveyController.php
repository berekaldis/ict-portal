<?php

namespace App\Http\Controllers;

use App\Models\HrSetting;
use App\Models\Survey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SurveyController extends Controller
{
    /**
     * Public survey form for customers (in Amharic).
     */
    public function publicForm(Request $request): Response
    {
        $branch = $request->query('branch', 'Bole Medhanialem');
        $table = $request->query('table', '');

        // Fetch active branches from HR settings or default Kaldis branches
        $branches = HrSetting::where('type', 'branch')->pluck('value')->toArray();
        if (empty($branches)) {
            $branches = [
                'Bole Medhanialem', 'Sarbet', 'Kazanchis', 'Piassa', 'CMC',
                'Mexico', 'Gerji', 'Jemo', 'Megenagna', '4 Kilo', 'Bole Atlas'
            ];
        }

        return Inertia::render('surveys/public-form', [
            'initialBranch' => $branch,
            'initialTable' => $table,
            'branches' => $branches,
        ]);
    }

    /**
     * Handle public customer survey submission.
     */
    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch' => 'required|string|max:150',
            'table_number' => 'nullable|string|max:50',
            'q1_frequency' => 'nullable|string|max:100',
            'q2_reason' => 'nullable|string|max:150',
            'q2_reason_other' => 'nullable|string|max:255',
            'q3_spend' => 'nullable|string|max:100',
            'q4_orders' => 'nullable|array',
            'q4_orders_other' => 'nullable|string|max:255',
            'q5_missing_menu' => 'nullable|string|max:50',
            'q5_missing_detail' => 'nullable|string',
            'q6_factors' => 'nullable|array',
            'q6_factors_other' => 'nullable|string|max:255',
            'q7_location_rating' => 'nullable|string|max:100',
            'q8_price_rating' => 'nullable|string|max:100',
            'q9_improvements' => 'nullable|array',
            'q9_improvements_other' => 'nullable|string|max:255',
            'q10_consider_other_cafe' => 'nullable|string|max:50',
            'q11_why_other_cafe' => 'nullable|string|max:150',
            'q11_why_other_other' => 'nullable|string|max:255',
            'q12_if_branch_closed' => 'nullable|string|max:150',
            'q12_if_branch_closed_other' => 'nullable|string|max:255',
            'q13_importance' => 'nullable|string|max:100',
            'q14_waiter_rating' => 'nullable|string|max:100',
            'q15_waiter_improvements' => 'nullable|array',
            'q15_waiter_improvements_other' => 'nullable|string|max:255',
            'q16_interior_rating' => 'nullable|string|max:100',
            'q17_interior_improvements' => 'nullable|array',
            'q17_interior_improvements_other' => 'nullable|string|max:255',
            'q18_waiter_recommendations' => 'nullable|string',
            'q19_service_suggestions' => 'nullable|string',
            'q20_visit_motivation' => 'nullable|string',
        ]);

        $survey = Survey::create(array_merge($validated, [
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'እናመሰግናለን! የሰጡን አስተያየት ለአገልግሎታችን ማሻሻያ በጣም ጠቃሚ ነው።',
            'id' => $survey->id,
        ]);
    }

    /**
     * Admin survey analytics & responses view.
     */
    public function index(Request $request): Response
    {
        $selectedBranch = $request->query('branch', 'all');
        $search = $request->query('search', '');

        $query = Survey::query();
        if ($selectedBranch !== 'all' && !empty($selectedBranch)) {
            $query->where('branch', $selectedBranch);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('branch', 'like', "%{$search}%")
                    ->orWhere('table_number', 'like', "%{$search}%")
                    ->orWhere('q18_waiter_recommendations', 'like', "%{$search}%")
                    ->orWhere('q19_service_suggestions', 'like', "%{$search}%")
                    ->orWhere('q20_visit_motivation', 'like', "%{$search}%");
            });
        }

        $totalResponses = (clone $query)->count();

        // Calculate KPI Metrics
        $waiterSatisfied = (clone $query)->where(function ($q) {
            $q->where('q14_waiter_rating', 'like', '%በጣም ጥሩ%')
                ->orWhere('q14_waiter_rating', 'like', '%ጥሩ%');
        })->count();
        $waiterPct = $totalResponses > 0 ? round(($waiterSatisfied / $totalResponses) * 100) : 0;

        $interiorSatisfied = (clone $query)->where(function ($q) {
            $q->where('q16_interior_rating', 'like', '%በጣም ጥሩ%')
                ->orWhere('q16_interior_rating', 'like', '%ጥሩ%');
        })->count();
        $interiorPct = $totalResponses > 0 ? round(($interiorSatisfied / $totalResponses) * 100) : 0;

        $priceFair = (clone $query)->where(function ($q) {
            $q->where('q8_price_rating', 'like', '%ተመጣጣኝ%')
                ->orWhere('q8_price_rating', 'like', '%መካከለኛ%');
        })->count();
        $pricePct = $totalResponses > 0 ? round(($priceFair / $totalResponses) * 100) : 0;

        // Chart aggregation: Frequency
        $frequencyStats = (clone $query)->whereNotNull('q1_frequency')
            ->select('q1_frequency as label', DB::raw('count(*) as count'))
            ->groupBy('q1_frequency')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // Chart aggregation: Spend per visit
        $spendStats = (clone $query)->whereNotNull('q3_spend')
            ->select('q3_spend as label', DB::raw('count(*) as count'))
            ->groupBy('q3_spend')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // Top customer text voice quotes
        $quotes = (clone $query)->whereNotNull('q19_service_suggestions')
            ->where('q19_service_suggestions', '!=', '')
            ->orderByDesc('created_at')
            ->take(8)
            ->get(['id', 'branch', 'table_number', 'q19_service_suggestions', 'created_at']);

        // Paginated responses list
        $responses = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $allBranches = Survey::distinct()->pluck('branch')->filter()->values()->toArray();

        return Inertia::render('surveys/index', [
            'responses' => $responses,
            'branches' => $allBranches,
            'selectedBranch' => $selectedBranch,
            'filters' => ['search' => $search],
            'kpis' => [
                'total' => $totalResponses,
                'waiterSatisfactionPct' => $waiterPct,
                'interiorSatisfactionPct' => $interiorPct,
                'priceFairPct' => $pricePct,
            ],
            'frequencyStats' => $frequencyStats,
            'spendStats' => $spendStats,
            'recentQuotes' => $quotes,
        ]);
    }

    /**
     * QR Code generator and printable Table Tent Cards.
     */
    public function qrGenerator(Request $request): Response
    {
        $branches = HrSetting::where('type', 'branch')->pluck('value')->toArray();
        if (empty($branches)) {
            $branches = [
                'Bole Medhanialem', 'Sarbet', 'Kazanchis', 'Piassa', 'CMC',
                'Mexico', 'Gerji', 'Jemo', 'Megenagna', '4 Kilo', 'Bole Atlas'
            ];
        }

        return Inertia::render('surveys/qr', [
            'branches' => $branches,
            'appUrl' => config('app.url', url('/')),
        ]);
    }

    /**
     * Export all survey responses to UTF-8 BOM CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $selectedBranch = $request->query('branch', 'all');

        $query = Survey::query()->orderByDesc('created_at');
        if ($selectedBranch !== 'all' && !empty($selectedBranch)) {
            $query->where('branch', $selectedBranch);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="kaldis_surveys_' . date('Y-m-d_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel Amharic character compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID', 'Date', 'Branch', 'Table', 'Q1 Frequency', 'Q2 Reason',
                'Q3 Spend', 'Q4 Orders', 'Q5 Missing Menu', 'Q6 Factors',
                'Q7 Location', 'Q8 Price', 'Q9 Improvements', 'Q10 Consider Other',
                'Q14 Waiter Rating', 'Q15 Waiter Improvements', 'Q16 Interior Rating',
                'Q18 Recommendations', 'Q19 Service Suggestions', 'Q20 Motivation'
            ]);

            $query->chunk(200, function ($surveys) use ($handle) {
                foreach ($surveys as $s) {
                    fputcsv($handle, [
                        $s->id,
                        $s->created_at?->format('Y-m-d H:i') ?? '',
                        $s->branch,
                        $s->table_number ?? '',
                        $s->q1_frequency ?? '',
                        $s->q2_reason ?? '',
                        $s->q3_spend ?? '',
                        is_array($s->q4_orders) ? implode(', ', $s->q4_orders) : (string) $s->q4_orders,
                        $s->q5_missing_menu ?? '',
                        is_array($s->q6_factors) ? implode(', ', $s->q6_factors) : (string) $s->q6_factors,
                        $s->q7_location_rating ?? '',
                        $s->q8_price_rating ?? '',
                        is_array($s->q9_improvements) ? implode(', ', $s->q9_improvements) : (string) $s->q9_improvements,
                        $s->q10_consider_other_cafe ?? '',
                        $s->q14_waiter_rating ?? '',
                        is_array($s->q15_waiter_improvements) ? implode(', ', $s->q15_waiter_improvements) : (string) $s->q15_waiter_improvements,
                        $s->q16_interior_rating ?? '',
                        $s->q18_waiter_recommendations ?? '',
                        $s->q19_service_suggestions ?? '',
                        $s->q20_visit_motivation ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Delete a single survey record.
     */
    public function destroy($id): RedirectResponse
    {
        Survey::findOrFail($id)->delete();
        return back()->with('success', 'Survey response deleted successfully.');
    }
}
