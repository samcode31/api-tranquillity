<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\EthnicGroup;
use App\Models\StudentPersonalData;
use Illuminate\Http\Request;

class ReportEthnicGroup extends Controller
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
        $this->pdf->MultiCell(0,6, 'Ethnic Group Statistics', 0, 'C');

        $this->pdf->Ln();

        $border= 'B';
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(10, 6, '', 0, 0, 'C' );
        $this->pdf->Cell(80, 6, 'Ethnic Group', $border, 0, 'L' );
        $this->pdf->Cell(30, 6, 'Male', $border, 0, 'C' );
        $this->pdf->Cell(30, 6, 'Female', $border, 0, 'C' );
        $this->pdf->Cell(30.9, 6, 'Total', $border, 0, 'C' );
        $this->pdf->Cell(10, 6, '', 0, 0, 'C' );
        $this->pdf->SetFont('Times', '', 12);
        $this->pdf->Ln();

        $records = $this->data($academicYearId);
        // return $records;
        $totalStudents = 0;
        $border= 'B';
        $this->pdf->SetDash(0.5, 0.5);
        $this->pdf->SetDrawColor(220,220,220);
        foreach($records as $record){
            $male = $record['male'];
            $female = $record['female'];
            $groupTotal = $male + $female;
            $this->pdf->Cell(10, 8, '', 0, 0, 'C' );
            $this->pdf->Cell(80, 8, $record['grouping'], $border, 0, 'L' );
            $this->pdf->Cell(30, 8, $male, $border, 0, 'C' );
            $this->pdf->Cell(30, 8, $female, $border, 0, 'C' );
            $this->pdf->Cell(30.9, 8, $groupTotal, $border, 0, 'C' );
            $this->pdf->Cell(10, 8, '', 0, 0, 'C' );
            $this->pdf->SetFont('Times', '', 12);
            $this->pdf->Ln();
            $totalStudents += $groupTotal;
            $groupTotal = 0;
        }
        $this->pdf->Ln();

        $this->pdf->Cell(10, 6, '', 0, 0, 'C' );
        $this->pdf->Cell(80, 6, '', 0, 0, 'L' );
        $this->pdf->Cell(30, 6, '', 0, 0, 'C' );
        $this->pdf->SetFont('Times', 'IB', 12);
        $this->pdf->Cell(30, 6, 'Total', 0, 0, 'C' );
        $this->pdf->Cell(30.9, 6, $totalStudents, 0, 0, 'C' );
        $this->pdf->Cell(10, 6, '', 0, 0, 'C' );
        $this->pdf->SetFont('Times', '', 12);

        $this->pdf->setY(-15);
        $this->pdf->SetFont('Times', 'I', 8);
        $this->pdf->Cell(45, 8, 'Generated at '.date('d M Y h:i a'));
        $this->pdf->Cell(0, 8, 'Page: '.$this->pdf->PageNo().'/{nb}', 0, 0, 'R');


        $this->pdf->Output('I', 'Ethnic Group Statistics.pdf');
        exit;
    }

    private function data ($academicYearId)
    {
        $ethnicGroups = EthnicGroup::all();
        $data = [];
        foreach($ethnicGroups as $ethnicGroup)
        {
            $group = [];
            $grouping = $ethnicGroup->grouping;
            $groupId = $ethnicGroup->id;
            $maleStudents = StudentPersonalData::join(
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
                'student_data_personal.student_id'
            )
            ->where([
                ['ethnic_group_id', $groupId],
                ['gender', 'M'],
                ['academic_year_id', $academicYearId]
            ])
            ->whereNull('students.deleted_at')
            ->get()
            ->count();

            $femaleStudents = StudentPersonalData::join(
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
                'student_data_personal.student_id'
            )
            ->where([
                ['ethnic_group_id', $groupId],
                ['gender', 'F'],
                ['academic_year_id', $academicYearId]
            ])
            ->whereNull('students.deleted_at')
            ->get()
            ->count();

            $group['grouping'] = $grouping;
            $group['male'] = $maleStudents;
            $group['female'] = $femaleStudents;
            $data[] = $group;
        }

        return $data;
    }
}
