<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\HrInternshipRequest;
use App\Models\HrQualification;
use App\Models\HrRecruitment;
use App\Models\HrSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $branchFilter = $request->query('branch', 'all');
        $jobFilter = $request->query('job', 'all');

        $internshipQuery = HrInternshipRequest::query();
        $qualificationQuery = HrQualification::query();
        $recruitmentQuery = HrRecruitment::query();

        if ($branchFilter !== 'all' && !empty($branchFilter)) {
            $internshipQuery->where(function ($q) use ($branchFilter) {
                $q->where('to_branch', $branchFilter)->orWhere('work_branch', $branchFilter);
            });
            $qualificationQuery->where('branch', $branchFilter);
            $recruitmentQuery->where('branch', $branchFilter);
        }

        if ($jobFilter !== 'all' && !empty($jobFilter)) {
            $internshipQuery->where('job', $jobFilter);
            $qualificationQuery->where('job', $jobFilter);
            $recruitmentQuery->where('assigned_job', $jobFilter);
        }

        $internshipCount = (clone $internshipQuery)->count();
        $qualificationCount = (clone $qualificationQuery)->count();
        $recruitmentCount = (clone $recruitmentQuery)->count();

        // Recent items
        $recentInternships = (clone $internshipQuery)->orderByDesc('created_at')->take(5)->get();
        $recentQualifications = (clone $qualificationQuery)->orderByDesc('created_at')->take(5)->get();
        $recentRecruitments = (clone $recruitmentQuery)->orderByDesc('created_at')->take(5)->get();

        // Branches and jobs for filters
        $branches = Branch::where('is_active', true)
            ->orderBy('branch_name')
            ->pluck('branch_name')
            ->filter()
            ->values()
            ->toArray();

        if (empty($branches)) {
            $branches = HrSetting::where('type', 'branch')->pluck('value')->filter()->values()->toArray();
        }
        $jobs = HrSetting::where('type', 'job')->pluck('value')->toArray();

        return Inertia::render('hr/dashboard', [
            'stats' => [
                'internships' => $internshipCount,
                'qualifications' => $qualificationCount,
                'recruitments' => $recruitmentCount,
                'totalBranches' => count($branches),
            ],
            'recentInternships' => $recentInternships,
            'recentQualifications' => $recentQualifications,
            'recentRecruitments' => $recentRecruitments,
            'branches' => $branches,
            'jobs' => $jobs,
            'filters' => [
                'branch' => $branchFilter,
                'job' => $jobFilter,
            ],
        ]);
    }
}
