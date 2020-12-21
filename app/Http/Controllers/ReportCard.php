<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Employee;
use App\Models\FormClass;
use App\Models\FormDeanAssignment;
use App\Models\FormTeacherAssignment;
use App\Models\Student;
use App\Models\StudentClassRegistration;
use App\Models\StudentSubjectComment;
use App\Models\StudentTermDetail;
use App\Models\StudentTermMark as ModelsStudentTermMark;
use App\Models\Subject;
use Codedge\Fpdf\Fpdf\Fpdf;

class ReportCard extends Controller
{
    private $fpdf;
    private $widths;
    private $aligns;

    public function __construct()
    {
        
    }
    
    public function show($termId, $formClass){
        $logo = public_path('/imgs/logo.png');
        $waterMark = public_path('/imgs/logo5%.png');        
        $data = [];
        //$waterMarkLogo = public_path('/imgs/logo-report.png');
        $school = strtoupper(config('app.school_name'));
        $primaryRed = config('app.primary_red');        
        $primaryGreen = config('app.primary_green');        
        $primaryBlue = config('app.primary_blue');
        $secondaryRed = config('app.secondary_red');        
        $secondaryGreen = config('app.secondary_green');        
        $secondaryBlue = config('app.secondary_blue');         
        $address = config('app.school_address');
        $formDean = null;               
        
        $academic_year_id = AcademicTerm::whereId($termId)
        ->first()
        ->academic_year_id;
       
        $form_level = FormClass::whereId($formClass)->first()->form_level;

        $formDeanAssignments = FormDeanAssignment::where([
            ['form_class_id', $formClass],
            ['academic_year_id', $academic_year_id]
        ])
        ->get();

        $numberOfDeans = sizeof($formDeanAssignments);

        foreach($formDeanAssignments as $key => $dean ){
            $employee = Employee::whereId($dean->employee_id)->first();
            $formDean .= $employee->first_name[0].". ".$employee->last_name;
            if($key+1 != $numberOfDeans) $formDean.="/";
        }

        $students = StudentClassRegistration::whereFormClassId($formClass)->get();
        //return $students;
        foreach($students as $student){
            $formTeacher = null; 
            $studentRecord = [];
            $studentTermMarks = [];
            $studentTermDetails = [];           
            
            $studentId = $student->student_id;
            
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
                    $courseAttendance = $studentCourseMarkRecord->assesment_attendance_id;
                    if($courseAttendance == 2) $studentMarkRecord['course_mark'] = 'ABS';
                    if($courseAttendance == 3) $studentMarkRecord['course_mark'] =  'NW';
                    $studentMarkRecord['exam_mark'] = $studentExamMarkRecord->mark;
                    $examAttendance = $studentExamMarkRecord->assesment_attendance_id;
                    if($examAttendance == 2) $studentMarkRecord['exam_mark'] = 'ABS';
                    $employee_id = $studentCourseMarkRecord->employee_id;
                    $employee = Employee::whereId($employee_id)->first();
                    $studentMarkRecord['employee_id'] = $studentCourseMarkRecord->employee_id;
                    $studentMarkRecord['teacher'] = $employee->first_name[0].'. '.$employee->last_name;                                       
                }
                else{                   
                    $studentMarkRecord['course_mark'] = null;
                    $studentMarkRecord['course_attendance'] = 1;
                    $studentMarkRecord['exam_mark'] = null;
                    $studentMarkRecord['exam_attendance'] = 1;
                    $studentMarkRecord['employee_id'] = null;                    
                }
               
                $firstName = Student::whereId($studentId)->first()->first_name;
                $lastName = Student::whereId($studentId)->first()->last_name;
                $studentMarkRecord['student_id'] = $studentId;
                $studentMarkRecord['academic_term_id'] = $termId;
                $studentMarkRecord['subject_id'] = $subjectId;           
                $studentMarkRecord['first_name'] = $firstName;
                $studentMarkRecord['last_name'] =  $lastName;
                $studentMarkRecord['subject'] = Subject::whereId($subjectId)->first()->title;                
                           
                array_push($studentTermMarks, $studentMarkRecord);            
            } 
            
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

