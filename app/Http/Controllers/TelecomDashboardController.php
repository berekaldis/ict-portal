<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\TelecomBroadband;
use App\Models\TelecomPhoneNumber;
use App\Models\TelecomProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TelecomDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $totalPhones = TelecomPhoneNumber::count();
        $activePhones = TelecomPhoneNumber::where('status', 'Active')->count();
        $totalPhoneCost = (float) TelecomPhoneNumber::where('status', 'Active')->sum('monthly_cost');

        $totalBroadbands = TelecomBroadband::count();
        $activeBroadbands = TelecomBroadband::where('status', 'Active')->count();
        $wttxCount = TelecomBroadband::where('connection_type', 'like', '%WTTx%')->count();
        $totalBroadbandCost = (float) TelecomBroadband::where('status', 'Active')->sum('monthly_cost');

        $totalProviders = TelecomProvider::count();

        // Phone numbers by Service Type
        $phoneTypesCount = TelecomPhoneNumber::selectRaw('service_type, COUNT(*) as count, SUM(monthly_cost) as total_cost')
            ->groupBy('service_type')
            ->get();

        // Broadband by Connection Type
        $broadbandTypesCount = TelecomBroadband::selectRaw('connection_type, COUNT(*) as count, SUM(monthly_cost) as total_cost')
            ->groupBy('connection_type')
            ->get();

        // Providers breakdown
        $providerStats = TelecomProvider::withCount(['phoneNumbers', 'broadbands'])->get();

        // Branch-level breakdown
        $allBranches = Branch::where('is_active', true)->orderBy('branch_name')->get();
        $branchStats = $allBranches->map(function ($branch) {
            $phoneCount = TelecomPhoneNumber::where('branch_id', $branch->id)->count();
            $broadbandCount = TelecomBroadband::where('branch_id', $branch->id)->count();
            $phoneCost = (float) TelecomPhoneNumber::where('branch_id', $branch->id)->where('status', 'Active')->sum('monthly_cost');
            $broadbandCost = (float) TelecomBroadband::where('branch_id', $branch->id)->where('status', 'Active')->sum('monthly_cost');

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'phone_count' => $phoneCount,
                'broadband_count' => $broadbandCount,
                'total_cost' => $phoneCost + $broadbandCost,
            ];
        })->filter(fn($b) => $b['phone_count'] > 0 || $b['broadband_count'] > 0)->values();

        // Upcoming contract expirations (broadband/WTTx expiring in next 45 days)
        $expiringBroadbands = TelecomBroadband::with(['branch', 'provider:id,name'])
            ->whereNotNull('contract_expiry_date')
            ->where('contract_expiry_date', '<=', now()->addDays(45))
            ->orderBy('contract_expiry_date')
            ->get();

        $employees = User::where('is_active', true)
            ->select('id', 'name', 'username')
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'employee_code' => $u->employee_code,
            ]);

        return Inertia::render('telecom/Dashboard', [
            'stats' => [
                'total_phones' => $totalPhones,
                'active_phones' => $activePhones,
                'total_phone_cost' => $totalPhoneCost,
                'total_broadbands' => $totalBroadbands,
                'active_broadbands' => $activeBroadbands,
                'wttx_count' => $wttxCount,
                'total_broadband_cost' => $totalBroadbandCost,
                'total_monthly_spend' => $totalPhoneCost + $totalBroadbandCost,
                'total_providers' => $totalProviders,
            ],
            'phone_types' => $phoneTypesCount,
            'broadband_types' => $broadbandTypesCount,
            'provider_stats' => $providerStats,
            'branch_stats' => $branchStats,
            'expiring_broadbands' => $expiringBroadbands,
            'providers' => TelecomProvider::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'branches' => $allBranches->map(fn($b) => ['id' => $b->id, 'name' => $b->name]),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employees' => $employees,
        ]);
    }
}
