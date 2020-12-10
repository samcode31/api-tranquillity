<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RegistrationSpreadSheetController extends Controller
{
    public function download(){
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $time = time();
        $data = [];
        $fields = [
            'Student ID',
            'Last Name',
            'First Name',
            'Middle Name',
            'Date of Birth',
            'Home Address',
            'Town',
            'Phone(H)',
            'Phone(Cell)',
            'Email',                                   
            'Father\'s Name',
            'Home Address (F)',
            'Home Phone (F)',
            'Father\'s Occupation',
            'Work Place (F)',
            'Work Address (F)',
            'Work Phone (F)',
            'Marital Status (F)',
            'Email (F)',
            'Mobile (F)',            
            'Mother\'s Name',
            'Home Address (M)',
            'Home Phone (M)',
            'Mother\'s Occupation',
            'Work Place (M)',            
            'Work Address (M)',
            'Work Phone (M)',
            'Marital Status (M)',
            'Email (M)',
            'Mobile (M)', 
            'Guardian Name',
            'Guardian Address',
            'Home Phone (G)',
            'Guardian Occupation',
            'Work Place (G)',
            'Work Address (G)',
            'Work Phone (G)',
            'Marital Status (G)',
            'Email (G)',
            'Mobile (G)',
            'Medical History',            
            'Class Id',
            'Entry Date',            
            'Previous School',
            'Religion Code',
            'Ethnic Group Code',
            'Place of Birth',
            'Nationality',
            'Hobbies',
            'Gender',
            'SEA No',
            'House Code',            
            'Achievements',
            'Transport',
            'School Feeding',
            'Social Welfare',
            'Birth Cert No',            
            'Activities',
            'Emergency Contact',            
            'Relationship(E)',
            'Phone (E)',
            'Mobile (E)',            
            'Internet Access',
            'Device Type',
            'Blood Type',
            'ID Number (F)',
            'ID Number (M)',
            'ID Number (G)',            
        ];
        array_push($data, $fields);       
        
        $students = Student::all();

        foreach($students as $student){
            $record = [];
            array_push($record, $student->id);
            array_push($record, $student->last_name);
            array_push($record, $student->first_name);
            array_push($record, $student->middle_name);
            array_push($record, date_format(date_create($student->date_of_birth), 'Y-m-d'));
            array_push($record, $student->home_address);
            array_push($record, $student->address_line_2);
            array_push($record, $student->phone_home);
            array_push($record, $student->phone_cell);
            array_push($record, $student->email);
            array_push($record, $student->father_name);
            array_push($record, $student->father_home_address);
            array_push($record, $student->father_phone_home);
            array_push($record, $student->father_occupation);
            array_push($record, $student->father_business_place);            
            array_push($record, $student->father_business_address);
            array_push($record, $student->father_business_phone);
            array_push($record, $student->father_marital_status);
            array_push($record, $student->email_father);
            array_push($record, $student->mobile_phone_father);             
            array_push($record, $student->mother_name);
            array_push($record, $student->mother_home_address);
            array_push($record, $student->mother_phone_home);
            array_push($record, $student->mother_occupation);
            array_push($record, $student->mother_business_place);            
            array_push($record, $student->mother_business_address);
            array_push($record, $student->mother_business_phone);
            array_push($record, $student->mother_marital_status);
            array_push($record, $student->mobile_phone_mother);
            array_push($record, $student->email_mother);
            array_push($record, $student->guardian_name);
            array_push($record, $student->guardian_home_address);
            array_push($record, $student->home_phone_guardian);
            array_push($record, $student->guardian_occupation);
            array_push($record, $student->guardian_business_place);
            array_push($record, $student->guardian_business_address);
            array_push($record, $student->guardian_phone);
            array_push($record, $student->guardian_marital_status);
            array_push($record, $student->email_guardian);
            array_push($record, $student->mobile_guardian);
            array_push($record, $student->medical_history);            
            array_push($record, $student->class_id);
            array_push($record, date_format(date_create($student->entry_date), 'Y-m-d'));            
            array_push($record, $student->previous_school);
            array_push($record, $student->religion_code);
            array_push($record, $student->ethnic_group_code);
            array_push($record, $student->place_of_birth);
            array_push($record, $student->nationality);
            array_push($record, $student->hobbies);
            array_push($record, $student->sex);
            array_push($record, $student->sea_no);            
            array_push($record, $student->house_code);            
            array_push($record, $student->achievements);
            array_push($record, $student->transport);
            array_push($record, $student->social_welfare);
            array_push($record, $student->school_feeding);
            array_push($record, $student->birth_certificate_no);           
            array_push($record, $student->activities);
            array_push($record, $student->emergency_contact);                          
            array_push($record, $student->relation_to_child);
            array_push($record, $student->emergency_home_phone);
            array_push($record, $student->emergency_work_phone);            
            array_push($record, $student->internet_access);
            array_push($record, $student->device_type);
            array_push($record, $student->blood_type);
            array_push($record, $student->id_card_father);
            array_push($record, $student->id_card_mother);
            array_push($record, $student->id_card_guardian);
            array_push($data, $record);
        }
        $sheet->fromArray($data);
        foreach($sheet->getColumnIterator() as $column){
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
        $writer = new Xlsx($spreadsheet);
        ///$file = './files/hello_world_'.$time.'.xlsx';
        $file = 'registration_data_'.$time.'.xlsx';
        //$file = 'hello_world.xlsx';
        $filePath = './files/'.$file;
        $writer->save($filePath);
        
        return response()->download($filePath, 'Registration Data.xlsx');

        //return $students;
    }
}
