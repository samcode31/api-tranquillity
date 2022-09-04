<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Models\CsecSubject;

class SubjectController extends Controller
{
    public function store(Request $request)
    {

    }

    public function show(){
        return Subject::all();
    }

    public function showCSECSubjects () {
        return CsecSubject::select(
            'id',
            'title',
            'abbreviation'
        )
        ->orderBy('title')
        ->get();
    }
}
