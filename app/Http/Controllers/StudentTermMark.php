<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\FormClass;
use App\Models\Student;
use App\Models\StudentClassRegistration;
use App\Models\StudentSubjectAssignment;
use App\Models\StudentSubjectComment;
use App\Models\StudentTermDetail;
use App\Models\StudentTermMark as ModelsStudentTermMark;
use App\Models\Subject;
use Illuminate\Http\Request;

class StudentTermMark extends Controller
{
    public function show($class, $termId, $subjectId)
    {
        $termMarksRecords = [];
        $data = [];
        $total = 0;
        $registered = 0;
        $entered = 0;
        $formLevel = FormClass::whereId($class)->first()->form_level;
        $yearId = AcademicTerm::whereId($termId)->first()->academic_year_id;


        $studentsRegistered = StudentClassRegistration::where([
            ['form_class_id', $class],
            ['academic_year_id', $yearId]
        ])->get();
        //return $studentsRegistered;
        $total = $studentsRegistered->count();
        foreach($studentsRegistered as $student)
        {
            $studentMarkRecord = [];
            $registered++;
            $studentId = $student->student_id;

            $studentSubjectComment = StudentSubjectComment::where([
                ['student_id', $studentId],
                ['academic_term_id', $termId],
                ['subject_id', $subjectId]
            ])->exists();

            if($studentSubjectComment){
                $studentSubjectComment = StudentSubjectComment::where([
                    ['student_id', $studentId],
                    ['academic_term_id', $termId],
                    ['subject_id', $subjectId]
                ])->first();
                $studentMarkRecord['comment'] = $studentSubjectComment->comment;
                $studentMarkRecord['conduct'] = $studentSubjectComment->conduct;
            }
            else{
                $studentMarkRecord['comment'] = null;
                $studentMarkRecord['coduct'] = null;
            }

            $studentMarkRecordExists = ModelsStudentTermMark::where([
                ['student_id', $studentId],
                ['academic_term_id', $termId],
                ['subject_id', $subjectId],
            ])->exists();

            if($studentMarkRecordExists){
                $entered++;
                $studentExamMarkRecord = ModelsStudentTermMark::where([
                    ['student_id', $studentId],
                    ['academic_term_id', $termId],
                    ['subject_id', $subjectId],
                    ['test_id', 1]
                ])->first();
                $studentCourseMarkRecord = ModelsStudentTermMark::where([
                    ['student_id', $studentId],
                    ['academic_term_id', $termId],
                    ['subject_id', $subjectId],
                    ['test_id', 2]
                ])->first();
                $studentMarkRecord['course_mark'] = $studentCourseMarkRecord->mark;
                $studentMarkRecord['course_attendance'] = $studentCourseMarkRecord->assesment_attendance_id;
                $studentMarkRecord['exam_mark'] = $studentExamMarkRecord->mark;
                $studentMarkRecord['exam_attendance'] = $studentExamMarkRecord->assesment_attendance_id;
                $studentMarkRecord['employee_id'] = $studentCourseMarkRecord->employee_id;
            }
            else{
                $studentMarkRecord['course_mark'] = null;
                $studentMarkRecord['course_attendance'] = 1;
                $studentMarkRecord['exam_mark'] = null;
                $studentMarkRecord['exam_attendance'] = 1;
                $studentMarkRecord['employee_id'] = null;
            }
            $studentMarkRecord['student_id'] = $studentId;
            $studentMarkRecord['academic_term_id'] = $termId;
            $studentMarkRecord['subject_id'] = $subjectId;
            if($student->student){
                $studentMarkRecord['first_name'] = $student->student->first_name;
                $studentMarkRecord['last_name'] = $student->student->last_name;

                $studentRecord['picture'] = Student::whereId($studentId)->first()->picture;
                array_push($data, $studentMarkRecord);
            }

        }
        // if($formLevel < 6){
        //     //lower school
        //     $total = $studentsRegistered->count();
        //     foreach($studentsRegistered as $student)
        //     {
        //         $studentMarkRecord = [];
        //         $registered++;
        //         $studentId = $student->student_id;

        //         $studentSubjectComment = StudentSubjectComment::where([
        //             ['student_id', $studentId],
        //             ['academic_term_id', $termId],
        //             ['subject_id', $subjectId]
        //         ])->exists();

        //         if($studentSubjectComment){
        //             $studentSubjectComment = StudentSubjectComment::where([
        //                 ['student_id', $studentId],
        //                 ['academic_term_id', $termId],
        //                 ['subject_id', $subjectId]
        //             ])->first();
        //             $studentMarkRecord['comment'] = $studentSubjectComment->comment;
        //             $studentMarkRecord['conduct'] = $studentSubjectComment->conduct;
        //         }
        //         else{
        //             $studentMarkRecord['comment'] = null;
        //             $studentMarkRecord['coduct'] = null;
        //         }

        //         $studentMarkRecordExists = ModelsStudentTermMark::where([
        //             ['student_id', $studentId],
        //             ['academic_term_id', $termId],
        //             ['subject_id', $subjectId],
        //         ])->exists();

        //         if($studentMarkRecordExists){
        //             $entered++;
        //             $studentExamMarkRecord = ModelsStudentTermMark::where([
        //                 ['student_id', $studentId],
        //                 ['academic_term_id', $termId],
        //                 ['subject_id', $subjectId],
        //                 ['test_id', 1]
        //             ])->first();
        //             $studentCourseMarkRecord = ModelsStudentTermMark::where([
        //                 ['student_id', $studentId],
        //                 ['academic_term_id', $termId],
        //                 ['subject_id', $subjectId],
        //                 ['test_id', 2]
        //             ])->first();
        //             $studentMarkRecord['course_mark'] = $studentCourseMarkRecord->mark;
        //             $studentMarkRecord['course_attendance'] = $studentCourseMarkRecord->assesment_attendance_id;
        //             $studentMarkRecord['exam_mark'] = $studentExamMarkRecord->mark;
        //             $studentMarkRecord['exam_attendance'] = $studentExamMarkRecord->assesment_attendance_id;
        //             $studentMarkRecord['employee_id'] = $studentCourseMarkRecord->employee_id;
        //         }
        //         else{
        //             $studentMarkRecord['course_mark'] = null;
        //             $studentMarkRecord['course_attendance'] = 1;
        //             $studentMarkRecord['exam_mark'] = null;
        //             $studentMarkRecord['exam_attendance'] = 1;
        //             $studentMarkRecord['employee_id'] = null;
        //         }
        //         $studentMarkRecord['student_id'] = $studentId;
        //         $studentMarkRecord['academic_term_id'] = $termId;
        //         $studentMarkRecord['subject_id'] = $subjectId;
        //         $studentMarkRecord['first_name'] = $student->student->first_name;
        //         $studentMarkRecord['last_name'] = $student->student->last_name;

        //         $studentRecord['picture'] = Student::whereId($studentId)->first()->picture;
        //         array_push($data, $studentMarkRecord);
        //     }
        // }
        // else{
        //     //form 6
        //     // foreach($studentsRegistered as $studentRegistered)
        //     // {
        //     //     $studentId = $studentRegistered->id;

        //     //     if(StudentSubjectAssignment::where([
        //     //         ['student_id', $studentId],
        //     //         ['subject_id', $subjectId]
        //     //     ])->exists())
        //     //     {
        //     //         $total++;
        //     //     }
        //     // }
        //     foreach($studentsRegistered as $student)
        //     {
        //         $studentId = $student->student_id;

        //         if(StudentSubjectAssignment::where([
        //             ['student_id', $studentId],
        //             ['subject_id', $subjectId],
        //             ['academic_year_id', $yearId]
        //         ])->exists())
        //         {
        //             $registered++;
        //             $total++;

        //             $studentSubjectComment = StudentSubjectComment::where([
        //                 ['student_id', $studentId],
        //                 ['academic_term_id', $termId],
        //                 ['subject_id', $subjectId]
        //             ])->exists();

        //             if($studentSubjectComment){
        //                 $studentSubjectComment = StudentSubjectComment::where([
        //                     ['student_id', $studentId],
        //                     ['academic_term_id', $termId],
        //                     ['subject_id', $subjectId]
        //                 ])->first();
        //                 $studentMarkRecord['comment'] = $studentSubjectComment->comment;
        //                 $studentMarkRecord['conduct'] = $studentSubjectComment->conduct;
        //             }
        //             else{
        //                 $studentMarkRecord['comment'] = null;
        //                 $studentMarkRecord['conduct'] = null;
        //             }

        //             $studentMarkRecordExists = ModelsStudentTermMark::where([
        //                 ['student_id', $studentId],
        //                 ['academic_term_id', $termId],
        //                 ['subject_id', $subjectId],
        //             ])->exists();
        //             if($studentMarkRecordExists){
        //                 $studentExamMarkRecord = ModelsStudentTermMark::where([
        //                     ['student_id', $studentId],
        //                     ['academic_term_id', $termId],
        //                     ['subject_id', $subjectId],
        //                     ['test_id', 1]
        //                 ])->first();
        //                 $studentCourseMarkRecord = ModelsStudentTermMark::where([
        //                     ['student_id', $studentId],
        //                     ['academic_term_id', $termId],
        //                     ['subject_id', $subjectId],
        //                     ['test_id', 2]
        //                 ])->first();
        //                 $studentMarkRecord['course_mark'] = $studentCourseMarkRecord->mark;
        //                 $studentMarkRecord['course_attendance'] = $studentCourseMarkRecord->assesment_attendance_id;
        //                 $studentMarkRecord['exam_mark'] = $studentExamMarkRecord->mark;
        //                 $studentMarkRecord['exam_attendance'] = $studentExamMarkRecord->assesment_attendance_id;
        //                 $studentMarkRecord['employee_id'] = $studentCourseMarkRecord->employee_id;
        //             }
        //             else{
        //                 $studentMarkRecord['course_mark'] = null;
        //                 $studentMarkRecord['course_attendance'] = 1;
        //                 $studentMarkRecord['exam_mark'] = null;
        //                 $studentMarkRecord['exam_attendance'] = 1;
        //                 $studentMarkRecord['employee_id'] = null;
        //             }
        //             $studentMarkRecord['student_id'] = $studentId;
        //             $studentMarkRecord['academic_term_id'] = $termId;
        //             $studentMarkRecord['subject_id'] = $subjectId;
        //             $studentMarkRecord['first_name'] = $student->student->first_name;
        //             $studentMarkRecord['last_name'] = $student->student->last_name;
        //             $studentRecord['picture'] = Student::whereId($studentId)->first()->picture;
        //             array_push($data, $studentMarkRecord);
        //         }
        //     }
        // }
        //usort($data, array($this, "cmp"));
        $lastName = array_column($data, 'last_name');
        array_multisort($lastName, SORT_ASC, $data);
        $table2Records['data'] = $data;
        $table2Records['total'] = $total;
        $table2Records['registered'] = $registered;
        $table2Records['entered'] = $entered;
        //return new ResourcesTable2($studentsRegistered);
        return $table2Records;
        //return $classTotal;
    }

