<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\FormClass;
use App\Models\StudentPersonalData;
use Illuminate\Http\Request;

class ReportDeviceAndInternet extends Controller
{
    protected $pdf;

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }

    public function show ($formLevel = null, $formClass = null)
    {
        date_default_timezone_set('America/Caracas');

        $academicTerm = AcademicTerm::where('is_current', 1)
        ->first();

        $academicYearId = null;

        if($academicTerm){
            $academicYearId = $academicTerm->academic_year_id;
        }

        $academicYear = substr($academicYearId, 0, 4).'-'.substr($academicYearId, 4);

        $this->pdf->AliasNbPages();
        $this->pdf->SetMargins(20, 8);
        $this->pdf->SetAutoPageBreak(false);

        // Default
        $formClasses = FormClass::select('id')->get();

        // Form Level and Form Class
        if($formLevel && $formClass){
            $formClasses = FormClass::where('id', $formClass)
            ->get();
        }

        // Form Level only
        if($formLevel && !$formClass){
            $formClasses = FormClass::where('form_level', $formLevel)
            ->get();
        }

        if(sizeof($formClasses) === 0){
            $this->pdf->AddPage('P', 'Letter');
            $this->header($academicYear);
            $this->tableHeader(null);
            $this->footer();
        }

        $totalStudents = 0; $totalDevices = 0; $totalInternetAccess = 0;
        $totalNoInternet = 0;
        $totalPhones = 0; $totalDesktops = 0; $totalLaptops = 0;
        $totalTablets = 0; $totalOthers = 0; $totalNoDevices = 0;

        foreach($formClasses as $formClassRecord){
            $phones = 0; $desktops = 0; $laptops = 0;
            $tablets = 0; $others = 0; $classNoDevices = 0;
            $classInternetAccess = 0; $classNoInternet = 0;
            $classStudents = 0; $totalClassDevices = 0;

            $this->pdf->AddPage('P', 'Letter');
            $this->header($academicYear);
            $this->tableHeader($formClassRecord->id);
            $data = $this->data($academicYearId, $formClassRecord->id);

            foreach($data as $index => $record){
                $totalStudents++;
                $classStudents++;

                if($record->device_type && $record->device_type !== 'No Device'){
                    $totalDevices++;
                    $totalClassDevices++;
                }

                if(!$record->device_type || $record->device_type === 'No Device'){
                    $totalNoDevices++;
                    $classNoDevices++;
                }

                switch($record->device_type){
                    case 'Phone':
                        $phones++;
                        $totalPhones++;
                        break;
                    case 'Desktop':
                        $desktops++;
                        $totalDesktops++;
                        break;
                    case 'Laptop':
                        $laptops++;
                        $totalLaptops++;
                        break;
                    case 'Tablet':
                        $tablets++;
                        $totalTablets++;
                        break;
                    case 'Other':
                        $others++;
                        $totalOthers++;
                        break;
                }

                if($record->internet_access){
                    $totalInternetAccess++;
                    $classInternetAccess++;
                }
                else{
                    $totalNoInternet++;
                    $classNoInternet++;
                }

                $this->pdf->SetTextColor(0);
                if(
                    !$record->device_type ||
                    $record->device_type === 'No Device' ||
                    !$record->internet_access
                ){
                    $this->pdf->SetTextColor(255, 0, 0);
                }

                $this->pdf->Cell(10, 8, $index+1 , 1 , 0, 'C');
                $this->pdf->Cell(30, 8, $record->student_id , 1, 0, 'C');
                $this->pdf->Cell(5, 8, '', 'LTB', 0, 'L');
                $this->pdf->Cell(56, 8, $record->last_name.', '.$record->first_name, 'TBR', 0, 'L');
                $this->pdf->Cell(25, 8, $record->gender, 1, 0, 'C');
                $deviceType = $record->device_type ? $record->device_type : 'No Device';
                $this->pdf->Cell(20, 8, $deviceType, 1, 0, 'C');
                $internetAccess = $record->internet_access ? 'Yes' : 'No';
                $this->pdf->Cell(30, 8, $internetAccess, 1, 0, 'C');
                $this->pdf->Ln();

                if($index && $index%24 == 0){
                    $this->footer();
                    $this->pdf->AddPage('P', 'Letter');
                    $this->header($academicYear);
                    $this->tableHeader($formClassRecord->id);
                }
            }


            $this->pdf->Ln();
            $this->pdf->SetTextColor(0);
            $this->pdf->SetFont('Times', 'B', 12);
            $this->pdf->Cell(58, 9, 'Class Total: '.$classStudents, 0);
            $this->pdf->Cell(58, 9, 'Total Devices: '.$totalClassDevices, 0);
            $this->pdf->Cell(0, 9, 'Total No Device: '.$classNoDevices, 0);
            $this->pdf->Ln(15);

            $border = 1;
            $this->pdf->SetFillColor(240,240,240);
            $this->pdf->Cell(88, 9, 'Total Internet', $border, 0, 'C', true);
            $this->pdf->Cell(88, 9, 'Total No Internet', $border, 0, 'C', true);
            $this->pdf->Ln();
            $this->pdf->Cell(88, 9, $classInternetAccess, $border, 0, 'C');
            $this->pdf->Cell(88, 9, $classNoInternet, $border, 0, 'C');
            $this->pdf->Ln(15);

            $this->pdf->Cell(29, 9, 'Laptops', $border, 0, 'C', true);
            $this->pdf->Cell(29, 9, 'Desktop', $border, 0, 'C', true);
            $this->pdf->Cell(29, 9, 'Phone', $border, 0, 'C',true);
            $this->pdf->Cell(29, 9, 'Tablet', $border, 0, 'C', true);
            $this->pdf->Cell(29, 9, 'Other', $border, 0, 'C', true);
            $this->pdf->Cell(0, 9, 'Total', $border, 0, 'C', true);
            $this->pdf->Ln();

            $this->pdf->Cell(29, 9, $laptops, $border, 0, 'C');
            $this->pdf->Cell(29, 9, $desktops, $border, 0, 'C');
            $this->pdf->Cell(29, 9, $phones, $border, 0, 'C');
            $this->pdf->Cell(29, 9, $tablets, $border, 0, 'C');
            $this->pdf->Cell(29, 9, $others, $border, 0, 'C');
            $total = $laptops + $desktops + $phones + $tablets + $others;
            $this->pdf->Cell(0, 9, $total, $border, 0, 'C');

            $this->footer();
        }

        if(
            $formLevel && !$formClass ||
            !$formLevel
        ){
            $this->pdf->AddPage('P', 'Letter');
            $this->header($academicYear, $formClassRecord->id);

            $this->pdf->SetFont('Times', 'B', 13);
            if($formLevel){
                $this->pdf->Cell(0, 9, 'FORM '.$formLevel.' SUMMARY DATA', $border, 0, 'C' );
            }
            else{
                $this->pdf->Cell(0, 9, 'SCHOOL SUMMARY DATA', $border, 0, 'C' );
            }

            $this->pdf->SetFont('Times', 'B', 12);
            $this->pdf->Ln();

            $border = 1;
            $this->pdf->Cell(58, 9, 'Total Students', $border, 0, 'C');
            $this->pdf->Cell(58, 9, 'Total Devices', $border, 0, 'C');
            $this->pdf->Cell(0, 9, 'Total Without Device', $border, 0, 'C');
            $this->pdf->Ln();
            $this->pdf->Cell(58, 9, $totalStudents, $border, 0, 'C');
            $this->pdf->Cell(58, 9, $totalDevices, $border, 0, 'C');
            $this->pdf->Cell(0, 9, $totalNoDevices, $border, 0, 'C');
            $this->pdf->Ln(15);

            $border = 1;

            $this->pdf->SetFillColor(240,240,240);
            $this->pdf->Cell(88, 9, 'Total Internet', $border, 0, 'C', true);
            $this->pdf->Cell(88, 9, 'Total Without Internet', $border, 0, 'C', true);
            $this->pdf->Ln();

            $this->pdf->Cell(88, 9, $totalInternetAccess, $border, 0, 'C');
            $this->pdf->Cell(88, 9, $totalNoInternet, $border, 0, 'C');
            $this->pdf->Ln(15);

            $this->pdf->Cell(29, 9, 'Laptops', $border, 0, 'C', true);
            $this->pdf->Cell(29, 9, 'Desktop', $border, 0, 'C', true);
            $this->pdf->Cell(29, 9, 'Phone', $border, 0, 'C',true);
            $this->pdf->Cell(29, 9, 'Tablet', $border, 0, 'C', true);
            $this->pdf->Cell(29, 9, 'Other', $border, 0, 'C', true);
            $this->pdf->Cell(0, 9, 'Total', $border, 0, 'C', true);
            $this->pdf->Ln();

            $this->pdf->Cell(29, 9, $totalLaptops, $border, 0, 'C');
            $this->pdf->Cell(29, 9, $totalDesktops, $border, 0, 'C');
            $this->pdf->Cell(29, 9, $totalPhones, $border, 0, 'C');
            $this->pdf->Cell(29, 9, $totalTablets, $border, 0, 'C');
            $this->pdf->Cell(29, 9, $totalOthers, $border, 0, 'C');
            $total = $totalLaptops + $totalDesktops + $totalPhones + $totalTablets + $totalOthers;
            $this->pdf->Cell(0, 9, $total, $border, 0, 'C');

        }
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
        $this->pdf->SetTextColor(0);
        $this->pdf->SetFont('Times', 'I', 10);
        $this->pdf->MultiCell(0, 6, $address, 0, 'C' );
        $this->pdf->MultiCell(0, 6, $contact, 0, 'C' );

        $this->pdf->SetTextColor(0);
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Ln();
        $this->pdf->MultiCell(0,6, 'DEVICE AND INTERNET ACCESS '.$academicYear, 0, 'C');
        $this->pdf->Ln(3);


    }

    private function tableHeader ($formClassId)
    {
        $border= 1;
        $this->pdf->SetDrawColor(220,220,220);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(0, 8, 'Form Class: '.$formClassId , 0 , 0, 'L');
        $this->pdf->Ln();

        $this->pdf->Cell(10, 8, '#' , $border , 0, 'C');
        $this->pdf->Cell(30, 8, 'Student ID' , $border, 0, 'C');
        $this->pdf->Cell(5, 8, '', 'LTB', 0, 'L');
        $this->pdf->Cell(56, 8, 'Student Name', 'TBR', 0, 'L');
        $this->pdf->Cell(25, 8, 'Gender', $border, 0, 'C');
        $this->pdf->Cell(20, 8, 'Device', $border, 0, 'C');
        $this->pdf->Cell(30, 8, 'Internet Access', $border, 0, 'C');

        $this->pdf->SetFont('Times', '', 12);
        $this->pdf->Ln();
    }

    private function data($academicYearId, $formClassId)
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
            'student_class_registrations.form_class_id',
            'student_data_personal.internet_access',
            'student_data_personal.device_type'
        )
        ->where([
            ['student_class_registrations.academic_year_id', $academicYearId],
            ['student_class_registrations.form_class_id', $formClassId]
        ])
        ->orderBy('form_class_id')
        // ->orderByRaw("CASE WHEN device_type IS NULL THEN 1 ELSE 0 END")
        ->orderByRaw("device_type = 'No Device' OR  device_type IS null")
        ->orderBy('internet_access', 'desc')
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

        return $students;
    }

    private function footer ()
    {
        $this->pdf->SetTextColor(0);
        $this->pdf->setY(-15);
        $this->pdf->SetFont('Times', 'I', 8);
        $this->pdf->Cell(45, 8, 'Generated at '.date('d M Y h:i a'));
        $this->pdf->Cell(0, 8, 'Page: '.$this->pdf->PageNo().'/{nb}', 0, 0, 'R');
    }
}
