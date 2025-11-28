<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RequestListController extends Controller
{
    /**
     * 管理者用：全ユーザーの修正申請一覧
     *
     * タブ
     * - 承認待ち：全ユーザーの未承認申請
     * - 承認済み：全ユーザーの承認済み申請
     */
    public function index(Request $request): View
    {
        /** @var string $activeTab */
        $activeTab = $request->query('tab', 'pending');

        // 全ユーザー対象なので user_id では絞り込まないことがポイント
        $pendingRequests = CorrectionRequest::with(['attendance', 'user'])
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();

        $approvedRequests = CorrectionRequest::with(['attendance', 'user'])
            ->where('status', CorrectionRequest::STATUS_APPROVED)
            ->orderByDesc('created_at')
            ->get();

        $displayRequests = $activeTab === 'approved'
            ? $approvedRequests
            : $pendingRequests;

        return view('admin.request-list', [
            'activeTab'       => $activeTab,
            'displayRequests' => $displayRequests,
        ]);
    }
}
