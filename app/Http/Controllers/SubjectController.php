<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SubjectController extends Controller
{
    public function store(Request $request)
    {

    }

    public function show(){
        return Subject::all();
    }
}
