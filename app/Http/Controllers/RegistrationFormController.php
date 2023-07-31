<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\EthnicGroup;
use App\Models\Religion;
use App\Models\Student;
use App\Models\StudentPersonalData as StudentDataPersonal;
use App\Models\StudentFamilyData as StudentDataFamily;
use App\Models\StudentMedicalData as StudentDataMedical;
use App\Models\StudentDataFile as StudentDataFiles;
use App\Models\StudentPicture;
use App\Models\StudentHouseAssignment;
use App\Models\AcademicTerm;
use App\Models\StudentClassRegistration;
use Codedge\pdf\pdf\pdf;

class RegistrationFormController extends Controller
{
    // private $pdf;
    protected $pdf;

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }


    public function createPDF($id = null)
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
        if(!$student) $student = new Student;
        $studentDataPersonal = StudentDataPersonal::where('student_id', $id)->first();
        if(!$studentDataPersonal) $studentDataPersonal = new StudentDataPersonal;
        $studentDataFamily = StudentDataFamily::where('student_id', $id)->get();
        if(!$studentDataFamily) $studentDataFamily = new StudentDataFamily;
        $studentDataMedical = StudentDataMedical::where('student_id', $id)->first();
        if(!$studentDataMedical) $studentDataMedical = new StudentDataMedical;
        $studentDataFiles = StudentDataFiles::where('student_id', $id)->first();
        if(!$studentDataFiles) $studentDataFiles = new StudentDataFiles;
        $studentPicture = StudentPicture::where('student_id', $id)->first();
        if(!$studentPicture) $studentPicture = new StudentPicture;
        $studentHouse = StudentHouseAssignment::join(
            'houses',
            'student_house_assignments.house_id',
            'houses.id'
        )
        ->where('student_id', $id)->first();
        if(!$studentHouse) $studentHouse = new StudentHouseAssignment;
        $academic_year_id = AcademicTerm::where('is_current', 1)->first()->academic_year_id;


        $religion_id = ($studentDataPersonal && $studentDataPersonal->religion_id) ? $studentDataPersonal->religion_id : null;

        $religion = Religion::where('id', $religion_id)->first();
        $religiousGroup = $religion ? $religion->grouping : null;
        $ethnic_group = null;
        if($studentDataPersonal){
            $ethnic_group = EthnicGroup::where('id', $studentDataPersonal->ethnic_group_id)->first();
        }
        $ethnicGroup = $ethnic_group ? $ethnic_group->grouping : null;

        $houseAssignment = $studentHouse ? $studentHouse->name : null;

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

        $birthCertificate = ($studentDataFiles && $studentDataFiles->file_birth_certificate) ?  3 : null;
        $seaSlip = ($studentDataFiles && $studentDataFiles->file_sea_slip) ? 3 : null;
        $immunizationCard = ($studentDataFiles && $studentDataFiles->file_immunization_card) ? 3 : null;
        $passportPhoto = ($studentDataFiles && $studentDataFiles->file_photo) ? 3 : null;
        $photo = $studentPicture ? $studentPicture->file : null;
        $photo = $photo && file_exists(public_path('/storage/pics/'.$photo)) ?  public_path('/storage/pics/'.$photo) : null;



        $imigrationPermit = ($studentDataPersonal && $studentDataPersonal->immigration_permit == 0) ? "No" : "Yes";
        $dob = ($student && $student->date_of_birth) ? date_format(date_create($student->date_of_birth), 'j M Y')  : null;
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


        // $this->pdf = new pdf;

        $this->pdf->AddPage("P", 'Legal');
        $this->pdf->SetMargins(10, 8);
        $this->pdf->SetDrawColor(220, 220, 220);

        $this->pdf->SetFont('Times', 'B', '18');
        $this->pdf->Image($logo, 10, 8, 30);
        $this->pdf->Rect(175, 9, 30, 28);
        $this->pdf->SetFont('Times', '', '9');
        $x = $this->pdf->GetX();
        $y = $this->pdf->GetY();
        $this->pdf->SetXY(176, 18);
        $this->pdf->SetTextColor(64, 64, 64);
        $ext = pathinfo($photo, PATHINFO_EXTENSION);
        // $this->pdf->Cell(30, 6, $ext, 0, 0, 'C');
        $this->pdf->Cell(30, 6, "", 0, 0, 'C');

        $this->pdf->SetTextColor(0);
        $this->pdf->SetXY($x, $y);
        if($photo && $ext != 'pdf' && ($ext == 'jpeg' || $ext == 'png' || $ext = 'jpg' )){
            $this->pdf->Image($photo, 175, 6, 30);
        }

        

        $this->pdf->SetFillColor(255, 255, 255);
        $this->pdf->Rect(181, 0, 25, 8, 'F');
        $this->pdf->Rect(181, 38, 25, 15, 'F');

        $this->pdf->SetTextColor($r, $g, $b);
        $this->pdf->SetFont('Times', 'B', '16');
        $this->pdf->MultiCell(0, 6, strtoupper($school), $border, 'C');
        $this->pdf->SetTextColor(0);
        $this->pdf->SetFont('Times', 'I', 9);
        $this->pdf->Cell(45, 5, "", $border);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(110, 4, $address, $border, 'C' );
        $this->pdf->SetXY($x,$y);
        $this->pdf->Cell(0, 5, "", $border);
        // $this->pdf->MultiCell(100, 5, $address_line_2, $border, 'C' );
        //$this->pdf->Line(10, 30, 206, 30);
        $this->pdf->SetFont('Times', 'B', '14');
        $this->pdf->Ln(12);

        $this->pdf->MultiCell(0, 5, 'STUDENT REGISTRATION FORM', $border, 'C' );
        $this->pdf->Ln();


        $x = $this->pdf->GetX();
        $y = $this->pdf->GetY() - 3;
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Rect($x, $y, 196, 45, 'F');
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->Cell(25, 6, 'Student ID#', 0, 0, 'L');
        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->Cell(30, 6, $id, $cellBorder, 0, 'C');
        $this->pdf->Cell(55, 6, '', 0, 0, 'L');
        $this->pdf->Cell(60, 6, 'Copy of Original Birth Certificate', 0, 0, 'R');
        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $birthCertificate, 1, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->Ln(10);

        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->Cell(25, 6, 'Class', 0, 0, 'L');
        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->Cell(30, 6, $class_id, $cellBorder, 0, 'C');
        $this->pdf->Cell(55, 6, '', 0, 0, 'L');
        $this->pdf->Cell(60, 6, 'Passport Photo', 0, 0, 'R');
        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $passportPhoto, 1, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->Ln(10);

        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->Cell(25, 6, 'Entry Date', 0, 0, 'L');
        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->Cell(30, 6, $entryDate, $cellBorder, 0, 'C');
        $this->pdf->Cell(55, 6, '', 0, 0, 'L');
        $this->pdf->Cell(60, 6, 'SEA Placement Slip', 0, 0, 'R');
        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $seaSlip, 1, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->Ln(10);

        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->Cell(25, 6, 'House', 0, 0, 'L');
        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->Cell(30, 6, $houseAssignment, $cellBorder, 0, 'C');
        $this->pdf->Cell(55, 6, '', 0, 0, 'L');
        $this->pdf->Cell(60, 6, 'Copy of Immunization Card', 0, 0, 'R');
        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $immunizationCard, 1, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Cell(5, 6, '', 0, 0, 'L');
        $this->pdf->Ln(10);

        $this->pdf->SetFillColor($r, $g, $b);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('Times', 'B', '10');
        $this->pdf->MultiCell(196, 6, 'STUDENT INFORMATION', 0, 'C', true);
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(5);

        // $this->pdf->SetFont('Arial', '', '11');
        $this->pdf->Cell(68, 6, utf8_decode($student->first_name), $cellBorder, 0, 'C');
        // $this->pdf->Cell(68, 6, utf8_decode("DÉjon"), $cellBorder, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(50, 6, utf8_decode($student->last_name), $cellBorder, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(68, 6, $student->other_name, $cellBorder, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(68, 6, 'First Name', 0, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(50, 6, 'Last Name', 0, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(68, 6, 'Other Names', 0, 0, 'C');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(8);
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->Cell(145, 6, $studentDataPersonal->address_home, $cellBorder, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(46, 6, $studentDataPersonal->town, $cellBorder, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(140, 6, 'Address', 0, 0, 'C');
        $this->pdf->Cell(10, 6, '', 0, 0, 'C');
        $this->pdf->Cell(46, 6, 'Town', 0, 0, 'C');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(8);
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->Cell(64, 6, $this->formatNumber($studentDataPersonal->phone_mobile), $cellBorder, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(85, 6, $studentDataPersonal->email, $cellBorder, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(37, 6, $studentDataPersonal->blood_group, $cellBorder, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(64, 6, 'Telphone', 0, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(85, 6, 'Email', 0, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(37, 6, 'Blood Type', 0, 0, 'C');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(8);
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->Cell(64, 6, $dob, $cellBorder, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(64, 6, $student->birth_certificate_pin, $cellBorder, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(58, 6, $student->gender, $cellBorder, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(59, 6, 'Date of Birth', 0, 0, 'C');
        $this->pdf->Cell(10, 6, '', 0, 0, 'C');
        $this->pdf->Cell(59, 6, 'Birth Certificate Pin', 0, 0, 'C');
        $this->pdf->Cell(10, 6, '', 0, 0, 'C');
        $this->pdf->Cell(58, 6, 'Gender', 0, 0, 'C');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(8);
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->Cell(118, 6, $studentDataPersonal->country_of_birth, $cellBorder, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(73, 6, $studentDataPersonal->nationality, $cellBorder, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(118, 6, 'Country of birth', 0, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(73, 6, 'Nationality', 0, 0, 'C');
        $this->pdf->SetTextColor(0, 0, 0);

        // $this->pdf->Ln(8);
        // $this->pdf->SetFont('Times', '', '11');
        // $this->pdf->Cell(64, 6, $imigrationPermit, $cellBorder, 0, 'C');
        // $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        // $this->pdf->Cell(59, 6, $studentDataPersonal->date_of_issue, $cellBorder, 0, 'C');
        // $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        // $this->pdf->Cell(63, 6, $studentDataPersonal->date_of_expiry, $cellBorder, 0, 'C');
        // $this->pdf->Ln();
        // $this->pdf->SetTextColor(64, 64, 64);
        // $this->pdf->SetFont('Times', '', '10');
        // $this->pdf->Cell(59, 6, 'Immigration Permit', 0, 0, 'C');
        // $this->pdf->Cell(10, 6, '', 0, 0, 'C');
        // $this->pdf->Cell(59, 6, 'Permit Issue Date', 0, 0, 'C');
        // $this->pdf->Cell(10, 6, '', 0, 0, 'C');
        // $this->pdf->Cell(58, 6, 'Permit Expiry Date', 0, 0, 'C');
        // $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->Ln(8);
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->Cell(140, 6, $studentDataPersonal->previous_school, $cellBorder, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(51, 6, $student->sea_number, $cellBorder, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(140, 6, 'Previous School', 0, 0, 'C');
        $this->pdf->Cell(10, 6, '', 0, 0, 'C');
        $this->pdf->Cell(46, 6, 'Sea Number', 0, 0, 'C');
        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->Ln(8);
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->Cell(118, 6, $religiousGroup, $cellBorder, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(73, 6, $ethnicGroup, $cellBorder, 0, 'C');
        // $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        // $this->pdf->Cell(63, 6, $studentDataPersonal->second_language, $cellBorder, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(113, 6, 'Religion', 0, 0, 'C');
        $this->pdf->Cell(10, 6, '', 0, 0, 'C');
        $this->pdf->Cell(73, 6, 'Ethnic Group', 0, 0, 'C');
        // $this->pdf->Cell(10, 6, '', 0, 0, 'C');
        // $this->pdf->Cell(58, 6, 'Second Language', 0, 0, 'C');
        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->Ln(10);
        $this->pdf->SetFillColor($r, $g, $b);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('Times', 'B', '10');
        $this->pdf->MultiCell(196, 6, 'FAMILY INFORMATION', 0, 'C', true);
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->Ln(5);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(25, 6, '', 0, 0, 'C');
        $this->pdf->Cell(53, 6, 'Father', 0, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(53, 6, 'Mother', 0, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(53, 6, 'Guardian', 0, 0, 'C');
        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->Ln();
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'Name', 0, 0, 'L');
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(54, 6, ucwords(strtolower($fatherRecord->name)), 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(54, 6, ucwords(strtolower($motherRecord->name)), 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(54, 6, ucwords(strtolower($guardianRecord->name)), 1, 0, 'C');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'Marital Status', 0, 0, 'L');
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(54, 6, $fatherRecord->marital_status, 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(54, 6, $motherRecord->marital_status, 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(54, 6, $guardianRecord->marital_status, 1, 0, 'C');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'Home Phone', 0, 0, 'L');
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(54, 6, $this->formatNumber($fatherRecord->phone_home), 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(54, 6, $this->formatNumber($motherRecord->phone_home), 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(54, 6, $this->formatNumber($guardianRecord->phone_home), 1, 0, 'C');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'Work Phone', 0, 0, 'L');
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(54, 6, $this->formatNumber($fatherRecord->phone_work), 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(54, 6, $this->formatNumber($motherRecord->phone_work), 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(54, 6, $this->formatNumber($guardianRecord->phone_work), 1, 0, 'C');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'Mobile Phone', 0, 0, 'L');
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(54, 6, $this->formatNumber($fatherRecord->phone_mobile), 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(54, 6, $this->formatNumber($motherRecord->phone_mobile), 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(54, 6,$this->formatNumber( $guardianRecord->phone_mobile), 1, 0, 'C');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'ID Card #', 0, 0, 'L');
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(54, 6, $fatherRecord->id_card, 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(54, 6, $motherRecord->id_card, 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(54, 6, $guardianRecord->id_card, 1, 0, 'C');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(115, 6, 'Father Address', 0, 0, 'L');
        $this->pdf->Cell(6, 6, '', 0, 0, 'C');
        $this->pdf->Cell(75, 6, 'Father Email', 0, 0, 'L');
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Ln();
        $this->pdf->SetTextColor(0);
        $this->pdf->Cell(115, 6, $fatherRecord->address_home, 1, 0, 'L');
        $this->pdf->Cell(6, 6, '', 0, 0, 'C');
        $this->pdf->Cell(75, 6, $fatherRecord->email, 1, 0, 'L');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(115, 6, 'Mother Address', 0, 0, 'L');
        $this->pdf->Cell(6, 6, '', 0, 0, 'C');
        $this->pdf->Cell(75, 6, 'Mother Email', 0, 0, 'L');
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Ln();
        $this->pdf->SetTextColor(0);
        $this->pdf->Cell(115, 6, $motherRecord->address_home, 1, 0, 'L');
        $this->pdf->Cell(6, 6, '', 0, 0, 'C');
        $this->pdf->Cell(75, 6, $motherRecord->email, 1, 0, 'L');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(115, 6, 'Guardian Address', 0, 0, 'L');
        $this->pdf->Cell(6, 6, '', 0, 0, 'C');
        $this->pdf->Cell(75, 6, 'Guardian Email', 0, 0, 'L');
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Ln();
        $this->pdf->SetTextColor(0);
        $this->pdf->Cell(115, 6, $guardianRecord->address_home, 1, 0, 'L');
        $this->pdf->Cell(6, 6, '', 0, 0, 'C');
        $this->pdf->Cell(75, 6, $guardianRecord->email, 1, 0, 'L');

        $this->pdf->AddPage("P", 'Legal');

        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(62, 6, 'Number of Children in Family', 0, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(62, 6, 'Number of Children at Home', 0, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(62, 6, 'Place in Family', 0, 0, 'C');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln();
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(62, 6, $studentDataPersonal->no_in_family, 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(62, 6, $studentDataPersonal->no_at_home, 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(62, 6, $studentDataPersonal->place_in_family, 1, 0, 'C');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(0, 6, 'Emergency Contact', 0, 0, 'L');
        $this->pdf->Ln();
        $this->pdf->Cell(0, 6, $emergencyRecord->name, 1, 0, 'L');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(62, 6, 'Telephone Number', 0, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(62, 6, 'Relation to Child', 0, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(62, 6, 'Workplace Number', 0, 0, 'C');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln();
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(62, 6, $this->formatNumber($emergencyRecord->phone_home), 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(62, 6, $emergencyRecord->relation, 1, 0, 'C');
        $this->pdf->Cell(5, 6, '', 0, 0, 'C');
        $this->pdf->Cell(62, 6, $this->formatNumber($emergencyRecord->phone_work), 1, 0, 'C');

        $this->pdf->Ln(10);
        $this->pdf->SetFillColor($r, $g, $b);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('Times', 'B', '10');
        $this->pdf->MultiCell(196, 6, 'HEALTH HISTORY', 0, 'C', true);
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln(5);

        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->Cell(0, 6, 'Immunization Records', 0, 0, 'C');
        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(25, 6, 'Hepatitis', 0, 0, 'L');
        $check = ($studentDataMedical->hepatitis == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(11, 6, '', 0, 0, 'C');

        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'Polio', 0, 0, 'L');
        $check = ($studentDataMedical->polio == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(11, 6, '', 0, 0, 'C');

        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'Diptheria', 0, 0, 'L');
        $check = ($studentDataMedical->diphtheria == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(11, 6, '', 0, 0, 'C');

        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'Tetanus', 0, 0, 'L');
        $check = ($studentDataMedical->tetanus == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(11, 6, '', 0, 0, 'C');

        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(25, 6, 'Yellow Fever', 0, 0, 'L');
        $check = ($studentDataMedical->yellow_fever == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(25, 6, 'Measles', 0, 0, 'L');
        $check = ($studentDataMedical->measles == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(11, 6, '', 0, 0, 'C');

        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'TB', 0, 0, 'L');
        $check = ($studentDataMedical->tb == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(11, 6, '', 0, 0, 'C');

        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'Chicken Pox', 0, 0, 'L');
        $check = ($studentDataMedical->chicken_pox == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(11, 6, '', 0, 0, 'C');

        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'Typhoid', 0, 0, 'L');
        $check = ($studentDataMedical->typhoid == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(9, 6, '', 0, 0, 'C');

        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(27, 6, 'Rheumatic Fever', 0, 0, 'L');
        $check = ($studentDataMedical->rheumatic_fever == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(11, 6, '', 0, 0, 'C');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->Cell(0, 6, 'Please indicate [by ticking] whether the child suffers from any of the following:', 0, 0, 'C');
        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(25, 6, 'Poor Eyesight', 0, 0, 'L');
        $check = ($studentDataMedical->poor_eyesight == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(11, 6, '', 0, 0, 'C');


        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'Poor Hearing', 0, 0, 'L');
        $check = ($studentDataMedical->poor_hearing == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(11, 6, '', 0, 0, 'C');

        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'Diabetes', 0, 0, 'L');
        $check = ($studentDataMedical->diabetes == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(11, 6, '', 0, 0, 'C');

        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(24, 6, 'Asthma', 0, 0, 'L');
        $check = ($studentDataMedical->asthma == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(11, 6, '', 0, 0, 'C');

        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(25, 6, 'Epilepsy', 0, 0, 'L');
        $check = ($studentDataMedical->epilepsy == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(11, 6, '', 0, 0, 'C');

        $this->pdf->Ln(11);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(0, 6, 'Other Health Condition', 0, 0, 'L');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln();
        $this->pdf->Cell(0, 6, $studentDataMedical->other, 1, 0, 'L');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(0, 6, 'Allergies', 0, 0, 'L');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln();
        $this->pdf->Cell(0, 6, $studentDataMedical->allergies, 1, 0, 'L');

        $this->pdf->Ln(10);
        $this->pdf->SetFillColor($r, $g, $b);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetFont('Times', 'B', '10');
        $this->pdf->MultiCell(196, 6, 'OTHER INFORMATION', 0, 'C', true);
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->SetTextColor(0, 0, 0);

        $this->pdf->Ln();
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(0, 6, 'Extra-Curricular Interests', 0, 0, 'L');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Ln();
        $this->pdf->Cell(0, 6, $studentDataPersonal->extra_curricular, 1, 0, 'L');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(80, 6, 'Would you child require School Feeding Meals?', 0, 0, 'L');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(10, 6, '', 0, 0, 'L');
        $this->pdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        $checkYes = ($studentDataPersonal->school_feeding == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->Cell(10, 6, '', 0, 0, 'L');
        $this->pdf->Cell(10, 6, 'No', 0, 0, 'L');
        $checkNo = ($studentDataPersonal->school_feeding === 0) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->Cell(10, 6, '', 0, 0, 'L');

        // $this->pdf->Ln(10);
        // $this->pdf->SetTextColor(64, 64, 64);
        // $this->pdf->SetFont('Times', '', '10');
        // $this->pdf->Cell(80, 6, 'Does you child access Social Welfare Grant?', 0, 0, 'L');
        // $this->pdf->SetTextColor(0, 0, 0);
        // $this->pdf->Cell(10, 6, '', 0, 0, 'L');
        // $this->pdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        // $checkYes = ($studentDataPersonal->social_welfare == 1) ? "3" : "";
        // $this->pdf->SetFont('ZapfDingbats', '', 13);
        // $this->pdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        // $this->pdf->SetFont('Times', '', 10);
        // $this->pdf->Cell(10, 6, '', 0, 0, 'L');
        // $this->pdf->Cell(10, 6, 'No', 0, 0, 'L');
        // $checkNo = ($studentDataPersonal->social_welfare == 0) ? "3" : "";
        // $this->pdf->SetFont('ZapfDingbats', '', 13);
        // $this->pdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        // $this->pdf->SetFont('Times', '', 10);
        // $this->pdf->Cell(10, 6, '', 0, 0, 'L');

        // $this->pdf->Ln(10);
        // $this->pdf->SetTextColor(64, 64, 64);
        // $this->pdf->SetFont('Times', '', '10');
        // $this->pdf->Cell(80, 6, 'Does you child access School Transport?', 0, 0, 'L');
        // $this->pdf->SetTextColor(0, 0, 0);
        // $this->pdf->Cell(10, 6, '', 0, 0, 'L');
        // $this->pdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        // $checkYes = ($studentDataPersonal->school_transport == 1) ? "3" : "";
        // $this->pdf->SetFont('ZapfDingbats', '', 13);
        // $this->pdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        // $this->pdf->SetFont('Times', '', 10);
        // $this->pdf->Cell(10, 6, '', 0, 0, 'L');
        // $this->pdf->Cell(10, 6, 'No', 0, 0, 'L');
        // $checkNo = ($studentDataPersonal->school_transport == 0) ? "3" : "";
        // $this->pdf->SetFont('ZapfDingbats', '', 13);
        // $this->pdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        // $this->pdf->SetFont('Times', '', 10);
        // $this->pdf->Cell(10, 6, '', 0, 0, 'L');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(80, 6, 'Does you child have internet access?', 0, 0, 'L');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(10, 6, '', 0, 0, 'L');
        $this->pdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        $checkYes = ($studentDataPersonal->internet_access == 1) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->Cell(10, 6, '', 0, 0, 'L');
        $this->pdf->Cell(10, 6, 'No', 0, 0, 'L');
        $checkNo = ($studentDataPersonal->internet_access === 0) ? "3" : "";
        $this->pdf->SetFont('ZapfDingbats', '', 13);
        $this->pdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->Cell(10, 6, '', 0, 0, 'L');

        $this->pdf->Ln(10);
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', '', '10');
        $this->pdf->Cell(60, 6, 'What type of device does you child have access to?', 0, 0, 'L');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->Cell(30, 6, '', 0, 0, 'L');
        $this->pdf->Cell(42, 6, $studentDataPersonal->device_type, 1, 0, 'L');
        $this->pdf->Cell(10, 6, '', 0, 0, 'L');

        // $this->pdf->Ln(15);
        // $this->pdf->SetTextColor(64, 64, 64);
        // $this->pdf->SetFont('Times', '', '10');
        // $this->pdf->Cell(30, 10, 'Student\' Signature', 0, 0, 'L');
        // $this->pdf->SetTextColor(0, 0, 0);
        // $this->pdf->SetFillColor(220, 220, 220);
        // $this->pdf->Cell(60, 10, '', 0, 0, 'L', true);

        $this->pdf->Ln(20);
        $this->pdf->SetFont('Times', 'BU', '10');
        $this->pdf->Cell(0, 6, 'Declaration', 0, 0, 'L');
        $this->pdf->SetFont('Times', '', '11');
        $this->pdf->Ln(10);
        $this->pdf->MultiCell(0, 6, $declaration, 0, 'L');
        $this->pdf->Ln();
        $this->pdf->SetFillColor(240, 240, 240);
        $this->pdf->Cell(60, 10, '', 'B', 0, 'C', true);
        $this->pdf->Cell(18, 10, '', 0, 0, 'C');
        $this->pdf->Cell(60, 10, '', 'B', 0, 'C', true);
        $this->pdf->Cell(18, 10, '', 0, 0, 'C');
        $this->pdf->Cell(40, 10, '', 'B', 0, 'C', true);

        $this->pdf->Ln();
        $this->pdf->SetTextColor(64, 64, 64);
        $this->pdf->SetFont('Times', 'I', '10');
        $this->pdf->Cell(60, 10, 'Student\'s Signature', 0, 0, 'C');
        $this->pdf->Cell(18, 10, '', 0, 0, 'C');
        $this->pdf->Cell(60, 10, 'Parent\'s / Guardian\'s Signature', 0, 0, 'C');
        $this->pdf->Cell(18, 10, '', 0, 0, 'C');
        $this->pdf->Cell(40, 10, 'Date', 0, 0, 'C');
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetFillColor(220, 220, 220);


        $this->pdf->Ln();



        $this->pdf->Output();
        exit;
        // $student = Student::whereId($id)->get();
        // $studentFirstName = $student[0]->first_name;
        // return $studentFirstName;
    }

    public function record($id){
        return Student::whereId($id)->get();
    }

    private function formatNumber ($number)
    {
        if($number) return "(868) ".substr($number, 0, 3)."-".substr($number, -4);
        return $number;
    }
}
