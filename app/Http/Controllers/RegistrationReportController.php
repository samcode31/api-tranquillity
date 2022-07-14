<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\FormClass;
use App\Models\Student;
use App\Models\StudentFamilyData;
use App\Models\StudentPersonalData;
use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf;

class RegistrationReportController extends Controller
{
    private $fpdf;
    private $widths;
    private $aligns;

    protected $pdf;

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }



    public function createPDF($id)
    {
        $record = Student::whereId($id)->first();
        $school = config('app.school_name');
        $address = config('app.school_address');
        $address_line_2 = config('app.school_address_line_2');
        $contact = config('app.school_contact');
        $entryDate = config('app.school_entry_date');
        $declaration = "I am in agreement and will abide with the school's policies, ".
        "procedures and rules which are clearly identified in the School's Prospectus.".
        " I will make every effort to have my child uphold the code of conduct and rules of the school";

        $student = Student::where('id', $id)->first();
        $studentDataPersonal = StudentDataPersonal::where('student_id', $id)->first();
        $studentDataFamily = StudentDataFamily::where('student_id', $id)->get();
        $studentDataMedical = StudentDataMedical::where('student_id', $id)->first();
        $studentDataFiles = StudentDataFiles::where('student_id', $id)->first();
        $academic_year_id = AcademicTerm::where('is_current', 1)->first()->academic_year_id;

        $religion_id = $studentDataPersonal->religion_id ? $studentDataPersonal->religion_id : null;

        $religion = Religion::where('id', $religion_id)->first();
        $religiousGroup = $religion ? $religion->grouping : null;
        $ethnic_group = EthnicGroup::where('id', $studentDataPersonal->ethnic_group_id)->first();
        $ethnicGroup = $ethnic_group ? $ethnic_group->group_type : null;

        $fatherRecord = new StudentDataFamily();
        $motherRecord = new StudentDataFamily();
        $guardianRecord = new StudentDataFamily();
        $emergencyRecord = new StudentDataFamily();

        foreach($studentDataFamily as $record){
            switch($record->relationship){
                case 1:
                    $fatherRecord = $record;
                    break;
                case 2:
                    $motherRecord = $record;
                    break;
                case 3:
                    $guardianRecord = $record;
                    break;
                case 4:
                    $emergencyRecord = $record;
                    break;
            }
        }

        $birthCertificate = $studentDataFiles->file_birth_certificate ?  3 : null;
        $seaSlip = $studentDataFiles->file_sea_slip ? 3 : null;
        $immunizationCard = $studentDataFiles->file_immunization_card ? 3 : null;
        $passportPhoto = $studentDataFiles->file_photo ? 3 : null;
        $photo = $studentDataFiles->file_photo;
        $photo = $photo ?  public_path('/storage/'.$photo) : null;



        $imigrationPermit = ($studentDataPersonal->immigration_permit == 0) ? "No" : "Yes";
        $dob = date_format(date_create($student->date_of_birth), 'j M Y');
        $class_id = StudentClassRegistration::where([
            ['student_id', $id],
            ['academic_year_id', $academic_year_id]
        ])->first();

        if($class_id){
            $class_id = $class_id->form_class_id;
        }
        else{
            $class_id = StudentClassRegistration::where([
                ['student_id', $id],
            ])->first();
            $class_id = $class_id ? $class_id->form_class_id : null;
        }

        $r = config('app.primary_red');
        $g = config('app.primary_green');
        $b = config('app.primary_blue');
        $house = "";
        $border = 0;
        $cellBorder = 1;
        $logo = public_path('/imgs/logo.png');


        $this->fpdf = new Fpdf;

        $this->fpdf->AddPage("P", 'Legal');
        $this->fpdf->SetMargins(10, 8);
        $this->fpdf->SetDrawColor(220, 220, 220);

        $this->fpdf->SetFont('Times', 'B', '18');
        $this->fpdf->Image($logo, 10, 8, 30);
        $this->fpdf->Rect(176, 9, 30, 28);
        $this->fpdf->SetFont('Times', '', '9');
        $x = $this->fpdf->GetX();
        $y = $this->fpdf->GetY();
        $this->fpdf->SetXY(176, 18);
        $this->fpdf->SetTextColor(64, 64, 64);
        $ext = pathinfo($photo, PATHINFO_EXTENSION);
        // $this->fpdf->Cell(30, 6, $ext, 0, 0, 'C');
        $this->fpdf->Cell(30, 6, "", 0, 0, 'C');

        $this->fpdf->SetTextColor(0);
        $this->fpdf->SetXY($x, $y);
        if($photo && $ext != 'pdf' && ($ext == 'jpeg' || $ext == 'png' || $ext = 'jpg' )){
            $this->fpdf->Image($photo, 181, 6, 25);
        }

        // $this->fpdf->Cell(20, 6, , 0, 0, 'C');

        $this->fpdf->SetFillColor(255, 255, 255);
        $this->fpdf->Rect(181, 0, 25, 8, 'F');
        $this->fpdf->Rect(181, 38, 25, 15, 'F');

        $this->fpdf->SetTextColor($r, $g, $b);
        $this->fpdf->SetFont('Times', 'B', '16');
        $this->fpdf->MultiCell(0, 6, strtoupper($school), $border, 'C');
        $this->fpdf->SetTextColor(0);
        $this->fpdf->SetFont('Times', 'I', 9);
        $this->fpdf->MultiCell(0, 5, $address, $border, 'C' );
        $this->fpdf->MultiCell(0, 5, $address_line_2, $border, 'C' );
        //$this->fpdf->Line(10, 30, 206, 30);
        $this->fpdf->SetFont('Times', 'B', '14');
        $this->fpdf->Ln(8);

        $this->fpdf->MultiCell(0, 5, 'STUDENT REGISTRATION FORM', $border, 'C' );
        $this->fpdf->Ln();

        $x = $this->fpdf->GetX();
        $y = $this->fpdf->GetY() - 3;
        $this->fpdf->SetFillColor(240, 240, 240);
        $this->fpdf->Rect($x, $y, 196, 45, 'F');
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(25, 6, 'Student ID#', 0, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(30, 6, $id, $cellBorder, 0, 'C');
        $this->fpdf->Cell(55, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(60, 6, 'Copy of Original Birth Certificate', 0, 0, 'R');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $birthCertificate, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 11);
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Ln(10);

        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(25, 6, 'Class', 0, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(30, 6, $class_id, $cellBorder, 0, 'C');
        $this->fpdf->Cell(55, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(60, 6, 'Passport Photo', 0, 0, 'R');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $passportPhoto, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 11);
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Ln(10);

        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(25, 6, 'Entry Date', 0, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(30, 6, $entryDate, $cellBorder, 0, 'C');
        $this->fpdf->Cell(55, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(60, 6, 'SEA Placement Slip', 0, 0, 'R');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $seaSlip, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 11);
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Ln(10);

        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(25, 6, 'House', 0, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(30, 6, $house, $cellBorder, 0, 'L');
        $this->fpdf->Cell(55, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(60, 6, 'Copy of Immunization Card', 0, 0, 'R');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $immunizationCard, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 11);
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Ln(10);

        $this->fpdf->SetFillColor($r, $g, $b);
        $this->fpdf->SetTextColor(255, 255, 255);
        $this->fpdf->SetFont('Times', 'B', '10');
        $this->fpdf->MultiCell(196, 6, 'STUDENT INFORMATION', 0, 'C', true);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln(5);

        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(68, 6, $student->first_name, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(50, 6, $student->last_name, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(68, 6, $student->other_name, $cellBorder, 0, 'C');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(68, 6, 'First Name', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(50, 6, 'Last Name', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(68, 6, 'Other Names', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln(8);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(145, 6, $studentDataPersonal->address_home, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(46, 6, $studentDataPersonal->town, $cellBorder, 0, 'C');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(140, 6, 'Address', 0, 0, 'C');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(46, 6, 'Town', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln(8);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(64, 6, $this->formatNumber($studentDataPersonal->phone_mobile), $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(85, 6, $studentDataPersonal->email, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(37, 6, $studentDataPersonal->blood_group, $cellBorder, 0, 'C');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(64, 6, 'Telphone', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(85, 6, 'Email', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(37, 6, 'Blood Type', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln(8);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(64, 6, $dob, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(64, 6, $student->birth_certificate_pin, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(58, 6, $student->gender, $cellBorder, 0, 'C');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(59, 6, 'Date of Birth', 0, 0, 'C');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(59, 6, 'Birth Certificate Pin', 0, 0, 'C');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(58, 6, 'Gender', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln(8);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(118, 6, $studentDataPersonal->country_of_birth, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(73, 6, $studentDataPersonal->nationality, $cellBorder, 0, 'C');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(118, 6, 'Country of birth', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(73, 6, 'Nationality', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);

        // $this->fpdf->Ln(8);
        // $this->fpdf->SetFont('Times', '', '11');
        // $this->fpdf->Cell(64, 6, $imigrationPermit, $cellBorder, 0, 'C');
        // $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        // $this->fpdf->Cell(59, 6, $studentDataPersonal->date_of_issue, $cellBorder, 0, 'C');
        // $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        // $this->fpdf->Cell(63, 6, $studentDataPersonal->date_of_expiry, $cellBorder, 0, 'C');
        // $this->fpdf->Ln();
        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(59, 6, 'Immigration Permit', 0, 0, 'C');
        // $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        // $this->fpdf->Cell(59, 6, 'Permit Issue Date', 0, 0, 'C');
        // $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        // $this->fpdf->Cell(58, 6, 'Permit Expiry Date', 0, 0, 'C');
        // $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(8);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(140, 6, $studentDataPersonal->previous_school, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(51, 6, $student->sea_number, $cellBorder, 0, 'C');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(140, 6, 'Previous School', 0, 0, 'C');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(46, 6, 'Sea Number', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(8);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(118, 6, $religiousGroup, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(73, 6, $ethnicGroup, $cellBorder, 0, 'C');
        // $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        // $this->fpdf->Cell(63, 6, $studentDataPersonal->second_language, $cellBorder, 0, 'C');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(113, 6, 'Religion', 0, 0, 'C');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(73, 6, 'Ethnic Group', 0, 0, 'C');
        // $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        // $this->fpdf->Cell(58, 6, 'Second Language', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(10);
        $this->fpdf->SetFillColor($r, $g, $b);
        $this->fpdf->SetTextColor(255, 255, 255);
        $this->fpdf->SetFont('Times', 'B', '10');
        $this->fpdf->MultiCell(196, 6, 'FAMILY INFORMATION', 0, 'C', true);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(5);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(53, 6, 'Father', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(53, 6, 'Mother', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(53, 6, 'Guardian', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Name', 0, 0, 'L');
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, ucwords(strtolower($fatherRecord->name)), 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, ucwords(strtolower($motherRecord->name)), 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, ucwords(strtolower($guardianRecord->name)), 1, 0, 'C');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Marital Status', 0, 0, 'L');
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $fatherRecord->marital_status, 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $motherRecord->marital_status, 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $guardianRecord->marital_status, 1, 0, 'C');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Home Phone', 0, 0, 'L');
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $this->formatNumber($fatherRecord->phone_home), 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $this->formatNumber($motherRecord->phone_home), 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $this->formatNumber($guardianRecord->phone_home), 1, 0, 'C');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Work Phone', 0, 0, 'L');
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $this->formatNumber($fatherRecord->phone_work), 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $this->formatNumber($motherRecord->phone_work), 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $this->formatNumber($guardianRecord->phone_work), 1, 0, 'C');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Mobile Phone', 0, 0, 'L');
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $this->formatNumber($fatherRecord->phone_mobile), 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $this->formatNumber($motherRecord->phone_mobile), 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6,$this->formatNumber( $guardianRecord->phone_mobile), 1, 0, 'C');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'ID Card #', 0, 0, 'L');
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $fatherRecord->id_card, 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $motherRecord->id_card, 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $guardianRecord->id_card, 1, 0, 'C');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(115, 6, 'Father Address', 0, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(75, 6, 'Father Email', 0, 0, 'L');
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(0);
        $this->fpdf->Cell(115, 6, $fatherRecord->address_home, 1, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(75, 6, $fatherRecord->email, 1, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(115, 6, 'Mother Address', 0, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(75, 6, 'Mother Email', 0, 0, 'L');
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(0);
        $this->fpdf->Cell(115, 6, $motherRecord->address_home, 1, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(75, 6, $motherRecord->email, 1, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(115, 6, 'Guardian Address', 0, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(75, 6, 'Guardian Email', 0, 0, 'L');
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(0);
        $this->fpdf->Cell(115, 6, $guardianRecord->address_home, 1, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(75, 6, $guardianRecord->email, 1, 0, 'L');

        $this->fpdf->AddPage("P", 'Legal');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(62, 6, 'Number of Children in Family', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, 'Number of Children at Home', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, 'Place in Family', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(62, 6, $studentDataPersonal->no_in_family, 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, $studentDataPersonal->no_at_home, 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, $studentDataPersonal->place_in_family, 1, 0, 'C');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(0, 6, 'Emergency Contact', 0, 0, 'L');
        $this->fpdf->Ln();
        $this->fpdf->Cell(0, 6, $emergencyRecord->name, 1, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(62, 6, 'Telephone Number', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, 'Relation to Child', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, 'Workplace Number', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(62, 6, $this->formatNumber($emergencyRecord->phone_home), 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, $emergencyRecord->relation, 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, $this->formatNumber($emergencyRecord->phone_work), 1, 0, 'C');

        $this->fpdf->Ln(10);
        $this->fpdf->SetFillColor($r, $g, $b);
        $this->fpdf->SetTextColor(255, 255, 255);
        $this->fpdf->SetFont('Times', 'B', '10');
        $this->fpdf->MultiCell(196, 6, 'HEALTH HISTORY', 0, 'C', true);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln(5);

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(0, 6, 'Immunization Records', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Hepatitis', 0, 0, 'L');
        $check = ($studentDataMedical->hepatitis == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Polio', 0, 0, 'L');
        $check = ($studentDataMedical->polio == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Diptheria', 0, 0, 'L');
        $check = ($studentDataMedical->diphtheria == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Tetanus', 0, 0, 'L');
        $check = ($studentDataMedical->tetanus == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Yellow Fever', 0, 0, 'L');
        $check = ($studentDataMedical->yellow_fever == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Measles', 0, 0, 'L');
        $check = ($studentDataMedical->measles == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'TB', 0, 0, 'L');
        $check = ($studentDataMedical->tb == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Chicken Pox', 0, 0, 'L');
        $check = ($studentDataMedical->chicken_pox == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Typhoid', 0, 0, 'L');
        $check = ($studentDataMedical->typhoid == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(9, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(27, 6, 'Rheumatic Fever', 0, 0, 'L');
        $check = ($studentDataMedical->rheumatic_fever == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(0, 6, 'Please indicate [by ticking] whether the child suffers from any of the following:', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Poor Eyesight', 0, 0, 'L');
        $check = ($studentDataMedical->poor_eyesight == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');


        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Poor Hearing', 0, 0, 'L');
        $check = ($studentDataMedical->poor_hearing == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Diabetes', 0, 0, 'L');
        $check = ($studentDataMedical->diabetes == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Asthma', 0, 0, 'L');
        $check = ($studentDataMedical->asthma == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Epilepsy', 0, 0, 'L');
        $check = ($studentDataMedical->epilepsy == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->Ln(11);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(0, 6, 'Other Health Condition', 0, 0, 'L');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln();
        $this->fpdf->Cell(0, 6, $studentDataMedical->other, 1, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(0, 6, 'Allergies', 0, 0, 'L');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln();
        $this->fpdf->Cell(0, 6, $studentDataMedical->allergies, 1, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetFillColor($r, $g, $b);
        $this->fpdf->SetTextColor(255, 255, 255);
        $this->fpdf->SetFont('Times', 'B', '10');
        $this->fpdf->MultiCell(196, 6, 'OTHER INFORMATION', 0, 'C', true);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(0, 6, 'Extra-Curricular Interests', 0, 0, 'L');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln();
        $this->fpdf->Cell(0, 6, $studentDataPersonal->extra_curricular, 1, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(80, 6, 'Would you child require School Feeding Meals?', 0, 0, 'L');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        $checkYes = ($studentDataPersonal->school_feeding == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'No', 0, 0, 'L');
        $checkNo = ($studentDataPersonal->school_feeding == 0) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');

        // $this->fpdf->Ln(10);
        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(80, 6, 'Does you child access Social Welfare Grant?', 0, 0, 'L');
        // $this->fpdf->SetTextColor(0, 0, 0);
        // $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        // $this->fpdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        // $checkYes = ($studentDataPersonal->social_welfare == 1) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        // $this->fpdf->Cell(10, 6, 'No', 0, 0, 'L');
        // $checkNo = ($studentDataPersonal->social_welfare == 0) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->Cell(10, 6, '', 0, 0, 'L');

        // $this->fpdf->Ln(10);
        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(80, 6, 'Does you child access School Transport?', 0, 0, 'L');
        // $this->fpdf->SetTextColor(0, 0, 0);
        // $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        // $this->fpdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        // $checkYes = ($studentDataPersonal->school_transport == 1) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        // $this->fpdf->Cell(10, 6, 'No', 0, 0, 'L');
        // $checkNo = ($studentDataPersonal->school_transport == 0) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->Cell(10, 6, '', 0, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(80, 6, 'Does you child have internet access?', 0, 0, 'L');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        $checkYes = ($studentDataPersonal->internet_access == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'No', 0, 0, 'L');
        $checkNo = ($studentDataPersonal->internet_access == 0) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(60, 6, 'What type of device does you child have access to?', 0, 0, 'L');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(30, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(42, 6, $studentDataPersonal->device_type, 1, 0, 'L');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');

        // $this->fpdf->Ln(15);
        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(30, 10, 'Student\' Signature', 0, 0, 'L');
        // $this->fpdf->SetTextColor(0, 0, 0);
        // $this->fpdf->SetFillColor(220, 220, 220);
        // $this->fpdf->Cell(60, 10, '', 0, 0, 'L', true);

        $this->fpdf->Ln(20);
        $this->fpdf->SetFont('Times', 'BU', '10');
        $this->fpdf->Cell(0, 6, 'Declaration', 0, 0, 'L');
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Ln(10);
        $this->fpdf->MultiCell(0, 6, $declaration, 0, 'L');
        $this->fpdf->Ln();
        $this->fpdf->SetFillColor(240, 240, 240);
        $this->fpdf->Cell(60, 10, '', 'B', 0, 'C', true);
        $this->fpdf->Cell(18, 10, '', 0, 0, 'C');
        $this->fpdf->Cell(60, 10, '', 'B', 0, 'C', true);
        $this->fpdf->Cell(18, 10, '', 0, 0, 'C');
        $this->fpdf->Cell(40, 10, '', 'B', 0, 'C', true);

        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', 'I', '10');
        $this->fpdf->Cell(60, 10, 'Student\'s Signature', 0, 0, 'C');
        $this->fpdf->Cell(18, 10, '', 0, 0, 'C');
        $this->fpdf->Cell(60, 10, 'Parent\'s / Guardian\'s Signature', 0, 0, 'C');
        $this->fpdf->Cell(18, 10, '', 0, 0, 'C');
        $this->fpdf->Cell(40, 10, 'Date', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->SetFillColor(220, 220, 220);


        $this->fpdf->Ln();



        $this->fpdf->Output();
        exit;
        // $student = Student::whereId($id)->get();
        // $studentFirstName = $student[0]->first_name;
        // return $studentFirstName;
    }

    private function Header($formClass=null)
    {
        $logo = public_path('/imgs/logo.png');
        $school = config('app.school_name');
        $address = config('app.school_address');
        $contact = config('app.school_contact');
        $border = 'TB';

        $academicTerm = AcademicTerm::where('is_current', 1)
        ->first();

        $academicYearId = null;

        if($academicTerm){
            $academicYearId = $academicTerm->academic_year_id;
        }

        $this->pdf->SetMargins(10, 8);
        $this->pdf->Image($logo, 10, 9, 15);
        $this->pdf->SetFont('Times', 'B', '16');
        $this->pdf->MultiCell(0, 8, $school, 0, 'C' );
        $this->pdf->SetFont('Times', 'I', 10);
        $this->pdf->MultiCell(0, 6, $address.'. '.$contact, 0, 'C' );
        $this->pdf->Ln(10);
        $this->pdf->SetFont('Times', 'B', 14);
        $this->pdf->Cell(129.9, 6, 'Student Contact List: '.$formClass, 0, 0, 'L');
        $academicYear = $academicYearId ? substr($academicYearId, 0, 4).'-'.substr($academicYearId, 4) : null;
        $this->pdf->Cell(129.5, 6, $academicYear, 0, 0, 'R');
        $this->pdf->Ln(10);
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->Cell(20, 6, 'ID#', $border, 0, 'C', true);
        $this->pdf->Cell(30, 6, 'Name', $border, 0, 'L', true);
        $this->pdf->Cell(15, 6, 'Gender', $border, 0, 'C', true);
        $this->pdf->Cell(25, 6, 'D.O.B', $border, 0, 'C', true);
        $this->pdf->Cell(25, 6, 'Phone(C)', $border, 0, 'C', true);
        $this->pdf->Cell(50, 6, 'Email', $border, 0, 'L', true);
        $this->pdf->Cell(94.4, 6, 'Parent / Guardian Contact Information', $border, 0, 'L', true);
        //$this->pdf->Cell(20, 6, 'Class', $border, 0, 'C', true);
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->Ln();
    }









    private function SetDash($black=null, $white=null)
    {
        if($black!==null)
            $s=sprintf('[%.3F %.3F] 0 d',$black*$this->pdf->k,$white*$this->pdf->k);
        else
            $s='[] 0 d';
        $this->pdf->_out($s);
    }

    public function RegistrationStatus ()
    {
        $this->pdf->SetMargins(10, 10);
        $this->pdf->AddPage('P', 'Letter');
        $this->pdf->SetFont('Times', 'B', '15');
        $this->pdf->MultiCell(0,6, 'END OF TERM REPORT', 0, 'C');
        $this->pdf->Output('I', 'ReportCard.pdf');
    }

    private function Footer ()
    {
        $this->pdf->setY(-15);
        $this->pdf->SetFont('Times', 'I', 8);
        $this->pdf->Cell(40, 8, 'Generated at '.date('d M Y h:i a'));
        $this->pdf->Cell(0, 8, 'Page: '.$this->pdf->PageNo().'/{nb}',0, 0, 'R');
    }

}
