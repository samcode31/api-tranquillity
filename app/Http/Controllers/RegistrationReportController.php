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



    public function show ($formClassId)
    {
        date_default_timezone_set('America/Caracas');
        $academicTerm = AcademicTerm::where('is_current', 1)
        ->first();

        $academicYearId = null;

        if($academicTerm){
            $academicYearId = $academicTerm->academic_year_id;
        }

        $students = Student::join(
            'student_class_registrations',
            'student_class_registrations.student_id',
            'students.id'
        )
        ->select(
            'students.id',
            'first_name',
            'last_name',
            'gender',
            'date_of_birth',
            'form_class_id'
        )
        ->where([
            [
                'student_class_registrations.academic_year_id',
                $academicYearId
            ],
            ['form_class_id', $formClassId]
        ])
        ->orderBy('last_name')
        ->get();

        foreach($students as $student){
            $studentDataPersonal = StudentPersonalData::where(
                'student_id',
                $student->id
            )->first();

            $studentDataFamily = StudentFamilyData::where(
                'student_id',
                $student->id
            )->get();

            $student->phone_mobile = $studentDataPersonal->phone_mobile;
            $student->email = $studentDataPersonal->email;

            foreach($studentDataFamily as $familyData){
                switch($familyData->relationship){
                    case 1:
                        $student->father_name = $familyData->name;
                        $student->phone_home_father = $familyData->phone_home;
                        $student->phone_mobile_father = $familyData->phone_mobile;
                        $student->email_father = $familyData->email;
                        break;
                    case 2:
                        $student->mother_name = $familyData->name;
                        $student->phone_home_mother = $familyData->phone_home;
                        $student->phone_mobile_mother = $familyData->phone_mobile;
                        $student->email_mother = $familyData->email;
                        break;
                    case 3:
                        $student->guardian_name = $familyData->name;
                        $student->phone_home_guardian = $familyData->phone_home;
                        $student->phone_mobile_guardian = $familyData->phone_mobile;
                        $student->email_guardian = $familyData->email;
                        break;
                }
            }

        }

        // return $students;

        $this->pdf->AliasNbPages();
        $this->pdf->SetMargins(10, 10);
        $this->pdf->AddPage('L', 'Letter');
        $this->pdf->SetAutoPageBreak(false);
        $this->Header($formClassId);


        $this->pdf->SetWidths(array(20, 30, 15, 25, 25, 50, 94.4));
        $this->pdf->SetAligns(array('C', 'L', 'C', 'C', 'C', 'L', 'L'));

        foreach($students as $record){
            $studentRecord = array();
            $id = $record->id;
            $name = $record->last_name.', '.$record->first_name;
            $gender = $record->gender;
            $dob = date_format(date_create($record->date_of_birth), 'd-m-Y');
            $phone_cell = ($record->phone_mobile != null) ? substr($record->phone_mobile, 0, 3).'-'.substr($record->phone_mobile,3) : '-';
            $email = ($record->email != null) ? strtolower($record->email) : '-';
            $currentFormClass = $record->form_class_id;
            $father_name = ucwords(strtolower($record->father_name));
            $father_phone = ($record->phone_mobile_father != null || '') ? substr($record->phone_mobile_father, 0, 3).'-'.substr($record->phone_mobile_father,3) : '-';
            $father_email = ($record->email_father != null || '') ? strtolower($record->email_father) : '-';
            $mother_name = ucwords(strtolower($record->mother_name));
            $mother_phone = ($record->phone_mobile_mother != null || '') ? substr($record->phone_mobile_mother, 0, 3).'-'.substr($record->phone_mobile_mother,3) : '-';
            $mother_email = ($record->email_mother != null || '') ? strtolower($record->email_mother) : '-';
            $guardian_name = ucwords(strtolower($record->guardian_name));
            $guardian_phone = ($record->phone_mobile_guardian != null || '') ? substr($record->phone_mobile_guardian, 0, 3).'-'.substr($record->phone_mobile_guardian,3) : '-';
            $guardian_email = ($record->email_guardian != null || '') ? strtolower($record->email_guardian) : '-';
            $parentGuardian = array();

            if($father_name){
                array_push($parentGuardian, "Father: ", $father_name, ", Phone (C): ", $father_phone, ", \nFather's Email: ", $father_email."\n");
            }
            if($mother_name){
                array_push($parentGuardian, "Mother: ", $mother_name, ", Phone (C): ", $mother_phone, ", \nMother's Email: ", $mother_email."\n");
            }
            if($guardian_name){
                array_push($parentGuardian, "Guardian: ", $guardian_name, ", Phone (C): ", $guardian_phone, ", \nGuardian's Email: ", $guardian_email."\n");
            }
            if(sizeof($parentGuardian) != 0){
                $parentGuardianContact = implode("",$parentGuardian);
            }
            else{
                $parentGuardianContact = '-';
            }
            array_push($studentRecord, $id, $name, $gender, $dob, $phone_cell, $email, $parentGuardianContact);

            $this->pdf->SetFont('Times', '', 10);
            if($this->pdf->GetY() > 180){
                $this->pdf->AddPage('L', 'Letter');
                $this->Header($formClassId);
            }
            $this->pdf->Row2($studentRecord, $currentFormClass);
        }
        $this->Footer();
        $this->pdf->Output('I', 'Student Contact Information.pdf');
        exit;
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
