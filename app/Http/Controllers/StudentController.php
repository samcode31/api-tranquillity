<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\StudentClassRegistration;
use App\Models\UserStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

class StudentController extends Controller
{
    public function index($id){
        //return $request->user()->student;
        //return $request->user();
        //return $user = Auth::user();        
        return Student::whereId($id)->get();
    }

    public function store(Request $request){
        $academicTerm = AcademicTerm::whereIsCurrent(1)->first();
        $academicYearId = $academicTerm->academic_year_id;        
        $id = $request->input('id');        
        $classId = $request->input('form_class_id');        
        $added = 0;
        $registered = 0;
        $userAccount = 0;

        $studentRecord = Student::updateOrCreate(
            ['id' => $id],
            [
                'id' => $id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'birth_certificate_pin' => $request->birth_certificate_pin
            ]
        );

        if($studentRecord->exists()){
            $added++;
            //$dateOfBirth = date_format(date_create($request->input('date_of_birth')), 'Ymd');
            $studentClassRegistration = StudentClassRegistration::updateOrCreate(
                ['student_id' => $id, 'academic_year_id' => $academicYearId],
                ['student_id' => $id, 'form_class_id' => $classId, 'academic_year_id' => $academicYearId]
            );
            if($studentClassRegistration->exists()) $registered++; 
            
            $birthPin = $request->input('birth_certificate_pin');          
           
            $user = UserStudent::create([
                'student_id'=> $id,
                'name' => $request->input('first_name').' '.$request->input('last_name'),
                'password_reset'=> 1,
                'password' => Hash::make($birthPin),
            ]);
            
            
            if($user->exists()){
                $userAccount++;
            }
        }

        $data['Students Added'] = $added;
        $data['Class Registered'] = $registered;
        $data['User Account'] = $userAccount;

        return $data;
        
    }

    public function retrieve(){
        return Student::select('id', 'first_name', 'last_name', 'class_id', 'birth_certificate_no', 'date_of_birth', 'updated_at')
        ->where('id','!=', 20000)
        ->get();
    }

    public function data(){
        //return Student::all();
        return Student::whereNotNull('date_of_birth')->get();
    }

    public function updateData(){
        $file = 'ARIMA_NORTH_SECONDARY(4445)_EDITED.xlsx';
        $filePath = './files/'.$file;
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        //return $worksheet->getCell('A1')->getCalculatedValue();
        $recordsUpdated = 0;
        $rows = $worksheet->getHighestRow();
        for($row = 1; $row <= $rows; $row++){
            $id = $worksheet->getCell('A'.$row)->getCalculatedValue();
            $house_code = $worksheet->getCell('B'.$row)->getCalculatedValue();
            $class_id = $worksheet->getCell('C'.$row)->getCalculatedValue();
            $student = Student::whereId($id)->first();
            $student->house_code = $house_code;
            $student->class_id = $class_id;
            $student->save();
            if($student->wasChanged()) $recordsUpdated++;            
        }
        return 'Records Updated: '.$recordsUpdated;
    }

    public function upload(){
        $file = './files/students.xlsx';
        $reader = new XlsxReader();
        $spreadsheet = $reader->load($file);       
        $academicTerm = AcademicTerm::whereIsCurrent(1)->first();
        $academicYearId = $academicTerm->academic_year_id;
        //return $academicYearId;
        //return $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,2)->getValue();
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        //return $rows;
        //return $classId = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(28,2)->getValue();
        $records = [];
        $studentRecords = 0;
        $classRegistrations = 0;
        for($i = 2; $i <= $rows; $i++){
            $id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
            $lastName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
            $firstName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
            $classId = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
            $gender = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
            $student = Student::updateOrCreate(
                ['id' => $id],
                ['id' => $id, 'last_name' => $lastName, 'first_name' => $firstName, 'gender' => $gender]
            );
            if($student->wasRecentlyCreated){
                $studentRecords++;                
                $studentClassRegistration = StudentClassRegistration::updateOrCreate(
                    ['student_id' => $id, 'academic_year_id' => $academicYearId],
                    ['student_id' => $id, 'form_class_id' => $classId, 'academic_year_id' => $academicYearId]
                );
                if($studentClassRegistration->wasRecentlyCreated) $classRegistrations++; 
            } 
        }
        //return $spreadsheet->getActiveSheet()->getHighestDataRow();
        $records['Students'] = $studentRecords;
        $records['ClassRegistration'] = $classRegistrations;
        return $records;
    }
    
    public function show(){
        $academicTerm = AcademicTerm::whereIsCurrent(1)->first();
        //return $academicTerm;
        $academic_year_id = $academicTerm->academic_year_id;
        //$studentsRegistered = StudentClassRegistration::where('academic_year_id', $academic_year_id)->get();
        $currentStudents = Student::join('student_class_registrations', 'students.id', 'student_class_registrations.student_id')
        ->select('student_class_registrations.student_id', 'students.first_name', 'students.last_name', 'students.gender', 'student_class_registrations.form_class_id')
        ->where('student_class_registrations.academic_year_id', $academic_year_id)
        ->get();
        
        return $currentStudents;
    }
   
}
