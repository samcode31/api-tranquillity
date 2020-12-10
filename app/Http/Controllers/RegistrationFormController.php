<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\EthnicGroup;
use App\Models\Religion;
use App\Models\Student;
use Codedge\Fpdf\Fpdf\Fpdf;

class RegistrationFormController extends Controller
{
    private $fpdf;

    public function __construct()
    {
        
    }
    
    
    public function createPDF($id)
    {
        $record = Student::whereId($id)->get()[0];        
        $school = config('app.school_name');        
        $address = config('app.school_address');
        $contact = config('app.school_contact');
        $entryDate = config('app.school_entry_date');
        $declaration = config('app.school_declaration');

        $religionArray = Religion::whereCode($record->religion_code)->get();
        $religion = (sizeof($religionArray) > 0) ? $religionArray[0]->religion : "";
             
        $ethnicGroupArray = EthnicGroup::whereId($record->ethnic_group_code)->get();
        $ethnicGroup = (sizeof($ethnicGroupArray) > 0) ? $ethnicGroupArray[0]->group_type : "";

        // $housesArray = House::whereId($record->house_code)->get();
        // $house = (sizeof($housesArray) > 0) ? $housesArray[0]->house_name : "";

        $imigrationPermit = ($record->immigration_permit == 0) ? "No" : "Yes";
        $dob = date_format(date_create($record->date_of_birth), 'j M Y');
        
        $r = config('app.school_color_red');
        $g = config('app.school_color_green');
        $b = config('app.school_color_blue');
        $house = "";
        $border = 0;
        $cellBorder = 1;
        $logo = public_path('/imgs/logo.png');


        $this->fpdf = new Fpdf;
        $this->fpdf->AddPage("P", 'Legal');
        $this->fpdf->SetMargins(10, 8);
        $this->fpdf->SetDrawColor(190, 190, 190);
        $this->fpdf->SetFont('Times', 'B', '16');
        $this->fpdf->Image($logo, 190, 8, 20);
        $this->fpdf->MultiCell(0, 6, $school, $border, 'C');
        $this->fpdf->SetFont('Times', 'I', 10);
        $this->fpdf->MultiCell(0, 5, $address, $border, 'C' );
        $this->fpdf->MultiCell(0, 5, $contact, $border, 'C' );
        $this->fpdf->Line(10, 30, 206, 30);
        $this->fpdf->SetFont('Times', 'B', '14');
        $this->fpdf->Ln(8);
        $this->fpdf->MultiCell(0, 5, 'STUDENT REGISTRATION FORM', $border, 'C' );
        $this->fpdf->Ln();
        $x = $this->fpdf->GetX();
        $y = $this->fpdf->GetY() - 3;
        $this->fpdf->SetFillColor(240, 240, 240);
        $this->fpdf->Rect($x, $y, 196, 45, 'F');
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(25, 6, 'Student ID#', 0, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(30, 6, $id, $cellBorder, 0, 'C');
        $this->fpdf->Cell(55, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(60, 6, 'Copy of Original Birth Certificate', 0, 0, 'R');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 1, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Ln(10);
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(25, 6, 'Class', 0, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(30, 6, $record->class_id, $cellBorder, 0, 'C');
        $this->fpdf->Cell(55, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(60, 6, 'Passport Photo', 0, 0, 'R');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 1, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Ln(10);
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(25, 6, 'Entry Date', 0, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(30, 6, $entryDate, $cellBorder, 0, 'C');
        $this->fpdf->Cell(55, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(60, 6, 'SEA Placement Slip', 0, 0, 'R');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 1, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Ln(10);
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(25, 6, 'House', 0, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(30, 6, $house, $cellBorder, 0, 'L');
        $this->fpdf->Ln(10);
        $this->fpdf->SetFillColor($r, $g, $b);
        $this->fpdf->SetTextColor(255, 255, 255);
        $this->fpdf->SetFont('Times', 'B', '10');
        $this->fpdf->MultiCell(196, 6, 'STUDENT INFORMATION', 0, 'C', true);
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln(5);
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->Cell(68, 6, $record->first_name, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(50, 6, $record->last_name, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(68, 6, $record->middle_name, $cellBorder, 0, 'C');
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
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->Cell(145, 6, $record->home_address, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(46, 6, $record->address_line_2, $cellBorder, 0, 'C');       
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(140, 6, 'Address', 0, 0, 'C');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(46, 6, 'Town', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln(8);
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->Cell(64, 6, $record->phone_cell, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(85, 6, $record->email, $cellBorder, 0, 'C');       
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(37, 6, $record->blood_type, $cellBorder, 0, 'C');       
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
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->Cell(64, 6, $dob, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(64, 6, $record->birth_certificate_no, $cellBorder, 0, 'C');       
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(58, 6, $record->sex, $cellBorder, 0, 'C');       
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
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->Cell(118, 6, $record->place_of_birth, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');        
        $this->fpdf->Cell(73, 6, $record->nationality, $cellBorder, 0, 'C');       
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(118, 6, 'Country of birth', 0, 0, 'C');                
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(73, 6, 'Nationality', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        
        $this->fpdf->Ln(8);
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->Cell(64, 6, $imigrationPermit, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(59, 6, $record->permit_issue_date, $cellBorder, 0, 'C');       
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(63, 6, $record->permit_expiry_date, $cellBorder, 0, 'C');       
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(59, 6, 'Immigration Permit', 0, 0, 'C');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(59, 6, 'Permit Issue Date', 0, 0, 'C');          
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(58, 6, 'Permit Expiry Date', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0); 
        
        $this->fpdf->Ln(8);
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->Cell(140, 6, $record->previous_school, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(51, 6, $record->sea_no, $cellBorder, 0, 'C');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(140, 6, 'Previous School', 0, 0, 'C');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(46, 6, 'Sea Number', 0, 0, 'C'); 
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(8);
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->Cell(84, 6, $religion, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(39, 6, $ethnicGroup, $cellBorder, 0, 'C');       
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(63, 6, $record->second_language, $cellBorder, 0, 'C');       
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(79, 6, 'Religion', 0, 0, 'C');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(39, 6, 'Ethnic Group', 0, 0, 'C');          
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(58, 6, 'Second Language', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        
        $this->fpdf->Ln(10);
        $this->fpdf->SetFillColor($r, $g, $b);
        $this->fpdf->SetTextColor(255, 255, 255);
        $this->fpdf->SetFont('Times', 'B', '10');
        $this->fpdf->MultiCell(196, 6, 'FAMILY INFORMATION', 0, 'C', true);
        $this->fpdf->SetFont('Times', '', '12');
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
        $this->fpdf->Cell(54, 6, ucwords(strtolower($record->father_name)), 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, ucwords(strtolower($record->mother_name)), 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, ucwords(strtolower($record->guardian_name)), 1, 0, 'C');        
        
        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Marital Status', 0, 0, 'L');       
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $record->father_marital_status, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->mother_marital_status, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->guardian_marital_status, 1, 0, 'C'); 

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Home Phone', 0, 0, 'L');        
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $record->father_phone_home, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->mother_phone_home, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->home_phone_guardian, 1, 0, 'C');        

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Work Phone', 0, 0, 'L');        
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $record->father_business_phone, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->mother_business_phone, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->business_phone_guardian, 1, 0, 'C');        

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Mobile Phone', 0, 0, 'L');       
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $record->mobile_phone_father, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->mobile_phone_mother, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->mobile_phone_guardian, 1, 0, 'C');
        
        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'ID Card #', 0, 0, 'L');        
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $record->id_card_father, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->id_card_mother, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->id_card_guardian, 1, 0, 'C');       

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(95, 6, 'Father Address', 0, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(95, 6, 'Father Email', 0, 0, 'L');        
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Ln();
        $this->fpdf->Cell(95, 6, $record->father_home_address, 1, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(95, 6, $record->email_father, 1, 0, 'L');
        
        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(95, 6, 'Mother Address', 0, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(95, 6, 'Mother Email', 0, 0, 'L');        
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Ln();
        $this->fpdf->Cell(95, 6, $record->mother_home_address, 1, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(95, 6, $record->email_mother, 1, 0, 'L');
                     
        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(95, 6, 'Guardian Address', 0, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(95, 6, 'Guardian Email', 0, 0, 'L');        
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Ln();
        $this->fpdf->Cell(95, 6, $record->home_address_guardian, 1, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(95, 6, $record->email_guardian, 1, 0, 'L');        

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
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(62, 6, $record->no_in_family, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, $record->no_at_home, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, $record->place_in_family, 1, 0, 'C');
        
        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(0, 6, 'Emergency Contact', 0, 0, 'L');        
        $this->fpdf->Ln();
        $this->fpdf->Cell(0, 6, $record->emergency_contact, 1, 0, 'L');

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
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(62, 6, $record->emergency_home_phone, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, $record->relation_to_child, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, $record->emergency_work_phone, 1, 0, 'C');

        $this->fpdf->Ln(10);
        $this->fpdf->SetFillColor($r, $g, $b);
        $this->fpdf->SetTextColor(255, 255, 255);
        $this->fpdf->SetFont('Times', 'B', '10');
        $this->fpdf->MultiCell(196, 6, 'HEALTH HISTORY', 0, 'C', true);
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln(5);

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->Cell(0, 6, 'Immunization Records', 0, 0, 'C');        
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Hepatitis', 0, 0, 'L');
        $check = ($record->hepatitis == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Polio', 0, 0, 'L');
        $check = ($record->polio == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Diptheria', 0, 0, 'L');
        $check = ($record->diphtheria == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Tetanus', 0, 0, 'L');        
        $check = ($record->tetanus == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Yellow Fever', 0, 0, 'L');
        $check = ($record->yellow_fever == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Measles', 0, 0, 'L');
        $check = ($record->measles == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'TB', 0, 0, 'L');
        $check = ($record->tb == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Chicken Pox', 0, 0, 'L');
        $check = ($record->chicken_pox == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Typhoid', 0, 0, 'L');
        $check = ($record->typhoid == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(9, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(27, 6, 'Rheumatic Fever', 0, 0, 'L');
        $check = ($record->rheumatic_fever == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->Cell(0, 6, 'Please indicate [by ticking] whether the child suffers from any of the following:', 0, 0, 'C');        
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Poor Eyesight', 0, 0, 'L');
        $check = ($record->poor_eyesight == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');


        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Poor Hearing', 0, 0, 'L');
        $check = ($record->poor_hearing == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Diabetes', 0, 0, 'L');
        $check = ($record->diabetes == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Asthma', 0, 0, 'L');
        $check = ($record->asthma == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Epilepsy', 0, 0, 'L');
        $check = ($record->epilepsy == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->Ln(12);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(0, 6, 'Other Health Condition', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln();
        $this->fpdf->Cell(0, 6, $record->medical_history, 1, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(0, 6, 'Other Illness', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln();
        $this->fpdf->Cell(0, 6, $record->other_illness, 1, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(0, 6, 'Allergies', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln();
        $this->fpdf->Cell(0, 6, $record->allergy, 1, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetFillColor($r, $g, $b);
        $this->fpdf->SetTextColor(255, 255, 255);
        $this->fpdf->SetFont('Times', 'B', '10');
        $this->fpdf->MultiCell(196, 6, 'OTHER INFORMATION', 0, 'C', true);
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->SetTextColor(0, 0, 0);        

        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(0, 6, 'Extra-Curricular Interests', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln();
        $this->fpdf->Cell(0, 6, $record->achivements, 1, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(80, 6, 'Would you child require School Feeding Meals?', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        $checkYes = ($record->school_feeding == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');        
        $this->fpdf->Cell(10, 6, 'No', 0, 0, 'L');
        $checkNo = ($record->school_feeding == 0) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(80, 6, 'Does you child access Social Welfare Grant?', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        $checkYes = ($record->social_welfare == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'No', 0, 0, 'L');
        $checkNo = ($record->social_welfare == 0) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(80, 6, 'Does you child access School Transport?', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        $checkYes = ($record->school_transport == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'No', 0, 0, 'L');
        $checkNo = ($record->school_transport == 0) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(80, 6, 'Does you child have internet access?', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        $checkYes = ($record->internet_access == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'No', 0, 0, 'L');
        $checkNo = ($record->internet_access == 0) ? "3" : "";
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
        $this->fpdf->Cell(42, 6, $record->device_type, 1, 0, 'L');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');

        $this->fpdf->Ln(15);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(30, 10, 'Student\' Signature', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->SetFillColor(220, 220, 220);
        $this->fpdf->Cell(60, 10, '', 0, 0, 'L', true);

        $this->fpdf->Ln(20);
        $this->fpdf->SetFont('Times', 'BU', '10');
        $this->fpdf->Cell(0, 6, 'Declaration', 0, 0, 'L');        
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->Ln(10);
        $this->fpdf->MultiCell(0, 6, $declaration, 0, 'L');
        $this->fpdf->Ln();
        $this->fpdf->Cell(60, 6, '', 'B', 0, 'C');
        $this->fpdf->Cell(76, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(60, 6, '', 'B', 0, 'C');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', 'I', '10');
        $this->fpdf->Cell(60, 10, 'Parent\'s / Guardian\'s Signature', 0, 0, 'C');        
        $this->fpdf->Cell(76, 10, '', 0, 0, 'C');
        $this->fpdf->Cell(60, 10, 'Date', 0, 0, 'C');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->SetFillColor(220, 220, 220);


        $this->fpdf->Ln();
        


        $this->fpdf->Output();
        exit;
        // $student = Student::whereId($id)->get();
        // $studentFirstName = $student[0]->first_name;
        // return $studentFirstName;
    }

    public function record($id){
        return Student::whereId($id)->get();
    }
}
