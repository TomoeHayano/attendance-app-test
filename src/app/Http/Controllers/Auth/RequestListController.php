<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RequestListController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $activeTab = $request->query('tab', 'pending');

        $pendingRequests = CorrectionRequest::with(['attendance', 'user'])
            ->where('user_id', $user->id)
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();

        $approvedRequests = CorrectionRequest::with(['attendance', 'user'])
            ->where('user_id', $user->id)
            ->where('status', CorrectionRequest::STATUS_APPROVED)
            ->orderByDesc('created_at')
            ->get();

        $displayRequests = $activeTab === 'approved'
            ? $approvedRequests
            : $pendingRequests;

        return view('attendance.request-list', [
            'activeTab'       => $activeTab,
            'displayRequests' => $displayRequests,
        ]);

    }
}
