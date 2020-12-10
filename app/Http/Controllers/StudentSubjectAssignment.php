<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\StudentClassRegistration;
use App\Models\StudentSubjectAssignment as ModelsStudentSubjectAssignment;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class StudentSubjectAssignment extends Controller
{
    public function upload(){
        $file = './files/subject_assignments.xlsx';
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        //return $rows;
        $records = 0;
        for($i = 2; $i <= $rows; $i++){
            $subject_id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
            $student_id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
            $employee_id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
            $academic_year_id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
            
            $subjectAssignment = ModelsStudentSubjectAssignment::updateOrCreate(
                [
                    'subject_id' => $subject_id,
                    'student_id' => $student_id,                    
                    'academic_year_id' => $academic_year_id
                ],
                [
                    'subject_id' => $subject_id,
                    'student_id' => $student_id,
                    'employee_id' => $employee_id,
                    'academic_year_id' => $academic_year_id
                ]
            );
            
            if($subjectAssignment->exists) $records++;
        }
        
        
        return $records;
    }
    
    public function store(Request $request){
        $academic_term = AcademicTerm::whereIsCurrent(1)->first();
        $academic_year_id = $academic_term->academic_year_id;
        if($request->has('form_class_id'))
        {
            $students = StudentClassRegistration::where([
                ['academic_year_id', $academic_year_id],
                ['form_class_id', $request->form_class_id]
            ])->get();

            $subjectAssignments = 0;

            foreach($students as $student){
                $subjectAssignment = ModelsStudentSubjectAssignment::updateOrCreate(
                    [
                        'subject_id' => $request->subject_id,
                        'student_id' => $student->student_id,
                        'academic_year_id' => $academic_year_id,                        
                    ],
                    [
                        'subject_id' => $request->subject_id,
                        'student_id' => $student->student_id,
                        'academic_year_id' => $academic_year_id,
                        'employee_id' => $request->employee_id
                    ]
                );

                if($subjectAssignment->exists()) $subjectAssignments++;
            }

            return $subjectAssignments;
        }
        else
        {
            $subjectAssignment = ModelsStudentSubjectAssignment::updateOrCreate(
                [
                    'subject_id' => $request->subject_id,
                    'student_id' => $request->student_id,                    
                    'academic_year_id' => $academic_year_id
                ],
                [
                    'subject_id' => $request->subject_id,
                    'student_id' => $request->student_id,
                    'employee_id' => $request->employee_id,
                    'academic_year_id' => $academic_year_id
                ]
            );

            return $subjectAssignment;
        }
        

        
    }

    public function show($subject_id)
    {                
        $academic_term = AcademicTerm::whereIsCurrent(1)->first();
        $academic_year_id = $academic_term->academic_year_id;
        //return $academic_year_id;
        $studentsAssigned = [];
        $studentSubjectAssignments = ModelsStudentSubjectAssignment::where([
            ['academic_year_id', $academic_year_id],
            ['subject_id', $subject_id]
        ])->get();
        //return $studentSubjectAssignments;
        foreach($studentSubjectAssignments as $studentSubjectAssignment){
            $studentRecord = [];
            $student = Student::whereId($studentSubjectAssignment->student_id)->first();
            //return $student;
            $studentRecord['student_id'] = $student->id;
            $studentRecord['first_name'] = $student->first_name;
            $studentRecord['last_name'] = $student->last_name;
            $studentRecord['gender'] = $student->gender;
            $studentClassRegistration = StudentClassRegistration::where([
                ['student_id', $student->id],
                ['academic_year_id', $academic_year_id]
            ])->first();
            $studentRecord['form_class'] = $studentClassRegistration->form_class_id;
            array_push($studentsAssigned, $studentRecord);
        }
        return $studentsAssigned;    
    }

    public function delete(Request $request)
    {
        $academic_term = AcademicTerm::whereIsCurrent(1)->first();
        $academic_year_id = $academic_term->academic_year_id;
        $deletedRow = ModelsStudentSubjectAssignment::where([
            ['academic_year_id', $academic_year_id],
            ['student_id', $request->student_id],
            ['subject_id', $request->subject_id]
        ])
        ->first()
        ->delete();
        return $deletedRow;   
    }
}
