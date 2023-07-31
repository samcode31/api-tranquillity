<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\FormClass;
use App\Models\FormDeanAssignment;
use App\Models\FormTeacherAssignment;
use App\Models\Student;
use App\Models\StudentClassRegistration;
use App\Models\StudentDeanComment;
use App\Models\StudentSubjectComment;
use App\Models\StudentTermDetail;
use App\Models\StudentTermMark as ModelsStudentTermMark;
use App\Models\Subject;
use App\Models\TermConfiguration;

class ReportCard extends Controller
{
    protected $pdf;

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }

    public function show($termId, $formClass, $student_id=null){
        $logo = public_path('/imgs/logo.png');
        $waterMark = public_path('/imgs/logo5%.png');
        $data = [];
        $school = strtoupper(config('app.school_name'));
        $primaryRed = config('app.primary_red');
        $primaryGreen = config('app.primary_green');
        $primaryBlue = config('app.primary_blue');
        $secondaryRed = config('app.secondary_red');
        $secondaryGreen = config('app.secondary_green');
        $secondaryBlue = config('app.secondary_blue');
        $address = config('app.school_address');
        $formDean = null;
        $term = null;
        $pass_mark = 50;
        $conduct = null;
        $class_total = 0;
        $sessions_absent = 0;
        $sessions_late = 0;
        $total_sessions = 0;
        $course_mark_only = false;
        $exam_mark_only = false;

        $academic_term = AcademicTerm::whereId($termId)
        ->first();

        $academic_year_id = $academic_term->academic_year_id;

        $term = $academic_term->term;

        $formLevel = FormClass::whereId($formClass)->first()->form_level;

        $term_configuration = TermConfiguration::where([
            ['academic_term_id', $termId]
        ])
        ->whereNull('form_level')
        ->first();

        if($term_configuration){
            $course_mark_only = ($term_configuration->exam_mark === 0) ? true : false;
        }

        $term_configuration = TermConfiguration::where([
            ['academic_term_id', $termId],
            ['form_level', $formLevel]
        ])->first();

        if($term_configuration){
            $course_mark_only = ($term_configuration->exam_mark === 0) ? true : false;
        }

        $formDeanAssignments = FormDeanAssignment::where([
            ['form_class_id', $formClass],
            ['academic_year_id', $academic_year_id]
        ])
        ->get();

        $numberOfDeans = sizeof($formDeanAssignments);

        foreach($formDeanAssignments as $key => $dean ){
            $employee = Employee::withTrashed()
            ->whereId($dean->employee_id)->first();
            $formDean .= $employee->first_name[0].". ".$employee->last_name;
            if($key+1 != $numberOfDeans) $formDean.="/";
        }

        if(!$student_id){
            $students = StudentClassRegistration::where([
                ['form_class_id', $formClass],
                ['academic_year_id', $academic_year_id]
            ])
            ->get();
        }
        else{
            $students = StudentClassRegistration::where([
                ['form_class_id', $formClass],
                ['academic_year_id', $academic_year_id],
                ['student_id', $student_id]
            ])
            ->get();
        }

        $class_students = StudentClassRegistration::where([
            ['form_class_id', $formClass],
            ['academic_year_id', $academic_year_id]
        ])
        ->get();

        $class_total = sizeof($class_students);

        if($course_mark_only)
        $class_summaries = $this->classTermSummary($class_students, $termId, 2, $pass_mark);
        else $class_summaries = $this->classTermSummary($class_students, $termId, 1, $pass_mark);

        foreach($students as $student){
            $formTeacher = null;
            $studentRecord = [];
            $studentTermMarks = [];
            $studentTermDetails = [];

            $studentId = $student->student_id;

            $distinctSubjects = ModelsStudentTermMark::join('subjects', 'student_term_marks.subject_id', 'subjects.id')
            ->where([
                ['student_id', $studentId],
                ['academic_term_id', $termId]
            ])
            ->select('subject_id', 'subjects.title')
            ->distinct()
            ->orderBy('subjects.title')
            ->get();

            //return $distinctSubjects;

            if($course_mark_only)
            $student_performance = $this->studentPerformance($studentId, $academic_term->id, 2, $pass_mark);
            else $student_performance = $this->studentPerformance($studentId, $academic_term->id, 1, $pass_mark);

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
                    $courseAttendance = $studentCourseMarkRecord->assesment_attendance_id;
                    if($courseAttendance == 2) $studentMarkRecord['course_mark'] = 'ABS';
                    if($courseAttendance == 3) $studentMarkRecord['course_mark'] =  'NW';
                    $studentMarkRecord['exam_mark'] = $studentExamMarkRecord->mark;
                    $examAttendance = $studentExamMarkRecord->assesment_attendance_id;
                    if($examAttendance == 2) $studentMarkRecord['exam_mark'] = 'ABS';
                    $employee_id = $studentCourseMarkRecord->employee_id;
                    $employee = Employee::withTrashed()
                    ->whereId($employee_id)->first();
                    $studentMarkRecord['employee_id'] = $studentCourseMarkRecord->employee_id;
                    $studentMarkRecord['teacher'] = $employee->first_name[0].'. '.$employee->last_name;
                    //return $this->highestMark($subjectId, $formClass, $termId, 2);
                    if($course_mark_only){
                        $studentMarkRecord['highest_mark'] = $this->highestMark($subjectId, $formClass, $termId, 2);
                    }
                    else{
                        $studentMarkRecord['highest_mark'] = $this->highestMark($subjectId, $formClass, $termId, 1);
                    }
                }
                else{
                    $studentMarkRecord['course_mark'] = null;
                    $studentMarkRecord['course_attendance'] = 1;
                    $studentMarkRecord['exam_mark'] = null;
                    $studentMarkRecord['exam_attendance'] = 1;
                    $studentMarkRecord['employee_id'] = null;
                }

                $firstName = Student::withTrashed()
                ->whereId($studentId)->first()->first_name;
                $lastName = Student::withTrashed()
                ->whereId($studentId)->first()->last_name;
                $studentMarkRecord['student_id'] = $studentId;
                $studentMarkRecord['academic_term_id'] = $termId;
                $studentMarkRecord['subject_id'] = $subjectId;
                $studentMarkRecord['first_name'] = $firstName;
                $studentMarkRecord['last_name'] =  $lastName;
                $studentMarkRecord['subject'] = Subject::whereId($subjectId)->first()->title;

                array_push($studentTermMarks, $studentMarkRecord);
            }

            $student_dean_comment = StudentDeanComment::where([
                ['student_id', $studentId],
                ['academic_term_id', $termId]
            ])->first();

            if($student_dean_comment){
                $studentTermDetails['dean_comment'] = $student_dean_comment->comment;
            }
            else $studentTermDetails['dean_comment'] = null;

            $studentTermDetailsRecord = StudentTermDetail::where([
                ['student_id', $studentId],
                ['academic_term_id', $termId]
            ]);

            if($studentTermDetailsRecord->exists()){
                $studentTermDetailsRecord = $studentTermDetailsRecord->first();
                $sessions_absent = $studentTermDetailsRecord->sessions_absent;
                $sessions_late = $studentTermDetailsRecord->sessions_late;
                $total_sessions = $studentTermDetailsRecord->total_sessions;
                $form_teacher_comment = $studentTermDetailsRecord->teacher_comment;
                $new_term_beginning = $studentTermDetailsRecord->new_term_beginning;
                $vice_principal_id = $studentTermDetailsRecord->vice_principal;
                $vice_principal = Employee::withTrashed()
                ->where('id', $vice_principal_id)->first();
                $vice_principal_name = $vice_principal ?  $vice_principal->first_name[0].'. '.$vice_principal->last_name : null;
                $principal_id = $studentTermDetailsRecord->principal;
                $principal = Employee::withTrashed()
                ->where('id', $principal_id)->first();
                $principal_name = $principal ? $principal->first_name[0].'. '.$principal->last_name : null;
                $studentTermDetails['sessions_absent'] = $sessions_absent;
                $studentTermDetails['sessions_late'] = $sessions_late;
                $studentTermDetails['total_sessions'] = $total_sessions;
                $studentTermDetails['form_teacher_comment'] = $form_teacher_comment;
                $studentTermDetails['new_term_beginning'] = $new_term_beginning;
                $studentTermDetails['vice_principal'] = $vice_principal_name;
                $studentTermDetails['principal'] = $principal_name;
            }
            else{
                $studentTermDetails['sessions_absent'] = null;
                $studentTermDetails['sessions_late'] = null;
                $studentTermDetails['total_sessions'] = null;
                $studentTermDetails['form_teacher_comment'] = null;
                $studentTermDetails['new_term_beginning'] = null;
                $studentTermDetails['vice_principal'] = null;
                $studentTermDetails['principal'] = null;
            }

            $studentTermDetails['average'] = $student_performance['average'];
            $studentTermDetails['subjects_passed'] = $student_performance['subjects_passed'];
            $studentTermDetails['class_average'] = $this->classAverage($class_summaries);
            $studentTermDetails['rank'] = $this->rank($student_performance['average'], $class_summaries);


            $formTeacherAssignments = FormTeacherAssignment::where([
                ['form_class_id', $formClass],
                ['academic_year_id', $academic_year_id]
            ]);

            if($formTeacherAssignments->exists()){
                $formTeacherAssignments = $formTeacherAssignments->get();
                $numberOfFormTeachers = sizeof($formTeacherAssignments);
                //return $numberOfFormTeachers;
                $count = 0;
                foreach($formTeacherAssignments as $formTeacherAssigned)
                {
                    $count++;
                    $employee = Employee::withTrashed()
                    ->whereId($formTeacherAssigned->employee_id)->first();
                    $employeeName = $employee->first_name[0].'. '.$employee->last_name;
                    $formTeacher .= $employeeName;
                    //return $count < $numberOfFormTeachers;
                    if($count < $numberOfFormTeachers) $formTeacher .= ' / ';
                }

                $studentTermDetails['form_teachers'] = $formTeacher;
            }
            else{
                $studentTermDetails['form_teachers'] = null;
            }

            $studentTermDetails['form_dean'] = $formDean;

            $firstName = Student::withTrashed()
            ->whereId($studentId)->first()->first_name;
            $lastName = Student::withTrashed()
            ->whereId($studentId)->first()->last_name;

            $studentRecord['student'] = $firstName.' '.$lastName;
            $studentRecord['student_id'] = $studentId;
            $studentRecord['marks'] = $studentTermMarks;
            $studentRecord['term_details'] = $studentTermDetails;

            array_push($data, $studentRecord);

        }

        //return $data;

        $this->pdf->SetMargins(10, 8);
        $this->pdf->SetAutoPageBreak(false);

        foreach($data as $record){
            $this->pdf->AddPage('P', 'Letter');
            $this->pdf->Image($logo, 8, 6, 27);
            $this->pdf->SetFont('Times', 'B', '20');
            $this->pdf->Image($waterMark, 20, 70, 175);
            $this->pdf->SetTextColor($primaryRed, $primaryGreen, $primaryBlue);
            $this->pdf->SetX(30);
            $this->pdf->MultiCell(0, 8, $school.' SCHOOL', 0, 'C' );
            $this->pdf->SetFont('Times', 'I', 9);
            $this->pdf->SetX(30);
            $this->pdf->MultiCell(0, 6, $address, 0, 'C' );
            $this->pdf->SetFont('Times', 'B', 12);
            $this->pdf->Ln(3);

            $this->pdf->SetFont('Times', 'UBI', 16);
            $this->pdf->MultiCell(0,6, utf8_decode($record['student']).' ', 0, 'C');
            $this->pdf->Ln(3);

            $this->pdf->SetDrawColor(219, 219, 219);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->pdf->SetFont('Times', '', 11);
            $this->pdf->Cell(12, 6, "\tClass: ", 'TL', 0, 'L');
            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(68, 6, $formClass, 'T', 0, 'L');
            $this->pdf->SetFont('Times', '', 11);
            $this->pdf->Cell(12, 6, "\tTerm: ", 'T', 0, 'L');
            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(38, 6, $term, 'T', 0, 'L');
            $this->pdf->SetFont('Times', '', 11);
            $this->pdf->Cell(50, 6, "\tStudent Average: ", 'T', 0, 'R');
            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(15.9, 6, $record['term_details']['average'].'%', 'TR', 0, 'L');
            $this->pdf->Ln();

            $this->pdf->SetFont('Times', '', 11);
            $this->pdf->Cell(30, 6, "\tSubjects Passed: ", 'L', 0, 'L');
            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(50, 6, $record['term_details']['subjects_passed'], 0, 0, 'L');
            $this->pdf->SetFont('Times', '', 11);
            $this->pdf->Cell(28, 6, "\tClass Average: ", 0, 0, 'L');
            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(22, 6, $record['term_details']['class_average'].'%', 0, 0, 'L');
            $this->pdf->SetFont('Times', '', 11);
            $this->pdf->Cell(50, 6, "\tStudents in Class: ", 0, 0, 'R');
            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(15.9, 6, $class_total, 'R', 0, 'L');
            $this->pdf->Ln();

            $this->pdf->SetFont('Times', '', 11);
            $this->pdf->Cell(31, 6, "\tSessions Absent: ", 'LB', 0, 'R');
            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(49, 6, $record['term_details']['sessions_absent'], 'B', 0, 'L');
            $this->pdf->SetFont('Times', '', 11);
            $this->pdf->Cell(26, 6, "\tSessions Late: ", 'B', 0, 'R');
            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(24, 6, $record['term_details']['sessions_late'], 'B', 0, 'L');
            $this->pdf->SetFont('Times', '', 11);
            $this->pdf->Cell(50, 6, "\tTotal Sessions: ", 'B', 0, 'R');
            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(15.9, 6, $record['term_details']['total_sessions'], 'RB', 0, 'L');
            $this->pdf->Ln(8);

            $this->pdf->SetFillColor($secondaryRed, $secondaryGreen, $secondaryBlue);
            //$this->pdf->SetDrawColor($primaryRed, $primaryGreen, $primaryBlue);
            //$this->pdf->SetDrawColor(219, 219, 219);

            $this->pdf->SetWidths(array(45, 30, 15, 15, 90.9));
            $this->pdf->SetAligns(array('L', 'C', 'C', 'C', 'C'));
            $this->pdf->SetBorders(array('TL',  1, 'TL', 'TLR', 'TR' ));
            $this->pdf->SetFont('Times', 'B', 9);
            $this->pdf->Row(array("", "MARKS", "Highest", "",  ""), false);

            $this->pdf->SetWidths(array(45, 15, 15, 15, 15, 90.9));
            $this->pdf->SetAligns(array('L', 'C', 'C', 'C', 'C', 'C'));
            $this->pdf->SetBorders(array('L', 'LR', 'LR', 'LR', 'LR', 'R' ));
            $this->pdf->SetFont('Times', 'B', 9);
            if($course_mark_only){
                $this->pdf->Row(array("Subject", "Term\n %", "Exam\n %", "Course Mark", "Conduct", "Subject Teacher Comment"), false);
            }
            else{
                $this->pdf->Row(array("Subject", "Term\n %", "Exam %", "Exam Mark", "Conduct", "Subject Teacher Comment"), false);
            }

            $this->pdf->SetAligns(array('L', 'C', 'C', 'C', 'C', 'L', 'L'));
            $this->pdf->SetBorders(array(1, 1, 1, 1, 1, 1, 1 ));
            $this->pdf->SetFont('Times', '', 9);
            $this->pdf->SetFillColor(255, 255, 255);

            $this->pdf->SetFillColor(51, 51, 255);
            $subjectRecords = $record['marks'];
            foreach($subjectRecords as $subjectRecord){
                if(sizeof($subjectRecord) != 0){
                    $this->pdf->Row(array(
                        $subjectRecord['subject'],
                        $subjectRecord['course_mark'],
                        $subjectRecord['exam_mark'],
                        $subjectRecord['highest_mark'],
                        $subjectRecord['conduct'],
                        $subjectRecord['comment']."\n\t",
                        $subjectRecord['teacher']
                    ), false);
                }
                else{
                    $this->pdf->Row(array(
                        ' ',
                        ' ',
                        ' ',
                        ' ',
                        ' ',
                        ' ',
                        ' ',
                        ' '
                    ), false);
                }
            }

            $this->pdf->Ln(2);
            //$this->pdf->SetFillColor($secondaryRed, $secondaryGreen, $secondaryBlue);
            $this->pdf->SetFont('Times','B','10');
            $this->pdf->Cell(30,8,"A : Excellent", 'TLB', 0, 'C');
            $this->pdf->Cell(30,8,"B : Very Good", 'TB', 0, 'C');
            $this->pdf->Cell(30,8,"C : Good", 'TB', 0, 'C');
            $this->pdf->Cell(30,8,"D : Unsatisfactory", 'TB', 0, 'C');
            $this->pdf->Cell(30,8,"E : Poor", 'TB', 0, 'C');
            $this->pdf->Cell(45.9,8,"NW : No Work Submitted", 'TBR', 0, 'C');

            $this->pdf->Ln(10);
            $border = 0;
            $this->pdf->SetFont('Times','B','10');
            $this->pdf->Cell(98,5,"Form Teacher's Comments:", $border, "J");
            $this->pdf->Cell(25,5,"Form Teachers:", $border);
            $this->pdf->SetFont('Times','I','10');
            $this->pdf->Cell(72.9,5,$record['term_details']['form_teachers'], $border,0,"L");
            $this->pdf->Ln();

            $this->pdf->SetFont('Times','I','10');
            $this->pdf->MultiCell(0, 5, $record['term_details']['form_teacher_comment']."\n\t", 1, "J");
            $this->pdf->SetFont('Times','B','10');

            $border = 0;
            $this->pdf->SetFont('Times','B','10');
            $this->pdf->Cell(98,5,"Dean's Comments:", $border, "J");
            $this->pdf->SetFont('Times','B','10');
            $this->pdf->Cell(10,5,"Dean:",$border,0,"L");
            $this->pdf->SetFont('Times','I','10');
            $this->pdf->Cell(87.9,5,$record['term_details']['form_dean'], $border,0,"L");
            $this->pdf->Ln();

            $this->pdf->SetFont('Times','I','10');
            $this->pdf->MultiCell(0, 5, $record['term_details']['dean_comment'], 1, "J");

            $border = 0;
            $this->pdf->SetFont('Times','B','10');
            $this->pdf->Cell(25,7,"Vice Principal:", $border,0,"L");
            $this->pdf->SetFont('Times','I','10');
            $this->pdf->Cell(73,7,$record['term_details']['vice_principal'], $border,0,"L");
            $this->pdf->SetFont('Times','B','10');
            $this->pdf->Cell(18,7,"Principal:", $border,0,"L");
            $this->pdf->SetFont('Times','I','10');
            $this->pdf->Cell(79.9,7,$record['term_details']['principal'], $border,0,"L");
            $this->pdf->Ln();

            // $this->pdf->Cell(65, 10, "",'B');
            // $this->pdf->Cell(65.9, 10, "", 0);
            // $this->pdf->Cell(65, 10, "", 'B');
            // $this->pdf->Ln();

            // $this->pdf->SetFont('Times','I','8');
            // $this->pdf->Cell(65, 8, "Dean's Signature",0, 0,"C");
            // $this->pdf->Cell(65.9, 8, "", 0, 0, "C");
            // $this->pdf->Cell(65, 8, "Form Teacher's Signature", 0, 0, "C");

            $this->pdf->SetY(-20);
            $this->pdf->SetFont('Times','B','10');
            $this->pdf->Cell(0,5,"School Reopens on : ".date_format(date_create($record['term_details']['new_term_beginning']), 'jS M Y'), 0, 0, "C");

            $this->pdf->Ln(10);

            $this->pdf->SetY(-15);
            $this->pdf->SetFont('Times','I',8);
            $this->pdf->Cell(0,7,"This is an official document which is not valid without the ".$school." school stamp.",0,0,'C');
            }
            $this->pdf->Output('I', 'ReportCard.pdf');
        exit;
    }

    private function studentPerformance($student_id, $academic_term_id, $test_id=1, $pass_mark){
        $average = 0;
        $total_subjects = 0;
        $total_marks = 0;
        $subjects_passed = 0;
        $data = [];
        $student_term_marks = ModelsStudentTermMark::where([
            ['student_id', $student_id],
            ['academic_term_id', $academic_term_id],
            ['test_id', $test_id]
        ])->get();

        if(sizeof($student_term_marks) != 0){
            foreach($student_term_marks as $student_term_mark){
                $mark = $student_term_mark->mark;
                if(is_numeric($mark)){
                    $total_marks += $student_term_mark->mark;
                    if($mark >= $pass_mark) $subjects_passed++;
                }

                $total_subjects++;
            }
            $average = round(($total_marks/$total_subjects),1);
        }
        $average = ($average == 0) ? null : $average;
        $data['average'] = $average;
        $data['subjects_passed'] = $subjects_passed;
        return $data;
        //return $student_id;
    }

    private function classTermSummary($students, $academic_term_id, $test_id, $pass_mark){
        $class_summaries = [];
        foreach($students as $student){
            $student_average = [];
            $average = 0;
            $total_marks = 0;
            $total_subjects = 0;
            $subjects_passed = 0;
            $student_term_marks = ModelsStudentTermMark::where([
                ['student_id', $student->student_id],
                ['academic_term_id', $academic_term_id],
                ['test_id', $test_id]
            ])->get();
            if(sizeof($student_term_marks) != 0){
                foreach($student_term_marks as $student_term_mark){
                    $mark = $student_term_mark->mark;
                    if(is_numeric($mark)){
                        $total_marks += $student_term_mark->mark;
                        if($mark >= $pass_mark) $subjects_passed++;
                    }
                    $total_subjects++;
                }
                $average = round(($total_marks/$total_subjects),1);
            }
            $student_average['student_id'] = $student->student_id;
            $student_average['average'] = $average;
            $student_average['subjects_passed'] = $subjects_passed;
            array_push($class_summaries, $student_average);
        }

        return $this->sort($class_summaries);

    }

    private function sort($array){
        $l=0; $m=0; $keyId=0; $keyAvg=0; $keyPass=0; $keyArray=[]; $n = sizeof($array);
        for($l = 1; $l < $n; $l++){
            $keyAvg = $array[$l]['average'];
            $keyId = $array[$l]['student_id'];
            $keyPass = $array[$l]['subjects_passed'];
            $m=$l-1;
            while($m >=0 && $keyAvg > $array[$m]['average']){
                $array[$m+1] = $array[$m];
                --$m;
            }
            $keyArray['student_id']=$keyId;
            $keyArray['average']=$keyAvg;
            $keyArray['subjects_passed']=$keyPass;
            $array[$m+1]=$keyArray;
        }
        return $array;
    }

    private function classAverage($array){
        $recordCount = 0;
        $totalAverage = 0;
        foreach($array as $record){
            $recordCount++;
            $totalAverage+=$record['average'];
        }
        if($recordCount != 0) return round(($totalAverage/$recordCount),1);
        return 0;
    }

    private function rank($average, $array){
        foreach($array as $key => $value){
            if($average == $value['average']){
                return $key+1;
            }
        }
        return 0;
    }

    private function highestMark($subject_id, $form_class_id, $academic_term_id, $test_id)
    {
        return ModelsStudentTermMark::join('student_class_registrations', 'student_term_marks.student_id', 'student_class_registrations.student_id')
        ->select('student_term_marks.mark')
        ->where([
            ['subject_id', $subject_id],
            ['form_class_id', $form_class_id],
            ['student_term_marks.academic_term_id', $academic_term_id],
            ['test_id', $test_id]
        ])
        ->max('mark');
    }

    public function terms(){
        $data = [];

        $distinct_academic_terms = ModelsStudentTermMark::select('academic_term_id')
        ->distinct()
        ->orderBy('academic_term_id')
        ->get();

        foreach($distinct_academic_terms as $distinct_academic_term){
            $record = [];
            $academic_term_id = $distinct_academic_term->academic_term_id;
            $academic_term = AcademicTerm::where('id', $academic_term_id)
            ->first();
            $term = $academic_term->term;
            $academic_year_id = $academic_term->academic_year_id;
            $academic_year = AcademicYear::where('id', $academic_year_id)
            ->first();
            $year_start = date_format(date_create($academic_year->start), "Y");
            $year_end = date_format(date_create($academic_year->end), "Y");
            $record['academic_term_id'] = $academic_term_id;
            $record['academic_year'] = $year_start.'-'.$year_end;
            $record['term'] = $term;
            array_push($data, $record);
        }

        return $data;
    }

}
