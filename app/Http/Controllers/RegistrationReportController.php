<?php

namespace App\Http\Controllers;

use App\Models\FormClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf;

class RegistrationReportController extends Controller
{
    private $fpdf;
    private $widths;
    private $aligns;

    public function __construct()
    {
        
    }

    public function create($classId)
    {       
        //$formClass = '1-1';
        //$formClasses = FormClass::whereFormLevel('1')->get();
        //$formClass = $formClasses[0]->id;
        $formClass = null;
        $records = Student::select(
        'id', 
        'first_name', 
        'last_name', 
        'sex', 
        'phone_cell', 
        'email', 
        'date_of_birth', 
        'home_address',
        'father_name',
        'mobile_phone_father',
        'email_father',
        'mother_name',
        'mobile_phone_mother', 
        'email_mother',
        'guardian_name',
        'mobile_phone_guardian',
        'email_guardian', 
        'class_id')
        ->where('id', '!=', 20000)
        ->get();
        
        $this->fpdf = new Fpdf('L', 'mm', 'Letter');              
        $this->fpdf->AddPage();
        //$this->Header();
             
        
        $this->SetWidths(array(20, 30, 15, 25, 25, 50, 94.4));
        $this->SetAligns(array('C', 'L', 'C', 'C', 'C', 'L', 'L'));
        
        foreach($records as $record){
            $studentRecord = array();
            $id = $record->id;
            $name = $record->last_name.', '.$record->first_name;
            $gender = $record->sex;
            $dob = date_format(date_create($record->date_of_birth), 'd-m-Y');
            $phone_cell = ($record->phone_cell != null) ? substr($record->phone_cell, 0, 3).'-'.substr($record->phone_cell,3) : '-';
            $email = ($record->email != null) ? strtolower($record->email) : '-';
            $currentFormClass = $record->class_id;
            $father_name = ucwords(strtolower($record->father_name));
            $father_phone = ($record->mobile_phone_father != null || '') ? substr($record->mobile_phone_father, 0, 3).'-'.substr($record->mobile_phone_father,3) : '-';
            $father_email = ($record->email_father != null || '') ? strtolower($record->email_father) : '-';
            $mother_name = ucwords(strtolower($record->mother_name));
            $mother_phone = ($record->mobile_phone_mother != null || '') ? substr($record->mobile_phone_mother, 0, 3).'-'.substr($record->mobile_phone_mother,3) : '-';
            $mother_email = ($record->email_mother != null || '') ? strtolower($record->email_mother) : '-';
            $guardian_name = ucwords(strtolower($record->guardian_name));
            $guardian_phone = ($record->mobile_phone_guardian != null || '') ? substr($record->mobile_phone_guardian, 0, 3).'-'.substr($record->mobile_phone_guardian,3) : '-';
            $guardian_email = ($record->email_guardian != null || '') ? strtolower($record->email_guardian) : '-';
            $parentGuardian = array();
            //$home_address = ucwords(strtolower($record->home_address));
            //$mother_home_address = ucwords(strtolower($record->mother_home_address));
            if($father_name != null){
                array_push($parentGuardian, "Father: ", $father_name, ", Phone: ", $father_phone, ", \nEmail: ", $father_email."\n");
            }
            if($mother_name != null){
                array_push($parentGuardian, "Mother: ", $mother_name, ", Phone: ", $mother_phone, ", \nEmail: ", $mother_email."\n");
            }
            if($guardian_name != null){
                array_push($parentGuardian, "Guardian: ", $guardian_name, ", Phone: ", $guardian_phone, ", \nEmail: ", $guardian_email."\n");
            }
            if(sizeof($parentGuardian) != 0){
                $parentGuardianContact = implode(" " ,$parentGuardian);
            }
            else{
                $parentGuardianContact = '-';
            }
            array_push($studentRecord, $id, $name, $gender, $dob, $phone_cell, $email, $parentGuardianContact);
           
            if($currentFormClass != $formClass && $formClass != null){
                $this->fpdf->AddPage();
                //$this->Header($currentFormClass);
            }
            $this->fpdf->SetFont('Times', '', 10);            
            $this->Row($studentRecord, $currentFormClass);
            $formClass = $currentFormClass;
            //break;
        }         
        $this->fpdf->Output('I', 'Student Contact Information.pdf');
        exit;
    }

