<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Employee;
use App\Models\FormClass;
use App\Models\FormDeanAssignment;
use App\Models\FormTeacherAssignment;
use App\Models\Student;
use App\Models\StudentClassRegistration;
use App\Models\StudentTermDetail;
use App\Models\StudentTermMark;
use App\Models\TermConfiguration;
use Illuminate\Http\Request;

class MarkSheetSubjectChoice extends Controller
{
    
    protected $pdf;

    public function __construct(\App\Models\Pdf $pdf)
    {        
        $this->pdf = $pdf;
    }
    
    public function show ($form_class_id, $student_id = null)
    {
        $data = $this->data($form_class_id, $student_id);      
        // return $data;

        $this->pdf->AliasNbPages();
        $this->pdf->SetMargins(10, 10);          

        foreach($data as $record){

            $this->pdf->AddPage('P', 'Legal');       
            $this->header($record["student_data"]);

            foreach($record["subjects"] as $subject){
                $this->pdf->SetFont('Times','B',15);
                $this->pdf->SetDrawColor(100, 100, 100);
                $this->pdf->SetFillColor(195, 195, 195);
                $this->pdf->Ln();
                $this->pdf->Cell(0, 6, $subject["title"], 0 ,2,'C');
                $this->pdf->Ln(2);            
                $this->pdf->SetFont('Times','',11);
                $this->pdf->Cell(54, 6, 'YEAR '.$subject["marks"][1]["year"], 'TLR', 0, 'C');
                $this->pdf->Cell(54, 6, 'YEAR '.$subject["marks"][2]["year"], 'TLR', 0, 'C');
                $this->pdf->Cell(54, 6, 'YEAR '.$subject["marks"][3]["year"], 'TLR', 0, 'C');
                $this->pdf->Cell(34, 6, 'AVERAGE', 'TLR', 0, 'C');
                $this->pdf->Ln();

                $this->pdf->SetFont('Times','',10);
                $this->pdf->Cell(54, 4, 'FORM 1', 'LR', 0, 'C');
                $this->pdf->Cell(54, 4, 'FORM 2', 'LR', 0, 'C');
                $this->pdf->Cell(54, 4, 'FORM 3', 'LR', 0, 'C');
                $this->pdf->Cell(34, 4, '', 'LR', 0, 'C');
                $this->pdf->Ln();

                $this->pdf->SetFont('Times','',12);
                $this->pdf->SetDrawColor(100, 100, 100);

                $this->pdf->Cell(18, 6, 'TERM 1', 1, 0, 'C', true);
                $this->pdf->Cell(18, 6, 'TERM 2', 1, 0, 'C');
                $this->pdf->Cell(18, 6, 'TERM 3', 1, 0, 'C', true);
                $this->pdf->Cell(18, 6, 'TERM 1', 1, 0, 'C');
                $this->pdf->Cell(18, 6, 'TERM 2', 1, 0, 'C', true);
                $this->pdf->Cell(18, 6, 'TERM 3', 1, 0, 'C');
                $this->pdf->Cell(18, 6, 'TERM 1', 1, 0, 'C', true);
                $this->pdf->Cell(18, 6, 'TERM 2', 1, 0, 'C');
                $this->pdf->Cell(18, 6, 'TERM 3', 1, 0, 'C', true);
                $this->pdf->SetFont('Times','',10);
                $this->pdf->SetDrawColor(100, 100, 100);
                $this->pdf->Cell(17, 6, 'COURSE', 'LRT', 0, 'C');           
                $this->pdf->Cell(17, 6, 'EXAM', 'LRT', 0, 'C');
                $this->pdf->Ln();
                $this->pdf->SetFont('Times','B',10);
                $fill=false;

                $cols = 0;
                foreach($subject["marks"] as $index => $subjectMark){                   
                    for($i = 1; $i <= 3 ; $i++){
                        $fill = false;
                        if($cols%2 == 0) $fill = true;                        
                        $this->pdf->Cell(9, 4, 'C', 1, 0, 'C', $fill);
                        $this->pdf->Cell(9, 4, 'E', 1, 0, 'C', $fill);
                        $cols++;
                    }                    
                }

                $this->pdf->Cell(17, 4, '%', 'L', 0, 'C');
                $this->pdf->Cell(17, 4, '%', 'LR', 0, 'C');
                $this->pdf->Ln();

                $cols = 0; $courseTotal = 0; $examTotal = 0;
                $courseCount = 0; $examCount = 0;

                for($i = 1; $i <= 3 ; $i++){
                    for($j = 1; $j <= 3; $j++){                        
                        $fill = false;
                        if($cols%2 == 0) $fill = true;
                        if($subject["marks"][$i][$j]["course"]){
                            $courseTotal += $subject["marks"][$i][$j]["course"];
                            $courseCount++;
                        }
                        if($subject["marks"][$i][$j]["exam"]){
                            $examTotal += $subject["marks"][$i][$j]["exam"];
                            $examCount++;
                        }    
                        $this->pdf->Cell(9, 8, $subject["marks"][$i][$j]["course"], 1, 0, 'C', $fill);
                        $this->pdf->Cell(9, 8, $subject["marks"][$i][$j]["exam"], 1, 0, 'C', $fill);
                        $cols++;
                    }                    
                }
                
                $courseAverage = ($courseCount != 0) ? round(($courseTotal / $courseCount), 1) : null;
                $examAverage = ($examCount != 0) ? round(($examTotal / $examCount), 1) : null;
                
                $this->pdf->Cell(17, 8, $courseAverage, 1, 0, 'C');           
                $this->pdf->Cell(17, 8, $examAverage, 1, 0, 'C');
                $this->pdf->Ln(14);

                $this->pdf->SetFillColor(0, 0, 0);
                $this->pdf->Cell(0, 1, '', 0 ,2,'',true);
                
                if($subject["title"] == "Science"){
                    $this->pdf->Ln(25);
                    $this->pdf->AddPage('P', 'Legal');
                    $this->pdf->Cell(0, 1, '', 0 ,2,'',true);
                } 
                
            } 
        }

        $this->pdf->Output('I', 'Class Summary Mark Sheet.pdf');
    } 

