<?php

namespace App\Http\Controllers;

use App\Models\StudentAttendance;
use Illuminate\Http\Request;

class StudentAttendanceController extends Controller
{
    public function store (Request $request)
    {
        return StudentAttendance::create([
            'serial_number' => $request->serialNum,
            'time_scanned' =>$request->attendanceTime,
            'date_scanned' => $request->attendanceDate
        ]);
    }
}