    private function Header($formClass=null)
    {
        $logo = public_path('/imgs/logo.png');
        $school = config('app.school_name');        
        $address = config('app.school_address');
        $contact = config('app.school_contact');
        $border = 'TB';

        $this->fpdf->SetMargins(10, 8);
        $this->fpdf->Image($logo, 10, 9, 15);        
        $this->fpdf->SetFont('Times', 'B', '16');
        $this->fpdf->MultiCell(0, 8, $school, 0, 'C' );
        $this->fpdf->SetFont('Times', 'I', 10);
        $this->fpdf->MultiCell(0, 6, $address.'. '.$contact, 0, 'C' );
        $this->fpdf->Ln(10);
        $this->fpdf->SetFont('Times', 'B', 14);
        $this->fpdf->Cell(129.9, 6, 'Student Contact List: '.$formClass, 0, 0, 'L');       
        $this->fpdf->Cell(129.5, 6, '2020-2021', 0, 0, 'R');
        $this->fpdf->Ln(10);
        $this->fpdf->SetFont('Times', 'B', 12);
        $this->fpdf->SetFillColor(220, 220, 220);
        $this->fpdf->Cell(20, 6, 'ID#', $border, 0, 'C', true);         
        $this->fpdf->Cell(30, 6, 'Name', $border, 0, 'L', true);         
        $this->fpdf->Cell(15, 6, 'Gender', $border, 0, 'C', true);         
        $this->fpdf->Cell(25, 6, 'D.O.B', $border, 0, 'C', true);         
        $this->fpdf->Cell(25, 6, 'Phone(C)', $border, 0, 'C', true);         
        $this->fpdf->Cell(50, 6, 'Email', $border, 0, 'L', true);         
        $this->fpdf->Cell(94.4, 6, 'Parent / Guardian Contact Information', $border, 0, 'L', true);
        //$this->fpdf->Cell(20, 6, 'Class', $border, 0, 'C', true);
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Ln();
    }

    private function Row($data, $formClass=null)
    {
        //Calculate the height of the row
        $nb=0; $nbMax=0;
        for($i=0;$i<count($data);$i++)
            $nbMax=max($nbMax,$this->NbLines($this->widths[$i],$data[$i]));
        $h=5*$nbMax;
        //Issue a page break first if needed
        $this->CheckPageBreak($h, $formClass);
        //Draw the cells of the row
        for($i=0;$i<count($data);$i++)
        {
            $nb=$this->NbLines($this->widths[$i], $data[$i]);
            if($nb == 0) $nb = 1;
            $w=$this->widths[$i];
            $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            //save the current position
            $x=$this->fpdf->GetX();
            $y=$this->fpdf->GetY();
            //print the text
            $this->fpdf->MultiCell($w, bcdiv($h, $nb, 1), $data[$i], 0, $a);
            $this->fpdf->SetLineWidth(0.1);
            $this->SetDash(0.5,0.5);
            $this->fpdf->setDrawColor(192, 192, 192);
            $this->fpdf->Line($x, $y, $x + $w, $y);
            $this->fpdf->setDrawColor(0, 0, 0);            
            //put the position to the right of the cell
            $this->fpdf->SetXY($x+$w, $y);
        }
        
        //Go to the next line
        $this->fpdf->Ln($h);
    }

    private function CheckPageBreak($h, $formClass)
    {
        //First page of document            
        if($this->fpdf->GetY() == $this->fpdf->tMargin){            
            $this->Header($formClass);            
        }
        //If the height h would cause an overflow, add a new page immediately
        if($this->fpdf->GetY()+$h>$this->fpdf->PageBreakTrigger)
        {
            $this->fpdf->AddPage($this->fpdf->CurOrientation);
            $this->Header($formClass);
        }            
    }

    private function NbLines($w,$txt)
    {
        //Computes the number of lines a MultiCell of width w will take
        $cw=&$this->fpdf->CurrentFont['cw'];        
        if($w==0)
            $w=$this->w-$this->fpdf->rMargin-$this->x;
        $wmax=($w-2*$this->fpdf->cMargin)*1000/$this->fpdf->FontSize;        
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);        
        if($nb>0 and $s[$nb-1]=="\n")
            $nb--;
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<$nb)
        {
            $c=$s[$i];
            if($c=="\n")
            {
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax)
            {
                if($sep==-1)
                {
                    if($i==$j)
                        $i++;
                }
                else
                    $i=$sep+1;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }

    private function SetWidths($w)
    {
        //Set the array of column widths
        $this->widths=$w;
    }

    private function SetAligns($a)
    {
        //Set the array of column alignments
        $this->aligns=$a;
    }

    private function SetDash($black=null, $white=null)
    {
        if($black!==null)
            $s=sprintf('[%.3F %.3F] 0 d',$black*$this->fpdf->k,$white*$this->fpdf->k);
        else
            $s='[] 0 d';
        $this->fpdf->_out($s);
    }  
   
}
