<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\FormClass;
use App\Models\TeacherLesson as ModelsTeacherLesson;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;


class TeacherLesson extends Controller
{
    public function show($id)
    {
        $academicTerm = AcademicTerm::where('is_current',1)->first();
        $academicYearId = null;
        if($academicTerm){
            $academicYearId = $academicTerm->academic_year_id;
        }
        $lessons = ModelsTeacherLesson::where([
            ['employee_id', $id],
            ['academic_year_id', $academicYearId]
        ])
        ->get();
        $records = [];
        foreach($lessons as $lesson){
            $lesson->subject;
            $lesson->formClass;
            array_push($records, $lesson);
        }

        return $records;
    }

    public function upload()
    {
        $file = './files/teacher_lessons.xlsx';
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        //return $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,2)->getValue();
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        //return $classId = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(28,2)->getValue();
        //return $rows;
        $records = 0;
        for($i = 2; $i <= $rows; $i++){
            $formClass = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
            $id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();            
            $subjectId = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();            
            $lesson = ModelsTeacherLesson::updateOrCreate(
                [
                    'employee_id' => $id,
                    'academic_year_id' => 20202021,
                    'subject_id' => $subjectId,
                    'form_class_id' => $formClass
                ],
                [
                    'employee_id' => $id,
                    'academic_year_id' => 20202021,
                    'subject_id' => $subjectId,
                    'form_class_id' => $formClass,                    
                ]
            );
            if($lesson->exists) $records++;
        }
        //return $spreadsheet->getActiveSheet()->getHighestDataRow();
        return $records;
    }

    public function store(Request $request){
        $data = [];                     
        $academic_term = AcademicTerm::whereIsCurrent(1)->first();
        $academic_year_id = $academic_term->academic_year_id;
        //return $academic_year_id;       
        
        $lesson = ModelsTeacherLesson::where([
            ['employee_id', $request->employee_id],
            ['academic_year_id', $academic_year_id],
            ['subject_id', $request->subject_id],
            ['form_class_id', $request->form_class_id]
        ])->firstOr( function () { 
            return false;
        });
        
        if(!$lesson){
            //insert
            $teacher_lesson = ModelsTeacherLesson::create([
                'employee_id' => $request->employee_id,
                'academic_year_id' => $academic_year_id,
                'subject_id' => $request->new_subject_id,
                'form_class_id' => $request->new_form_class_id
            ]);
            $teacher_lesson->save();
            $data['inserted'] = $teacher_lesson; 
        }
        else{ 
            //update
            $teacherLesson = ModelsTeacherLesson::where([
                ['employee_id', $request->employee_id],
                ['academic_year_id', $academic_year_id],
                ['subject_id', $request->subject_id],
                ['form_class_id', $request->form_class_id]
            ])->first();
            //return $teacherLesson;
            $teacherLesson->subject_id = $request->new_subject_id;
            $teacherLesson->form_class_id = $request->new_form_class_id;
            $teacherLesson->save();                
            $data['updated'] = $teacherLesson;
        }       
       

        return $data;
    }

    public function delete(Request $request){ 
        $academic_term = AcademicTerm::whereIsCurrent(1)->first();
        $academic_year_id = $academic_term->academic_year_id;       
        $lesson = ModelsTeacherLesson::where([
            ['employee_id', $request->employee_id],
            ['academic_year_id', $academic_year_id],
            ['subject_id', $request->subject_id],
            ['form_class_id', $request->form_class_id],
        ])->first();
        $lesson->delete();
        if($lesson->exists) return 'Lesson not deleted';
        return 'Lesson deleted';
    }
}
