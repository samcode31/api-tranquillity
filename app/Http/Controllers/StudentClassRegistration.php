<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
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
}
