<?php

namespace App\Http\Controllers;

use App\Http\Resources\Table2 as ResourcesTable2;
use Illuminate\Http\Request;
use App\Models\FormClass;
use App\Models\Student;
use App\Models\StudentClassRegistration;
use App\Models\Table1;
use App\Models\Table2;
use App\Models\StudentSubject;
use App\Models\StudentSubjectAssignment;
use App\Models\StudentTermMark;

class Table2Controller extends Controller
{
    public function show($class, $termId, $subjectId)
    {        
        $termMarksRecords = [];
        $data = [];
        $total = 0;
        $registered = 0;
        $entered = 0;
        $formLevel = FormClass::whereId($class)->first()->form_level;
        //return $formLevel;       
        $studentsRegistered = StudentClassRegistration::where([
            ['form_class_id', $class],
            ['academic_term_id', $termId]
        ])->get();                     
        //return $studentsRegistered[0]->student;      
        if($formLevel < 4){
            //lower school
            $total = $studentsRegistered->count();            
            foreach($studentsRegistered as $student)
            {
                $studentMarkRecord = [];
                $registered++;                
                $studentId = $student->student_id;
                $studentMarkRecordExists = StudentTermMark::where([
                    ['student_id', $studentId],
                    ['academic_term_id', $termId],
                    ['subject_id', $subjectId],
                ])->exists();
                
                if($studentMarkRecordExists){
                    $entered++;
                    $studentExamMarkRecord = StudentTermMark::where([
                        ['student_id', $studentId],
                        ['academic_term_id', $termId],
                        ['subject_id', $subjectId],
                        ['test_id', 1]
                    ])->first();                                       
                    $studentCourseMarkRecord = StudentTermMark::where([
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
                $studentMarkRecord['first_name'] = $student->student->first_name;
                $studentMarkRecord['last_name'] = $student->student->last_name;
                $studentRecord['picture'] = Student::whereId($studentId)->first()->picture;                
                array_push($data, $studentMarkRecord);
            }            
        }
        else{
            //upper school
            foreach($studentsRegistered as $studentRegistered)
            {
                $studentId = $studentRegistered->id;
                if(StudentSubjectAssignment::where([
                    ['student_id', $studentId],
                    ['subject_id', $subjectId]
                ])->exists())
                {
                    $total++;
                }
            }
            foreach($studentsRegistered as $student)
            {
                $studentId = $student->student_id;
                if(StudentSubjectAssignment::where([
                    ['student_id', $studentId],
                    ['subject_id', $subjectId]
                ])->exists())
                {
                    $registered++;
                    $studentMarkRecordExists = 
                    $studentMarkRecordExists = StudentTermMark::where([
                        ['student_id', $studentId],
                        ['academic_term_id', $termId],
                        ['subject_id', $subjectId],
                    ])->exists();
                    if($studentMarkRecordExists){
                        $entered++;
                        $studentExamMarkRecord = StudentTermMark::where([
                            ['student_id', $studentId],
                            ['academic_term_id', $termId],
                            ['subject_id', $subjectId],
                            ['test_id', 1]
                        ])->first();                                       
                        $studentCourseMarkRecord = StudentTermMark::where([
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
                    $studentMarkRecord['first_name'] = $student->student->first_name;
                    $studentMarkRecord['last_name'] = $student->student->last_name;
                    $studentRecord['picture'] = Student::whereId($studentId)->first()->picture;                
                    array_push($data, $studentMarkRecord);
                }
            }
        }
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
        $record = Table2::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'year' => $request->year,
                'term' => $request->term,
                'subject_id' =>$request->subject_id,
            ],
            [
                'student_id' => $request->student_id,
                'year' => $request->year,
                'term' => $request->term,
                'subject_id' =>$request->subject_id,
                'exam_mark' => $request->exam_mark,
                'course_mark' => $request->course_mark,
                'conduct' => $request->conduct,
                'application' => $request->application,
                'preparedness' => $request->preparedness,
                'coded_comment' => $request->coded_comment,
                'coded_comment_1' => $request->coded_comment_1,
                'employee_id' => $request->employee_id
            ]
        );

        return $record;
    }

    public function studentRecords($studentId, $year, $term)
    {
        $records = Table2::join('subjects', 'subjects.id', 'table2.subject_id')
        ->select('table2.*','subjects.abbr')
        ->where([
            ['student_id', $studentId],  
            ['year', $year],
            ['term', $term]            
        ])->get();

        return ResourcesTable2::collection($records);
    }

    public function termRecords($year, $term)
    {
        $records = Table2::where([
            'year' => $year,
            'term' => $term
        ])->get();

        return $records;
    }
}
