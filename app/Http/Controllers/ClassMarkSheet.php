<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\FormDeanAssignment;
use App\Models\FormTeacherAssignment;
use App\Models\StudentClassRegistration;
use App\Models\StudentTermDetail;
use App\Models\StudentTermMark;
use App\Models\TermConfiguration;
use Illuminate\Http\Request;

class ClassMarkSheet extends Controller
{
    protected $pdf;

    public function __construct(\App\Models\Pdf $pdf)
    {        
        $this->pdf = $pdf;
    }
    
    public function show($academic_term_id, $form_class_id){
        $logo = public_path('/imgs/logo.png');
        $school = strtoupper(config('app.school_name'));        
        $address = config('app.school_address');
        $data = []; $course_mark_only = false; $max_cols=14; $max_rows=15;
        $form_teacher_assignments = []; $form_dean_assignments = [];
        $academic_term = AcademicTerm::where('id', $academic_term_id)
        ->first();
        $term_start = date_format(date_create($academic_term->term_start), "j M");
        $term_end = date_format(date_create($academic_term->term_end), "M Y");
        $academic_year_id = $academic_term->academic_year_id;
        $term_configuration = TermConfiguration::where('academic_term_id', $academic_term_id)->first();
        if($term_configuration){
            $course_mark_only = ($term_configuration->exam_mark === 0) ? true : false;
        }        
        
        //$class_averages = $course_mark_only ? $this->classAverages($form_class_id, $academic_term_id, 2) : $this-> $this->classAverages($form_class_id, $academic_term_id, 1);
        if($course_mark_only) $class_averages = $this->classAverages($form_class_id, $academic_term_id, 2);
        else $class_averages = $this->classAverages($form_class_id, $academic_term_id, 1);

        //return $class_averages;

        $distinct_subjects = StudentTermMark::join('student_class_registrations', 'student_term_marks.student_id', 'student_class_registrations.student_id')
        ->join('subjects', 'student_term_marks.subject_id', 'subjects.id')
        ->where([
            ['student_term_marks.academic_term_id', $academic_term_id],
            ['student_class_registrations.form_class_id', $form_class_id],
            ['student_class_registrations.academic_year_id', $academic_year_id]
        ])
        ->select('student_term_marks.subject_id', 'subjects.abbr', 'subjects.title')
        ->orderBy('subjects.title')
        ->distinct()
        ->get();

        $form_teachers = FormTeacherAssignment::where([
            ['form_class_id', $form_class_id],
            ['academic_year_id', $academic_year_id]
        ])->get();

        if($form_teachers->count()!=0){
            foreach($form_teachers as $teacher){
                $employee = Employee::where('id', $teacher->employee_id)->first();
                $employee_name = $employee->first_name[0].'. '.$employee->last_name;
                array_push($form_teacher_assignments, $employee_name);
            }
        }        

        $form_deans = FormDeanAssignment::where([
            ['form_class_id', $form_class_id],
            ['academic_year_id', $academic_year_id]
        ])->get();

        if($form_deans->count()!=0){
            foreach($form_deans as $dean){
                $employee = Employee::where('id', $dean->employee_id)->first();
                $employee_name = $employee->first_name[0].'. '.$employee->last_name;
                array_push($form_dean_assignments, $employee_name);
            }
        }        

        $students_registered = StudentTermDetail::where([
            ['form_class_id', $form_class_id],
            ['academic_term_id', $academic_term_id]
        ])
        ->select('student_id', 'sessions_absent', 'sessions_late')
        ->get();

        foreach($students_registered as $student){
            $student_record = [];  $student_term_marks = [];
            $total_marks = 0; $total_subjects = 0;

            $student_id = $student->student_id;
            foreach($distinct_subjects as $subject){
                $mark_record = [];
                $subject_id = $subject->subject_id;

                $exam_mark = StudentTermMark::where([
                    ['student_id', $student_id],
                    ['academic_term_id', $academic_term_id],
                    ['subject_id', $subject_id],
                    ['test_id', 1]
                ])->first();

                $course_mark = StudentTermMark::where([
                    ['student_id', $student_id],
                    ['academic_term_id', $academic_term_id],
                    ['subject_id', $subject_id],
                    ['test_id', 2]
                ])->first();
                
                if($course_mark){
                    $mark = $course_mark->mark;
                    if($course_mark_only) $total_subjects++;
                    if($course_mark_only && is_numeric($mark)) $total_marks += $mark;
                    $mark_record['course_mark'] = $mark;
                    $course_attendance = $course_mark->assesment_attendance_id;
                    if($course_attendance == 2) $mark_record['course_mark'] = 'ABS';
                    else if($course_attendance == 3) $mark_record['course_mark'] = 'NW';
                    
                }
                else{
                    $mark_record['course_mark'] = null;                    
                }
                
                if($exam_mark){
                    $mark = $exam_mark->mark;
                    if(!$course_mark_only) $total_subjects++;
                    if(!$course_mark_only && is_numeric($mark)) $total_marks += $mark;
                    $mark_record['exam_mark'] = $mark;
                    $exam_attendance = $exam_mark->assesment_attendance_id;
                    if($exam_attendance == 2) $mark_record['exam_mark'] = 'ABS';
                }
                else{
                    $mark_record['exam_mark'] = null;
                }
                $mark_record['subject'] = $subject->title;
                $mark_record['abbr'] = $subject->abbr;
                $mark_record['id'] = $subject_id;
                array_push($student_term_marks, $mark_record);                           
            }
            $average = ($total_subjects != 0) ? round($total_marks/$total_subjects, 1) : null;
            $student_record['total_marks'] = ($total_marks!=0) ? $total_marks : null;
            $student_record['average'] = $average;
            $student_record['rank'] = $this->rank($average, $class_averages);
            $student_record['sessions_absent'] = $student->sessions_absent;
            $student_record['sessions_late'] = $student->sessions_late;
            $student_record['term_marks'] = $student_term_marks;
            $student = $student->student;
            $student_record['name'] = $student->last_name.', '.$student->first_name;
            array_push($data, $student_record); 
        }
        
        $data = $this->sort($data);
        $this->pdf->AliasNbPages();
        $this->pdf->SetMargins(10, 10);
        $this->pdf->SetAutoPageBreak(false);        

        foreach($data as $key => $record){
            if($key%$max_rows==0){
                if($key!=0){
                    $this->pdf->SetY(-15);
                    $this->pdf->SetFont('Times','I',8);
                    $this->pdf->Cell(88, 6, 'Report Generated: '.date("d/m/Y h:i:sa"), 0, 0, 'L');
                    $this->pdf->Cell(88, 6, 'Page '.$this->pdf->PageNo().'/{nb}', 0, 0, 'R');
                } 
                $this->pdf->AddPage('L', 'Legal');
                $this->pdf->Image($logo, 10, 6, 25);
                $this->pdf->SetFont('Times', 'B', '18');
                $this->pdf->MultiCell(0, 8, $school, 0, 'C' );
                $this->pdf->SetFont('Times', 'I', 10);
                $this->pdf->MultiCell(0, 6, $address, 0, 'C' );
                $this->pdf->Ln(4);

                $this->pdf->SetFont('Times', 'B', 12);
                $this->pdf->MultiCell(0,6, 'CLASS SUMMARY MARK SHEET', 0, 'C');                
                $this->pdf->Ln(10);

                $border=0;
                $this->pdf->SetDrawColor(220,220,220);
                $this->pdf->SetFont('Times', 'B', 12);
                $this->pdf->Cell(14,8,'Class: ',$border,0,'L');
                $this->pdf->SetFont('Times', '', 12);
                $this->pdf->Cell(64,8, $form_class_id ,$border,0,'L');
                $this->pdf->SetFont('Times', 'B', 12);
                $this->pdf->Cell(24,8,'Form Dean: ',$border,0,'L');
                $this->pdf->SetFont('Times', 'I', 12);
                $this->pdf->Cell(70,8,implode(" / ", $form_dean_assignments),$border,0,'L');                
                $this->pdf->SetFont('Times', 'B', 12);
                $this->pdf->Cell(30,8,'Form Teacher:',$border,0,'L');
                $this->pdf->SetFont('Times', 'I', 12);
                $this->pdf->Cell(80,8,implode(" / ", $form_teacher_assignments),$border,0,'L');
                $this->pdf->SetFont('Times', 'B', 12);
                $this->pdf->Cell(20,8,'Term: ',$border,0,'R');
                $this->pdf->SetFont('Times', '', 12);
                $this->pdf->Cell(34,8,$term_start.' - '.$term_end,$border,0,'L');
                $this->pdf->SetFont('Times', 'B', 12);
                $this->pdf->Ln();

                $x = $this->pdf->GetX();
                $y = $this->pdf->GetY();
                
                $this->pdf->MultiCell(62, 35, "STUDENT'S NAME", 'TLR', 'C');
                $this->pdf->SetXY($x+62, $y);
                $this->pdf->SetFont('Times', 'B', 10);
                $distinct_subject_count = 0;
                foreach($distinct_subjects as $subject){
                    $distinct_subject_count++;
                    $x = $this->pdf->GetX();
                    $y = $this->pdf->GetY();
                    $NbLines = $this->pdf->NbLines(16,$subject->abbr);
                    if($NbLines == 1) $this->pdf->MultiCell(16,15,$subject->abbr,1,'C');
                    else $this->pdf->MultiCell(16,7.5,$subject->abbr,1,'C');
                    //$this->pdf->MultiCell(16,15,$NbLines,1,'C');
                    $this->pdf->SetXY($x+16,$y);
                }
                if($distinct_subject_count < $max_cols){
                    for($i=$distinct_subject_count; $i<$max_cols; $i++ ){                
                        $x = $this->pdf->GetX();
                        $y = $this->pdf->GetY();
                        $this->pdf->MultiCell(16,15,'',1,'C');
                        $this->pdf->SetXY($x+16,$y);
                    }
                }
                $this->pdf->Cell(30,15,"AGGREGATE",1,0,'C');
                $this->pdf->MultiCell(20,7.5,"NUMBER\t\nOF TIMES",1,'C');
                $x = $this->pdf->GetX();
                $this->pdf->setX($x+62);
                $this->pdf->SetFont('Times','',10);
                foreach($distinct_subjects as $subject){
                    $this->pdf->Cell(8,20,"",'B',0,'C');
                    $this->pdf->RotateText("Course",90,9);
                    $this->pdf->Cell(8,20,"",'BR',0,'C');
                    $this->pdf->RotateText("Exam",90,8);
                }
                if($distinct_subject_count < $max_cols){
                    for($i=$distinct_subject_count; $i<$max_cols; $i++ ){                
                        $this->pdf->Cell(8,20,"",'B',0,'C');                
                        $this->pdf->Cell(8,20,"",'BR',0,'C');               
                    }
                }
                $this->pdf->Cell(10,20,"",1,0,'C');
                $this->pdf->RotateText("Marks",90,10);
                $this->pdf->Cell(10,20,"",1,0,'C');
                $this->pdf->RotateText("Avg %",90,10);
                $this->pdf->Cell(10,20,"",1,0,'C');
                $this->pdf->RotateText("Rank",90,10);
                $this->pdf->Cell(10,20,"",1,0,'C');
                $this->pdf->RotateText("Absent",90,10);
                $this->pdf->Cell(10,20,"",1,0,'C');
                $this->pdf->RotateText("Late",90,10);
                $this->pdf->Ln();
            }
            if($key%2==0) $this->pdf->SetFillColor(239,240,242);
            else $this->pdf->SetFillColor(255,255,255);
            $this->pdf->Cell(8,7,$key+1,'TLB','0','C',true);
            $this->pdf->Cell(54,7,$record['name'],'TLB',0,'L',true);
            $term_marks = $record['term_marks'];
            foreach($term_marks as $mark){
                $this->pdf->Cell(8,7,$mark['course_mark'],'TLB',0,'C',true);
                $this->pdf->Cell(8,7,$mark['exam_mark'],'TRB',0,'C',true);
            }
            if($distinct_subject_count<$max_cols){
                for($i=$distinct_subject_count; $i<$max_cols; $i++ ){                
                    $this->pdf->Cell(8,7,'','TLB',0,'C',true);
                    $this->pdf->Cell(8,7,'','TRB',0,'C',true);              
                }
            }
            $this->pdf->Cell(10,7,$record['total_marks'],1,0,'C',true);
            $this->pdf->Cell(10,7,$record['average'],1,0,'C',true);
            $this->pdf->Cell(10,7,$record['rank'],1,0,'C',true);
            $this->pdf->Cell(10,7,$record['sessions_absent'],1,0,'C',true);
            $this->pdf->Cell(10,7,$record['sessions_late'],1,0,'C',true);
            $this->pdf->Ln();            
        }

        $this->pdf->SetY(-15);
        $this->pdf->SetFont('Times','I',8);
        $this->pdf->Cell(88, 6, 'Report Generated: '.date("d/m/Y h:i:sa"), 0, 0, 'L');
        $this->pdf->Cell(88, 6, 'Page '.$this->pdf->PageNo().'/{nb}', 0, 0, 'R');

        $this->pdf->Output('I', 'Class Summary Mark Sheet.pdf');
    }