    public function cmp($a, $b){
        return strcmp($a->last_name, $b->last_name);
    }

    public function store(Request $request)
    {
        $records = 0;

        $course_record = ModelsStudentTermMark::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'academic_term_id' => $request->academic_term_id,
                'subject_id' => $request->subject_id,
                'test_id' => 2
            ],
            [
                'student_id' => $request->student_id,
                'subject_id' =>$request->subject_id,
                'academic_term_id' => $request->academic_term_id,
                'test_id' => 2,
                "mark" => $request->course_mark,
                "assesment_attendance_id" => $request->course_attendance,
                'employee_id' => $request->employee_id
            ]
        );

        if($course_record->exists) $records++;

        $exam_record = ModelsStudentTermMark::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'academic_term_id' => $request->academic_term_id,
                'subject_id' => $request->subject_id,
                'test_id' => 1
            ],
            [
                'student_id' => $request->student_id,
                'subject_id' =>$request->subject_id,
                'academic_term_id' => $request->academic_term_id,
                'test_id' => 1,
                "mark" => $request->exam_mark,
                "assesment_attendance_id" => $request->exam_attendance,
                'employee_id' => $request->employee_id
            ]
        );

        if($exam_record->exists) $records++;

        $subject_comment = StudentSubjectComment::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'subject_id' => $request->subject_id,
                'academic_term_id' => $request->academic_term_id,
            ],
            [
                'student_id' => $request->student_id,
                'subject_id' => $request->subject_id,
                'academic_term_id' => $request->academic_term_id,
                'comment' => $request->comment,
                'conduct' => $request->conduct,
            ]
        );

        if($subject_comment->exists) $records++;

        return $records;
    }

    public function studentRecords($studentId, $termId)
    {
        $data = [];
        $distinctSubjects = ModelsStudentTermMark::where([
            ['student_id', $studentId],
            ['academic_term_id', $termId]
        ])
        ->select('subject_id')
        ->distinct()
        ->get();
        //return $distinctSubjects;

        foreach($distinctSubjects as $subject){
            $studentMarkRecord = [];
            $subjectId = $subject->subject_id;

            $studentSubjectComment = StudentSubjectComment::where([
                ['student_id', $studentId],
                ['academic_term_id', $termId],
                ['subject_id', $subjectId]
            ])->exists();

            if($studentSubjectComment){
                $studentSubjectComment = StudentSubjectComment::where([
                    ['student_id', $studentId],
                    ['academic_term_id', $termId],
                    ['subject_id', $subjectId]
                ])->first();
                $studentMarkRecord['comment'] = $studentSubjectComment->comment;
                $studentMarkRecord['conduct'] = $studentSubjectComment->conduct;
            }
            else{
                $studentMarkRecord['comment'] = null;
                $studentMarkRecord['conduct'] = null;
            }

            $studentMarkRecordExists = ModelsStudentTermMark::where([
                ['student_id', $studentId],
                ['academic_term_id', $termId],
                ['subject_id', $subjectId],
            ])->exists();

            if($studentMarkRecordExists){
                $studentExamMarkRecord = ModelsStudentTermMark::where([
                    ['student_id', $studentId],
                    ['academic_term_id', $termId],
                    ['subject_id', $subjectId],
                    ['test_id', 1]
                ])->first();
                $studentCourseMarkRecord = ModelsStudentTermMark::where([
                    ['student_id', $studentId],
                    ['academic_term_id', $termId],
                    ['subject_id', $subjectId],
                    ['test_id', 2]
                ])->first();
                $studentMarkRecord['course_mark'] = $studentCourseMarkRecord->mark;
                $studentMarkRecord['course_attendance'] = $studentCourseMarkRecord->assesment_attendance_id;
                $studentMarkRecord['exam_mark'] = $studentExamMarkRecord->mark;
                $studentMarkRecord['exam_attendance'] = $studentExamMarkRecord->assesment_attendance_id;
                $studentMarkRecord['employee_id'] = $studentCourseMarkRecord->employee_id;
            }
            else{
                $studentMarkRecord['course_mark'] = null;
                $studentMarkRecord['course_attendance'] = 1;
                $studentMarkRecord['exam_mark'] = null;
                $studentMarkRecord['exam_attendance'] = 1;
                $studentMarkRecord['employee_id'] = null;
            }
            $studentMarkRecord['student_id'] = $studentId;
            $studentMarkRecord['academic_term_id'] = $termId;
            $studentMarkRecord['subject_id'] = $subjectId;
            $studentMarkRecord['first_name'] = Student::whereId($studentId)->first()->first_name;
            $studentMarkRecord['last_name'] = Student::whereId($studentId)->first()->last_name;
            $studentMarkRecord['abbr'] = Subject::whereId($subjectId)->first()->abbr;
            $studentRecord['picture'] = Student::whereId($studentId)->first()->picture;
            array_push($data, $studentMarkRecord);
        }

        return $data;
    }

    public function termRecords($year, $term)
    {

        $records = ModelsStudentTermMark::where([
            'year' => $year,
            'term' => $term
        ])->get();

        return $records;
    }

    public function showReportTerms($student_id)
    {
        $termsAvailable = [];

        $termDetails = StudentTermDetail::whereStudentId($student_id)
        ->select('academic_term_id', 'form_class_id')
        ->orderBy('academic_term_id', 'desc')
        ->get();

        foreach($termDetails as $term){
            $termData = [];
            $academic_term = AcademicTerm::whereId($term->academic_term_id)->first();
            $form_level = FormClass::whereId($term->form_class_id)->first()->form_level;
            $termData['form_level'] = $form_level;
            $termData['term'] = $academic_term->term;
            $termData['term_end'] = $academic_term->term_end;
            $termData['form_class_id'] = $term->form_class_id;
            $academic_year = AcademicYear::whereId($academic_term->academic_year_id)->first();
            $academic_year_start = date_format(date_create($academic_year->start), 'Y');
            $academic_year_end = date_format(date_create($academic_year->end), 'Y');
            $termData['academic_year'] = $academic_year_start.'-'.$academic_year_end;
            $termData['academic_term_id'] = $term->academic_term_id;
            array_push($termsAvailable, $termData);
        }

        return $termsAvailable;
    }

    public function delete(Request $request)
    {
        return ModelsStudentTermMark::where([
            ['student_id', $request->student_id],
            ['academic_term_id', $request->academic_term_id],
            ['subject_id', $request->subject_id],
        ])->delete();
    }

    public function update(Request $request)
    {
        $student_term_mark = ModelsStudentTermMark::where([
            ['student_id', $request->student_id],
            ['academic_term_id', $request->academic_term_id],
            ['subject_id', $request->prev_subject_id],
        ])->update([
            'subject_id' => $request->new_subject_id,
            'employee_id' => $request->employee_id
        ]);

        $student_stubject_comment = StudentSubjectComment::where([
            ['student_id', $request->student_id],
            ['academic_term_id', $request->academic_term_id],
            ['subject_id', $request->prev_subject_id],
        ])->update(['subject_id' => $request->new_subject_id]);

        $data['student_term_mark'] = $student_term_mark;
        $data['student_subject_comment'] = $student_stubject_comment;

        return $data;
    }
}
