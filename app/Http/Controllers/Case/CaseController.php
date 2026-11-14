<?php

namespace App\Http\Controllers\Case;

use App\Http\Controllers\Controller;
use App\Models\CaseAssignee;
use App\Models\CaseHistory;
use App\Models\CaseItem;
use App\Models\CaseSetting;
use App\Models\CaseType;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaseController extends Controller
{
    public function index(Request $request): Response
    {
        $query = CaseItem::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('case_id', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('assigned_to', 'like', "%{$search}%")
                    ->orWhere('created_by', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if ($status !== 'All') {
                $query->where('status', $status);
            }
        }

        if ($priority = $request->input('priority')) {
            if ($priority !== 'All') {
                $query->where('priority', $priority);
            }
        }

        if ($caseType = $request->input('case_type')) {
            if ($caseType !== 'All') {
                $query->where('case_type', $caseType);
            }
        }

        if ($assignedTo = $request->input('assigned_to')) {
            if ($assignedTo !== 'All') {
                $query->where('assigned_to', $assignedTo);
            }
        }

        if ($request->boolean('assignedToMe')) {
            $user = $request->user();
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->name)
                    ->orWhere('assigned_to', $user->username);
            });
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_date', '<=', $dateTo);
        }

        $cases = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $types = CaseType::pluck('name')->toArray();
        $assignees = CaseAssignee::where('status', 'active')->pluck('name')->toArray();

        return Inertia::render('cases/index', [
            'cases' => $cases,
            'filters' => $request->only(['search', 'status', 'priority', 'case_type', 'assigned_to', 'assignedToMe', 'date_from', 'date_to']),
            'types' => $types,
            'assignees' => $assignees,
        ]);
    }

    public function create(): Response
    {
        $types = CaseType::pluck('name')->toArray();
        $assignees = CaseAssignee::where('status', 'active')->pluck('name')->toArray();

        return Inertia::render('cases/create', [
            'types' => $types,
            'assignees' => $assignees,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'case_type' => 'required|string|max:100',
            'description' => 'required|string',
            'priority' => 'required|in:Critical,Medium,Low',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // 10MB
        ]);

        // Auto-generate case_id (e.g. CS-20261111-001)
        $dateStr = date('Ymd');
        $prefix = "CS-{$dateStr}-";
        $latest = CaseItem::where('case_id', 'like', "{$prefix}%")->orderByDesc('id')->first();
        $nextNum = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest->case_id, $matches)) {
            $nextNum = (int)$matches[1] + 1;
        }
        $caseId = $prefix . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('case_attachments', 'public');
        }

        $user = $request->user();
        $case = CaseItem::create([
            'case_id' => $caseId,
            'case_type' => $validated['case_type'],
            'description' => $validated['description'],
            'created_date' => now()->toDateString(),
            'due_date' => $validated['due_date'] ?? null,
            'status' => 'Not Started',
            'priority' => $validated['priority'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'created_by' => $user->name ?: $user->username,
            'remarks' => $validated['remarks'] ?? null,
            'attachments' => $attachmentPath,
            'last_modified' => now(),
        ]);

        CaseHistory::create([
            'case_id' => $caseId,
            'action' => 'Created Case',
            'details' => "Case created with priority {$case->priority} by {$case->created_by}",
            'user' => $case->created_by,
            'timestamp' => now(),
        ]);

        TelegramService::notifyCaseCreated($case->toArray(), $case->created_by);

        return redirect()->route('cases.show', $case->id)->with('success', "Case {$caseId} created successfully!");
    }

    public function show(int $id): Response
    {
        $case = CaseItem::with(['comments', 'history'])->findOrFail($id);
        $types = CaseType::pluck('name')->toArray();
        $assignees = CaseAssignee::where('status', 'active')->pluck('name')->toArray();

        return Inertia::render('cases/show', [
            'caseItem' => $case,
            'types' => $types,
            'assignees' => $assignees,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $case = CaseItem::findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|string|in:Not Started,In Progress,Fixed,Return,Reject,Completed',
            'priority' => 'nullable|string|in:Critical,Medium,Low',
            'case_type' => 'nullable|string|max:100',
            'assigned_to' => 'nullable|string|max:100',
            'due_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $user = $request->user();
        $actor = $user->name ?: $user->username;
        $changes = [];

        foreach ($validated as $field => $newVal) {
            $oldVal = $case->{$field};
            if ($newVal !== null && $oldVal != $newVal) {
                $changes[] = ucfirst(str_replace('_', ' ', $field)) . ": '{$oldVal}' ➡️ '{$newVal}'";
                $case->{$field} = $newVal;
            }
        }

        if (!empty($changes)) {
            $case->last_modified = now();
            $case->save();

            $changeSummary = implode(', ', $changes);
            CaseHistory::create([
                'case_id' => $case->case_id,
                'action' => 'Updated Case',
                'details' => $changeSummary,
                'user' => $actor,
                'timestamp' => now(),
            ]);

            if (isset($validated['status']) && $validated['status'] !== $case->getOriginal('status')) {
                TelegramService::notifyCaseUpdated(
                    $case->toArray(),
                    $case->getOriginal('status'),
                    $validated['status'],
                    $actor,
                    $validated['remarks'] ?? null
                );
            }
        }

        return back()->with('success', 'Case updated successfully!');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'case_ids' => 'required|array',
            'case_ids.*' => 'string',
            'status' => 'nullable|string|in:Not Started,In Progress,Fixed,Return,Reject,Completed',
            'assigned_to' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $actor = $request->user()->name ?: $request->user()->username;
        $cases = CaseItem::whereIn('case_id', $validated['case_ids'])->get();

        foreach ($cases as $c) {
            $oldStatus = $c->status;
            if (!empty($validated['status'])) {
                $c->status = $validated['status'];
            }
            if (!empty($validated['assigned_to'])) {
                $c->assigned_to = $validated['assigned_to'];
            }
            if (!empty($validated['remarks'])) {
                $c->remarks = $validated['remarks'];
            }
            $c->last_modified = now();
            $c->save();

            CaseHistory::create([
                'case_id' => $c->case_id,
                'action' => 'Bulk Updated',
                'details' => "Updated status to '{$c->status}', assigned to '{$c->assigned_to}'",
                'user' => $actor,
                'timestamp' => now(),
            ]);
        }

        return back()->with('success', count($cases) . ' cases updated successfully!');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $case = CaseItem::findOrFail($id);
        $caseId = $case->case_id;

        CaseHistory::create([
            'case_id' => $caseId,
            'action' => 'Deleted Case',
            'details' => "Case {$caseId} deleted",
            'user' => $request->user()->name ?: $request->user()->username,
            'timestamp' => now(),
        ]);

        $case->delete();

        return redirect()->route('cases.index')->with('success', "Case {$caseId} removed successfully.");
    }

    public function sla(Request $request): Response
    {
        $cases = CaseItem::all();
        $today = date('Y-m-d');
        $terminal = ['Completed', 'Reject'];

        $openCases = $cases->whereNotIn('status', $terminal);
        $overdueCases = $openCases->filter(fn($c) => $c->due_date && $c->due_date->format('Y-m-d') < $today);

        $slaData = [
            'totalOpen' => $openCases->count(),
            'totalOverdue' => $overdueCases->count(),
            'criticalCount' => $openCases->where('priority', 'Critical')->count(),
            'mediumCount' => $openCases->where('priority', 'Medium')->count(),
            'lowCount' => $openCases->where('priority', 'Low')->count(),
            'cases' => $openCases->values(),
        ];

        return Inertia::render('cases/sla', [
            'slaData' => $slaData,
        ]);
    }

    public function sendSlaReport(Request $request): RedirectResponse
    {
        $cases = CaseItem::whereNotIn('status', ['Completed', 'Reject'])->get();
        $today = date('Y-m-d');

        $critical = $cases->where('priority', 'Critical')->count();
        $overdue = $cases->filter(fn($c) => $c->due_date && $c->due_date->format('Y-m-d') < $today)->count();

        $msg = "<b>📊 KALDIS ICT CASE SLA REPORT</b>\n\n" .
            "<b>Total Active Cases:</b> " . $cases->count() . "\n" .
            "<b>🔴 Critical Cases:</b> " . $critical . "\n" .
            "<b>⚠️ Overdue Cases:</b> " . $overdue . "\n" .
            "<b>Generated At:</b> " . now()->format('Y-m-d H:i') . "\n\n" .
            "<i>Please review open tickets on the ICT Portal.</i>";

        $chatId = CaseSetting::get('telegram_chat_id_admin') ?: env('TELEGRAM_CHAT_ID_ADMIN', '-1001502645326');
        TelegramService::sendMessage($chatId, $msg);

        return back()->with('success', 'SLA Report sent to Telegram successfully!');
    }

    public function export(Request $request): StreamedResponse
    {
        $cases = CaseItem::orderByDesc('id')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="cases_export_' . date('Y-m-d_His') . '.csv"',
        ];

        return response()->stream(function () use ($cases) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Case ID', 'Type', 'Description', 'Priority', 'Status', 'Assigned To', 'Created Date', 'Due Date', 'Created By', 'Remarks']);

            foreach ($cases as $c) {
                fputcsv($handle, [
                    $c->case_id,
                    $c->case_type,
                    $c->description,
                    $c->priority,
                    $c->status,
                    $c->assigned_to,
                    $c->created_date ? $c->created_date->format('Y-m-d') : '',
                    $c->due_date ? $c->due_date->format('Y-m-d') : '',
                    $c->created_by,
                    $c->remarks,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
