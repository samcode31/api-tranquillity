<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\FormClass;
use App\Models\Student;
use Illuminate\Http\Request;

class ReportAgeStatistics extends Controller
{
    protected $pdf;

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }

    public function show ($date = null)
    {
        date_default_timezone_set('America/Caracas');
        $logo = public_path('/imgs/logo.png');
        $school = strtoupper(config('app.school_name'));
        $primaryRed = config('app.primary_red');
        $primaryGreen = config('app.primary_green');
        $primaryBlue = config('app.primary_blue');
        $address = config('app.school_address');
        $contact = config('app.school_contact');
        $formLevels = 6;
        $col1 = 30; $colOffset = 24.7;

        $this->pdf->SetMargins(10, 8);
        $this->pdf->AliasNbPages();
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->AddPage('L', 'Letter');

        $academicTerm = AcademicTerm::where('is_current', 1)
        ->first();
        $academicYearId = null;
        if($academicTerm){
            $academicYearId = $academicTerm->academic_year_id;
        }
        $yearStart = $academicYearId ? substr($academicYearId,0,4) : null;
        $date = $yearStart.'-11-30';

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
        $this->pdf->MultiCell(0,6, 'Enrollment Age Statistics', 0, 'C');

        $this->pdf->Ln();

        $border= 1;
        $this->pdf->SetDrawColor(190,190,190);
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell($colOffset, 18, '', 0, 0, 'C' );
        $this->pdf->Cell($col1, 18, 'Age', $border, 0, 'C' );
        $this->pdf->Cell(144, 6, 'Form', $border, 0, 'C');
        $y=$this->pdf->GetY();
        $this->pdf->SetFillColor(220,220,220);
        $this->pdf->Cell(36, 18, 'Total', $border, 0, 'C', true);
        $this->pdf->Ln();

        $x=$this->pdf->GetX();
        $this->pdf->SetXY($x+$col1+$colOffset, $y+6);
        for($i = 1; $i <= $formLevels; $i++){
            $this->pdf->Cell(24, 6, $i, $border, 0, 'C' );
        }
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Ln();

        $x=$this->pdf->GetX();
        $this->pdf->SetX($x+$col1+$colOffset);
        for($i = 1; $i <= $formLevels; $i++){
            $this->pdf->Cell(12, 6, 'M', $border, 0, 'C' );
            $this->pdf->Cell(12, 6, 'F', $border, 0, 'C' );
        }
        $this->pdf->Cell(12, 6, 'M', $border, 0, 'C', true );
        $this->pdf->Cell(12, 6, 'F', $border, 0, 'C', true );
        $this->pdf->SetFont('Times', '', 9);
        $this->pdf->MultiCell(12, 3, "Both\r\nSexes", $border,'C', true );
        $this->pdf->SetFont('Times', '', 11);

        $ageGroups = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
        $totalFormMales = [];
        $totalFormFemales = [];
        $totalMalePop = 0;
        $totalFemalePop = 0;

        for($i = 1; $i <= $formLevels; $i++){
            $totalFormMales[$i] = 0;
            $totalFormFemales[$i] = 0;
        }

        // return $this->data($academicYearId, $date, 11);

        foreach($ageGroups as $group){
            $records = $this->data($academicYearId, $date, $group);
            // $records = $this->data($academicYearId, $date, $group, 19);
            // return $records;
            $totalMales = 0; $totalFemales = 0;
            $x=$this->pdf->GetX();
            $this->pdf->SetX($x+$colOffset);
            $this->pdf->Cell($col1, 7, $group.'<'.($group+1).' years', $border, 0, 'C' );
            foreach($records as $index => $record){
                $totalMales += $record['M'];
                $totalFemales += $record['F'];

                $males = $totalFormMales[$index];
                $males += $record['M'];
                $totalFormMales[$index] = $males;

                $females = $totalFormFemales[$index];
                $females += $record['F'];
                $totalFormFemales[$index] = $females;

                $this->pdf->Cell(12, 7, $record['M'], $border, 0, 'C' );
                $this->pdf->Cell(12, 7, $record['F'], $border, 0, 'C' );
            }
            $totalMalePop += $totalMales;
            $totalFemalePop += $totalFemales;
            $this->pdf->Cell(12, 7, $totalMales, $border, 0, 'C', true );
            $this->pdf->Cell(12, 7, $totalFemales, $border, 0, 'C', true );
            $this->pdf->Cell(12, 7, $totalMales+$totalFemales, $border, 0, 'C', true );
            $this->pdf->Ln();
        }

        $this->pdf->SetFont('Times', 'B', 11);
        $this->pdf->SetX($x+$colOffset);
        $this->pdf->Cell($col1, 7, 'Total', $border, 0, 'C' );
        for($i = 1; $i <= $formLevels; $i++){
            $this->pdf->Cell(12, 7, $totalFormMales[$i], $border, 0, 'C', true );
            $this->pdf->Cell(12, 7, $totalFormFemales[$i], $border, 0, 'C', true );
        }
        $this->pdf->Cell(12, 7, $totalMalePop, $border, 0, 'C', true );
        $this->pdf->Cell(12, 7, $totalFemalePop, $border, 0, 'C', true );
        $this->pdf->Cell(12, 7, $totalFemalePop+$totalMalePop, $border, 0, 'C', true );


        $this->pdf->setY(-15);
        $this->pdf->SetFont('Times', 'I', 8);
        $this->pdf->Cell(45, 8, 'Generated at '.date('d M Y h:i a'));
        $this->pdf->Cell(0, 8, 'Page: '.$this->pdf->PageNo().'/{nb}', 0, 0, 'R');


        $this->pdf->Output('I', 'Ethnic Group Statistics.pdf');
        exit;
    }

    private function data ($academicYearId, $date, $ageGroupMin)
    {
        $date = date_create($date);
        $students = Student::join(
            'student_class_registrations',
            'student_class_registrations.student_id',
            'students.id'
        )
        ->select(
            'students.id',
            'students.date_of_birth',
            'student_class_registrations.form_class_id',
            'students.gender'
        )
        ->where('academic_year_id', $academicYearId)
        ->get();

        // return $students->count();

        $forms = [];
        for($i = 1; $i<=6; $i++){
            // $forms[$i] = 0;
            $forms[$i] = array('M' => 0, 'F' => 0);
        }
        $ages = array();
        foreach($students as $student)
        {
            $dateOfBirth = $student->date_of_birth;
            $age = 0;
            if($dateOfBirth){
                $dateOfBirth = date_create($dateOfBirth);
                $diff = $date->diff($dateOfBirth);
                $age = $diff->y;
            }
            // if(!in_array($age, $ages))
            // $ages[] = $age;
            $formClassRecord = FormClass::where('id',$student->form_class_id)
            ->first();
            $formLevel = $formClassRecord->form_level;

            if($age != 0 && $age == $ageGroupMin && $ageGroupMin < 19)
            {
                if($student->gender == 'M'){
                    $males = $forms[$formLevel]['M'];
                    $males++;
                    $forms[$formLevel]['M'] = $males;
                }
                if($student->gender == 'F') {
                    $females = $forms[$formLevel]['F'];
                    $females++;
                    $forms[$formLevel]['F'] = $females;
                }
            }

            elseif($age != 0 && $ageGroupMin >= 19 && $age >= 19)
            {
                $ages[] = $age.' '.$student->id.' '.$student->gender.' '.$formLevel;
                if($student->gender == 'M'){
                    $males = $forms[$formLevel]['M'];
                    $males++;
                    $forms[$formLevel]['M'] = $males;
                }
                if($student->gender == 'F') {
                    $females = $forms[$formLevel]['F'];
                    $females++;
                    $forms[$formLevel]['F'] = $females;
                }
            }
        }
        // $forms['ages'] = $ages;
        return $forms;
    }
}
