<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use Illuminate\Http\Request;


class AcademicTermController extends Controller
{
    public function show(){
        return AcademicTerm::whereIsCurrent(1)->first();
    }
}
