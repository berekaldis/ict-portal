<?php

namespace App\Http\Controllers\Case;

use App\Http\Controllers\Controller;
use App\Models\CaseAssignee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CaseAssigneeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:case_assignees,name',
            'telegram_id' => 'nullable|string|max:100',
        ]);

        CaseAssignee::create([
            'name' => $validated['name'],
            'telegram_id' => $validated['telegram_id'] ?? null,
            'status' => 'active',
        ]);

        return back()->with('success', 'Assignee added successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $assignee = CaseAssignee::findOrFail($id);
        $assignee->delete();

        return back()->with('success', 'Assignee removed.');
    }
}
