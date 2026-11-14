<?php

namespace App\Http\Controllers\Case;

use App\Http\Controllers\Controller;
use App\Models\CaseType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CaseTypeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:case_types,name',
        ]);

        CaseType::create(['name' => $validated['name']]);

        return back()->with('success', 'Case type added successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $type = CaseType::findOrFail($id);
        $type->delete();

        return back()->with('success', 'Case type deleted.');
    }
}
