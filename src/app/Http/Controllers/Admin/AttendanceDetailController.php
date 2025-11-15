<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Contracts\View\View;

class AdminAttendanceDetailController extends Controller
{
    public function show(Attendance $attendance): View
    {
        // ひとまず仮画面。後でちゃんとした詳細ビューに差し替えればOK
        return view('admin.attendance_detail', [
            'attendance' => $attendance,
        ]);
    }
}
