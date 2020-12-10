<?php

namespace App\Http\Controllers;

use App\Models\FormTeacher as ModelsFormTeacher;
use Illuminate\Http\Request;

class FormTeacher extends Controller
{
    public function show($id, $year){
        $formClass = ModelsFormTeacher::where([
            ['academic_year_id', $year ],
            ['employee_id', $id ]
        ])->get();

        return $formClass;
    }

    public function store(Request $request){
        
        $formTeacherClass = ModelsFormTeacher::whereId($request->id)->first();
        
        if($formTeacherClass != null && $formTeacherClass->exists())
        {            
            $formTeacherClass->class_id = $request->class_id;
            $formTeacherClass->save();
            return $formTeacherClass;
        }
        else{            
            $formTeacherClass = ModelsFormTeacher::create([
                'employee_id' => $request->employee_id,
                'class_id' => $request->class_id,
                'academic_year_id' => $request->academic_year_id
            ]);
            return $formTeacherClass;            
        }       
        
    }
}
