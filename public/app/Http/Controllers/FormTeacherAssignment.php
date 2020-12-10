<?php

namespace App\Http\Controllers;

use App\Models\FormTeacherAssignment as ModelsFormTeacherAssignment;
use Illuminate\Http\Request;

class FormTeacherAssignment extends Controller
{
    public function show($id, $year){        
        $formClass = ModelsFormTeacherAssignment::where([
            ['academic_year_id', $year ],
            ['employee_id', $id ]
        ])->get();

        return $formClass;
    }

    public function store(Request $request){
        
        $formTeacherClass = ModelsFormTeacherAssignment::whereId($request->id)->first();
        
        if($formTeacherClass != null && $formTeacherClass->exists())
        {            
            $formTeacherClass->form_class_id = $request->class_id;
            $formTeacherClass->save();
            return $formTeacherClass;
        }
        else{            
            $formTeacherClass = ModelsFormTeacherAssignment::create([
                'employee_id' => $request->employee_id,
                'form_class_id' => $request->class_id,
                'academic_year_id' => $request->academic_year_id
            ]);
            return $formTeacherClass;            
        }       
        
    }
}
