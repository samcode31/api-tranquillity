<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\FormClass;
use App\Models\Student;
use App\Models\StudentClassRegistration;
use App\Models\StudentPersonalData;
use App\Models\StudentFamilyData;
use Illuminate\Http\Request;

class RegistrationStatus extends Controller
{
    protected $pdf;

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }

    public function show ($formLevel = null)
    {

        date_default_timezone_set('America/Caracas');
        $this->pdf->AliasNbPages();
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->SetMargins(8, 8);
        $this->pdf->AddPage('P', 'Letter');

        $this->header();

        $formClasses = FormClass::where('form_level', $formLevel)
        ->get();

        $totalNotRegistered = 0; $total = 0;

        // return $formClasses;
        // return $this->data('1:1');

        foreach($formClasses as $index => $formClass){
            $records = 0; $notRegistered = 0;
            $data = $this->data($formClass->id);

            foreach($data as $record){
                $total++;
                $status = $record->status ? "Registered" : "Not Registered";
                $this->pdf->SetTextColor(0);
                if($status === "Not Registered"){
                    $this->pdf->SetTextColor(255, 0, 0);
                    $notRegistered++;
                    $totalNotRegistered++;
                }
                $border = 1;
                $this->pdf->SetFont('Times', '', 11);
                $this->pdf->Cell(10, 8, ++$records , $border , 0, 'C');
                $this->pdf->Cell(25, 8, $record->student_id , $border, 0, 'C');
                $name = ucfirst(strtolower($record->last_name)).', '.ucfirst(strtolower($record->first_name));
                $this->pdf->Cell(61, 8, $name, $border, 0, 'L');
                $gender = $record->gender ? $record->gender[0] : null;
                $this->pdf->Cell(20, 8, $gender, $border, 0, 'C');
                $this->pdf->Cell(30, 8, $record->date_of_birth, $border, 0, 'C');
                $this->pdf->Cell(24, 8, $record->form_class_id, $border, 0, 'C');
                $this->pdf->Cell(30, 8, $status, $border, 0, 'C');
                $this->pdf->SetFont('Times', '', 11);
                $this->pdf->SetDrawColor(219, 219, 219);
                $this->pdf->Ln();

                if($records%25==0){
                    $this->footer();
                    $this->pdf->AddPage('P', 'Letter');
                    $this->header();
                }
            }
            $this->pdf->Ln();
            $border = 0;
            $this->pdf->SetTextColor(0);
            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(50, 8, "Class Total: ".$records, $border, 0, 'L');
            $this->pdf->Cell(50, 8, "Registered: ".($records - $notRegistered), $border, 0, 'L');
            $this->pdf->Cell(50, 8, "Not Registered: ".$notRegistered, $border, 0, 'L');
            $this->pdf->SetFont('Times', '', 11);

            if($index < sizeof($formClasses) -1){
                $this->footer();
                $this->pdf->AddPage('P', 'Letter');
                $this->header();
            }
            else{
                $this->pdf->Ln(15);
                $border = 0;
                $this->pdf->SetTextColor(0);
                $this->pdf->SetFont('Times', 'B', 11);
                $this->pdf->Cell(50, 8, "Total Students: ".$total, $border, 0, 'L');
                $this->pdf->Cell(50, 8, "Total Registered: ".($total - $totalNotRegistered), $border, 0, 'L');
                $this->pdf->Cell(50, 8, "Total Not Registered: ".$totalNotRegistered, $border, 0, 'L');
                $this->pdf->SetFont('Times', '', 11);
                $this->footer();
            }

        }



        $this->pdf->Output('I', 'Registration Status.pdf');
        exit;
    }

    private function header ()
    {
        $logo = public_path('/imgs/logo.png');
        $school = strtoupper(config('app.school_name'));
        $address = config('app.school_address');
        $contact = config('app.school_contact');

        $this->pdf->Image($logo, 10, 6, 23);

        $this->pdf->SetFont('Times', 'B', '15');
        $this->pdf->SetTextColor(0);
        $this->pdf->MultiCell(0, 8, $school, 0, 'C' );

        $this->pdf->SetFont('Times', 'I', 10);
        $this->pdf->MultiCell(0, 6, $address, 0, 'C' );
        $this->pdf->MultiCell(0, 6, $contact, 0, 'C' );
        $this->pdf->Ln();

        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->MultiCell(0,6, 'REGISTRATION STATUS REPORT ('.date("d-M-Y").')', 0, 'C');
        $this->pdf->Ln();

        $border = 1;
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->SetDrawColor(219, 219, 219);
        $this->pdf->Cell(10, 8, '#' , $border , 0, 'C');
        $this->pdf->Cell(25, 8, 'Student ID' , $border, 0, 'C');
        $this->pdf->Cell(61, 8, 'Student Name', $border, 0, 'L');
        $this->pdf->Cell(20, 8, 'Gender', $border, 0, 'C');
        $this->pdf->Cell(30, 8, 'Date of Birth', $border, 0, 'C');
        $this->pdf->Cell(24, 8, 'Form Class', $border, 0, 'C');
        $this->pdf->Cell(30, 8, 'Status', $border, 0, 'C');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Ln();
    }

    private function data ($formClassId)
    {
        $registered = []; $unregistered = [];

        // $academicTerm = AcademicTerm::where('is_current', 1)
        // ->first();

        // $academicYearId = null;

        // if($academicTerm){
        //     $academicYearId = $academicTerm->academic_year_id;
        // }

        $academicYearId = '20222023';

        $students = StudentClassRegistration::join(
            'students',
            'students.id',
            'student_class_registrations.student_id'
        )
        ->where([
            ['academic_year_id', $academicYearId],
            ['form_class_id', $formClassId]
        ])
        ->select(
            'student_id',
            'first_name',
            'last_name',
            'form_class_id',
            'gender',
            'date_of_birth'
        )
        ->orderBy('form_class_id')
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

        foreach($students as $studentRecord){
            $studentRecord->status = 1;

            $studentData = StudentPersonalData::where('student_id', $studentRecord->student_id)
            ->first();

            $studentDataFamily = StudentFamilyData::where(
                'student_id',
                $studentRecord->student_id
            )->first();

            if(
                (
                    $studentData &&
                    !$studentData->address_home &&
                    !$studentData->town &&
                    !$studentData->phone_mobile &&
                    !$studentData->email &&
                    !$studentData->blood_group &&
                    !$studentData->previous_school &&
                    !$studentData->religion_id &&
                    !$studentData->ethnic_group_id &&
                    !$studentData->living_status_id &&
                    !$studentData->device_type
                ) ||
                !$studentDataFamily
            ){
                $studentRecord->status = 0;
                array_push($unregistered, $studentRecord);
            }
            else{
                array_push($registered, $studentRecord);
            }
        }

        return array_merge($registered, $unregistered);

    }

    private function footer ()
    {
        $this->pdf->setY(-15);
        $this->pdf->SetFont('Times', 'I', 8);
        $this->pdf->Cell(40, 8, 'Generated at '.date('d M Y h:i a'));
        $this->pdf->Cell(0, 8, 'Page: '.$this->pdf->PageNo().'/{nb}',0, 0, 'R');
    }
}
