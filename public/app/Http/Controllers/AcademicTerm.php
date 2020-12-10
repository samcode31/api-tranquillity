<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm as ModelsAcademicTerm;
use Illuminate\Http\Request;


class AcademicTerm extends Controller
{
    
    public function show(){
        return ModelsAcademicTerm::whereIsCurrent(1)->first();        
    }
}
