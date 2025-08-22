<?php

namespace App\Http\Controllers;

use App\Models\SixthFormApplication as ModelsSixthFormApplication;
use App\Models\SixthFormApplicationGrade;
use App\Models\SixthFormApplicationPeriod;
use App\Models\SixthFormApplicationSubjectLine;
use App\Models\SixthFormApplicationSubjects;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\URL;


class SixthFormApplication extends Controller
{
    private $pdf;    

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }
    
    public function store (Request $request)
    {
        date_default_timezone_set('America/Caracas');
        $year = date('Y');
        $applicationId = $request->input('application_id');
        if($applicationId){
            return ModelsSixthFormApplication::updateOrCreate(
                [
                    'application_id' => $applicationId,
                    'year' => $year
                ],
                $request->input()
            );
        }
        else{
            $applicationId = $this->generateApplicationId();
            return ModelsSixthFormApplication::create([
                'application_id' => $applicationId,
                'year' => $year
            ]);
        }
    }

    public function show ($applicationId, $year)
    {
        return ModelsSixthFormApplication::where([
            ['application_id', $applicationId],
            ['year', $year]
        ])->first();
    }

    public function showAll () 
    {
        $data = [];

        $applicationPeriod = SixthFormApplicationPeriod::where('current_year', 1)
        ->first();
        $applicationYear = $applicationPeriod ? $applicationPeriod->year : null;

        $applications = ModelsSixthFormApplication::select(
            'first_name',
            'last_name',
            'email',
            'year',
            'application_id',
            'birth_certificate_pin',
            'status',
            'date_of_birth',
            'subjects_option',
            'birth_certificate',
            'results_slip',
            'transfer_form',
            'picture',
            'recommendation_1'
        )
        ->where('year', $applicationYear)   
        ->get();

        foreach($applications as $application){
            
            if($application->birth_certificate){
                $application->birth_certificate = 
                URL::asset('storage/'.$application->birth_certificate); 
            }

            if($application->results_slip){
                $application->results_slip = 
                URL::asset('storage/'.$application->results_slip); 
            }

            if($application->transfer_form){
                $application->transfer_form = 
                URL::asset('storage/'.$application->transfer_form); 
            }

            if($application->picture){
                $application->picture = 
                URL::asset('storage/'.$application->picture); 
            }

            if($application->recommendation_1){
                $application->recommendation_1 = 
                URL::asset('storage/'.$application->recommendation_1);
            }

            $applicationCSECGrades = SixthFormApplicationGrade::where('application_id', $application->application_id)
            ->get();

            if(
                $application->first_name &&
                $application->last_name &&
                $application->birth_certificate_pin &&
                sizeof($applicationCSECGrades) > 0
            ){
                $data[] = $application;
            }
        }

        // return $applications;
        return $data;
    }

    public function storeGrade (Request $request)
    {
        date_default_timezone_set('America/Caracas');
        $year = $request->input('year');
        $applicationId = $request->input('application_id');
        if($applicationId){
            return SixthFormApplicationGrade::updateOrCreate(
                [
                    'application_id' => $applicationId,
                    'year' => $year,
                    'subject_id' => $request->subject_id
                ],
                [
                    'grade' => $request->grade,
                    'profiles' => $request->profiles,
                    'examination_year' => $request->examination_year
                ]
            );
        }

        return abort(500, 'No Application ID Present');
        // else{
        //     $applicationId = $this->generateApplicationId();
        //     return SixthFormApplicationGrade::create([
        //         'application_id' => $applicationId,
        //         'year' => $year
        //     ]);
        // }
    }

    public function showGrades ($applicationId, $year)
    {
        return SixthFormApplicationGrade::where([
            ['application_id', $applicationId],
            ['year', $year]
        ])->get();
    }

    public function storeSubjectChoice (Request $request)
    {
        date_default_timezone_set('America/Caracas');
        $year = $request->input('year');
        $applicationId = $request->input('application_id');
        if($applicationId){
            return SixthFormApplicationSubjects::updateOrCreate(
                [
                    'application_id' => $applicationId,
                    'year' => $year,
                    'line' => $request->input('line'),
                    'choice' => $request->input('choice')
                ],
                [
                    'subject_title' => $request->input('subject_title'),
                ]
            );
        }

        return abort(500, 'No Application ID Present');
        // else{
        //     $applicationId = $this->generateApplicationId();
        //     return SixthFormApplicationSubjects::create([
        //         'application_id' => $applicationId,
        //         'year' => $year
        //     ]);
        // }
    }

    public function showSubjectChoices ($applicationId, $year)
    {
        return SixthFormApplicationSubjects::where([
            ['application_id', $applicationId],
            ['year', $year]
        ])->get();
    }

    private function generateApplicationId () 
    {
        $length = 6;
        return 'TSS'.substr(str_shuffle(
            str_repeat($x='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) 
        )),1,$length);
    }

    public function showSubjectLines ()
    {
        $data = [];
        $line1 = []; $line2 = []; $line3 = []; $line4 = [];
        $subjects = SixthFormApplicationSubjectLine::select(
            'line',
            'subject_title',
            'subject_id'
        )
        ->get();
        foreach($subjects as $subject){
            switch ($subject->line){
                case 1:
                    array_push($line1, $subject);
                    break;
                case 2:
                    array_push($line2, $subject);
                    break;
                case 3:
                    array_push($line3, $subject);
                    break;
                case 4:
                    array_push($line4, $subject);
                    break;
            }            
        }
        $data[1] = $line1;
        $data[2] = $line2;
        $data[3] = $line3;
        $data[4] = $line4;
        return $data;
    }

    public function applicationForm ($applicationId, $year)
    {
        $school = html_entity_decode(config('app.school_name'), ENT_QUOTES, 'ISO-8859-1');
        $address = config('app.school_address');
        $contact = config('app.school_contact');
        $logo = public_path('/imgs/logo.png');
        $pictureFile = null;
        $photo = $pictureFile ?  public_path('/storage/'.$pictureFile) : null;

        $data = $this->dataSixthFormApplication($applicationId, $year);
        $studentData = $data['studentData'];
        // return $data;

        $this->pdf->AddPage("P", 'Legal');
        $this->pdf->SetMargins(10, 8);
        $this->pdf->SetDrawColor(180);

        $this->pdf->Image($logo, 10, 9, 22);
        $this->pdf->Rect(171, 6, 40, 30);
        $this->pdf->SetFont('Times', 'B', '9');
        $x = $this->pdf->GetX();
        $y = $this->pdf->GetY();
        $this->pdf->SetXY(171, 6);
        $this->pdf->SetTextColor(0);
        $this->pdf->Cell(40, 6, 'Official Use Only', 0, 0, 'C');
        $this->pdf->SetFont('Times', 'I', '9');

        $this->pdf->SetXY(171, 12);
        $rectX = $this->pdf->GetX();
        $this->pdf->Rect($rectX+2, $y+3, 3, 3);
        $accepted = ($studentData->status && $studentData->status == 'Accepted') ? 3 : null;
        $notAccepted = ($studentData->status && $studentData->status == 'Not Accepted') ? 3 : null;
        $this->pdf->SetFont('ZapfDingbats', '', 14);
        $this->pdf->Cell(8, 5, $accepted, 'L', 0, 'C');
        $this->pdf->SetFont('Times', 'B', '9');
        $this->pdf->setDash(0.6,0.6);
        $this->pdf->Cell(14, 5, 'Accepted', 0, 0, 'L');
        // $this->pdf->Cell(23, 5, '', 'B', 0, 'L');
        $this->pdf->setDash(0);

        $this->pdf->SetXY(171, 20);
        $rectX = $this->pdf->GetX();
        $option1 = (
            $studentData->subjects_option && 
            $studentData->status == 'Accepted' &&
            $studentData->subjects_option == 1
        ) ? 3 : null;
        $option2 = (
            $studentData->subjects_option && 
            $studentData->status == 'Accepted' &&
            $studentData->subjects_option == 2
        ) ? 3 : null;
        $this->pdf->Rect($rectX+15, $y+11, 3, 3);
        $this->pdf->Cell(14, 5, ' Option 1', 0, 0, 'L');
        $this->pdf->SetFont('ZapfDingbats', '', 14);
        $this->pdf->Cell(4, 5, $option1, 0, 0, 'L');
        $this->pdf->SetFont('Times', 'B', '9');
        $this->pdf->Cell(2, 5, ' ', 0, 0, 'L');
       
        $this->pdf->Rect($rectX+35, $y+11, 3, 3);
        $this->pdf->Cell(15, 5, 'Option 2', 0, 0, 'L');
        $this->pdf->SetFont('ZapfDingbats', '', 14);
        $this->pdf->Cell(4, 5, $option2, 0, 0, 'L');
        $this->pdf->SetFont('Times', 'B', '9');
        // $this->pdf->Cell(4, 5, '', 0, 0, 'L');
        $this->pdf->SetTextColor(0);

        $this->pdf->SetXY(171, 28);
        $rectX = $this->pdf->GetX();
        $this->pdf->Rect($rectX+2, $y+19, 3, 3);
        $this->pdf->SetFont('ZapfDingbats', '', 14);
        $this->pdf->Cell(8, 5, $notAccepted, 0, 0, 'C');
        $this->pdf->SetFont('Times', 'B', '9');
        $this->pdf->setDash(0.6,0.6);
        $this->pdf->Cell(19, 5, 'Not Accepted', 0, 0, 'L');
        // $this->pdf->Cell(18, 5, '', 'B', 0, 'L');
        $this->pdf->setDash(0);

        $this->pdf->SetXY($x, $y);
        $this->pdf->SetFillColor(255, 255, 255);
        
        $border= 0;
        $this->pdf->SetFont('Times', 'B', '14');        
        $this->pdf->Cell(0, 6, strtoupper($school), $border, 0, 'C');
        $this->pdf->Ln();
        
        $this->pdf->SetFont('Times', 'I', 10);
        $addressArray = explode('.', $address);
        $this->pdf->MultiCell(0, 5, $addressArray[0], $border, 'C' );
        $addressArray = array_slice($addressArray, 1);
        $addressLine2 = implode(' ', $addressArray);
        $this->pdf->MultiCell(0, 5, $addressLine2, $border, 'C' );    
        $this->pdf->Ln(18);

        $this->pdf->SetFont('Times', 'B', 14);
        $this->pdf->Cell(0, 5, $year.'-'.($year+1).' APPLICATION FORM FOR ADMISSION INTO SIXTH FORM ', $border, 0, 'C' );
        $this->pdf->SetFont('Times', 'BU', 12);
        $this->pdf->Ln(12);

        $border='B';
        $border1= 0;
        $this->pdf->setDash(0.6,0.6);
        $this->pdf->Ln();
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Cell(12, 6, 'Name:', $border1, 0, 'L');
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(183.9, 6, $studentData->first_name.' '.$studentData->last_name, $border, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Ln(8);

        $this->pdf->Cell(15, 6, 'Address:', $border1, 0, 'L');
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(180.9, 6, $studentData->address, $border, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Ln(8);

        $this->pdf->Cell(28, 6, 'Mobile Number:', $border1, 0, 'L');
        $phoneMobile = $studentData->phone_mobile ? $this->formatNumber($studentData->phone_mobile) : null;
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(59, 6, $phoneMobile, $border, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Cell(26, 6, 'Email Address:', $border1, 0, 'L');
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(82.9, 6, $studentData->email, $border, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Ln(8);

        $this->pdf->Cell(48, 6, 'Birth Certificate Pin Number:', $border1, 0, 'L');
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(49.9, 6, $studentData->birth_certificate_pin, $border, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Cell(46, 6, 'Date of Birth (dd/mm/yyyy):', $border1, 0, 'L');
        $dob = $studentData->date_of_birth;  
        $dob = $dob ? date_format(date_create($studentData->date_of_birth), 'd/m/Y') : null;
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(52, 6, $dob, $border, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Ln(8);

        $this->pdf->Cell(28, 6, 'Previous School:', $border1, 0, 'L');
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(167.9, 6, $studentData->previous_school, $border, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Ln(8);

        $this->pdf->Cell(40, 6, 'Parent / Guardian Name:', $border1, 0, 'L');
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(100, 6, $studentData->parent_name, $border, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $phoneMobileParent = $studentData->phone_mobile_parent ? $this->formatNumber($studentData->phone_mobile_parent) : null;
        $this->pdf->Cell(28, 6, "Mobile Number:", 0, 0, 'L');
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(27.9, 6, $phoneMobileParent, $border, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Ln(8);

        $this->pdf->Cell(28, 6, 'Proposed Career:', $border1, 0, 'L');
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(167.9, 6, $studentData->proposed_career, $border, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Ln(8);

        // $this->pdf->Cell(65, 6, 'TELEPHONE CONTACT: Parent: (h)', 0, 0, 'L');
        // $phoneHomeParent = $studentData->phone_home_parent ? $this->formatNumber($studentData->phone_home_parent) : null;
        // $this->pdf->Cell(62, 6, $phoneHomeParent, $border, 0, 'L');
        // $this->pdf->Cell(6, 6, '(c)', $border, 0, 'L');
        // $phoneMobileParent = $studentData->phone_mobile_parent ? $this->formatNumber($studentData->phone_mobile_parent) : null;
        // $this->pdf->Cell(62.9, 6, $phoneMobileParent, $border, 0, 'L');
        // $this->pdf->Ln(10);

        $this->pdf->SetFont('Times', 'BI', 11);
        $this->pdf->Cell(195.9, 8, 'CXC CSEC Results', $border, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Ln();

        $border=1;
        $this->pdf->setDash();
        $this->pdf->SetFillColor(220,220,220);
        $this->pdf->SetDrawColor(100, 100, 100);
        $this->pdf->SetFont('Times', 'B', 11);
        $this->pdf->Cell(115, 8, 'SUBJECTS', $border, 0, 'C', true);
        $this->pdf->Cell(32, 8, 'GRADE(I,II,III)', $border, 0, 'L', true);
        $this->pdf->Cell(48.9, 8, 'PROFILES(e.g A,B,A)', $border, 0, 'L', true);
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Ln();

        $csecSubjects = $data['csecSubjectGrades'];
        $grade1s = 0;
        $grade2s = 0;
        $grade3s = 0;

        for($i = 0; $i < 10; $i++){
            $subject = isset($csecSubjects[$i]) ? $csecSubjects[$i]->title : null;
            // $grade = isset($csecSubjects[$i]) ? $csecSubjects[$i]->grade: null;
            $grade = null;
            if(isset($csecSubjects[$i])){
                $grade = $csecSubjects[$i]->grade;
                switch($grade){
                    case 'I':
                        $grade1s+=1;
                        break;
                    case 'II':
                        $grade2s+=1;
                        break;
                    case 'III':
                        $grade3s+=1;
                        break;
                }
            }

            $profiles = isset($csecSubjects[$i]) ? $csecSubjects[$i]->profiles: null;
            $this->pdf->Cell(5, 6, ($i+1).'.', $border, 0, 'C');
            $this->pdf->Cell(110, 6, $subject, $border, 0, 'L');
            $this->pdf->Cell(32, 6, $grade, $border, 0, 'C');
            $this->pdf->Cell(48.9, 6, $profiles, $border, 0, 'C');
            $this->pdf->SetFont('Times', '', 11);
            $this->pdf->Ln();
        }
        $this->pdf->Ln();

        // $this->pdf->SetFont('Times', 'BI', 11);
        // $this->pdf->Cell(0, 8, 'Grade Totals', 0, 0, 'L');
        // $this->pdf->SetFont('Times', '', 11);
        // $this->pdf->Ln();

        // $this->pdf->SetFont('Times', 'B', 11);
        // $this->pdf->Cell(46, 6, '', 0, 0, 'L');
        // $this->pdf->Cell(35, 6, 'No. grade 1s', 1, 0, 'L');
        // $this->pdf->Cell(35, 6, 'No. grade 2s', 1, 0, 'L');
        // $this->pdf->Cell(35, 6, 'No. grade 3s', 1, 0, 'L');
        // $this->pdf->Cell(45.9, 6, '', 0, 0, 'L');
        // $this->pdf->Ln();

        // $this->pdf->SetFont('Times', '', 11);
        // $this->pdf->Cell(46, 6, '', 0, 0, 'L');
        // $this->pdf->Cell(35, 6, $grade1s ? $grade1s : null, 1, 0, 'C');
        // $this->pdf->Cell(35, 6, $grade2s ? $grade2s : null, 1, 0, 'C');
        // $this->pdf->Cell(35, 6, $grade3s ? $grade3s : null, 1, 0, 'C');
        // $this->pdf->Cell(45.9, 6, '', 0, 0, 'L');
        // $this->pdf->Ln(15);

        // $this->pdf->SetFont('Times', 'IU', 11);
        // $this->pdf->Cell(195.9, 8, 'CHOICE OF ADVANCED LEVEL SUBJECTS', 0, 0, 'C');
        // $this->pdf->SetFont('Times', '', 11);
        // $this->pdf->Ln();

        $this->pdf->SetFont('Times', 'BI', 11);
        $this->pdf->Cell(0, 6, 'Subject groupings', 0, 0, 'L');
        $this->pdf->Ln();
        
        $this->pdf->Cell(195.9, 6, 'Choose three (3) subjects - ONLY ONE(1) SUBJECT PER ROW', 0, 0, 'L');
        $this->pdf->Ln(12);

        $this->pdf->SetFont('Times', 'B', 11);
        $this->pdf->Cell(195.9, 6, 'OPTION 1', 0, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Ln();

        $line1 = $this->dataSubjectLine(1,1,$applicationId, $year);
        $line2 = $this->dataSubjectLine(2,1,$applicationId, $year);
        $line3 = $this->dataSubjectLine(3,1,$applicationId, $year);
        $line4 = $this->dataSubjectLine(4,1,$applicationId, $year);

        $this->pdf->SetFont('Times', 'B', 11);
        $this->pdf->Cell(55.3, 8, 'LINE 1', $border, 0, 'C', true);
        $this->pdf->Cell(10, 8, 'Tick', $border, 0, 'C', true);
        $this->pdf->Cell(55.3, 8, 'LINE 2', $border, 0, 'C', true);
        $this->pdf->Cell(10, 8, 'Tick', $border, 0, 'C', true);
        $this->pdf->Cell(55.3, 8, 'LINE 3', $border, 0, 'C', true);
        $this->pdf->Cell(10, 8, 'Tick', $border, 0, 'C', true);
        // $this->pdf->Cell(38.9, 8, 'LINE 4', $border, 0, 'C', true);
        // $this->pdf->Cell(10, 8, 'Tick', $border, 0, 'C', true);
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->Ln();

        
        for($i = 0; $i <= 5; $i++){
            $subject1 = isset($line1[$i]) ? $line1[$i]->subject_title : null;
            if(strlen($subject1) > 35){
                $subject1 = substr($subject1, 0, 33)."..";
            }
            $subject2 = isset($line2[$i]) ? $line2[$i]->subject_title : null;
            $subject3 = isset($line3[$i]) ? $line3[$i]->subject_title : null;
            // $subject4 = isset($line4[$i]) ? $line4[$i]->subject_title : null;

            $tick1 = isset($line1[$i]) ? $line1[$i]->tick : null;
            $tick2 = isset($line2[$i]) ? $line2[$i]->tick : null;
            $tick3 = isset($line3[$i]) ? $line3[$i]->tick : null;
            // $tick4 = isset($line4[$i]) ? $line4[$i]->tick : null;

            
            $this->pdf->Cell(55.3, 5, $subject1, $border, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $this->pdf->Cell(10, 5, $tick1, $border, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);  
            $this->pdf->Cell(55.3, 5, $subject2, $border, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $this->pdf->Cell(10, 5, $tick2, $border, 0, 'C');
            $this->pdf->SetFont('Times', '', 10); 
            $this->pdf->Cell(55.3, 5, $subject3, $border, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $this->pdf->Cell(10, 5, $tick3, $border, 0, 'C');
            $this->pdf->SetFont('Times', '', 10); 
            // $this->pdf->Cell(38.9, 5, $subject4, $border, 0, 'L');
            // $this->pdf->SetFont('ZapfDingbats', '', 13);
            // $this->pdf->Cell(10, 5, $tick4, $border, 0, 'C');
            // $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();
        }
        

        $line1 = $this->dataSubjectLine(1,2,$applicationId, $year);
        $line2 = $this->dataSubjectLine(2,2,$applicationId, $year);
        $line3 = $this->dataSubjectLine(3,2,$applicationId, $year);
        // $line4 = $this->dataSubjectLine(4,2,$applicationId, $year);
        $this->pdf->Ln(6);

        $this->pdf->SetFont('Times', 'B', 11);
        $this->pdf->Cell(195.9, 6, 'OPTION 2', 0, 0, 'L');
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Ln();

        $this->pdf->SetFont('Times', 'B', 11);
        $this->pdf->Cell(55.3, 8, 'LINE 1', $border, 0, 'C', true);
        $this->pdf->Cell(10, 8, 'Tick', $border, 0, 'C', true);
        $this->pdf->Cell(55.3, 8, 'LINE 2', $border, 0, 'C', true);
        $this->pdf->Cell(10, 8, 'Tick', $border, 0, 'C', true);
        $this->pdf->Cell(55.3, 8, 'LINE 3', $border, 0, 'C', true);
        $this->pdf->Cell(10, 8, 'Tick', $border, 0, 'C', true);
        // $this->pdf->Cell(38.9, 8, 'LINE 4', $border, 0, 'C', true);
        // $this->pdf->Cell(10, 8, 'Tick', $border, 0, 'C', true);
        $this->pdf->SetFont('Times', '', 10);
        $this->pdf->Ln();

        for($i = 0; $i <= 3; $i++){
            $subject1 = isset($line1[$i]) ? $line1[$i]->subject_title : null;
            if(strlen($subject1) > 35){
                $subject1 = substr($subject1, 0, 33)."..";
            }
            $subject2 = isset($line2[$i]) ? $line2[$i]->subject_title : null;
            $subject3 = isset($line3[$i]) ? $line3[$i]->subject_title : null;
            // $subject4 = isset($line4[$i]) ? $line4[$i]->subject_title : null;

            $tick1 = isset($line1[$i]) ? $line1[$i]->tick : null;
            $tick2 = isset($line2[$i]) ? $line2[$i]->tick : null;
            $tick3 = isset($line3[$i]) ? $line3[$i]->tick : null;
            // $tick4 = isset($line4[$i]) ? $line4[$i]->tick : null;

            
            $this->pdf->Cell(55.3, 6, $subject1, $border, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $this->pdf->Cell(10, 6, $tick1, $border, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);  
            $this->pdf->Cell(55.3, 6, $subject2, $border, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $this->pdf->Cell(10, 6, $tick2, $border, 0, 'C');
            $this->pdf->SetFont('Times', '', 10); 
            $this->pdf->Cell(55.3, 6, $subject3, $border, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $this->pdf->Cell(10, 6, $tick3, $border, 0, 'C');
            $this->pdf->SetFont('Times', '', 10); 
            // $this->pdf->Cell(38.9, 6, $subject4, $border, 0, 'L');
            // $this->pdf->SetFont('ZapfDingbats', '', 13);
            // $this->pdf->Cell(10, 6, $tick4, $border, 0, 'C');
            // $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();
        }
        $this->pdf->Ln(10);
        
        $border="B";
        $this->pdf->setDash(0.6,0.6);
        $this->pdf->SetFont('Times', '', 11);
        $this->pdf->Cell(33, 6, "Student's Signature:", 0, 0, 'L');
        $this->pdf->Cell(64.9, 6, '', $border, 0, 'L');
        $this->pdf->Cell(15, 6, 'Date', 0, 0, 'L');
        $this->pdf->Cell(83, 6, '', $border, 0, 'L');
        
        $this->pdf->Ln(4);

        
        // $this->pdf->Ln();
        // $this->pdf->Cell(40, 6, "Student's email address", 0, 0, 'L');
        // $this->pdf->Cell(57, 6, $studentData->email, $border, 0, 'L');
        // $this->pdf->Cell(42, 6, "Parent's email address", 0, 0, 'L');
        // $this->pdf->Cell(56.9, 6, $studentData->email_parent, $border, 0, 'L');

        
        // $csecSlip = $studentData->results_slip ? 3 : null;

        // $this->pdf->SetFont('Times', '', 12);
        // $this->pdf->Cell(59, 8, "", 0, 'L');
        // $y = $this->pdf->getY();
        // $x = $this->pdf->getX();
        // $this->pdf->Rect($x+5, $y+2.5, 3, 3);
        // $this->pdf->Cell(4, 8, "", 'LT', 'L');
        // $this->pdf->SetFont('ZapfDingbats', '', 14);
        // $this->pdf->Cell(6, 8, $csecSlip, 'T', 'L');
        // $this->pdf->SetFont('Times', '', 10); 
        // $this->pdf->Cell(68, 8, "Copy of CSEC result slip", 'RT', 'L');
        // $this->pdf->Cell(58.9, 8, "", 0, 'L');
        // $this->pdf->SetFont('Times', '', 12);
        // $this->pdf->Ln();

        // $passportPhoto = $studentData->picture ? 3 : null;

        // $this->pdf->Cell(59, 8, "", 'R', 'L');
        // $y = $this->pdf->getY();
        // $x = $this->pdf->getX();
        // $this->pdf->Rect($x+5, $y+2.5, 3, 3);
        // $this->pdf->Cell(4, 8, "", 'L', 'L');
        // $this->pdf->SetFont('ZapfDingbats', '', 14);
        // $this->pdf->Cell(6, 8, $passportPhoto, 0, 'L');
        // $this->pdf->SetFont('Times', '', 12); 
        // $this->pdf->Cell(68, 8, "Passport size photograph", 'R', 'L');
        // $this->pdf->Cell(58.9, 8, "", 0, 'L');
        // $this->pdf->SetFont('Times', '', 12);
        // $this->pdf->Ln();

        // $transferForm = $studentData->trasfer_form ? 3 : null;

        // $this->pdf->SetFont('Times', '', 12);
        // $this->pdf->Cell(59, 8, "", 0, 'L');
        // $y = $this->pdf->getY();
        // $x = $this->pdf->getX();
        // $this->pdf->Rect($x+5, $y+2.5, 3, 3);
        // $this->pdf->Cell(4, 8, "", 'L', 'L');
        // $this->pdf->SetFont('ZapfDingbats', '', 14);
        // $this->pdf->Cell(6, 8, $transferForm, 0, 'L');
        // $this->pdf->SetFont('Times', '', 12); 
        // $this->pdf->Cell(68, 8, "A transfer form from previous school", 'R', 'L');
        // $this->pdf->Cell(58.9, 8, "", 0, 'L');
        // $this->pdf->SetFont('Times', '', 12);
        // $this->pdf->Ln();

        // $birthCertificate = $studentData->birth_certificate ? 3 : null;
        
        // $y = $this->pdf->getY();
        // $this->pdf->Cell(59, 8, "", 0, 'L');
        // $x = $this->pdf->getX();
        // $this->pdf->Rect($x+5, $y+2.5, 3, 3);
        // $this->pdf->Cell(4, 8, "", 'L', 'L');
        // $this->pdf->SetFont('ZapfDingbats', '', 14);
        // $this->pdf->Cell(6, 8, $birthCertificate, 0, 'L');
        // $this->pdf->SetFont('Times', '', 12); 
        // $this->pdf->Cell(68, 8, "Original birth certificate and copy", 'R', 'L');
        // $this->pdf->Cell(58.9, 6, "", 0, 'L');
        // $this->pdf->SetFont('Times', '', 12);
        // $this->pdf->Ln();

        // $recommendation1 = $studentData->recommendation_1 ? 3 : null;

        // $y = $this->pdf->getY();
        // $this->pdf->Cell(59, 8, "", 0, 'L');
        // $x = $this->pdf->getX();
        // $this->pdf->Rect($x+5, $y+2.5, 3, 3);
        // $this->pdf->Cell(4, 8, "", 'LB', 'L');
        // $this->pdf->SetFont('ZapfDingbats', '', 14);
        // $this->pdf->Cell(6, 8, $recommendation1, 'B', 'L');
        // $this->pdf->SetFont('Times', '', 12); 
        // $this->pdf->Cell(68, 8, "Recommendation", 'RB', 'L');
        // $this->pdf->Cell(58.9, 8, "", 0, 'L');
        // $this->pdf->SetFont('Times', '', 12);
        // $this->pdf->Ln();



        $this->pdf->Output();
        exit;

    }

    private function dataSixthFormApplication($applicationId, $year)
    {
        $data = [];
        $studentData = ModelsSixthFormApplication::where([
            ['application_id', $applicationId],
            ['year', $year]
        ])->first();

        if(!$studentData) {
            $studentData = ModelsSixthFormApplication::create([
                'application_id' => $applicationId,
                'year' => $year
            ]);
        }
        $data['studentData'] = $studentData;

        $data['subjectChoices'] = SixthFormApplicationSubjects::where([
            ['application_id', $applicationId],
            ['year', $year]
        ])->get();

        $data['csecSubjectGrades'] = SixthFormApplicationGrade::join(
            'csec_subjects',
            'csec_subjects.id',
            'sixth_form_application_grades.subject_id'
        )
        ->where([
            ['application_id', $applicationId],
            ['year', $year]
        ])->get();

        return $data;
    }

    private function dataSubjectLine ($line, $choice, $applicationId, $year) 
    {

        $lineSubjects = SixthFormApplicationSubjectLine::where('line', $line)
        ->get();

        foreach($lineSubjects as $subject){
            $studentSubjectChoice = SixthFormApplicationSubjects::where([
                ['application_id', $applicationId],
                ['year', $year],
                ['line', $line],
                ['choice', $choice],
                ['subject_title', $subject->subject_title]
            ])->first();
            $subject->tick = null;
            if($studentSubjectChoice){
                $subject->tick = 3;
            }
        }

        return $lineSubjects;
    }

    private function formatNumber ($number) 
    {
        if($number) return "(868) ".substr($number, 0, 3)."-".substr($number, -4);
        return $number;
    }

    public function instructions ()
    {
        $school = html_entity_decode(config('app.school_name'), ENT_QUOTES, 'ISO-8859-1');
        $address = config('app.school_address');
        $contact = config('app.school_contact');
        $logo = public_path('/imgs/logo.png');

        $this->pdf->AddPage("P", 'Legal');
        $this->pdf->SetMargins(10, 8);
        $this->pdf->SetDrawColor(180);

        $this->pdf->Image($logo, 10, 6, 30);

        $border=0;
        $this->pdf->SetFont('Times', 'B', '14');        
        $this->pdf->MultiCell(0, 6, strtoupper($school), $border, 'C');
        
        $this->pdf->SetFont('Times', 'I', 10);
        $this->pdf->MultiCell(0, 5, $address, $border, 'C' );
        $this->pdf->MultiCell(0, 5, $contact, $border, 'C' );
        $this->pdf->SetFillColor(220,220,220);
        $this->pdf->SetFont('Times', 'BU', 14);
        $this->pdf->MultiCell(0, 10, 'SUBJECT REQUIREMENTS', 0, 'C' );
        $border=1;
        $this->pdf->Cell(20, 6, "", 0, 0, 'L');
        $this->pdf->SetFont('Times', 'B', 13);
        $this->pdf->Cell(78, 6, "SUBJECT", $border, 0, 'C', true);
        $this->pdf->Cell(78, 6, "REQUIREMENTS", $border, 0, 'C', true);
        $this->pdf->Cell(19.9, 6, '', 0, 0, 'L');
        $this->pdf->Ln();

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "1. ACCOUNTING\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Principles of Accounts - I\n\n", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "2. ART AND  DESIGN\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Visual Arts - I or II\n\n", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "3. APPLIED MATHEMATICS\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Mathematics - I AND \nAdditional Mathematics", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "4. BIOLOGY\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Biology - I\n\n", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "5. CHEMISTRY\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Chemistry - I\n\n", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "6. COMPUTER SCIENCE\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Information Technology - I or II AND\nMathematics - I or II", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "7. ECONOMICS\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Principles of Business - I or II OR\nEconomics - I or II", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "8. ENTREPRENEURSHIP\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Principles of Business - I or II\n\n", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "9. ENVIRONMENTAL SCIENCE\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Geography - I AND\nBiology/Chemistry/Physics/Int. Sci - I", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "10. GEOGRAPHY\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Geography - I AND\nMathematics - I or II", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "11. HISTORY\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "History - I or II\n\n", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');


        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "12. LITERATURES IN ENGLISH\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Enligsh Literature - I or II\n\n", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "13. MANAGEMENT OF BUSINESS\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Principles of Accounts - I or II AND\nPrinciples of Business - I or II", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "14. PHYSICS\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Physics - I AND Mathematics - I AND\nAdditional Mathematics", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "15. PURE MATHEMATICS\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Mathematics - I AND\nAdditional Mathematics", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "16. SOCIOLOGY\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "English Literature - I OR\nSocial Studies - I", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');

        $this->pdf->SetFont('Times', '', 12);
        $x=$this->pdf->GetX();
        $y=$this->pdf->GetY();
        $this->pdf->MultiCell(20, 6, "\n\n", 0, 'L');
        $this->pdf->setXY($x+20,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(10, 6, "\n\n", 'BTL', 'L');
        $this->pdf->setXY($x+10,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(68, 6, "17. SPANISH\n\n", 'BTR', 'L');
        $this->pdf->setXY($x+68,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(5, 6, "\n\n", 'LTB','L');
        $this->pdf->setXY($x+5,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(73, 6, "Spanish - I AND\nEnglish Literature I", 'RTB', 'L');
        $this->pdf->setXY($x+73,$y);
        $x=$this->pdf->GetX();
        $this->pdf->MultiCell(19.9, 6, "\n\n", 0, 'L');
        $this->pdf->Ln();

        $this->pdf->SetLineWidth(1);
        $this->pdf->Cell(10, 10, "", 0, 0, 'L');
        $this->pdf->SetFont('Times', 'BIU', 12);
        $this->pdf->Cell(78, 10, "MINIMUM REQUIREMENTS:", 'TL', 0, 'C');
        $this->pdf->Cell(98, 10, "FIVE (5) CSEC Subjects", 'TR', 0, 'L');
        $this->pdf->Cell(9.9, 10, "", 0, 0, 'L');
        $this->pdf->Ln();

        $this->pdf->Cell(10, 8, "", 0, 0, 'L');
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(10, 8, "", 'L', 0, 'C');
        $this->pdf->Cell(68, 8, "1. Mathematics (I or II)", 0, 0, 'L');
        $this->pdf->SetFont('Times', 'BI', 12);
        $this->pdf->Cell(98, 8, "[Compulsory]", 'R', 0, 'L');
        $this->pdf->Cell(9.9, 8, "", 0, 0, 'L');
        $this->pdf->Ln();

        $this->pdf->Cell(10, 8, "", 0, 0, 'L');
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(10, 8, "", 'L', 0, 'C');
        $this->pdf->Cell(68, 8, "2. English A (I or II)", 0, 0, 'L');
        $this->pdf->SetFont('Times', 'BI', 12);
        $this->pdf->Cell(98, 8, "[Compulsory]", 'R', 0, 'L');
        $this->pdf->Cell(9.9, 8, "", 0, 0, 'L');
        $this->pdf->Ln();

        $this->pdf->Cell(10, 8, "", 0, 0, 'L');
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(10, 8, "", 'LB', 0, 'C');
        $this->pdf->Cell(68, 8, "3. Any other three (3) subjects", 'B', 0, 'L');
        $this->pdf->SetFont('Times', 'BI', 12);
        $this->pdf->Cell(98, 8, "[Preferably I's in subjects being pursued in Form 6]", 'RB', 0, 'L');
        $this->pdf->Cell(9.9, 8, "", 0, 0, 'L');
        $this->pdf->Ln(15);

        $this->pdf->SetFont('Times', 'UI', 12);;
        $this->pdf->Cell(20, 6, "");
        $this->pdf->Cell(0, 6, "APPLICATION DOCUMENTS FOR SIXTH FORM:");
        $this->pdf->Ln(8);

        $this->pdf->SetLineWidth(0.2);
        $this->pdf->Cell(20, 6, "", 0, 'L');
        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->Cell(78, 10, "Checklist - Students from St. Francois:", 'TLR', 'L');
        $this->pdf->Cell(78, 10, "Checklist - Students from other schools:", 'TLR', 'L');
        $this->pdf->Cell(19.9, 6, "", 0, 'L');
        $this->pdf->SetFont('Times', '', 12);
        $this->pdf->Ln();

        $this->pdf->Cell(20, 6, "", 0, 'L');
        $this->pdf->SetFont('Times', '', 12);
        $x = $this->pdf->getX();
        $y = $this->pdf->getY();
        $this->pdf->Rect($x+5, $y+2.5, 3, 3);
        $this->pdf->Cell(10, 10, "", 'L', 'L');
        $this->pdf->Cell(68, 10, "Copy of CSEC result slip", 'R', 'L');
        $x = $this->pdf->getX();
        $this->pdf->Rect($x+5, $y+2.5, 3, 3);
        $this->pdf->Cell(10, 10, "", 'L', 'L');
        $this->pdf->Cell(68, 10, "Copy of CSEC result slip", 'R', 'L');
        $this->pdf->Cell(19.9, 6, "", 0, 'L');
        $this->pdf->SetFont('Times', '', 12);
        $this->pdf->Ln();

        $this->pdf->Cell(20, 6, "", 0, 'L');
        $this->pdf->SetFont('Times', '', 12);
        $x = $this->pdf->getX();
        $y = $this->pdf->getY();
        $this->pdf->Rect($x+5, $y+2.5, 3, 3);
        $this->pdf->Cell(10, 10, "", 'L', 'L');
        $this->pdf->Cell(68, 10, "Two passport size photographs", 'R', 'L');
        $x = $this->pdf->getX();
        $this->pdf->Rect($x+5, $y+2.5, 3, 3);
        $this->pdf->Cell(10, 10, "", 'L', 'L');
        $this->pdf->Cell(68, 10, "Two passport size photographs", 'R', 'L');
        $this->pdf->Cell(19.9, 6, "", 0, 'L');
        $this->pdf->SetFont('Times', '', 12);
        $this->pdf->Ln();

        $this->pdf->Cell(20, 6, "", 0, 'L');
        $this->pdf->SetFont('Times', '', 12);
        $x = $this->pdf->getX();
        $y = $this->pdf->getY();
        // $this->pdf->Rect($x+5, $y+3.5, 3, 3);
        $this->pdf->Cell(10, 10, "", 'L', 'L');
        $this->pdf->Cell(68, 10, "", 'R', 'L');
        $x = $this->pdf->getX();
        $this->pdf->Rect($x+5, $y+2.5, 3, 3);
        $this->pdf->Cell(10, 10, "", 'L', 'L');
        $this->pdf->Cell(68, 10, "A transfer form from previous school", 'R', 'L');
        $this->pdf->Cell(19.9, 6, "", 0, 'L');
        $this->pdf->SetFont('Times', '', 12);
        $this->pdf->Ln();

        $this->pdf->Cell(20, 6, "", 0, 'L');
        $this->pdf->SetFont('Times', '', 12);
        $x = $this->pdf->getX();
        $y = $this->pdf->getY();
        // $this->pdf->Rect($x+5, $y+3.5, 3, 3);
        $this->pdf->Cell(10, 10, "", 'LB', 'L');
        $this->pdf->Cell(68, 10, "", 'RB', 'L');
        $x = $this->pdf->getX();
        $this->pdf->Rect($x+5, $y+2.5, 3, 3);
        $this->pdf->Cell(10, 10, "", 'LB', 'L');
        $this->pdf->Cell(68, 10, "Original birth certificate and copy", 'RB', 'L');
        $this->pdf->Cell(19.9, 6, "", 0, 'L');
        $this->pdf->SetFont('Times', '', 12);
        $this->pdf->Ln();


        $this->pdf->Output();
        exit;
    }

    public function checkApplication ($applicationId, $birthCertificatePin)
    {
        return ModelsSixthFormApplication::where([
            ['application_id', $applicationId],
            ['birth_certificate_pin', $birthCertificatePin]
        ])->first();
    }

    public function delete (Request $request)
    {
        $applicationId = $request->input('application_id');
        $year = $request->input('year');

        $data['deletedCSECSubjects'] = SixthFormApplicationGrade::where([
            ['application_id', $applicationId],
            ['year', $year]
        ])->delete();

        $data['deletedSubjectChoices'] = SixthFormApplicationSubjects::where([
            ['application_id', $applicationId],
            ['year', $year]
        ])->delete();

        $data['deletedApplication'] = ModelsSixthFormApplication::where([
            ['application_id', $applicationId],
            ['year', $year]
        ])->delete();

        return $data;
    }

    public function acceptedPDF ($year)
    {
        $maxChars = 30;

        $this->pdf->AliasNbPages();
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->AddPage("P", 'Letter');
        $this->pdf->SetMargins(10, 8);
        $this->pdf->SetDrawColor(180);

        $data = $this->acceptedPDFData($year);
        $this->header($year);
        // return $data;

        $border=1;
        $this->pdf->SetFont('Times', '', 11);
        foreach($data as $index => $record){
            $x = $this->pdf->GetX();
            $y = $this->pdf->GetY();
            $this->pdf->MultiCell(10, 6, ($index+1).".\n\n", 'TBL', 'R');
            $this->pdf->SetXY($x+10,$y);
            $x = $this->pdf->GetX();
            $name = $record->last_name.", ".$record->first_name;
            $this->pdf->MultiCell(40, 6, $name."\n\n",'TBR', 'L');
            $this->pdf->SetXY($x+40,$y);
            $x = $this->pdf->GetX();
            $this->pdf->MultiCell(23, 6, ($record->date_of_birth)."\n\n", $border,'C');
            $this->pdf->SetXY($x+23,$y);
            $x = $this->pdf->GetX();
            $this->pdf->MultiCell(34.4, 6, ($record->birth_certificate_pin)."\n\n", $border,'C');
            $this->pdf->SetXY($x+34.4,$y);
            $x = $this->pdf->GetX();
            $phoneMobile = $record->phone_mobile ? $this->formatNumber($record->phone_mobile) : null;
            $this->pdf->MultiCell(33, 6, $phoneMobile."\n\n", $border, 'C');
            
            $this->pdf->SetXY($x+33,$y);
            $x = $this->pdf->GetX();
            if(strlen($record->email) > $maxChars){
                $this->pdf->MultiCell(0, 6, $record->email, $border, 'L');
            }
            else{
                $this->pdf->MultiCell(0, 6, ($record->email)."\n\n", $border, 'L');
            }
            // $this->pdf->SetXY($x+33,$y);
            // $x = $this->pdf->GetX();
            // if(strlen($record->email_parent) > $maxChars){
            //     $this->pdf->MultiCell(43, 6, $record->email_parent, $border, 'C');
            // }
            // else{
            //     $this->pdf->MultiCell(43, 6, ($record->email_parent)."\n\n", $border, 'C');
            // }            
            // $this->pdf->SetXY($x+43,$y);
            // $x = $this->pdf->GetX();
            // $phoneMobileParent = $record->phone_mobile_parent ? $this->formatNumber($record->phone_mobile_parent) : null;
            // $this->pdf->MultiCell(33, 6, $phoneMobileParent."\n\n", $border,'C');
            
            if($index && $index%11 == 0){
                $this->footer();
                $this->pdf->AddPage("L", 'Letter');
                $this->header($year);
                $this->pdf->SetFont('Times', '', 11);
            }
        }
        $this->footer();
        $this->pdf->Output();
        exit;
    }

    private function acceptedPDFData ($year) 
    {
        return ModelsSixthFormApplication::where([
            ['year', $year],
            ['status', 'Accepted']
        ])->get();
    }
    
    private function footer ()
    {
        $this->pdf->SetTextColor(0); 
        $this->pdf->setY(-15);
        $this->pdf->SetFont('Times', 'I', 8);
        $this->pdf->Cell(45, 8, 'Generated at '.date('d M Y h:i a'));
        $this->pdf->Cell(0, 8, 'Page: '.$this->pdf->PageNo().'/{nb}', 0, 0, 'R');
    }
    
    private function header ($year)
    {
        $school = html_entity_decode(config('app.school_name'), ENT_QUOTES, 'ISO-8859-1');
        $address = config('app.school_address');
        $contact = config('app.school_contact');
        $logo = public_path('/imgs/logo.png');

        $this->pdf->Image($logo, 10, 6, 20);

        $border=0;
        $this->pdf->SetFont('Times', 'B', '14');        
        $this->pdf->MultiCell(0, 6, strtoupper($school), $border, 'C');
        
        $this->pdf->SetFont('Times', 'I', 10);
        $this->pdf->MultiCell(0, 5, $address, $border, 'C' );
        // $this->pdf->MultiCell(0, 5, $contact, $border, 'C' );
        $this->pdf->SetFillColor(220,220,220);
        $this->pdf->SetFont('Times', 'BU', 14);
        $this->pdf->MultiCell(0, 8, $year."-".($year+1).' SIXTH FORM ACCEPTED LIST', 0, 'C' );
        $this->pdf->Ln();

        $border=1;
        $this->pdf->SetFont('Times', '', 12);
        $this->pdf->SetFillColor(220,220,220);
        $x = $this->pdf->GetX();
        $y = $this->pdf->GetY();
        $this->pdf->MultiCell(10, 6, "\n\n", 'TBL', 'L', true );
        $this->pdf->SetXY($x+10,$y);
        $x = $this->pdf->GetX();
        $this->pdf->MultiCell(40, 6, "Name\n\n",'TBR', 'L', true );
        $this->pdf->SetXY($x+40,$y);
        $x = $this->pdf->GetX();
        $this->pdf->MultiCell(23, 6, "D.O.B\n\n", $border, 'C', true );
        $this->pdf->SetXY($x+23,$y);
        $x = $this->pdf->GetX();
        $this->pdf->MultiCell(34.4, 6, "Birth Cert. Pin\n\n", $border, 'C', true );
        $this->pdf->SetXY($x+34.4,$y);
        $x = $this->pdf->GetX();
        $this->pdf->MultiCell(33, 6, "Mobile\n\n", $border, 'C', true );
        $this->pdf->SetXY($x+33,$y);
        $x = $this->pdf->GetX();
        $this->pdf->MultiCell(0, 6, "Email\n\n", $border, 'L', true );
        // $this->pdf->SetXY($x+43,$y);
        
        // $x = $this->pdf->GetX();
        // $this->pdf->MultiCell(43, 6, "Parent Email\n\n", $border, 'L', true );
        // $this->pdf->SetXY($x+43,$y);
        // $x = $this->pdf->GetX();
        // $this->pdf->MultiCell(33, 6, "Parent\nPhone (C)", $border, 'C', true );
    }
    
    public function acceptedSpreadsheet ($year)
    {
        $data = $this->acceptedSpreadsheetData($year);
        // return $data;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            "Last Name",
            "First Name",
            "Date of Birth",
            "Birth Certificate Pin",
            "Mobile",
            "Email",
            // "Email (Parent)",
            // "Phone(C) (Parent)",
        ];

        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->fromArray($data, NULL, 'A2');

        $sheet->freezePane('C2');
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getRowDimension('1')->setRowHeight(25);

        $styleArray = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFBFBFBF',
                ]
            ]
        ];

        $sheet->getStyle('A1:'.$highestColumn.'1')->applyFromArray($styleArray);

        $highestRow = $sheet->getHighestRow();
        foreach($sheet->getColumnIterator() as $col){            
            $sheet->getColumnDimension($col->getColumnIndex())->setAutoSize(true);
            for($row = 1; $row <= $highestRow; $row++){
                $sheet->getStyle($col->getColumnIndex().$row.':'.$col->getColumnIndex().$row)->getAlignment()
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                // $value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
                if($row == 1){
                    $value = $sheet->getCell($col->getColumnIndex().$row)->getValue();
                    if(
                        $value == 'Date of Birth' ||
                        $value == 'Birth Certificate Pin' ||
                        $value == 'Phone(C) (Student)' ||
                        $value == 'Phone(C) (Parent)'
                    ){
                        $sheet->getStyle($col->getColumnIndex().$row.':'.$col->getColumnIndex().$highestRow)->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    } 
                }
                
            }
                       
        }

        $sheet->setTitle($year." Accepted Form 6");
        $file = $year." Accepted Sixth Form ".date('Ymdhis').".xlsx";
        $filePath = storage_path('app/public/'.$file);
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        return response()->download($filePath, $file);
    }

    private function acceptedSpreadsheetData ($year)
    {
        $data = [];

        $acceptedApplications = ModelsSixthFormApplication::where([
            ['year', $year],
            ['status', 'Accepted']
        ])->get();

        foreach($acceptedApplications as $record){
            $application = [];
            $phoneMobile = $this->formatNumber($record->phone_mobile);
            $phoneMobileParent = $this->formatNumber($record->phone_mobile_parent);
            array_push(
                $application, 
                $record->last_name,
                $record->first_name,
                $record->date_of_birth,
                $record->birth_certificate_pin,
                $phoneMobile,
                $record->email,                
                // $record->email_parent,
                // $phoneMobileParent
            );
            $data[] = $application;
        }

        return $data;
        
    }

    public function applicationsLock (Request $request)
    {
        
        
        $applicationPeriod = SixthFormApplicationPeriod::firstOrCreate([
            'year' => $request->input('year')
        ]);

        if($applicationPeriod){
            $applicationPeriod->locked = $request->input('locked');
            //not locked set as current
            if(!$request->input('locked')) {
                SixthFormApplicationPeriod::where('id', '>=', '1')
                ->update(['current_year' => 0]);
                $applicationPeriod->current_year = 1;
                $applicationPeriod->locked = 0;
            }
            $applicationPeriod->save();
        }
        

        return $applicationPeriod;
    }

    public function applicationsLockStatus ($year = null)
    {
        if(!$year){
            return SixthFormApplicationPeriod::where('current_year', 1)
            ->first();
        }

        return SixthFormApplicationPeriod::where('year', $year)
        ->first();
    }

    public function applicationPeriods ()
    {
        return SixthFormApplicationPeriod::orderBy('year', 'desc')
        ->get();
    }

    public function currentPeriod ()
    {
        return SixthFormApplicationPeriod::where('current_year', 1)
        ->first();
    }

    public function allApplications ($year)
    {
        $data = $this->allApplicationsData($year);
        // return $data;
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            "Application ID",
            "Year",
            "First Name",
            "Last Name",
            "Email",
            "Phone (Mobile)",
            "Address",
            "Birth Certificate Pin",
            "Date of Birth",
            "Previous School",
            "Parent Name",
            "Parent Mobile Phone",
            "Proposed Career",
            "CSEC Grades",
            "Line 1 (1st Choice)",
            "Line 2 (1st Choice)",
            "Line 3 (1st Choice)",
            "Line 1 (2nd Choice)",
            "Line 2 (2nd Choice)",
            "Line 3 (2nd Choice)",
            "Status",
            'Time Stamp'
        ];

        $sheet->fromArray($headers, NULL, 'A1');
        $sheet->fromArray($data, NULL, 'A2');

        // $sheet->freezePane('C2');
        $highestColumn = $sheet->getHighestColumn();
        $sheet->getRowDimension('1')->setRowHeight(25);

        $styleArray = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFBFBFBF',
                ]
            ]
        ];

        $sheet->getStyle('A1:'.$highestColumn.'1')->applyFromArray($styleArray);

        $highestRow = $sheet->getHighestRow();
        foreach($sheet->getColumnIterator() as $col){            
            $sheet->getColumnDimension($col->getColumnIndex())->setAutoSize(true);
            for($row = 1; $row <= $highestRow; $row++){
                $sheet->getStyle($col->getColumnIndex().$row.':'.$col->getColumnIndex().$row)->getAlignment()
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                // $value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
                if($row == 1){
                    $value = $sheet->getCell($col->getColumnIndex().$row)->getValue();
                    if(
                        $value == 'Application ID' ||
                        $value == 'Birth Certificate Pin' ||
                        $value == 'Phone (Mobile)' ||
                        $value == 'Phone(C) (Parent)' ||
                        $value == 'Year'
                    ){
                        $sheet->getStyle($col->getColumnIndex().$row.':'.$col->getColumnIndex().$highestRow)->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                    } 
                }
                
            }
                       
        }

        $sheet->freezePane('E2');

        $sheet->setTitle($year." Form 6 Applications");
        $file = $year." Sixth Form Applications_".date('Ymdhis').".xlsx";
        $filePath = storage_path('app/public/'.$file);
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        return response()->download($filePath, $file);
    }

     private function allApplicationsData ($year) 
    {
        $data = array();

        $applications = ModelsSixthFormApplication::where('year', $year)
        ->select(
            'application_id',
            'year',
            'first_name',
            'last_name',
            'email',
            'phone_mobile',
            'address',
            'birth_certificate_pin',
            'date_of_birth',
            'previous_school',
            'parent_name',
            'phone_mobile_parent',
            'proposed_career',
            'status',
            'created_at'
        )
        ->orderBy('created_at', 'desc')
        ->get();

        // return $applications;

        foreach($applications as $application)
        {
            $applicationData = array();
            $applicationCSECGrades = SixthFormApplicationGrade::join(
                'csec_subjects',
                'csec_subjects.id',
                'sixth_form_application_grades.subject_id'
            )
            ->where('application_id', $application->application_id)
           
            ->get();

            $applicationSubjectChoices = SixthFormApplicationSubjects::where('application_id', $application->application_id)
             ->select(
                'subject_title',
                'line',
                'choice'
            )
            ->orderBy('choice')
            ->orderBy('line')
            ->get();

            if(sizeof($applicationSubjectChoices) < 6)
            {
                //incomplete application
                // continue;
                $option1 = [1 => null, 2 => null, 3 => null];
                $option2 = [1 => null, 2 => null, 3 => null];
                foreach($applicationSubjectChoices as $subjectChoice){
                    if($subjectChoice->choice == 1){
                        $option1[$subjectChoice->line] = $subjectChoice->subject_title;
                    }
                    if($subjectChoice->choice == 2){
                        $option2[$subjectChoice->line] = $subjectChoice->subject_title;
                    }
                }

                foreach($option1 as $index => $option)
                {
                    if(!$option)
                    {
                        $applicationSubjectChoices[] = new SixthFormApplicationSubjects([
                            'line' => $index,
                            'choice' => 1,
                            'subject_title' => null
                        ]);
                    }
                }

                foreach($option2 as $index => $option)
                {
                    if(!$option)
                    {
                        $applicationSubjectChoices[] = new SixthFormApplicationSubjects([
                            'line' => $index,
                            'choice' => 2,
                            'subject_title' => null
                        ]);
                    }
                }
                
            }

            
            if(
                $application->first_name &&
                $application->last_name &&
                // $application->phone_mobile &&
                $application->birth_certificate_pin &&
                // $application->date_of_birth &&
                sizeof($applicationCSECGrades) > 0 &&
                sizeof($applicationSubjectChoices) > 0

            ){
                // $application->csec_grades = implode(",", $applicationCSECGrades);
                // if($application->application_id == 'TSS9UEMF5' ) return $applicationSubjectChoices;
                $applicationData[] = $application->application_id;
                $applicationData[] = $application->year;
                $applicationData[] = $application->first_name;
                $applicationData[] = $application->last_name;
                $applicationData[] = $application->email;
                $applicationData[] = $this->formatNumber($application->phone_mobile);
                $applicationData[] = $application->address;
                $applicationData[] = $application->birth_certificate_pin;
                $applicationData[] = $application->date_of_birth;
                $applicationData[] = $application->previous_school;
                $applicationData[] = $application->parent_name;
                $applicationData[] = $this->formatNumber($application->phone_mobile_parent);
                $applicationData[] = $application->proposed_career;
                
                $csecGrades = null;
                foreach($applicationCSECGrades as $grade){
                    $csecGrades .= $grade->title." ".$grade->grade."(".$grade->profiles."), ";
                }
                $applicationData[] = $csecGrades;

                for($i=1; $i<3; $i++){
                    for($j=1; $j<4; $j++){
                        foreach($applicationSubjectChoices as $subjectChoice){
                            if($subjectChoice->line == $j && $subjectChoice->choice == $i){
                                $applicationData[] = $subjectChoice->subject_title;
                            }
                        }
                       
                    }
                    
                }
                // $application->choice_1_line_1 = 
                $applicationData[] = $application->status;
                $applicationData[] = date("Y-m-d H:i", strtotime($application->created_at));
                $data[] = $applicationData;
            }
        }

        return $data;
    }
    
}
