<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrInternshipRequest;
use App\Models\HrQualification;
use App\Models\HrSetting;
use App\Services\HrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrQualificationController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');
        $branch = $request->query('branch', 'all');
        $status = $request->query('status', 'all');

        $query = HrQualification::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('request_id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('job', 'like', "%{$search}%")
                    ->orWhere('manager_name', 'like', "%{$search}%");
            });
        }

        if ($branch !== 'all' && !empty($branch)) {
            $query->where('branch', $branch);
        }

        if ($status !== 'all' && !empty($status)) {
            $query->where('reference_status', $status);
        }

        $qualifications = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $branches = HrSetting::where('type', 'branch')->pluck('value')->toArray();
        $jobs = HrSetting::where('type', 'job')->pluck('value')->toArray();
        $ranks = HrSetting::where('type', 'rank')->pluck('value')->toArray();

        // Available internships that can be linked
        $pendingInternships = HrInternshipRequest::select('request_id', 'name', 'gender', 'work_branch', 'job')
            ->orderByDesc('id')
            ->take(50)
            ->get();

        return Inertia::render('hr/qualifications/index', [
            'qualifications' => $qualifications,
            'branches' => $branches,
            'jobs' => $jobs,
            'ranks' => $ranks,
            'pendingInternships' => $pendingInternships,
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
            'branch' => 'required|string|max:100',
            'job' => 'required|string|max:100',
            'probation_start' => 'required|date',
            'manager_name' => 'nullable|string|max:150',
            'manager_rank' => 'nullable|string|max:100',
            'destination' => 'nullable|string|max:150',
            'internship_ref_id' => 'nullable|string|max:50',
        ]);

        $requestId = HrService::generateId('QF', HrQualification::class, 'request_id');

        $record = HrQualification::create(array_merge($validated, [
            'request_id' => $requestId,
            'reference_status' => 'Pending',
        ]));

        // If linked to an internship request, mark that internship completed
        if (!empty($validated['internship_ref_id'])) {
            HrInternshipRequest::where('request_id', $validated['internship_ref_id'])
                ->update(['reference_status' => 'Completed']);
        }

        // Dispatch Telegram alert
        HrService::notifyBranch($record->branch, "📢 🎓 Qualification Recorded ({$requestId})", [
            'Record ID' => $requestId,
            'Staff Name' => "{$record->gender} {$record->name}",
            'Branch' => $record->branch,
            'Position' => $record->job,
            'Manager' => "{$record->manager_name} ({$record->manager_rank})",
        ]);

        return back()->with('success', "Qualification record {$requestId} created successfully.");
    }

    public function updateStatus(Request $request, $id): RedirectResponse
    {
        $validated = $request->validate([
            'reference_status' => 'required|string|in:Pending,Approved,Rejected,Completed',
        ]);

        $record = HrQualification::findOrFail($id);
        $record->update(['reference_status' => $validated['reference_status']]);

        return back()->with('success', "Qualification record {$record->request_id} updated to {$validated['reference_status']}.");
    }

    public function letter($id): JsonResponse
    {
        $record = HrQualification::findOrFail($id);
        return response()->json(HrService::getQualificationLetterData($record));
    }

    public function destroy($id): RedirectResponse
    {
        $record = HrQualification::findOrFail($id);
        $record->delete();
        return back()->with('success', "Qualification record deleted successfully.");
    }
}
