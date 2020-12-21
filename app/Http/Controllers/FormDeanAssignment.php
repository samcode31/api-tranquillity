<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\FormDeanAssignment as ModelsFormDeanAssignment;
use Illuminate\Http\Request;

class FormDeanAssignment extends Controller
{
    public function show($employee_id)
    {       
        $academic_term = AcademicTerm::whereIsCurrent(1)->first();
        $academic_year_id = $academic_term->academic_year_id;  
        $form_dean_assignment = ModelsFormDeanAssignment::where([
            ['academic_year_id', $academic_year_id],
            ['employee_id', $employee_id]
        ])
        ->get();
        return $form_dean_assignment;    
    }

    public function store(Request $request)
    {
        $academic_term = AcademicTerm::whereIsCurrent(1)->first();
        $academic_year_id = $academic_term->academic_year_id;
        $employee_id = $request->employee_id;
        $form_classes = $request->form_classes;
        $data = [];
        $form_dean_assignments = ModelsFormDeanAssignment::where([
            ['employee_id', $employee_id],
            ['academic_year_id', $academic_year_id]
        ])
        ->get();

        if(sizeof($form_dean_assignments) == 0)
        {
            foreach($form_classes as $form_class_id)
            {
                $form_dean_assignment = ModelsFormDeanAssignment::create([
                    'employee_id' => $employee_id,
                    'form_class_id' => $form_class_id,
                    'academic_year_id' => $academic_year_id
                ]);

                array_push($data, $form_dean_assignment);
            }
        }
        else{           
            foreach($form_classes as $form_class_id)
            {
                $assignment_exists = false;
                foreach($form_dean_assignments as $form_dean_assignment)
                {
                    if($form_class_id == $form_dean_assignment->form_class_id)
                    {
                        $assignment_exists = true;
                        break;
                    }                   
                }

                if(!$assignment_exists)
                {
                    $form_dean_assignment = ModelsFormDeanAssignment::create([
                        'employee_id' => $employee_id,
                        'form_class_id' => $form_class_id,
                        'academic_year_id' => $academic_year_id
                    ]);
                }
            }

            $form_dean_assignments = ModelsFormDeanAssignment::where([
                ['employee_id', $employee_id],
                ['academic_year_id', $academic_year_id]
            ])
            ->get();

            foreach($form_dean_assignments as $form_dean_assignment)
            {
                $assignment_exists = false;
                foreach($form_classes as $form_class_id)
                {
                    if($form_class_id == $form_dean_assignment->form_class_id)
                    {
                        $assignment_exists = true;
                        break;
                    }
                }

                if(!$assignment_exists)
                {
                    $form_dean_assignment->delete();
                }
                else{
                    array_push($data, $form_dean_assignment);
                }
            }
        }

        return $data;
        
    }
}