                $studentTermDetails['sessions_absent'] = $sessions_absent;            
                $studentTermDetails['sessions_late'] = $sessions_late;            
                $studentTermDetails['total_sessions'] = $total_sessions;            
                $studentTermDetails['form_teacher_comment'] = $form_teacher_comment;            
                $studentTermDetails['new_term_beginning'] = $new_term_beginning; 
            }
            else{
                $studentTermDetails['sessions_absent'] = null;            
                $studentTermDetails['sessions_late'] = null;            
                $studentTermDetails['total_sessions'] = null;            
                $studentTermDetails['form_teacher_comment'] = null;            
                $studentTermDetails['new_term_beginning'] = null; 
            }

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
                    $employee = Employee::whereId($formTeacherAssigned->employee_id)->first();
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
            
            $firstName = Student::whereId($studentId)->first()->first_name;
            $lastName = Student::whereId($studentId)->first()->last_name;

            $studentRecord['student'] = $firstName.' '.$lastName;
            $studentRecord['marks'] = $studentTermMarks;
            $studentRecord['term_details'] = $studentTermDetails;

            array_push($data, $studentRecord);
           
        }

        //return $data;        

        $this->fpdf = new Fpdf('P', 'mm', 'Legal');
        $this->fpdf->SetMargins(10, 8);
        $this->fpdf->SetAutoPageBreak(false);
        
        foreach($data as $record){
            $this->fpdf->AddPage();
            $this->fpdf->Image($logo, 10, 9, 12);
            $this->fpdf->SetFont('Times', 'B', '18');
            $this->fpdf->Image($waterMark, 50, 70, 120);            
            $this->fpdf->SetTextColor($primaryRed, $primaryGreen, $primaryBlue);
            $this->fpdf->MultiCell(0, 8, $school, 0, 'C' );
            $this->fpdf->SetFont('Times', 'I', 10);
            $this->fpdf->MultiCell(0, 6, $address, 0, 'C' );                     
            $this->fpdf->SetFont('Times', 'B', 12);
            $this->fpdf->MultiCell(0, 6, 'END OF TERM REPORT', 0, 'C' );
            $this->fpdf->Ln(4);
            $this->fpdf->SetFont('Times', 'UBI', 16);
            $this->fpdf->MultiCell(0,6, $record['student'], 0, 'C');
            $this->fpdf->Ln(4);

            $border = 1;
            $this->fpdf->SetDrawColor(219, 219, 219);
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(21, 6, "", 0, 'L');
            $this->fpdf->Cell(34, 6, "\tClass: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, $formClass, $border, 0, 'C');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(35, 6, "\tTerm: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, '1', $border, 0, 'C');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(40, 6, "\tYear: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, '2020', $border, 0, 'C');
            $this->fpdf->Cell(20.9, 6, "", 0, 'L');
            $this->fpdf->Ln();
             
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(21, 6, "", 0, 'L');
            $this->fpdf->Cell(34, 6, "\tTotal Sessions: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, $record['term_details']['total_sessions'], $border, 0, 'C');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(35, 6, "\tSessions Absent: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, $record['term_details']['sessions_absent'], $border, 0, 'C');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(40, 6, "\tSessions Late: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, $record['term_details']['sessions_late'], $border, 0, 'C');
            $this->fpdf->Cell(20.9, 6, "", 0, 'L');
            $this->fpdf->Ln();

            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(21, 6, "", 0, 'L');
            $this->fpdf->Cell(34, 6, "\tTotal Cycles: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, '', $border, 0, 'C');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(35, 6, "\tPackages Collected: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, '', $border, 0, 'C');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(40, 6, "\tPackages Not Collected: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, '', $border, 0, 'C');
            $this->fpdf->Cell(20.9, 6, "", 0, 'L');
            $this->fpdf->Ln(8);

            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(35, 6, "\tSubjects Passed: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(14, 6, '', $border, 0, 'L');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(35, 6, "\tOverall Percentage: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(14, 6, '', $border, 0, 'L');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(35, 6, "\tOverall Grade: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(14, 6, '', $border, 0, 'L');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(35, 6, "\tRank in Class: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(14, 6, '', $border, 0, 'L');
            $this->fpdf->Ln(8);

            $this->fpdf->SetFillColor($secondaryRed, $secondaryGreen, $secondaryBlue);
            $this->fpdf->SetDrawColor(219, 219, 219); 

            $this->SetWidths(array(45, 15, 15, 15, 15, 90.9));        
            $this->SetAligns(array('L', 'C', 'C', 'C', 'C', 'C'));
            $this->fpdf->SetFont('Times', 'B', 10);           
            
            
            $this->Row(array("Subject", "Course Mark %", "Exam Mark %", "Highest Exam Mark", "Conduct", "Subject Teacher Comment"), true);
            //$this->fpdf->SetTextColor(32, 32, 32);
            $this->SetAligns(array('L', 'C', 'C', 'C', 'C', 'L'));
            $this->fpdf->SetFont('Times', '', 11);
            $this->fpdf->SetFillColor(255, 255, 255);
            
            $this->fpdf->SetFillColor(51, 51, 255);
            $subjectRecords = $record['marks'];
            foreach($subjectRecords as $subjectRecord){
                if(sizeof($subjectRecord) != 0){
                    $this->Row(array(
                        $subjectRecord['subject'],
                        $subjectRecord['course_mark'],
                        $subjectRecord['exam_mark'],
                        '',
                        '',
                        $subjectRecord['comment']."\n\t",
                        $subjectRecord['teacher']
                    ), false);
                }
                else{
                    $this->Row(array(
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

            $this->fpdf->Ln(4);
            $this->fpdf->SetFillColor($secondaryRed, $secondaryGreen, $secondaryBlue);   
            $this->fpdf->SetFont('Times','B','10');
            $this->fpdf->Cell(30,8,"A : Excellent", 0, 0, 'C', true);
            $this->fpdf->Cell(30,8,"B : Very Good", 0, 0, 'C', true);
            $this->fpdf->Cell(30,8,"C : Good", 0, 0, 'C', true);
            $this->fpdf->Cell(30,8,"D : Poor", 0, 0, 'C', true);
            $this->fpdf->Cell(30,8,"E : Unsatisfactory", 0, 0, 'C', true);            
            $this->fpdf->Cell(45.9,8,"NW : No Work Submitted", 0, 0, 'C', true);

            $this->fpdf->Ln(10);        
            $this->fpdf->SetFont('Times','B','10');
            $this->fpdf->Cell(0,5,"Form Teacher's Comments:", 0, "J");
            $this->fpdf->Ln();

            $this->fpdf->SetFont('Times','I','10');
            $this->fpdf->MultiCell(0, 5, $record['term_details']['form_teacher_comment'], 1, "J");        
            $this->fpdf->SetFont('Times','B','10');

            $this->fpdf->Cell(25,7,"Form Teachers:", 0);
            $this->fpdf->SetFont('Times','I','10');
            $this->fpdf->Cell(130,7,$record['term_details']['form_teachers'], 0,0,"L");

            $this->fpdf->SetFont('Times','B','10');
            $this->fpdf->Cell(12,7,"Date:", 0);
            $this->fpdf->SetFont('Times','I','10');
            $this->fpdf->Cell(54,7,'', 0, 0,"L");            
            $this->fpdf->Ln(10);

            $this->fpdf->SetFont('Times','B','10');
            $this->fpdf->Cell(0,5,"Dean's Comments:", 0, "J");
            $this->fpdf->Ln();

            $this->fpdf->SetFont('Times','I','10');
            $this->fpdf->MultiCell(0, 5, '', 1, "J");        
            $this->fpdf->SetFont('Times','B','10');

            $this->fpdf->Cell(25,7,"Dean:", 0);
            $this->fpdf->SetFont('Times','I','10');
            $this->fpdf->Cell(130,7,$record['term_details']['form_dean'], 0,0,"L");

            $this->fpdf->SetFont('Times','B','10');
            $this->fpdf->Cell(12,7,"Date:", 0);
            $this->fpdf->SetFont('Times','I','10');
            $this->fpdf->Cell(54,7,'', 0, 0,"L");            
            $this->fpdf->Ln();

            $this->fpdf->Cell(65, 10, "",'B');
            $this->fpdf->Cell(65.9, 10, "", 0);
            $this->fpdf->Cell(65, 10, "", 'B');
            $this->fpdf->Ln();

            $this->fpdf->SetFont('Times','I','8');
            $this->fpdf->Cell(65, 8, "Dean's Signature",0, 0,"C");
            $this->fpdf->Cell(65.9, 8, "", 0, 0, "C");
            $this->fpdf->Cell(65, 8, "Form Teacher's Signature", 0, 0, "C");
                            
            $this->fpdf->SetY(-101);
            $this->fpdf->SetFont('Times','B','10');      
            $this->fpdf->Cell(0,5,"School Reopens on : ".date_format(date_create($record['term_details']['new_term_beginning']), 'jS M Y'), 0, 0, "C");      
            
            $this->fpdf->Ln(10);        
            
            $this->fpdf->SetY(-91);
            $this->fpdf->SetFont('Times','I',8);
            $this->fpdf->Cell(0,7,"This is an official document which is not valid without the ".$school." school school stamp.",0,0,'C'); 
            }
            $this->fpdf->Output('I', 'ReportCard.pdf');
        exit;  
    }
    
    public function showOne($studentId, $termId)
    {
        $logo = public_path('/imgs/logo.png');
        $waterMark = public_path('/imgs/logo5%.png');        
        $data = [];
        //$waterMarkLogo = public_path('/imgs/logo-report.png');
        $school = strtoupper(config('app.school_name')).' SCHOOL';
        $primaryRed = config('app.primary_red');        
        $primaryGreen = config('app.primary_green');        
        $primaryBlue = config('app.primary_blue');
        $secondaryRed = config('app.secondary_red');        
        $secondaryGreen = config('app.secondary_green');        
        $secondaryBlue = config('app.secondary_blue');        
        $address = config('app.school_address');        
        $formDean = null;
        $formTeacher = null;           
        
        $academic_year_id = AcademicTerm::whereId($termId)
        ->first()
        ->academic_year_id;
        
        $studentRecord = [];
        $studentTermMarks = [];
        $studentTermDetails = [];
        $formClass = StudentClassRegistration::where([
            ['student_id', $studentId],
            ['academic_year_id', $academic_year_id]
        ])
        ->first()
        ->form_class_id;

        $form_level = FormClass::whereId($formClass)->first()->form_level;

        $formDeanAssignments = FormDeanAssignment::where([
            ['form_class_id', $formClass],
            ['academic_year_id', $academic_year_id]
        ])
        ->get();

        $numberOfDeans = sizeof($formDeanAssignments);

        foreach($formDeanAssignments as $key => $dean ){
            $employee = Employee::whereId($dean->employee_id)->first();
            $formDean .= $employee->first_name[0].". ".$employee->last_name;
            if($key+1 != $numberOfDeans) $formDean.="/";
        }
        
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
                $courseAttendance = $studentCourseMarkRecord->assesment_attendance_id;
                if($courseAttendance == 2) $studentMarkRecord['course_mark'] = 'ABS';
                if($courseAttendance == 3) $studentMarkRecord['course_mark'] =  'NW';
                $studentMarkRecord['exam_mark'] = $studentExamMarkRecord->mark;
                $examAttendance = $studentExamMarkRecord->assesment_attendance_id;
                if($examAttendance == 2) $studentMarkRecord['exam_mark'] = 'ABS';
                $employee_id = $studentCourseMarkRecord->employee_id;
                $employee = Employee::whereId($employee_id)->first();
                $studentMarkRecord['employee_id'] = $studentCourseMarkRecord->employee_id;
                $studentMarkRecord['teacher'] = $employee->first_name[0].'. '.$employee->last_name;                                       
            }
            else{                   
                $studentMarkRecord['course_mark'] = null;
                $studentMarkRecord['course_attendance'] = 1;
                $studentMarkRecord['exam_mark'] = null;
                $studentMarkRecord['exam_attendance'] = 1;
                $studentMarkRecord['employee_id'] = null;                    
            }
            
            $firstName = Student::whereId($studentId)->first()->first_name;
            $lastName = Student::whereId($studentId)->first()->last_name;
            $studentMarkRecord['student_id'] = $studentId;
            $studentMarkRecord['academic_term_id'] = $termId;
            $studentMarkRecord['subject_id'] = $subjectId;           
            $studentMarkRecord['first_name'] = $firstName;
            $studentMarkRecord['last_name'] =  $lastName;
            $studentMarkRecord['subject'] = Subject::whereId($subjectId)->first()->title;                
                        
            array_push($studentTermMarks, $studentMarkRecord);            
        } 
        
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

            $studentTermDetails['sessions_absent'] = $sessions_absent;            
            $studentTermDetails['sessions_late'] = $sessions_late;            
            $studentTermDetails['total_sessions'] = $total_sessions;            
            $studentTermDetails['form_teacher_comment'] = $form_teacher_comment;            
            $studentTermDetails['new_term_beginning'] = $new_term_beginning; 
        }
        else{
            $studentTermDetails['sessions_absent'] = null;            
            $studentTermDetails['sessions_late'] = null;            
            $studentTermDetails['total_sessions'] = null;            
            $studentTermDetails['form_teacher_comment'] = null;            
            $studentTermDetails['new_term_beginning'] = null; 
        }

        $formTeacherAssignments = FormTeacherAssignment::where([
            ['form_class_id', $formClass],
            ['academic_year_id', $academic_year_id]
        ]);

        //$formTeacherAssignments = $formTeacherAssignments->get();
        //return sizeof($formTeacherAssignments);

        if($formTeacherAssignments->exists()){
            $formTeacherAssignments = $formTeacherAssignments->get();
            $numberOfFormTeachers = sizeof($formTeacherAssignments);
            //return $numberOfFormTeachers;
            $count = 0;
            foreach($formTeacherAssignments as $formTeacherAssigned)
            {
                $count++;
                $employee = Employee::whereId($formTeacherAssigned->employee_id)->first();
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
        
        $firstName = Student::whereId($studentId)->first()->first_name;
        $lastName = Student::whereId($studentId)->first()->last_name;

        $studentRecord['student'] = $firstName.' '.$lastName;
        $studentRecord['marks'] = $studentTermMarks;
        $studentRecord['term_details'] = $studentTermDetails;

        array_push($data, $studentRecord);           
             

        $this->fpdf = new Fpdf('P', 'mm', 'Legal');
        $this->fpdf->SetMargins(10, 8);
        $this->fpdf->SetAutoPageBreak(false);
        
        foreach($data as $record){
            $this->fpdf->AddPage();
            $this->fpdf->Image($logo, 10, 9, 12);
            $this->fpdf->SetFont('Times', 'B', '18');
            $this->fpdf->Image($waterMark, 50, 70, 120);            
            $this->fpdf->SetTextColor($primaryRed, $primaryGreen, $primaryBlue);
            $this->fpdf->MultiCell(0, 8, $school, 0, 'C' );
            $this->fpdf->SetFont('Times', 'I', 10);
            $this->fpdf->MultiCell(0, 6, $address, 0, 'C' );                     
            $this->fpdf->SetFont('Times', 'B', 12);
            $this->fpdf->MultiCell(0, 6, 'END OF TERM REPORT', 0, 'C' );
            $this->fpdf->Ln(4);
            $this->fpdf->SetFont('Times', 'UBI', 16);
            $this->fpdf->MultiCell(0,6, $record['student'], 0, 'C');
            $this->fpdf->Ln(4);

            $border = 1;
            $this->fpdf->SetDrawColor(219, 219, 219);
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(21, 6, "", 0, 'L');
            $this->fpdf->Cell(34, 6, "\tClass: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, $formClass, $border, 0, 'C');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(35, 6, "\tTerm: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, '1', $border, 0, 'C');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(40, 6, "\tYear: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, '2020', $border, 0, 'C');
            $this->fpdf->Cell(20.9, 6, "", 0, 'L');
            $this->fpdf->Ln();
             
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(21, 6, "", 0, 'L');
            $this->fpdf->Cell(34, 6, "\tTotal Sessions: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, $record['term_details']['total_sessions'], $border, 0, 'C');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(35, 6, "\tSessions Absent: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, $record['term_details']['sessions_absent'], $border, 0, 'C');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(40, 6, "\tSessions Late: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, $record['term_details']['sessions_late'], $border, 0, 'C');
            $this->fpdf->Cell(20.9, 6, "", 0, 'L');
            $this->fpdf->Ln();

            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(21, 6, "", 0, 'L');
            $this->fpdf->Cell(34, 6, "\tTotal Cycles: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, '', $border, 0, 'C');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(35, 6, "\tPackages Collected: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, '', $border, 0, 'C');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(40, 6, "\tPackages Not Collected: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(15, 6, '', $border, 0, 'C');
            $this->fpdf->Cell(20.9, 6, "", 0, 'L');
            $this->fpdf->Ln(8);

            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(35, 6, "\tSubjects Passed: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(14, 6, '', $border, 0, 'L');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(35, 6, "\tOverall Percentage: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(14, 6, '', $border, 0, 'L');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(35, 6, "\tOverall Grade: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(14, 6, '', $border, 0, 'L');
            $this->fpdf->SetFont('Times', 'B', 10);
            $this->fpdf->Cell(35, 6, "\tRank in Class: ", $border, 0, 'L');
            $this->fpdf->SetFont('Times', '', 10);
            $this->fpdf->Cell(14, 6, '', $border, 0, 'L');
            $this->fpdf->Ln(8);

            $this->fpdf->SetFillColor($secondaryRed, $secondaryGreen, $secondaryBlue);
            $this->fpdf->SetDrawColor(219, 219, 219); 

            $this->SetWidths(array(45, 15, 15, 15, 15, 90.9));        
            $this->SetAligns(array('L', 'C', 'C', 'C', 'C', 'C'));
            $this->fpdf->SetFont('Times', 'B', 10);           
            
            
            $this->Row(array("Subject", "Course Mark %", "Exam Mark %", "Highest Exam Mark", "Conduct", "Subject Teacher Comment"), true);
            //$this->fpdf->SetTextColor(32, 32, 32);
            $this->SetAligns(array('L', 'C', 'C', 'C', 'C', 'L'));
            $this->fpdf->SetFont('Times', '', 11);
            $this->fpdf->SetFillColor(255, 255, 255);
            
            $this->fpdf->SetFillColor(51, 51, 255);
            $subjectRecords = $record['marks'];
            foreach($subjectRecords as $subjectRecord){
                if(sizeof($subjectRecord) != 0){
                    $this->Row(array(
                        $subjectRecord['subject'],
                        $subjectRecord['course_mark'],
                        $subjectRecord['exam_mark'],
                        '',
                        '',
                        $subjectRecord['comment']."\n\t",
                        $subjectRecord['teacher']
                    ), false);
                }
                else{
                    $this->Row(array(
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

            $this->fpdf->Ln(4);
            $this->fpdf->SetFillColor($secondaryRed, $secondaryGreen, $secondaryBlue);   
            $this->fpdf->SetFont('Times','B','10');
            $this->fpdf->Cell(30,8,"A : Excellent", 0, 0, 'C', true);
            $this->fpdf->Cell(30,8,"B : Very Good", 0, 0, 'C', true);
            $this->fpdf->Cell(30,8,"C : Good", 0, 0, 'C', true);
            $this->fpdf->Cell(30,8,"D : Poor", 0, 0, 'C', true);
            $this->fpdf->Cell(30,8,"E : Unsatisfactory", 0, 0, 'C', true);            
            $this->fpdf->Cell(45.9,8,"NW : No Work Submitted", 0, 0, 'C', true);

            $this->fpdf->Ln(10);        
            $this->fpdf->SetFont('Times','B','10');
            $this->fpdf->Cell(0,5,"Form Teacher's Comments:", 0, "J");
            $this->fpdf->Ln();

            $this->fpdf->SetFont('Times','I','10');
            $this->fpdf->MultiCell(0, 5, $record['term_details']['form_teacher_comment'], 1, "J");        
            $this->fpdf->SetFont('Times','B','10');

            $this->fpdf->Cell(25,7,"Form Teachers:", 0);
            $this->fpdf->SetFont('Times','I','10');
            $this->fpdf->Cell(130,7,$record['term_details']['form_teachers'], 0,0,"L");

            $this->fpdf->SetFont('Times','B','10');
            $this->fpdf->Cell(12,7,"Date:", 0);
            $this->fpdf->SetFont('Times','I','10');
            $this->fpdf->Cell(54,7,'', 0, 0,"L");            
            $this->fpdf->Ln(10);

            $this->fpdf->SetFont('Times','B','10');
            $this->fpdf->Cell(0,5,"Dean's Comments:", 0, "J");
            $this->fpdf->Ln();

            $this->fpdf->SetFont('Times','I','10');
            $this->fpdf->MultiCell(0, 5, '', 1, "J");        
            $this->fpdf->SetFont('Times','B','10');

            $this->fpdf->Cell(25,7,"Dean:", 0);
            $this->fpdf->SetFont('Times','I','10');
            $this->fpdf->Cell(130,7,$record['term_details']['form_dean'], 0,0,"L");

            $this->fpdf->SetFont('Times','B','10');
            $this->fpdf->Cell(12,7,"Date:", 0);
            $this->fpdf->SetFont('Times','I','10');
            $this->fpdf->Cell(54,7,'', 0, 0,"L");            
            $this->fpdf->Ln();

            $this->fpdf->Cell(65, 10, "",'B');
            $this->fpdf->Cell(65.9, 10, "", 0);
            $this->fpdf->Cell(65, 10, "", 'B');
            $this->fpdf->Ln();

            $this->fpdf->SetFont('Times','I','8');
            $this->fpdf->Cell(65, 8, "Dean's Signature",0, 0,"C");
            $this->fpdf->Cell(65.9, 8, "", 0, 0, "C");
            $this->fpdf->Cell(65, 8, "Form Teacher's Signature", 0, 0, "C");
                            
            $this->fpdf->SetY(-101);
            $this->fpdf->SetFont('Times','B','10');      
            $this->fpdf->Cell(0,5,"School Reopens on : ".date_format(date_create($record['term_details']['new_term_beginning']), 'jS M Y'), 0, 0, "C");      
            
            $this->fpdf->Ln(10);        
            
            $this->fpdf->SetY(-91);
            $this->fpdf->SetFont('Times','I',8);
            $this->fpdf->Cell(0,7,"This is an official document which is not valid without the ".$school." school school stamp.",0,0,'C');
            
            }
            $this->fpdf->Output('I', 'ReportCard.pdf');
        exit;  
    }

    private function SetWidths($w)
    {
        //Set the array of column widths
        $this->widths=$w;
    }

    private function SetAligns($a)
    {
        //Set the array of column alignments
        $this->aligns=$a;
    }

    private function Row($data, $fill)
    {
        //Calculate the height of the row
        $nb=0; $nbMax=0; $noComment = false; $teacherCol = 6; $teacherInitialOffset = 90; $passmark = 50;
        
        for($i=0;$i<count($data);$i++)
            if($i != $teacherCol) $nbMax=max($nbMax,$this->NbLines($this->widths[$i],$data[$i]));
        $h=5*$nbMax;
        
        //Issue a page break first if needed
        $this->CheckPageBreak($h);
        //Draw the cells of the row
        for($i=0;$i<count($data);$i++)
        {
            if($i != $teacherCol) $nb=$this->NbLines($this->widths[$i],$data[$i]);
            if($nb == 0) $nb = 1;
            if($i != $teacherCol) $w=$this->widths[$i];
            if($i != $teacherCol)$a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            //Save the current position
            $x=$this->fpdf->GetX();
            $y=$this->fpdf->GetY();
            //set mark to red
            if($i == 2 && is_numeric($data[$i]) && $data[$i] < $passmark) $this->fpdf->SetTextColor(255, 0, 0);
            //Print the text
            if($i == $teacherCol ){                
                $this->fpdf->SetFont('Times','BI','10');
                if($data[$teacherCol-1] == "\n\t")
                    $this->fpdf->Text($this->fpdf->GetX() - $teacherInitialOffset, $this->fpdf->GetY() + (bcdiv($h,$nb) + 1),$data[$i]);
                else $this->fpdf->Text($this->fpdf->GetX() - $teacherInitialOffset, $this->fpdf->GetY() + (bcdiv($h,$nb) * $nbMax) - 2," ".$data[$i]);
                $this->fpdf->SetFont('Times','','10');
            }else{
                if(count($data) == 7 && ( $i == 2 || $i == 3 ))
                {
                    $this->fpdf->SetFillColor(240, 240, 240); 
                    $this->fpdf->MultiCell($w,bcdiv($h,$nb,1),$data[$i],1,$a,true);  
                }
                else
                {
                    //$this->fpdf->SetFillColor(220, 220, 220); 
                    $this->fpdf->MultiCell($w,bcdiv($h,$nb,1),$data[$i],1,$a,$fill);  
                }               
                            
            }  
            
            $this->fpdf->SetTextColor(0, 0, 0);           
            
            //Put the position to the right of the cell
            $this->fpdf->SetXY($x+$w,$y);
        }
        //Go to the next line
        $this->fpdf->Ln($h);
    }

    private function CheckPageBreak($h)
    {
        //If the height h would cause an overflow, add a new page immediately
        if($this->fpdf->GetY()+$h>$this->fpdf->PageBreakTrigger)
            $this->AddPage($this->fpdf->CurOrientation);
    }

    private function NbLines($w,$txt)
    {
        //Computes the number of lines a MultiCell of width w will take
        $cw=&$this->fpdf->CurrentFont['cw'];
        if($w==0)
            $w=$this->w-$this->fpdf->rMargin-$this->x;
        $wmax=($w-2*$this->fpdf->cMargin)*1000/$this->fpdf->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n")
            $nb--;
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<$nb)
        {
            $c=$s[$i];
            if($c=="\n")
            {
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax)
            {
                if($sep==-1)
                {
                    if($i==$j)
                        $i++;
                }
                else
                    $i=$sep+1;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }
}
