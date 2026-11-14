<?php

namespace App\Http\Controllers\Case;

use App\Http\Controllers\Controller;
use App\Models\CaseComment;
use App\Models\CaseHistory;
use App\Models\CaseItem;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CaseCommentController extends Controller
{
    public function store(Request $request, int $caseId): RedirectResponse
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $case = CaseItem::findOrFail($caseId);
        $user = $request->user();
        $author = $user->name ?: $user->username;

        CaseComment::create([
            'case_id' => $case->case_id,
            'author' => $author,
            'comment' => $validated['comment'],
            'created_at' => now(),
        ]);

        CaseHistory::create([
            'case_id' => $case->case_id,
            'action' => 'Added Comment',
            'details' => $validated['comment'],
            'user' => $author,
            'timestamp' => now(),
        ]);

        return back()->with('success', 'Comment posted.');
    }
}
