<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
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

        //return $request->all();
        $form_class_ids = $request->form_class_ids;
        $employee_id = $request->employee_id;
        $data = [];        
        $academicTerm = AcademicTerm::whereIsCurrent(1)->first();
        $academic_year_id = $academicTerm->academic_year_id;

        $form_teacher_assignments = ModelsFormTeacherAssignment::where([
            ['employee_id', $employee_id],
            ['academic_year_id', $academic_year_id]
        ])
        ->get();

        if(sizeof($form_teacher_assignments) == 0)
        {
            foreach($form_class_ids as $form_class_id)
            {
                $form_teacher_assignment = ModelsFormTeacherAssignment::create([
                    'employee_id' => $employee_id,
                    'form_class_id' => $form_class_id,
                    'academic_year_id' => $academic_year_id
                ]);

                array_push($data, $form_teacher_assignment);
            }
        }
        else
        {
            foreach($form_class_ids as $form_class_id)
            {
                $assignment_exists = false;
                foreach($form_teacher_assignments as $form_teacher_assignment)
                {
                    if($form_class_id == $form_teacher_assignment->form_class_id)
                    {
                        $assignment_exists = true;
                        break;
                    } 
                }
                
                if(!$assignment_exists){
                    $form_teacher_assignment = ModelsFormTeacherAssignment::create([
                        'employee_id' => $employee_id,
                        'form_class_id' => $form_class_id,
                        'academic_year_id' => $academic_year_id
                    ]);
    
                    //array_push($data, $form_teacher_assignment);
                }
            }

            $form_teacher_assignments = ModelsFormTeacherAssignment::where([
                ['employee_id', $employee_id],
                ['academic_year_id', $academic_year_id]
            ])
            ->get();

            //return $form_teacher_assignments;

            foreach($form_teacher_assignments as $form_teacher_assignment)
            {
                $assignment_exists = false;
                foreach($form_class_ids as $form_class_id)
                {
                    if($form_class_id == $form_teacher_assignment->form_class_id){
                        $assignment_exists = true;
                        break;
                    }
                }

                if(!$assignment_exists)
                {
                    $form_teacher_assignment->delete();
                }
                else
                {
                    array_push($data, $form_teacher_assignment);
                }
            }
        }

        return $data;           
        
    }
}
