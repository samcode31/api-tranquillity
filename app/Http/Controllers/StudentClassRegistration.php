<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentClassRegistration as ModelsStudentClassRegistration;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class StudentClassRegistration extends Controller
{
    public function register()
    {
        $students = Student::select('id', 'form_class_id')->get();
        $academicTerm = AcademicTerm::whereIsCurrent(1)->first();
        $academic_year_id = $academicTerm->academic_year_id;
        //return $academic_year_id;
        $registered = 0;
        foreach($students as $student){
            $student_id = $student->id;
            $form_class_id = $student->form_class_id;
            $studentClassRegistration = ModelsStudentClassRegistration::updateOrCreate(
                ['student_id' => $student_id, 'academic_year_id' => $academic_year_id],
                [
                    'student_id' => $student_id,
                    'form_class_id' => $form_class_id,
                    'academic_year_id' => $academic_year_id
                ]
            );
            if($studentClassRegistration->exists()) $registered++;
        }

        return $registered;
    }

    public function upload()
    {
        $academicTerm = AcademicTerm::whereIsCurrent(1)->first();
        $academic_year_id = $academicTerm->academic_year_id;
        $file = './files/student_class_registrations.xlsx';
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        //return $rows;
        
        for($i = 2; $i <= $rows; $i++){            
            $student_id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
            $form_class_id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
            if(!$student_id || !$form_class_id) continue;
            ModelsStudentClassRegistration::updateOrCreate(
                ['student_id' => $student_id, 'academic_year_id' => $academic_year_id ],
                [
                    'student_id' => $student_id,
                    'form_class_id' => $form_class_id,
                    'academic_year_id' => $academic_year_id
                ]
            );
            
        }    
    }

    public function promote ()
    {
        $academicTerm = AcademicTerm::where('is_current', 1)
        ->first();

        $currentAcademicYearId = null;

        if($academicTerm){
            $currentAcademicYearId = $academicTerm->academic_year_id;
        }
        
        $academicYears = AcademicYear::orderBy('id', 'desc')
        ->get();

        $previousAcademicYearId = null;

        if($academicYears[1]){
            $previousAcademicYearId = $academicYears[1]->id;
        }

        $studentClassRegistrations = ModelsStudentClassRegistration::join(
            'form_classes',
            'form_classes.id',
            'student_class_registrations.form_class_id'
        )
        ->select(
            'student_id',
            'form_class_id',
            'form_level'
        )
        ->where(
            'academic_year_id',
            $previousAcademicYearId
        )
        ->orderBy('form_class_id')
        ->get();

        foreach($studentClassRegistrations as $record){
            $promotedClassId = null;

            switch($record->form_level){
                case 1:
                    $promotedClassId = "2".substr($record->form_class_id, 1);
                    break;
                case 2:
                    $promotedClassId = "3".substr($record->form_class_id, 1);
                    break;
                case 3:
                    $promotedClassId = "4".substr($record->form_class_id, 1);
                    break;
                case 4:
                    $promotedClassId = "5".substr($record->form_class_id, 1);
                    break;
                case 6:
                    if($record->form_class_id == '6 Lw'){
                        $promotedClassId = "6 Up";
                    }                   
                    break;    
            }

            if($promotedClassId){
                ModelsStudentClassRegistration::updateOrCreate(
                    [
                        'student_id' => $record->student_id,
                        'academic_year_id' => $currentAcademicYearId
                    ],
                    [
                        'form_class_id' => $promotedClassId
                    ]
                );
            }

            
        }
    }
}