    private function header ($data) 
    {
        $border = 0;
        $logo = public_path('/imgs/logo.png');
        $school = strtoupper(config('app.school_name')); 

        $this->pdf->SetFont('Times','',12);
        $x = $this->pdf->GetX();
        $this->pdf->SetXY(161,15);
        $this->pdf->MultiCell(45,4,'', 0, 'C');
        $x = $this->pdf->getX();            
        $y = $this->pdf->getY();
        $this->pdf->SetXY($x, $y + 8);            
        $this->pdf->Cell(40,5, '', 0, 0, 'C');                                    
        $this->pdf->Image($logo, 95.5,8,25);
        $this->pdf->SetY(32);
        $this->pdf->SetFont('Times','B',15);
        $this->pdf->Ln(); 
        $this->pdf->Cell(0, 6, $school.' SCHOOL', 0 ,2,'C');
        $this->pdf->SetFont('Times','',14);
        $this->pdf->Cell(0, 6, 'Subject Choice Mark Sheet', 0 ,2,'C');
        $this->pdf->Cell(0, 1, '', 0 ,2,'',true);           
        $this->pdf->SetFont('Times','',12);            
        $this->pdf->Ln(8);            
        // $this->pdf->Cell(80, 6, 'Student Number: ', $border, 0, 'R');
        // $this->pdf->Cell(20, 6, '', $border, 0, 'R');
        // $this->pdf->Cell(96, 6, $data['student_id'], $border, 0, 'L');
        // $this->pdf->Ln();
        $this->pdf->Cell(60, 6, 'Student ID: '.$data['student_id'], $border, 0, 'L');
        $this->pdf->Cell(27, 6, 'Student Name:', $border, 0, 'L');
        $this->pdf->SetFont('Times','B',14);
        $this->pdf->Cell(53, 6, $data['name'], $border, 0, 'L');
        $this->pdf->Cell(56, 6, '', $border, 0, 'L');
        $this->pdf->Ln();
        $this->pdf->SetFont('Times','',12);
        $this->pdf->Cell(80, 6, 'Gender: '.$data['gender'], $border, 0, 'R');
        $this->pdf->Cell(10, 6, '', $border, 0, 'L');
        $this->pdf->Cell(60, 6, 'Date of Birth: '.$data['date_of_birth'], $border, 0, 'L');
        $this->pdf->Cell(46, 6, '', $border, 0, 'L');
        $this->pdf->Ln();
        $this->pdf->SetFont('Times','',12);
        $this->pdf->Cell(80, 6, 'Class: '.$data['form_class_id'], $border, 0, 'R');
        $this->pdf->Cell(10, 6, '', $border, 0, 'L');
        $this->pdf->Cell(60, 6, '', $border, 0, 'L');
        $this->pdf->Cell(46, 6, '', $border, 0, 'L');
        $this->pdf->Ln();            
        $this->pdf->SetFont('Times','',12);           
        $this->pdf->Ln(8);
        $this->pdf->Cell(0, 1, '', 0 ,2,'',true);
    }