    public function terms(){
        $data = [];
        $distinct_academic_terms = StudentTermMark::select('academic_term_id')
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

    private function rank($average, $array){        
        foreach($array as $key => $value){
            if($average == $value){
                return $key+1;
            }
        }
        return 0;
    }

    private function classAverages($form_class_id, $academic_term_id, $test_id){
        $class_averages = [];
        $students_registered = StudentTermDetail::where([
            ['form_class_id', $form_class_id],
            ['academic_term_id', $academic_term_id]
        ])
        ->select('student_id')
        ->get();       

        foreach($students_registered as $student){
            $total_marks = 0; $total_subjects = 0; 
            $student_term_marks = StudentTermMark::where([
                ['student_id', $student->student_id],
                ['academic_term_id', $academic_term_id],
                ['test_id', $test_id]
            ])->get();

            foreach($student_term_marks as $term_mark){
                $mark = $term_mark->mark;
                if(is_numeric($mark)) $total_marks += $mark;
                $total_subjects++;
            }
            
            $average = ($total_subjects != 0) ? round($total_marks/$total_subjects, 1) : null;
            array_push($class_averages, $average);
        }
        rsort($class_averages);
        return $class_averages;
    }

    private function sort($array){
        $l=0; $m=0; $keyAvg=0; $keyArray=[]; $n = sizeof($array);        
        for($l = 1; $l < $n; $l++){            
            $keyTotalMarks = $array[$l]['total_marks'];
            $keyAvg = $array[$l]['average'];
            $keyRank = $array[$l]['rank'];
            $keyAbsent = $array[$l]['sessions_absent'];
            $keyLate = $array[$l]['sessions_late'];
            $keyMarks = $array[$l]['term_marks'];
            $keyName = $array[$l]['name'];
            $m=$l-1;
            while($m >=0 && $keyRank < $array[$m]['rank']){
                $array[$m+1] = $array[$m];
                --$m;
            }
            $keyArray['total_marks']=$keyTotalMarks;
            $keyArray['average']=$keyAvg;
            $keyArray['rank']=$keyRank;
            $keyArray['sessions_absent']=$keyAbsent;
            $keyArray['sessions_late']=$keyLate;
            $keyArray['term_marks']=$keyMarks;
            $keyArray['name']=$keyName;
            $array[$m+1]=$keyArray;
        }
        return $array;
    }

    

}
