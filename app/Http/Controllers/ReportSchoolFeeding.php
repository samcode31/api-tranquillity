<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\StudentClassRegistration;
use App\Models\StudentPersonalData;
use Illuminate\Http\Request;

class ReportSchoolFeeding extends Controller
{
    protected $pdf;

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }

    public function show ()
    {
        date_default_timezone_set('America/Caracas');

        $academicTerm = AcademicTerm::where('is_current', 1)
        ->first();

        $academicYearId = null;

        if($academicTerm){
            $academicYearId = $academicTerm->academic_year_id;
        }

        $academicYear = substr($academicYearId, 0, 4).'-'.substr($academicYearId, 4);

        $data = $this->data($academicYearId);

        $this->pdf->AddPage('P', 'Letter');
        $this->pdf->AliasNbPages();
        $this->pdf->SetMargins(20, 8);
        $this->pdf->SetAutoPageBreak(false);

        $this->header($academicYear);

        // return $data;

        $border = 1;
        $recordCount = 0;
        $males = 0;
        $females = 0;
        foreach($data as $index => $record){
            $recordCount++;
            if($record->gender === 'M') $males++;
            if($record->gender === 'F') $females++;
            $this->pdf->Cell(10, 8, $index+1 , $border , 0, 'C');
            $this->pdf->Cell(30, 8, $record->student_id , $border, 0, 'C');
            $this->pdf->Cell(5, 8, '', 'LTB', 0, 'L');
            $this->pdf->Cell(86, 8, $record->last_name.', '.$record->first_name, 'TBR', 0, 'L');
            $this->pdf->Cell(25, 8, $record->gender, $border, 0, 'C');
            $this->pdf->Cell(20, 8, $record->form_class_id, $border, 0, 'C');
            $this->pdf->Ln();

            if($index && $index%24 === 0){
                $this->footer();
                $this->pdf->AddPage('P', 'Letter');
                $this->header($academicYear);
            }
        }

        $this->pdf->Ln();
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(58, 8, 'TOTAL: '.$recordCount, 0, 0, 'L');
        $this->pdf->Cell(58, 8, 'Males: '.$males, 0, 0, 'L');
        $this->pdf->Cell(0, 8, 'Females: '.$females, 0, 0, 'L');

        $this->footer();

        $this->pdf->Output('I', 'ReportCard.pdf');
        exit;
    }

    private function header ($academicYear)
    {
        $logo = public_path('/imgs/logo.png');
        $school = strtoupper(config('app.school_name'));
        $primaryRed = config('app.primary_red');
        $primaryGreen = config('app.primary_green');
        $primaryBlue = config('app.primary_blue');
        $address = config('app.school_address');
        $contact = config('app.school_contact');


        $this->pdf->Image($logo, 10, 6, 23);
        $this->pdf->SetFont('Times', 'B', '15');
        $this->pdf->SetTextColor($primaryRed, $primaryGreen, $primaryBlue);
        $this->pdf->MultiCell(0, 8, $school, 0, 'C' );
        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetFont('Times', 'I', 10);
        $this->pdf->MultiCell(0, 6, $address, 0, 'C' );
        $this->pdf->MultiCell(0, 6, $contact, 0, 'C' );

        $this->pdf->SetTextColor(0,0,0);
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Ln();
        $this->pdf->MultiCell(0,6, 'SCHOOL FEEDING LIST '.$academicYear, 0, 'C');
        $this->pdf->Ln(3);

        $border= 1;
        $this->pdf->SetDrawColor(220,220,220);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(10, 8, '#' , $border , 0, 'C');
        $this->pdf->Cell(30, 8, 'Student ID' , $border, 0, 'C');
        $this->pdf->Cell(5, 8, '', 'LTB', 0, 'L');
        $this->pdf->Cell(86, 8, 'Student Name', 'TBR', 0, 'L');
        $this->pdf->Cell(25, 8, 'Gender', $border, 0, 'C');
        $this->pdf->Cell(20, 8, 'Class', $border, 0, 'C');

        $this->pdf->SetFont('Times', '', 12);
        $this->pdf->Ln();
    }

    private function data ($academicYearId)
    {

        $students = StudentPersonalData::join(
            'students',
            'students.id',
            'student_data_personal.student_id'
        )
        ->join(
            'student_class_registrations',
            'student_class_registrations.student_id',
            'student_data_personal.student_id'
        )
        ->select(
            'student_class_registrations.student_id',
            'students.first_name',
            'students.last_name',
            'students.gender',
            'student_class_registrations.form_class_id'
        )
        ->where([
            ['school_feeding', 1],
            ['student_class_registrations.academic_year_id', $academicYearId]
        ])
        ->orderBy('form_class_id')
        ->get();

        return $students;
    }

    private function footer ()
    {
        $this->pdf->setY(-15);
        $this->pdf->SetFont('Times', 'I', 8);
        $this->pdf->Cell(40, 8, 'Generated at '.date('d M Y h:i a'));
        $this->pdf->Cell(0, 8, 'Page: '.$this->pdf->PageNo().'/{nb}',0, 0, 'R');
    }
}