    private function data ($form_class_id, $student_id) 
    {
        $data = [];
        $ncseSubjects= [];
        $ncseSubjects[] = ["id" => 18, "title" => "English Language Arts"];
        $ncseSubjects[] = ["id" => 28, "title" => "Information and Communication Technology"];
        $ncseSubjects[] = ["id" => 33, "title" => "Mathematics"];
        $ncseSubjects[] = ["id" => 41, "title" => "Physical Education"];
        $ncseSubjects[] = ["id" => 30, "title" => "Science"];
        $ncseSubjects[] = ["id" => 48, "title" => "Social Studies"];
        $ncseSubjects[] = ["id" => 49, "title" => "Spanish"];
        $ncseSubjects[] = ["id" => 51, "title" => "Technology Education"];
        $ncseSubjects[] = ["id" => "VAPA", "title" => "Visual and Performing Arts"];        

        $term = AcademicTerm::where('is_current', 1)
        ->first();

        $terms = $this->getTerms($term);
         
        $students = $this->getStudents($form_class_id, $term, $student_id);

        foreach($students as $student){
            $studentRecords = []; $studentMarks = []; $studentData = [];

            $studentData["name"] = $student->first_name.' '.$student->last_name;
            $studentData["gender"] = $student->gender;
            $dateOfBirth = date_format(date_create($student->date_of_birth), 'd-M-Y');
            $studentData["date_of_birth"] = $dateOfBirth;
            $studentData["student_id"] = $student->student_id;
            $studentData["form_class_id"] = $form_class_id;
            
            $studentRecords["student_data"] = $studentData;
            
            foreach($ncseSubjects as $subject){
                $subjectMarks = []; $formMarks = [];                              

                if($subject["id"] == "VAPA"){
                    foreach($terms as $index => $term){
                        $formMarks[$index] = $this->getVAPAMarks($term, $student->student_id);
                    }
                }
                else{
                    foreach($terms as $index => $term){
                        $formMarks[$index] = $this->getFormMarks($term, $student->student_id, $subject["id"]);
                    }                    
                }
                $subjectMarks["title"] = $subject["title"];
                $subjectMarks["marks"] = $formMarks; 
                array_push($studentMarks, $subjectMarks);
            }
            
            $studentRecords["subjects"] = $studentMarks;
            array_push($data, $studentRecords);
       }

       return $data;        
    }

