<?php

namespace App\Http\Controllers;

use App\Models\TeacherLesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class TeacherLessonController extends Controller
{
    public function show($id)
    {
        $lessons = TeacherLesson::whereEmployeeId($id)->get();
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
            $id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
            $formClass = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
            $subjectId = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();            
            $lesson = TeacherLesson::updateOrCreate(
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
        $lesson = TeacherLesson::where([
            ['employee_id', $request->employee_id],
            ['academic_year_id', $request->academic_year_id],
            ['subject_id', $request->subject_id],
            ['form_class_id', $request->form_class_id]
        ])->exists();

        if($lesson){
            //update
            $updated = TeacherLesson::where([
                ['employee_id', $request->employee_id],
                ['academic_year_id', $request->academic_year_id],
                ['subject_id', $request->subject_id],
                ['form_class_id', $request->form_class_id]
            ])->update(               
                [
                    'subject_id' => $request->new_subject_id, 
                    "form_class_id" => $request->new_class_id
                ]
                );    
            $data['updated'] = $updated;
        }
        else{
            //insert
            $lesson = TeacherLesson::create([
                'employee_id' => $request->employee_id,
                'academic_year_id' => $request->academic_year_id,
                'subject_id' => $request->subject_id,
                'form_class_id' => $request->form_class_id
            ]);
            $lesson->save();
            if($lesson->exists) $data['inserted'] = $lesson;    
        }

        return $data;
    }

    public function delete(Request $request){        
        $lesson = TeacherLesson::where([
            ['employee_id', $request->employee_id],
            ['academic_year_id', $request->academic_year_id],
            ['subject_id', $request->subject_id],
            ['form_class_id', $request->form_class_id],
        ])->first();
        $lesson->delete();
        if($lesson->exists) return 'Lesson not deleted';
        return 'Lesson deleted';
    }
   
}
