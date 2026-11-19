<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\TelecomPhoneNumber;
use App\Models\TelecomProvider;
use App\Models\TelecomTransfer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TelecomPhoneNumberController extends Controller
{
    public function index(Request $request): Response
    {
        $query = TelecomPhoneNumber::query()
            ->with(['provider:id,name', 'employee:id,name,username', 'branch', 'department:id,name']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%")
                    ->orWhere('sim_card_number', 'like', "%{$search}%")
                    ->orWhere('package_type', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('branch', fn($bq) => $bq->where('branch_name', 'like', "%{$search}%"))
                    ->orWhereHas('employee', fn($eq) => $eq->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"));
            });
        }

        if ($providerId = $request->query('telecom_provider_id')) {
            $query->where('telecom_provider_id', $providerId);
        }

        if ($serviceType = $request->query('service_type')) {
            $query->where('service_type', $serviceType);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($assignedType = $request->query('assigned_type')) {
            $query->where('assigned_type', $assignedType);
        }

        if ($branchId = $request->query('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        $perPage = (int) $request->query('per_page', 15);
        $phoneNumbers = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

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

        $branches = Branch::where('is_active', true)->orderBy('branch_name')->get()->map(fn($b) => ['id' => $b->id, 'name' => $b->name]);

        return Inertia::render('telecom/phone-numbers/Index', [
            'phoneNumbers' => $phoneNumbers,
            'providers' => TelecomProvider::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'branches' => $branches,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employees' => $employees,
            'transfers' => TelecomTransfer::where('telecom_type', 'phone')
                ->with(['fromEmployee:id,name,username', 'toEmployee:id,name,username', 'fromBranch', 'toBranch', 'fromDepartment:id,name', 'toDepartment:id,name', 'transferredByUser:id,name'])
                ->latest()
                ->limit(30)
                ->get(),
            'filters' => $request->only(['search', 'telecom_provider_id', 'service_type', 'status', 'assigned_type', 'branch_id', 'per_page']),
        ]);
    }

    public function create(): Response
    {
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

        return Inertia::render('telecom/phone-numbers/Create', [
            'providers' => TelecomProvider::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::where('is_active', true)->orderBy('branch_name')->get()->map(fn($b) => ['id' => $b->id, 'name' => $b->name]),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employees' => $employees,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:50', 'unique:telecom_phone_numbers,phone_number'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'sim_card_number' => ['nullable', 'string', 'max:100'],
            'telecom_provider_id' => ['nullable', 'exists:telecom_providers,id'],
            'service_type' => ['required', 'string', 'max:50'],
            'package_type' => ['nullable', 'string', 'max:150'],
            'package_start_date' => ['nullable', 'date'],
            'package_expiry_date' => ['nullable', 'date'],
            'monthly_cost' => ['required', 'numeric', 'min:0'],
            'billing_type' => ['required', 'string', 'max:50'],
            'assigned_type' => ['required', 'string', 'max:50'],
            'employee_id' => ['nullable', 'exists:users,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'status' => ['required', 'string', 'max:50'],
            'issue_date' => ['nullable', 'date'],
            'renewal_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
        ]);

        if (!empty($validated['employee_id'])) {
            $validated['user_id'] = $validated['employee_id'];
        }

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('telecom/sim', 'public');
        }

        unset($validated['attachment']);

        TelecomPhoneNumber::create($validated);

        return redirect()->route('telecom.phone-numbers.index')
            ->with('success', 'Phone number created successfully.');
    }

    public function edit(TelecomPhoneNumber $phoneNumber): Response
    {
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

        return Inertia::render('telecom/phone-numbers/Edit', [
            'phoneNumber' => $phoneNumber->load(['provider', 'employee', 'branch', 'department']),
            'providers' => TelecomProvider::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::where('is_active', true)->orderBy('branch_name')->get()->map(fn($b) => ['id' => $b->id, 'name' => $b->name]),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employees' => $employees,
        ]);
    }

    public function update(Request $request, TelecomPhoneNumber $phoneNumber): RedirectResponse
    {
        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:50', 'unique:telecom_phone_numbers,phone_number,' . $phoneNumber->id],
            'account_number' => ['nullable', 'string', 'max:100'],
            'sim_card_number' => ['nullable', 'string', 'max:100'],
            'telecom_provider_id' => ['nullable', 'exists:telecom_providers,id'],
            'service_type' => ['required', 'string', 'max:50'],
            'package_type' => ['nullable', 'string', 'max:150'],
            'package_start_date' => ['nullable', 'date'],
            'package_expiry_date' => ['nullable', 'date'],
            'monthly_cost' => ['required', 'numeric', 'min:0'],
            'billing_type' => ['required', 'string', 'max:50'],
            'assigned_type' => ['required', 'string', 'max:50'],
            'employee_id' => ['nullable', 'exists:users,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'status' => ['required', 'string', 'max:50'],
            'issue_date' => ['nullable', 'date'],
            'renewal_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
            'remove_attachment' => ['nullable', 'boolean'],
        ]);

        if (!empty($validated['employee_id'])) {
            $validated['user_id'] = $validated['employee_id'];
        }

        if ($request->boolean('remove_attachment')) {
            if ($phoneNumber->attachment_path && Storage::disk('public')->exists($phoneNumber->attachment_path)) {
                Storage::disk('public')->delete($phoneNumber->attachment_path);
            }
            $validated['attachment_path'] = null;
        } elseif ($request->hasFile('attachment')) {
            if ($phoneNumber->attachment_path && Storage::disk('public')->exists($phoneNumber->attachment_path)) {
                Storage::disk('public')->delete($phoneNumber->attachment_path);
            }
            $validated['attachment_path'] = $request->file('attachment')->store('telecom/sim', 'public');
        }

        unset($validated['attachment'], $validated['remove_attachment']);

        $phoneNumber->update($validated);

        return redirect()->route('telecom.phone-numbers.index')
            ->with('success', 'Phone number updated successfully.');
    }

    public function destroy(TelecomPhoneNumber $phoneNumber): RedirectResponse
    {
        if ($phoneNumber->attachment_path && Storage::disk('public')->exists($phoneNumber->attachment_path)) {
            Storage::disk('public')->delete($phoneNumber->attachment_path);
        }

        $phoneNumber->delete();

        return redirect()->route('telecom.phone-numbers.index')
            ->with('success', 'Phone number deleted successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $query = TelecomPhoneNumber::query()
            ->with(['provider:id,name', 'employee:id,name', 'branch', 'department:id,name']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%")
                    ->orWhere('package_type', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('phone_number')->get();

        $response = new StreamedResponse(function () use ($items) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'ID', 'Phone Number', 'Account No', 'SIM Card / ICCID', 'Provider',
                'Service Type', 'Package Type', 'Package Start Date', 'Package Expiry Date', 'Billing Type', 'Monthly Cost',
                'Assigned To', 'Assigned Name', 'Status', 'Issue Date', 'Renewal Date', 'Notes'
            ]);

            foreach ($items as $item) {
                $assignedName = '-';
                if ($item->assigned_type === 'Employee' && $item->employee) {
                    $assignedName = $item->employee->name;
                } elseif ($item->assigned_type === 'Branch' && $item->branch) {
                    $assignedName = $item->branch->name;
                } elseif ($item->assigned_type === 'Department' && $item->department) {
                    $assignedName = $item->department->name;
                }

                fputcsv($handle, [
                    $item->id,
                    $item->phone_number,
                    $item->account_number ?? '',
                    $item->sim_card_number ?? '',
                    $item->provider?->name ?? 'N/A',
                    $item->service_type,
                    $item->package_type ?? '',
                    $item->package_start_date ? $item->package_start_date->format('Y-m-d') : '',
                    $item->package_expiry_date ? $item->package_expiry_date->format('Y-m-d') : '',
                    $item->billing_type,
                    $item->monthly_cost,
                    $item->assigned_type,
                    $assignedName,
                    $item->status,
                    $item->issue_date ? $item->issue_date->format('Y-m-d') : '',
                    $item->renewal_date ? $item->renewal_date->format('Y-m-d') : '',
                    $item->notes ?? '',
                ]);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="company_phone_numbers_' . date('Y-m-d') . '.csv"');

        return $response;
    }

    public function transfer(Request $request, TelecomPhoneNumber $phoneNumber): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_type' => ['required', 'string', 'in:Employee,Branch,Department,Unassigned'],
            'employee_id' => ['nullable', 'exists:users,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'transfer_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $targetEmployeeId = $validated['assigned_type'] === 'Employee' 
            ? ($validated['employee_id'] ?? $validated['user_id'] ?? null) 
            : null;
        $targetBranchId = $validated['assigned_type'] === 'Branch' ? ($validated['branch_id'] ?? null) : null;
        $targetDepartmentId = $validated['assigned_type'] === 'Department' ? ($validated['department_id'] ?? null) : null;

        TelecomTransfer::create([
            'telecom_type' => 'phone',
            'item_id' => $phoneNumber->id,
            'reference_number' => $phoneNumber->phone_number,
            'from_employee_id' => $phoneNumber->employee_id ?? $phoneNumber->user_id,
            'to_employee_id' => $targetEmployeeId,
            'from_branch_id' => $phoneNumber->branch_id,
            'to_branch_id' => $targetBranchId,
            'from_department_id' => $phoneNumber->department_id,
            'to_department_id' => $targetDepartmentId,
            'transfer_reason' => $validated['transfer_reason'] ?? null,
            'transferred_by' => auth()->id(),
        ]);

        $phoneNumber->update([
            'assigned_type' => $validated['assigned_type'],
            'employee_id' => $targetEmployeeId,
            'user_id' => $targetEmployeeId,
            'branch_id' => $targetBranchId,
            'department_id' => $targetDepartmentId,
        ]);

        return redirect()->route('telecom.phone-numbers.index')
            ->with('success', "Phone number {$phoneNumber->phone_number} successfully transferred.");
    }
}
