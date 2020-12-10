<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\StudentClassRegistration;
use App\Models\StudentDeanComment;
use App\Models\StudentTermDetail as ModelsStudentTermDetail;
use Illuminate\Http\Request;

class StudentTermDetail extends Controller
{
    public function show($termId, $formClass){
       
        $yearId = AcademicTerm::whereId($termId)->first()->academic_year_id;       
        $studentsRegistered = StudentClassRegistration::where([
            ['form_class_id', $formClass],
            ['academic_year_id', $yearId]
        ])->get();

        //return $studentsRegistered;
        //register records for term if doesnt exist       
        foreach($studentsRegistered as $student){
            $studentId = $student->student_id;
           
            $studentTermDetailsExists = ModelsStudentTermDetail::where([
                ['student_id', $studentId],
                ['academic_term_id', $termId]
            ])->exists();
            
            if(!$studentTermDetailsExists){
                ModelsStudentTermDetail::create([
                    'student_id' => $studentId,
                    'academic_term_id' => $termId,
                    'form_class_id' => $formClass
                ]);                
            }
            
            $studentDeanComments = StudentDeanComment::where([
                ['student_id', $studentId],
                ['academic_term_id', $termId]
            ]);

            if(!$studentDeanComments->exists()){
                StudentDeanComment::create([
                    'student_id' => $studentId,
                    'academic_term_id' => $termId,
                ]);
            }
           
        }       

        $records = ModelsStudentTermDetail::join('students', 'students.id', 'student_term_details.student_id')
        ->join('student_dean_comments', 'student_dean_comments.student_id', 'student_term_details.student_id')
        ->select('student_term_details.*','students.first_name', 'students.last_name', 'students.picture', 'student_dean_comments.comment as dean_comment')
        ->where([
            ['student_term_details.academic_term_id', $termId],            
            ['student_dean_comments.academic_term_id', $termId],            
            ['student_term_details.form_class_id', $formClass]
        ])
        ->orderBy('last_name')
        ->paginate(1);        

        return $records;       
    }

    public function store(Request $request)
    {        
        $record = ModelsStudentTermDetail::updateOrCreate(
            [
                'student_id' => $request->student_id,                
                'academic_term_id' => $request->academic_term_id,
            ],
            [
                "student_id" => $request->student_id,
                "academic_term_id" => $request->academic_term_id,
                "form_class_id" => $request->form_class_id,
                "sessions_absent" => $request->sessions_absent,
                "sessions_late" => $request->sessions_late,
                "total_sessions" => $request->total_sessions,
                "teacher_comment" => $request->teacher_comment,
                "packages_collected" => $request->packages_collected,
                "packages_not_collected" => $request->packages_not_collected,
                "new_term_beginning" => $request->new_term_beginning,
                "employee_id" => $request->employee_id
            ]
        );

        return $record;
    }
}
