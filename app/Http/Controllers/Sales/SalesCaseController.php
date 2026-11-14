<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesCase;
use App\Models\SalesCaseAttachment;
use App\Models\SalesCaseComment;
use App\Models\SalesCaseHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SalesCaseController extends Controller
{
    public function dashboard()
    {
        $totalAllCases = SalesCase::count();
        $completedCases = SalesCase::where('progress', 'Done')->count();
        $activeCases = SalesCase::where('progress', '!=', 'Done')->count();
        $approvedCases = SalesCase::where('approved', true)->count();
        $notStartedCases = SalesCase::where('progress', 'Not Start')->count();
        $inProgressCases = SalesCase::where('progress', 'In Progress')->count();
        $escalatedCases = SalesCase::where('progress', 'Escalated')->count();

        $totalDiscrepancy = SalesCase::sum('difference');

        $progressData = [
            'Not Start' => $notStartedCases,
            'In Progress' => $inProgressCases,
            'Escalated' => $escalatedCases,
            'Done' => $completedCases,
        ];

        // Monthly trends for last 6 months
        $monthlyCases = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $monthName = Carbon::now()->subMonths($i)->format('M Y');

            $count = SalesCase::whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month])->count();
            $difference = SalesCase::whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month])->sum('difference');

            $monthlyCases[] = [
                'month' => $monthName,
                'count' => $count,
                'difference' => round($difference, 2),
            ];
        }

        // Branch Discrepancies
        $branchDiscrepancies = SalesCase::select('branch_name', DB::raw('COUNT(*) as total_cases'), DB::raw('SUM(difference) as total_difference'))
            ->groupBy('branch_name')
            ->orderByDesc('total_difference')
            ->limit(8)
            ->get();

        // Recent cases
        $recentCases = SalesCase::orderByDesc('created_at')->limit(6)->get();

        return Inertia::render('sales/dashboard', [
            'stats' => [
                'total_all' => $totalAllCases,
                'active' => $activeCases,
                'completed' => $completedCases,
                'approved' => $approvedCases,
                'not_started' => $notStartedCases,
                'in_progress' => $inProgressCases,
                'escalated' => $escalatedCases,
                'total_discrepancy' => round($totalDiscrepancy, 2),
            ],
            'progressData' => $progressData,
            'monthlyCases' => $monthlyCases,
            'branchDiscrepancies' => $branchDiscrepancies,
            'recentCases' => $recentCases,
        ]);
    }

    public function index(Request $request)
    {
        $query = SalesCase::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('case_id', 'like', "%{$search}%")
                  ->orWhere('branch_name', 'like', "%{$search}%")
                  ->orWhere('technical_name', 'like', "%{$search}%")
                  ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        if ($request->filled('progress') && $request->input('progress') !== 'all') {
            $query->where('progress', $request->input('progress'));
        }

        if ($request->filled('branch')) {
            $query->where('branch_name', $request->input('branch'));
        }

        // Date Presets
        $datePreset = $request->input('date_preset', 'all');
        if ($datePreset === 'today') {
            $query->whereDate('date', Carbon::today());
        } elseif ($datePreset === 'yesterday') {
            $query->whereDate('date', Carbon::yesterday());
        } elseif ($datePreset === 'this_week') {
            $query->whereBetween('date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($datePreset === 'this_month') {
            $query->whereBetween('date', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
        } elseif ($request->filled('start_date') || $request->filled('end_date')) {
            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->input('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->input('end_date'));
            }
        }

        $cases = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        $branches = DB::table('branches')->pluck('branch_name')->filter()->unique()->values();
        if ($branches->isEmpty()) {
            $branches = SalesCase::distinct()->pluck('branch_name')->filter()->values();
        }

        $technicians = DB::table('users')->pluck('name')->filter()->unique()->values();

        return Inertia::render('sales/index', [
            'cases' => $cases,
            'branches' => $branches,
            'technicians' => $technicians,
            'filters' => $request->only(['search', 'progress', 'branch', 'date_preset', 'start_date', 'end_date']),
        ]);
    }

    public function create()
    {
        $nextId = $this->generateCaseId();

        $branches = DB::table('branches')->select('id', 'branch_name as name')->orderBy('branch_name')->get();
        if ($branches->isEmpty()) {
            $branches = collect([
                ['id' => 1, 'name' => 'Bole Medhanialem'],
                ['id' => 2, 'name' => 'Kazanchis'],
                ['id' => 3, 'name' => 'Sarbet'],
                ['id' => 4, 'name' => 'Piassa'],
                ['id' => 5, 'name' => 'CMC'],
            ]);
        }

        $technicians = DB::table('users')->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('sales/create', [
            'nextCaseId' => $nextId,
            'branches' => $branches,
            'technicians' => $technicians,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'branch_name' => 'required|string|max:150',
            'branch_id' => 'nullable|integer',
            'z_report' => 'required|numeric',
            'cash_sales' => 'required|numeric',
            'progress' => 'required|in:Not Start,In Progress,Escalated,Done',
            'technical_name' => 'nullable|string|max:150',
            'escalated_to' => 'nullable|string|max:150',
            'remark' => 'nullable|string',
            'attachment' => 'nullable|file|max:20480', // 20MB
        ]);

        $caseId = $this->generateCaseId();
        $zReport = floatval($validated['z_report']);
        $cashSales = floatval($validated['cash_sales']);
        $difference = $zReport - $cashSales;

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('sales_attachments', 'public');
        }

        $case = SalesCase::create([
            'case_id' => $caseId,
            'date' => $validated['date'],
            'branch_name' => $validated['branch_name'],
            'branch_id' => $validated['branch_id'] ?? null,
            'z_report' => $zReport,
            'cash_sales' => $cashSales,
            'difference' => $difference,
            'progress' => $validated['progress'],
            'technical_name' => $validated['technical_name'] ?? null,
            'escalated_to' => $validated['escalated_to'] ?? null,
            'escalation_date' => $validated['progress'] === 'Escalated' ? now() : null,
            'case_fixed_date' => $validated['progress'] === 'Done' ? now() : null,
            'remark' => $validated['remark'] ?? null,
            'submitted_by' => auth()->user()?->name ?? 'System',
            'attachment_file' => $attachmentPath ? "/storage/{$attachmentPath}" : null,
            'attachment_name' => $attachmentName,
            'attachment_type' => $attachmentPath ? 'file' : 'url',
        ]);

        if ($attachmentPath) {
            SalesCaseAttachment::create([
                'case_id' => $caseId,
                'file_path' => "/storage/{$attachmentPath}",
                'file_name' => $attachmentName,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->user()?->name ?? 'System',
                'uploaded_at' => now(),
            ]);
        }

        // History record
        SalesCaseHistory::create([
            'case_id' => $caseId,
            'username' => auth()->user()?->name ?? 'System',
            'action' => 'Create Case',
            'details' => "Created reconciliation case {$caseId} for branch {$validated['branch_name']} with difference {$difference} ETB",
        ]);

        return redirect()->route('sales.show', $case->id)->with('success', "Sales case {$caseId} created successfully!");
    }

    public function show($id)
    {
        $case = SalesCase::with(['comments', 'history', 'caseAttachments'])->findOrFail($id);

        $technicians = DB::table('users')->pluck('name')->filter()->unique()->values();

        return Inertia::render('sales/show', [
            'caseItem' => $case,
            'technicians' => $technicians,
        ]);
    }

    public function update(Request $request, $id)
    {
        $case = SalesCase::findOrFail($id);

        $validated = $request->validate([
            'date' => 'required|date',
            'branch_name' => 'required|string|max:150',
            'z_report' => 'required|numeric',
            'cash_sales' => 'required|numeric',
            'progress' => 'required|in:Not Start,In Progress,Escalated,Done',
            'technical_name' => 'nullable|string|max:150',
            'escalated_to' => 'nullable|string|max:150',
            'remark' => 'nullable|string',
        ]);

        $zReport = floatval($validated['z_report']);
        $cashSales = floatval($validated['cash_sales']);
        $difference = $zReport - $cashSales;

        $oldProgress = $case->progress;
        $newProgress = $validated['progress'];

        $updateData = [
            'date' => $validated['date'],
            'branch_name' => $validated['branch_name'],
            'z_report' => $zReport,
            'cash_sales' => $cashSales,
            'difference' => $difference,
            'progress' => $newProgress,
            'technical_name' => $validated['technical_name'] ?? null,
            'escalated_to' => $validated['escalated_to'] ?? null,
            'remark' => $validated['remark'] ?? null,
        ];

        if ($newProgress === 'Escalated' && $oldProgress !== 'Escalated') {
            $updateData['escalation_date'] = now();
        }
        if ($newProgress === 'Done' && $oldProgress !== 'Done') {
            $updateData['case_fixed_date'] = now();
        }

        $case->update($updateData);

        SalesCaseHistory::create([
            'case_id' => $case->case_id,
            'username' => auth()->user()?->name ?? 'System',
            'action' => 'Update Case',
            'details' => "Updated case details (Status: {$newProgress}, Difference: {$difference} ETB)",
        ]);

        return redirect()->back()->with('success', 'Case updated successfully!');
    }

    public function updateProgress(Request $request, $id)
    {
        $case = SalesCase::findOrFail($id);

        $validated = $request->validate([
            'progress' => 'required|in:Not Start,In Progress,Escalated,Done',
            'escalated_to' => 'nullable|string|max:150',
            'note' => 'nullable|string',
        ]);

        $newProgress = $validated['progress'];
        $updateData = ['progress' => $newProgress];

        if ($newProgress === 'Escalated') {
            $updateData['escalated_to'] = $validated['escalated_to'] ?? $case->escalated_to;
            $updateData['escalation_date'] = now();
        } elseif ($newProgress === 'Done') {
            $updateData['case_fixed_date'] = now();
        }

        $case->update($updateData);

        $note = !empty($validated['note']) ? " - Note: " . $validated['note'] : "";
        SalesCaseHistory::create([
            'case_id' => $case->case_id,
            'username' => auth()->user()?->name ?? 'System',
            'action' => 'Status Change',
            'details' => "Changed progress to {$newProgress}{$note}",
        ]);

        return redirect()->back()->with('success', "Case status changed to {$newProgress}!");
    }

    public function approve(Request $request, $id)
    {
        $case = SalesCase::findOrFail($id);

        $case->update([
            'approved' => true,
            'approved_by' => auth()->user()?->name ?? 'Admin',
            'approved_at' => now(),
        ]);

        SalesCaseHistory::create([
            'case_id' => $case->case_id,
            'username' => auth()->user()?->name ?? 'System',
            'action' => 'Approval',
            'details' => "Case approved by " . (auth()->user()?->name ?? 'Admin'),
        ]);

        return redirect()->back()->with('success', "Case {$case->case_id} has been approved!");
    }

    public function addComment(Request $request, $id)
    {
        $case = SalesCase::findOrFail($id);

        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        SalesCaseComment::create([
            'case_id' => $case->case_id,
            'username' => auth()->user()?->name ?? 'User',
            'comment' => $validated['comment'],
        ]);

        return redirect()->back()->with('success', 'Comment added.');
    }

    public function export(Request $request)
    {
        $cases = SalesCase::orderByDesc('date')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sales_cases_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($cases) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Case ID', 'Date', 'Branch', 'Z-Report Total', 'Cash Sales',
                'Discrepancy (ETB)', 'Progress Status', 'Assigned Technician',
                'Escalated To', 'Approved', 'Submitted By', 'Remark'
            ]);

            foreach ($cases as $c) {
                fputcsv($file, [
                    $c->case_id,
                    $c->date?->format('Y-m-d'),
                    $c->branch_name,
                    number_format($c->z_report, 2),
                    number_format($c->cash_sales, 2),
                    number_format($c->difference, 2),
                    $c->progress,
                    $c->technical_name,
                    $c->escalated_to,
                    $c->approved ? 'Yes' : 'No',
                    $c->submitted_by,
                    $c->remark,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function generateCaseId()
    {
        $maxNum = DB::table('sales_case_sequence')->max('case_number') ?? 1000;
        $nextNum = $maxNum + 1;

        DB::table('sales_case_sequence')->insert([
            'case_number' => $nextNum,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return "CASE-" . str_pad($nextNum, 4, '0', STR_PAD_LEFT) . "-FIN";
    }
}