    private function getStudents ($form_class_id, $term, $student_id)
    {
        if($student_id){
            return Student::select(
                'first_name', 
                'last_name',
                'students.gender',
                'students.date_of_birth',
                'students.id as student_id'
            )
            ->where([
                ['id', $student_id],
            ])->get();
        }

        return StudentClassRegistration::join(
            'students', 
            'students.id',             
            'student_class_registrations.student_id'
        )
        ->select(
            'student_class_registrations.student_id', 
            'first_name', 
            'last_name',
            'students.gender',
            'students.date_of_birth',
        )
        ->where([
            ['form_class_id', $form_class_id],
            ['academic_year_id', $term->academic_year_id]
        ])->get();
    }

    private function getTerms ($academic_term)
    {
        $years = []; 
        $academic_year_id = $academic_term->id;
        $year_start = substr($academic_year_id, 0, 4);
        
        for($i = 3; $i >= 1; $i--){            
            $terms = [];
            $terms[1] = $year_start*100 + 1;
            $terms[2] = $year_start*100 + 2;
            $terms[3] = $year_start*100 + 3;
            $years[$i] = $terms;
            $year_start--;
        }       
        
        return $years;
    }

    private function getFormMarks ($terms, $student_id, $subject_id)
    {
        $termMarks = [];      
             
        foreach($terms as $index => $term){
            $marks = []; 
             
            $examMarkRecord = StudentTermMark::where([
                ['student_id', $student_id],
                ['academic_term_id', $term],
                ['subject_id', $subject_id],
                ['test_id', 1]
            ])->first();

            $courseMarkRecord = StudentTermMark::where([
                ['student_id', $student_id],
                ['academic_term_id', $term],
                ['subject_id', $subject_id],
                ['test_id', 2]
            ])->first();

            $marks["exam"] = ($examMarkRecord && $examMarkRecord->assesment_attendance_id == 1) ? 
            $examMarkRecord->mark : null;
            
            $marks["course"] = ($courseMarkRecord && $courseMarkRecord->assesment_attendance_id == 1) ?
            $courseMarkRecord->mark : null;
            
            $year_start = substr($term, 0, 4); 
            $termMarks["year"] = $year_start.' - '.($year_start+1); 
            $termMarks[$index] = $marks;
        }

        return $termMarks;
    }

    private function getVAPAMarks ($terms, $student_id) {
        $vapaSubjects = [81, 80, 38];  $termMarks = [];       

        foreach($terms as $index => $term){
            $marks = []; $examMarkTotal = 0; 
            $courseMarkTotal = 0; $vapaSubjectCount = 0;
            
            foreach($vapaSubjects as $vapaSubject){
                $examMarkRecord = StudentTermMark::where([
                    ['student_id', $student_id],
                    ['academic_term_id', $term],
                    ['subject_id', $vapaSubject],
                    ['test_id', 1]
                ])->first();
    
                $courseMarkRecord = StudentTermMark::where([
                    ['student_id', $student_id],
                    ['academic_term_id', $term],
                    ['subject_id', $vapaSubject],
                    ['test_id', 2]
                ])->first();

                if($examMarkRecord || $courseMarkRecord) $vapaSubjectCount++;

                if($examMarkRecord && $examMarkRecord->assesment_attendance_id == 1) 
                $examMarkTotal += $examMarkRecord->mark;

                if($courseMarkRecord && $courseMarkRecord->assesment_attendance_id == 1) 
                $courseMarkTotal += $courseMarkRecord->mark;
            } 

            $marks["exam"] = ($vapaSubjectCount != 0) ? ($examMarkTotal / $vapaSubjectCount) : null; 
            $marks["course"] = ($vapaSubjectCount != 0) ? ($courseMarkTotal / $vapaSubjectCount) : null;
            $year_start = substr($term, 0, 4); 
            $termMarks["year"] = $year_start.' - '.($year_start+1); 
            $termMarks[$index] = $marks;
        }

        return $termMarks;
    }
   
}
