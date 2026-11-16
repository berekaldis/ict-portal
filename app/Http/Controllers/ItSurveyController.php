<?php

namespace App\Http\Controllers;

use App\Models\ItHardwareAsset;
use App\Models\ItSurveyCategory;
use App\Models\ItSurveyChecklistItem;
use App\Models\ItSurveyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ItSurveyController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'reports');

        $query = ItSurveyReport::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                  ->orWhere('technician_name', 'like', "%{$search}%")
                  ->orWhere('branch_manager', 'like', "%{$search}%")
                  ->orWhere('report_uuid', 'like', "%{$search}%")
                  ->orWhere('issues_summary', 'like', "%{$search}%");
            });
        }

        if ($request->filled('branch')) {
            $query->where('branch_name', $request->input('branch'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('health_status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('submitted_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('submitted_at', '<=', $request->input('date_to'));
        }

        $reports = $query->orderBy('submitted_at', 'desc')->paginate(15)->withQueryString();

        // Hardware Assets Query (for Hardware Inventory tab or direct access)
        $assetQuery = ItHardwareAsset::query();
        if ($request->filled('asset_search')) {
            $aSearch = $request->input('asset_search');
            $assetQuery->where(function ($q) use ($aSearch) {
                $q->where('asset_tag', 'like', "%{$aSearch}%")
                  ->orWhere('serial_number', 'like', "%{$aSearch}%")
                  ->orWhere('brand', 'like', "%{$aSearch}%")
                  ->orWhere('model', 'like', "%{$aSearch}%")
                  ->orWhere('branch_name', 'like', "%{$aSearch}%")
                  ->orWhere('assigned_to', 'like', "%{$aSearch}%")
                  ->orWhere('ip_address', 'like', "%{$aSearch}%");
            });
        }
        if ($request->filled('asset_branch')) {
            $assetQuery->where('branch_name', $request->input('asset_branch'));
        }
        if ($request->filled('asset_status')) {
            $assetQuery->where('status', $request->input('asset_status'));
        }
        if ($request->filled('asset_type')) {
            $assetQuery->where('asset_type', $request->input('asset_type'));
        }
        $assets = $assetQuery->orderBy('branch_name')->orderBy('asset_type')->paginate(20, ['*'], 'assets_page')->withQueryString();

        // Branches for filter dropdowns
        $branches = DB::table('branches')->pluck('branch_name')->filter()->unique()->values();
        if ($branches->isEmpty()) {
            $branches = ItSurveyReport::distinct()->pluck('branch_name')->filter()->values();
        }

        // Checklist categories & questions
        $categories = ItSurveyCategory::with(['items' => function ($q) {
            $q->where('is_active', true)->orderBy('display_order');
        }])->where('is_active', true)->orderBy('display_order')->get();

        // Statistics
        $totalReports = ItSurveyReport::count();
        $goodCount = ItSurveyReport::where('health_status', 'Good')->count();
        $warningCount = ItSurveyReport::where('health_status', 'Warning')->count();
        $criticalCount = ItSurveyReport::where('health_status', 'Critical')->count();
        $avgScore = $totalReports > 0 ? round(ItSurveyReport::avg('health_score')) : 100;

        $totalAssets = ItHardwareAsset::count();
        $operationalAssets = ItHardwareAsset::where('status', 'operational')->count();
        $maintenanceAssets = ItHardwareAsset::whereIn('status', ['maintenance', 'repair'])->count();
        $faultyAssets = ItHardwareAsset::where('status', 'faulty')->count();
        $missingAssets = ItHardwareAsset::where('status', 'missing')->count();

        return Inertia::render('it-surveys/index', [
            'tab' => $tab,
            'reports' => $reports,
            'assets' => $assets,
            'categories' => $categories,
            'branches' => $branches,
            'assetTypes' => ['pos', 'printer', 'switch', 'router', 'ups', 'cctv', 'computer', 'scanner', 'server', 'other'],
            'stats' => [
                'total_reports' => $totalReports,
                'good_count' => $goodCount,
                'warning_count' => $warningCount,
                'critical_count' => $criticalCount,
                'avg_score' => $avgScore,
                'total_assets' => $totalAssets,
                'operational_assets' => $operationalAssets,
                'maintenance_assets' => $maintenanceAssets,
                'faulty_assets' => $faultyAssets,
                'missing_assets' => $missingAssets,
            ],
            'filters' => $request->only([
                'search', 'branch', 'type', 'status', 'date_from', 'date_to',
                'asset_search', 'asset_branch', 'asset_status', 'asset_type', 'tab'
            ]),
        ]);
    }

    public function create(Request $request)
    {
        $type = $request->query('type', 'hardware');

        $categories = ItSurveyCategory::where('type', $type)
            ->where('is_active', true)
            ->with(['items' => function ($q) {
                $q->where('is_active', true)->orderBy('display_order');
            }])
            ->orderBy('display_order')
            ->get();

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

        return Inertia::render('it-surveys/create', [
            'type' => $type,
            'categories' => $categories,
            'branches' => $branches,
            'technicians' => $technicians,
            'defaultTechnician' => auth()->user()?->name ?? 'IT Specialist',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_name' => 'required|string|max:150',
            'branch_id' => 'nullable|integer',
            'technician_name' => 'required|string|max:150',
            'branch_manager' => 'required|string|max:150',
            'type' => 'required|in:hardware,software',
            'checklist_data' => 'required|array',
            'issues_summary' => 'nullable|string',
            'actions_taken' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'photos' => 'nullable|array',
        ]);

        $checklist = $validated['checklist_data'];
        $totalItems = count($checklist);
        $passedItems = 0;
        $failedItems = 0;
        $skippedItems = 0;

        foreach ($checklist as $item) {
            $status = $item['status'] ?? 'OK';
            if ($status === 'OK') {
                $passedItems++;
            } elseif ($status === 'Issue') {
                $failedItems++;
            } elseif ($status === 'Skip') {
                $skippedItems++;
            }
        }

        $evaluated = $passedItems + $failedItems;
        $healthScore = $evaluated > 0 ? round(($passedItems / $evaluated) * 100) : 100;

        $healthStatus = 'Good';
        if ($healthScore < 60) {
            $healthStatus = 'Critical';
        } elseif ($healthScore < 85) {
            $healthStatus = 'Warning';
        }

        $reportUuid = (string) Str::uuid();

        // Process uploaded base64 photos if present
        $storedPhotos = [];
        if (!empty($validated['photos']) && is_array($validated['photos'])) {
            foreach ($validated['photos'] as $idx => $photoData) {
                if (is_string($photoData) && str_starts_with($photoData, 'data:image')) {
                    if (preg_match('/^data:image\/(\w+);base64,/', $photoData, $matches)) {
                        $extension = strtolower($matches[1]);
                        $data = substr($photoData, strpos($photoData, ',') + 1);
                        $decoded = base64_decode($data);
                        if ($decoded !== false) {
                            $filename = "survey_{$reportUuid}_{$idx}.{$extension}";
                            Storage::disk('public')->put("surveys/{$filename}", $decoded);
                            $storedPhotos[] = "/storage/surveys/{$filename}";
                        }
                    }
                } elseif (is_string($photoData)) {
                    $storedPhotos[] = $photoData;
                }
            }
        }

        $report = ItSurveyReport::create([
            'report_uuid' => $reportUuid,
            'branch_id' => $validated['branch_id'] ?? null,
            'branch_name' => $validated['branch_name'],
            'technician_name' => $validated['technician_name'],
            'technician_id' => auth()->id(),
            'branch_manager' => $validated['branch_manager'],
            'type' => $validated['type'],
            'health_score' => $healthScore,
            'health_status' => $healthStatus,
            'critical_count' => $failedItems,
            'total_items' => $totalItems,
            'passed_items' => $passedItems,
            'failed_items' => $failedItems,
            'skipped_items' => $skippedItems,
            'checklist_data' => $checklist,
            'issues_summary' => $validated['issues_summary'] ?? null,
            'actions_taken' => $validated['actions_taken'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
            'photos' => $storedPhotos,
            'submitted_at' => now(),
        ]);

        return redirect()->route('it-surveys.show', $report->id)->with('success', 'Branch infrastructure survey submitted successfully!');
    }

    public function show($id)
    {
        $report = ItSurveyReport::findOrFail($id);

        return Inertia::render('it-surveys/show', [
            'report' => $report,
        ]);
    }

    public function destroy($id)
    {
        $report = ItSurveyReport::findOrFail($id);

        if (!empty($report->photos) && is_array($report->photos)) {
            foreach ($report->photos as $photo) {
                if (is_string($photo) && str_starts_with($photo, '/storage/')) {
                    $relPath = str_replace('/storage/', '', $photo);
                    Storage::disk('public')->delete($relPath);
                }
            }
        }

        $report->delete();

        return redirect()->route('it-surveys.index')->with('success', 'Audit report deleted successfully.');
    }

    public function hardwareIndex(Request $request)
    {
        return redirect()->route('it-surveys.index', array_merge($request->query(), ['tab' => 'hardware']));
    }

    public function hardwareStore(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'branch_name' => 'required|string|max:150',
            'asset_tag' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'asset_type' => 'required|string|max:100',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'specifications' => 'nullable|string',
            'status' => 'required|in:operational,maintenance,repair,faulty,missing,retired',
            'assigned_to' => 'nullable|string|max:150',
            'location' => 'nullable|string|max:150',
            'ip_address' => 'nullable|string|max:64',
            'notes' => 'nullable|string',
        ]);

        if (!empty($validated['id'])) {
            $asset = ItHardwareAsset::findOrFail($validated['id']);
            $asset->update($validated);
            $msg = 'Hardware asset updated successfully!';
        } else {
            ItHardwareAsset::create($validated);
            $msg = 'Hardware asset registered successfully!';
        }

        return redirect()->back()->with('success', $msg);
    }

    public function hardwareDestroy($id)
    {
        $asset = ItHardwareAsset::findOrFail($id);
        $asset->delete();

        return redirect()->back()->with('success', 'Hardware asset removed from inventory.');
    }

    public function exportReports(Request $request)
    {
        $reports = ItSurveyReport::orderBy('submitted_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="it_branch_audits_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($reports) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Report UUID', 'Branch Name', 'Type', 'Technician', 'Manager',
                'Health Score', 'Status', 'Critical Issues', 'Passed Items', 'Failed Items',
                'Skipped Items', 'Issues Summary', 'Actions Taken', 'Submitted Date'
            ]);

            foreach ($reports as $r) {
                fputcsv($file, [
                    $r->report_uuid,
                    $r->branch_name,
                    ucfirst($r->type),
                    $r->technician_name,
                    $r->branch_manager,
                    $r->health_score . '%',
                    $r->health_status,
                    $r->critical_count,
                    $r->passed_items,
                    $r->failed_items,
                    $r->skipped_items,
                    $r->issues_summary,
                    $r->actions_taken,
                    $r->submitted_at?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportHardware(Request $request)
    {
        $assets = ItHardwareAsset::orderBy('branch_name')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="hardware_assets_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($assets) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID', 'Branch', 'Asset Tag', 'Asset Type', 'Brand', 'Model',
                'Serial Number', 'Status', 'Assigned To', 'IP Address', 'Notes'
            ]);

            foreach ($assets as $a) {
                fputcsv($file, [
                    $a->id,
                    $a->branch_name,
                    $a->asset_tag,
                    strtoupper($a->asset_type),
                    $a->brand,
                    $a->model,
                    $a->serial_number,
                    ucfirst($a->status),
                    $a->assigned_to,
                    $a->ip_address,
                    $a->notes,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
