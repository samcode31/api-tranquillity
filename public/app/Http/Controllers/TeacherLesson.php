<?php

namespace App\Http\Controllers;

use App\Models\FormClass;
use App\Models\TeacherLesson as ModelsTeacherLesson;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;


class TeacherLesson extends Controller
{
    public function show($id)
    {
        
        $lessons = ModelsTeacherLesson::whereEmployeeId($id)->get();
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
        $updates = 0;
        $inserts = 0;
        $form_class_id = $request->form_class_id;
        if($form_class_id == 'Form 4'){
            $form_classes = FormClass::whereFormLevel(4)->get();
            //return $form_classes;
        }
        elseif($form_class_id == 'Form 5'){
            $form_classes = FormClass::whereFormLevel(5)->get();
            //return $form_classes;
        }
        elseif($form_class_id == 'Lower 6'){
            $form_classes = FormClass::where('id', 'like', '6L%')
            ->get();
            //return $form_classes;
        }
        elseif($form_class_id == 'Upper 6'){
            $form_classes = FormClass::where('id', 'like', '6U%')
            ->get();
            //return $form_classes;
        }
        else{
            $form_classes = FormClass::whereId($form_class_id)->get();
            //return $form_classes;            
        }       
        
        //return $form_classes;
        foreach($form_classes as $form_class){
            $lesson = ModelsTeacherLesson::where([
                ['employee_id', $request->employee_id],
                ['academic_year_id', $request->academic_year_id],
                ['subject_id', $request->subject_id],
                ['form_class_id', $form_class->id]
            ])->exists();
            if($lesson){
                //update
                $teacherLesson = ModelsTeacherLesson::where([
                    ['employee_id', $request->employee_id],
                    ['academic_year_id', $request->academic_year_id],
                    ['subject_id', $request->subject_id],
                    ['form_class_id', $form_class->id]
                ])->first();
                $teacherLesson->subject_id = $request->new_subject_id;
                $teacherLesson->form_class_id = $request->new_class_id;
                $teacherLesson->save();                
                if($teacherLesson->isDirty()) $updates++;
            }
            else{
                //insert
                $lesson = ModelsTeacherLesson::create([
                    'employee_id' => $request->employee_id,
                    'academic_year_id' => $request->academic_year_id,
                    'subject_id' => $request->subject_id,
                    'form_class_id' => $form_class->id
                ]);
                $lesson->save();
                if($lesson->exists) $inserts++;    
            }
        }
        
        $data['inserted'] = $inserts;
        $data['updated'] = $updates;

        return $data;
    }

    public function delete(Request $request){        
        $lesson = ModelsTeacherLesson::where([
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
