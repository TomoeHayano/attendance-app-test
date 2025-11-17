<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class StaffController extends Controller
{
    /**
     * スタッフ一覧（一般ユーザー一覧）を表示する.
     *
     * @return View
     */
    public function index(): View
    {
        $users = User::query()
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'email',
            ]);

        return view('admin.staff', [
            'users' => $users,
        ]);
    }
}