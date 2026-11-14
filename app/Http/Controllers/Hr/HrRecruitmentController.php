<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrQualification;
use App\Models\HrRecruitment;
use App\Models\HrSetting;
use App\Services\HrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrRecruitmentController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->query('search', '');
        $branch = $request->query('branch', 'all');

        $query = HrRecruitment::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('request_id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('requested_job', 'like', "%{$search}%")
                    ->orWhere('assigned_job', 'like', "%{$search}%");
            });
        }

        if ($branch !== 'all' && !empty($branch)) {
            $query->where('branch', $branch);
        }

        $recruitments = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $branches = HrSetting::where('type', 'branch')->pluck('value')->toArray();
        $jobs = HrSetting::where('type', 'job')->pluck('value')->toArray();

        // Qualified candidates that can be recruited
        $qualifications = HrQualification::select('request_id', 'name', 'gender', 'branch', 'job')
            ->orderByDesc('id')
            ->take(50)
            ->get();

        return Inertia::render('hr/recruitments/index', [
            'recruitments' => $recruitments,
            'branches' => $branches,
            'jobs' => $jobs,
            'qualifications' => $qualifications,
            'filters' => [
                'search' => $search,
                'branch' => $branch,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'gender' => 'required|string|in:አቶ,ወ/ሪት,ወ/ሮ,Mr.,Ms.,Mrs.',
            'branch' => 'required|string|max:100',
            'requested_job' => 'required|string|max:100',
            'assigned_job' => 'required|string|max:100',
            'start_date' => 'required|date',
            'destination' => 'nullable|string|max:150',
            'qualification_ref_id' => 'nullable|string|max:50',
        ]);

        $requestId = HrService::generateId('RF', HrRecruitment::class, 'request_id');

        $record = HrRecruitment::create(array_merge($validated, [
            'request_id' => $requestId,
        ]));

        // Dispatch Telegram alert
        HrService::notifyBranch($record->branch, "📢 💼 Recruitment Placement Confirmed ({$requestId})", [
            'Record ID' => $requestId,
            'Candidate' => "{$record->gender} {$record->name}",
            'Branch' => $record->branch,
            'Assigned Position' => $record->assigned_job,
            'Start Date' => (string) $record->start_date,
        ]);

        return back()->with('success', "Recruitment notification {$requestId} created successfully.");
    }

    public function letter($id): JsonResponse
    {
        $record = HrRecruitment::findOrFail($id);
        return response()->json(HrService::getRecruitmentLetterData($record));
    }

    public function destroy($id): RedirectResponse
    {
        $record = HrRecruitment::findOrFail($id);
        $record->delete();
        return back()->with('success', "Recruitment record deleted successfully.");
    }
}
