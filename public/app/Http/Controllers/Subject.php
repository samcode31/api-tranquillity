<?php

namespace App\Http\Controllers;

use App\Models\Subject as ModelsSubject;
use Illuminate\Http\Request;

class Subject extends Controller
{
    public function show(){        
        return ModelsSubject::all();
    }
}
