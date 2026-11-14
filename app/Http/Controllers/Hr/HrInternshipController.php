<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrInternshipRequest;
use App\Models\HrSetting;
use App\Services\HrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrInternshipController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');
        $branch = $request->query('branch', 'all');
        $status = $request->query('status', 'all');

        $query = HrInternshipRequest::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('request_id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('job', 'like', "%{$search}%");
            });
        }

        if ($branch !== 'all' && !empty($branch)) {
            $query->where(function ($q) use ($branch) {
                $q->where('to_branch', $branch)->orWhere('work_branch', $branch);
            });
        }

        if ($status !== 'all' && !empty($status)) {
            $query->where('reference_status', $status);
        }

        $internships = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $branches = HrSetting::where('type', 'branch')->pluck('value')->toArray();
        $jobs = HrSetting::where('type', 'job')->pluck('value')->toArray();

        return Inertia::render('hr/internships/index', [
            'internships' => $internships,
            'branches' => $branches,
            'jobs' => $jobs,
            'filters' => [
                'search' => $search,
                'branch' => $branch,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'gender' => 'required|string|in:አቶ,ወ/ሪት,ወ/ሮ,Mr.,Ms.,Mrs.',
            'to_branch' => 'required|string|max:100',
            'work_branch' => 'required|string|max:100',
            'job' => 'required|string|max:100',
            'start_date' => 'required|date',
        ]);

        $requestId = HrService::generateId('IRF', HrInternshipRequest::class, 'request_id');

        $record = HrInternshipRequest::create(array_merge($validated, [
            'request_id' => $requestId,
            'reference_status' => 'Pending',
        ]));

        // Dispatch Telegram alert
        HrService::notifyBranch($record->to_branch, "📢 🆕 Internship Request ({$requestId})", [
            'Request ID' => $requestId,
            'Name' => "{$record->gender} {$record->name}",
            'To Branch' => $record->to_branch,
            'Work Branch' => $record->work_branch,
            'Position' => $record->job,
            'Start Date' => (string) $record->start_date,
        ]);

        return back()->with('success', "Internship request {$requestId} created successfully.");
    }

    public function updateStatus(Request $request, $id): RedirectResponse
    {
        $validated = $request->validate([
            'reference_status' => 'required|string|in:Pending,Approved,Rejected,Completed',
        ]);

        $record = HrInternshipRequest::findOrFail($id);
        $record->update(['reference_status' => $validated['reference_status']]);

        return back()->with('success', "Internship request {$record->request_id} updated to {$validated['reference_status']}.");
    }

    public function letter($id): JsonResponse
    {
        $record = HrInternshipRequest::findOrFail($id);
        return response()->json(HrService::getInternshipLetterData($record));
    }

    public function destroy($id): RedirectResponse
    {
        $record = HrInternshipRequest::findOrFail($id);
        $record->delete();
        return back()->with('success', "Internship request deleted successfully.");
    }
}
