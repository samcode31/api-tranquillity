<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AcademicTerm;
use App\Models\StudentMedicalData;

class ReportHealthController extends Controller
{
    protected $pdf;

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }

    public function show ()
    {
        date_default_timezone_set('America/Caracas');
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

        $data = $this->data($academicYearId);

        // return $data;

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
        $this->pdf->MultiCell(0,6, 'Student Health Data', 0, 'C');

        $this->pdf->Ln();

        $border=1;
        $this->pdf->SetFont('Times', 'B', 11);
        $this->pdf->Cell(10, 8, '#' , $border , 0, 'C');
        $this->pdf->Cell(25, 8, 'Student ID' , $border, 0, 'C');
        $this->pdf->Cell(50, 8, 'Student Name', 'TBR', 0, 'L');
        $this->pdf->Cell(20, 8, 'Gender', $border, 0, 'C');
        $this->pdf->Cell(15, 8, 'Class', $border, 0, 'C');
        $this->pdf->Cell(75.9, 8, 'Medical Condition', $border, 0, 'C');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Ln();

        $this->pdf->SetWidths(array(10, 25, 50, 20, 15, 75.9));
        $this->pdf->SetAligns(array('C', 'C', 'L', 'C', 'C', 'L'));
        $this->pdf->SetBorders(array(1,1,1,1,1,1));

        foreach($data as $index => $record){
            $this->pdf->Row2(array(
                $index+1,
                $record->student_id,
                $record->last_name.', '.$record->first_name,
                $record->gender,
                $record->form_class_id,
                $record->other
            ), false);
            // $this->pdf->Cell(10, 8, $index+1 , $border , 0, 'C');
            // $this->pdf->Cell(30, 8, $record->student_id , $border, 0, 'C');
            // $this->pdf->Cell(5, 8, '', 'LTB', 0, 'L');
            // $this->pdf->Cell(56, 8, $record->last_name.', '.$record->first_name, 'TBR', 0, 'L');
            // $this->pdf->Cell(20, 8, $record->gender, $border, 0, 'C');
            // $this->pdf->Cell(20, 8, $record->form_class_id, $border, 0, 'C');
            // $this->pdf->Cell(54.9, 8, $record->other, $border, 0, 'L');
            // $this->pdf->Ln();
        }


        $this->pdf->setY(-15);
        $this->pdf->SetFont('Times', 'I', 8);
        $this->pdf->Cell(45, 8, 'Generated at '.date('d M Y h:i a'));
        $this->pdf->Cell(0, 8, 'Page: '.$this->pdf->PageNo().'/{nb}', 0, 0, 'R');


        $this->pdf->Output('I', 'Student Health Report.pdf');
        exit;

    }

    private function data ($academicYearId)
    {
        return StudentMedicalData::join(
            'students',
            'students.id',
            'student_data_medical.student_id'
        )
        ->join(
            'student_class_registrations',
            'student_class_registrations.student_id',
            'student_data_medical.student_id'
        )
        ->select(
            'student_class_registrations.student_id',
            'students.first_name',
            'students.last_name',
            'students.gender',
            'student_class_registrations.form_class_id',
            'student_data_medical.other',
        )
        ->where([
            ['student_class_registrations.academic_year_id', $academicYearId],
            ['other', '<>', "none" ],
            ['other', '<>', "no" ],
            ['other', '<>', "nil" ],
            ['other', '<>', "N\A" ],

        ])
        ->whereNotNull('other')
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

    }

}
