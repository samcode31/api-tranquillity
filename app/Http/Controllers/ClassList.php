<?php

namespace App\Http\Controllers;

use App\Models\StudentClassRegistration;
use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf;

class ClassList extends Controller
{
    private $fpdf;
    private $widths;
    private $aligns;

    // public function __construct()
    // {
    //     //
    // }

    public function show($form_class_id, $academic_year_id)
    {
        $logo = public_path('/imgs/logo.png');
        $school = strtoupper(config('app.school_name')).' SCHOOL';
        $primaryRed = config('app.primary_red');        
        $primaryGreen = config('app.primary_green');        
        $primaryBlue = config('app.primary_blue');
        $secondaryRed = config('app.secondary_red');        
        $secondaryGreen = config('app.secondary_green');        
        $secondaryBlue = config('app.secondary_blue');        
        $address = config('app.school_address');
        $contact = config('app.school_contact');

        date_default_timezone_set('America/Caracas');

        $studentClassRegistrations = StudentClassRegistration::where([
            ['academic_year_id', $academic_year_id],
            ['form_class_id', $form_class_id]
        ])       
        ->get();

        //return $studentClassRegistrations;

        $classList = [];
        $females = null;
        $males = null;

        //return $studentClassRegistrations[0]->student;

        foreach($studentClassRegistrations as $studentClassRegistration)
        {
            $record = [];
           
            $record['student_id'] = $studentClassRegistration->student_id;
            $record['form_class_id'] = $studentClassRegistration->form_class_id;
            $student = $studentClassRegistration->student;
            $gender = $student->gender;
            if($gender == 'F') $females++;
            if($gender == 'M') $males++;
            $record['first_name'] = $student->first_name;
            $record['last_name'] = $student->last_name;            
            $record['gender'] = $gender;            
            $record['date_of_birth'] = $student->date_of_birth;            
            $record['birth_certificate_pin'] = $student->birth_certificate_pin;            
            array_push($classList, $record);
        }

        $first_name_column = array_column($classList, 'first_name');
        $last_name_column = array_column($classList, 'last_name');
        array_multisort($last_name_column, SORT_ASC, $first_name_column, SORT_ASC, $classList);

        //return $classList;
        
        $this->fpdf = new Fpdf('P', 'mm', 'Letter');
        $this->fpdf->AliasNbPages();
        $this->fpdf->SetMargins(20, 8);
        $this->fpdf->SetAutoPageBreak(false);

        $this->fpdf->AddPage();
        $this->header($form_class_id);        
        $this->fpdf->SetDrawColor(219, 219, 219); 

        $count = 0;
        $border = 'B';
        $records = sizeof($classList);

        foreach($classList as $record){
            $count++;
            $dob = ($record['date_of_birth'] != null) ? date_format(date_create($record['date_of_birth']), 'd-M-Y') : null;
            $this->fpdf->Cell(10, 6, $count , $border , 0, 'C');
            $this->fpdf->Cell(20, 6, $record['student_id'] , $border, 0, 'C');
            $this->fpdf->Cell(10, 6, '', $border, 0, 'C');
            $this->fpdf->Cell(50, 6, $record['first_name'].', '.$record['last_name'] , $border, 0,  'L');
            $this->fpdf->Cell(25, 6, $record['gender'] , $border, 0,  'C');
            $this->fpdf->Cell(25, 6, $dob, $border, 0,  'C');
            $this->fpdf->Cell(5, 6, '', $border, 0, 'C');
            $this->fpdf->Cell(31, 6, $record['birth_certificate_pin'] , $border, 0, 'C');
            $this->fpdf->Ln();

            if($count%30 == 0 && $count != 0){
                $this->fpdf->SetY(-15);
                $this->fpdf->SetFont('Times','I',8);
                $this->fpdf->Cell(88, 6, 'Report Generated: '.date("d/m/Y h:i:sa"), 0, 0, 'L');
                $this->fpdf->Cell(88, 6, 'Page '.$this->fpdf->PageNo().'/{nb}', 0, 0, 'R');
                $this->fpdf->AddPage();
                $this->header($form_class_id);                
            }
            
        }

        $this->fpdf->Ln(10);
        $this->fpdf->SetFont('Times','B', 12);
        $this->fpdf->Cell(88, 6, 'Total No. of Students: '.$count , 0, 0, 'L');
        $this->fpdf->Cell(44, 6, 'Males: '.$males , 0, 0, 'L');
        $this->fpdf->Cell(44, 6, 'Females: '.$females , 0, 0, 'L');
        
        $this->fpdf->SetY(-15);
        $this->fpdf->SetFont('Times','I',8);
        $this->fpdf->Cell(88, 6, 'Report Generated: '.date("d/m/Y h:i:sa"), 0, 0, 'L');
        $this->fpdf->Cell(88, 6, 'Page '.$this->fpdf->PageNo().'/{nb}', 0, 0, 'R');

        $this->fpdf->Output('I', 'ReportCard.pdf');
        exit;
    }

    private function header($form_class_id){
        $logo = public_path('/imgs/logo.png');
        $school = strtoupper(config('app.school_name')).' SCHOOL';
        $primaryRed = config('app.primary_red');        
        $primaryGreen = config('app.primary_green');        
        $primaryBlue = config('app.primary_blue');
        $secondaryRed = config('app.secondary_red');        
        $secondaryGreen = config('app.secondary_green');        
        $secondaryBlue = config('app.secondary_blue');        
        $address = config('app.school_address');
        $contact = config('app.school_contact');

        $this->fpdf->Image($logo, 10, 9, 12);
        $this->fpdf->SetFont('Times', 'B', '18');
        
        //$this->fpdf->SetTextColor(50, 52, 155);
        $this->fpdf->SetTextColor($primaryRed, $primaryGreen, $primaryBlue);
        $this->fpdf->MultiCell(0, 8, $school, 0, 'C' );
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->SetFont('Times', 'I', 10);
        $this->fpdf->MultiCell(0, 6, $address, 0, 'C' );
        $this->fpdf->MultiCell(0, 6, $contact, 0, 'C' );            
        $this->fpdf->SetFont('Times', 'B', 15);
        $this->fpdf->MultiCell(0, 6, $form_class_id.' Class list', 0, 'C' );
        $this->fpdf->Ln(10);

        $this->fpdf->SetFont('Times', 'B', 14);
        $this->fpdf->SetFillColor($secondaryRed, $secondaryGreen, $secondaryBlue);
        $this->fpdf->Cell(88, 8, 'Form Teacher/s: ' , 0 , 0, 'L');
        $this->fpdf->Cell(88, 8, '' , 0 , 0, 'C');
        $this->fpdf->Ln(10);

        $this->fpdf->SetFont('Times', '', 12);
        $this->fpdf->Cell(10, 8, '#' , 0 , 0, 'C', true);
        $this->fpdf->Cell(20, 8, 'Student ID' , 0, 0, 'C', true);
        $this->fpdf->Cell(10, 8, '', 0, 0, 'C', true);
        $this->fpdf->Cell(50, 8, 'Student Name',0, 0, 'L', true);
        $this->fpdf->Cell(25, 8, 'Gender', 0, 0, 'C', true);
        $this->fpdf->Cell(25, 8, 'Date of Birth', 0, 0, 'C', true);
        $this->fpdf->Cell(5, 8, '', 0, 0, 'C', true);
        $this->fpdf->Cell(31, 8, 'Birth Cert. Pin', 0, 0, 'C', true);
        $this->fpdf->Ln();

    }
}
