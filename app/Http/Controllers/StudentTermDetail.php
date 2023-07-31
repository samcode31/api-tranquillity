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
        ->get();

        return $records;
    }

    public function showAll()
    {
        $academic_term_id = AcademicTerm::where('is_current', 1)
        ->first()
        ->id;
        return ModelsStudentTermDetail::join('students', 'student_term_details.student_id', 'students.id')
        ->select('student_term_details.student_id', 'students.first_name', 'students.last_name', 'student_term_details.form_class_id')
        ->where('academic_term_id',$academic_term_id)
        ->orderBy('student_term_details.form_class_id')
        ->orderBy('students.last_name')
        ->orderBy('students.first_name')
        ->get();

    }

    public function store(Request $request)
    {
        $data = [];
        $student_term_detail = ModelsStudentTermDetail::updateOrCreate(
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
                // "new_term_beginning" => $request->new_term_beginning,
                "employee_id" => $request->employee_id
            ]
        );

        $student_dean_comment = StudentDeanComment::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'academic_term_id' => $request->academic_term_id,
            ],
            [
                'student_id' => $request->student_id,
                'academic_term_id' => $request->academic_term_id,
                'comment' => $request->dean_comment,
                'employee_id' => $request->employee_id,
            ]
        );

        $data['student_term_details'] = $student_term_detail;
        $data['student_dean_comment'] = $student_dean_comment;

        return $data;
    }

    public function register(Request $request)
    {

        $student_term_details_inserts = 0; $student_term_details_updates = 0;

        $student_dean_comment_inserts = 0; $student_dean_comment_updates = 0;

        $academic_term = AcademicTerm::where('is_current', 1)
        ->first();

        $academic_year_id = $academic_term->academic_year_id;

        $academic_term_id = $academic_term->id;

        $student_class_registrations = StudentClassRegistration::where([
            ['academic_year_id', $academic_year_id]
        ])->get();

        foreach($student_class_registrations as $student){
            $student_id = $student->student_id;

            $student_term_details = ModelsStudentTermDetail::updateOrCreate(
                [
                    'student_id' => $student_id,
                    'academic_term_id' => $academic_term_id
                ],
                [
                    'student_id' => $student_id,
                    'academic_term_id' => $academic_term_id,
                    'total_sessions' => $request->total_sessions,
                    'form_class_id' => $student->form_class_id,
                    'new_term_beginning' => $request->new_term_beginning,
                ]
            );

            if($student_term_details->wasRecentlyCreated) $student_term_details_inserts++;
            elseif($student_term_details->wasChanged()) $student_term_details_updates++;

            $student_dean_comments = StudentDeanComment::updateOrCreate(
                [
                    'student_id' => $student_id,
                    'academic_term_id' => $academic_term_id
                ],
                [
                    'student_id' => $student_id,
                    'academic_term_id' => $academic_term_id
                ]
            );

            if($student_dean_comments->wasRecentlyCreated) $student_dean_comment_inserts++;
            elseif($student_dean_comments->wasChanged()) $student_dean_comment_updates++;

        }

        $data['student_term_details_inserts'] = $student_term_details_inserts;
        $data['student_term_details_updates'] = $student_term_details_updates;
        $data['student_dean_comments_inserts'] = $student_dean_comment_inserts;
        $data['student_dean_comments_updates'] = $student_dean_comment_updates;

        return $data;


    }
}
