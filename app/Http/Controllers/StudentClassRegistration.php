<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\StudentClassRegistration as ModelsStudentClassRegistration;
use Illuminate\Http\Request;

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
}
