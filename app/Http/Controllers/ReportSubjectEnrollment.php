<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AcademicTerm;
use App\Models\StudentTermMark;
use App\Models\StudentClassRegistration;
use App\Models\FormClass;

class ReportSubjectEnrollment extends Controller
{
    protected $pdf;

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }

    public function show ()
    {
        date_default_timezone_set('America/Caracas');

        $maxRows = 30;

        $this->header();

        $this->TableHeader();

        $data = $this->data();

        // return $data;
        foreach($data as $index => $record )
        {
            if($index && $index%$maxRows == 0){
                $this->Footer();
                $this->header();
                $this->TableHeader();
            }
            $this->pdf->Cell(10,6,$index+1,1,0,"C");
            $this->pdf->Cell(106.9,6,$record["subject_title"],1,0);
            $this->pdf->Cell(15,6,$record["subject_id"],1,0,"C");
            foreach($record["subject_count"] as $subjectCount){
                $this->pdf->Cell(8,6,$subjectCount,1,0,"C");
            }
            $this->pdf->Ln();
        }
        $this->Footer();
        $this->pdf->Output('I', 'Student Subject Enrollment Statistics.pdf');
        exit;
    }

    private function header ()
    {
        $logo = public_path('/imgs/logo.png');
        $school = strtoupper(config('app.school_name'));
        $primaryRed = config('app.primary_red');
        $primaryGreen = config('app.primary_green');
        $primaryBlue = config('app.primary_blue');
        $address = config('app.school_address');
        $contact = config('app.school_contact');

        $this->pdf->SetMargins(10, 8);
        $this->pdf->AliasNbPages();
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->AddPage('P', 'Letter');

        $academicTerm = AcademicTerm::where('is_current', 1)
        ->first();
        $academicYearId = null;
        if($academicTerm){
            $academicYearId = $academicTerm->academic_year_id;
        }

        $this->pdf->Image($logo, 10, 6, 23);
        $this->pdf->SetFont('Times', 'B', '15');
        $this->pdf->SetTextColor($primaryRed, $primaryGreen, $primaryBlue);
        $this->pdf->MultiCell(0, 8, $school, 0, 'C' );
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetFont('Times', 'I', 10);
        $this->pdf->MultiCell(0, 6, $address, 0, 'C' );
        $this->pdf->MultiCell(0, 6, $contact, 0, 'C' );

        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetFont('Times', 'B', 14);
        $this->pdf->MultiCell(0,6, substr($academicYearId,0,4).'-'.substr($academicYearId,4), 0, 'C');
        $this->pdf->Ln(3);
        $this->pdf->MultiCell(0,6, 'Subject Enrollment Statistics', 0, 'C');
        $this->pdf->SetFont('Times', '', 11);

        $this->pdf->Ln();
    }

    private function Footer ()
    {
        $this->pdf->setY(-15);
        $this->pdf->SetFont('Times', 'I', 8);
        $this->pdf->Cell(45, 8, 'Generated at '.date('d M Y h:i a'));
        $this->pdf->Cell(0, 8, 'Page: '.$this->pdf->PageNo().'/{nb}', 0, 0, 'R');
    }

    private function TableHeader ()
    {
        $this->pdf->Cell(116.9,18,'Subject',1,0);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(15,4.5,"\nSubject\nCodes\n\n",1);
        $this->pdf->SetXY($x+15,$y);
        $this->pdf->Cell(8,18,"",1,0);
        $this->pdf->RotateText("Form 1",90,9);
        $this->pdf->Cell(8,18,"",1,0);
        $this->pdf->RotateText("Form 2",90,9);
        $this->pdf->Cell(8,18,"",1,0);
        $this->pdf->RotateText("Form 3",90,9);
        $this->pdf->Cell(8,18,"",1,0);
        $this->pdf->RotateText("Form 4",90,9);
        $this->pdf->Cell(8,18,"",1,0);
        $this->pdf->RotateText("Form 5",90,9);
        $this->pdf->Cell(8,18,"",1,0);
        $this->pdf->RotateText("Lower 6",90,9);
        $this->pdf->Cell(8,18,"",1,0);
        $this->pdf->RotateText("Upper 6",90,9);
        $this->pdf->Cell(8,18,"",1,0);
        $this->pdf->RotateText("Total",90,9);
        $this->pdf->Ln();
    }

    private function data ()
    {
        $data = array();
        $academicTerm = AcademicTerm::where('is_current', 1)
        ->first();
        $academicYearId = $academicTerm ? $academicTerm->academic_year_id : null;
        $academicTermId = ($academicTerm && $academicTerm->term == 1) ? $academicTerm->id : null; 
        if($academicTerm && $academicTerm->term != 1){
            $academicTerm = AcademicTerm::where([
                ['academic_year_id', $academicYearId],
                ['term', $academicTerm->term-1]
            ])
            ->first();
    
            $academicTermId = $academicTerm->id;
        }

        $subjects = StudentTermMark::where([
            ['academic_term_id', $academicTermId]
        ])
        ->join(
            'subjects',
            'student_term_marks.subject_id',
            'subjects.id'
        )
        ->select('subject_id', 'title')
        ->distinct()
        ->orderBy('title')
        ->get();

        $formClasses = FormClass::get();

        $formSubjectCount = array();

        foreach($formClasses as $formClass){
            if($formClass->form_level != 6){
                $formSubjectCount[$formClass->form_level] = 0;
                continue;
            }
            $formSubjectCount[$formClass->id] = 0; 
        }

        foreach($subjects as $subject)
        {
            $subjectStudentEnrollments = array();
            $subjectStudents = StudentTermMark::where([
                ['academic_term_id', $academicTermId],
                ['subject_id', $subject->subject_id],
                ['academic_year_id', $academicYearId]
            ])
            ->join(
                'student_class_registrations',
                'student_term_marks.student_id',
                'student_class_registrations.student_id'
            )
            ->select(
                'student_term_marks.student_id',
                'form_class_id'
            )
            ->distinct()
            ->get();


            foreach($subjectStudents as $subjectStudent)
            {
                $formClassRecord = FormClass::where('id', $subjectStudent->form_class_id)
                ->first();

                $formLevel = $formClassRecord ? $formClassRecord->form_level : null;

                if($formLevel && $formLevel != 6){
                    $formSubjectCount[$formLevel] += 1;
                }
                elseif($formLevel && $formLevel == 6){
                    $formSubjectCount[$subjectStudent->form_class_id] += 1;
                }
            }

            $formSubjectCount["total"] = $subjectStudents->count();
            
            $subjectStudentEnrollments['subject_title'] = $subject->title;
            $subjectStudentEnrollments['subject_id'] = $subject->subject_id;
            $subjectStudentEnrollments['subject_count'] = $formSubjectCount;
            $data[] = $subjectStudentEnrollments;

            foreach($formSubjectCount as $index => $value )
            {
                $formSubjectCount[$index] = 0;
            }
        }
       

        return $data;
    }

}
