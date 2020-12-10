<?php

namespace App\Http\Controllers;

use App\Models\Employee as ModelsEmployee;
use App\Models\UserEmployee;
use App\Models\UserPermissionsAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class Employee extends Controller
{
    public function addEmployee(Request $request){
        $firstName = $request->input('firstName');
        $lastName = $request->input('lastName');
        //$teacherNum = isset($request->input('teacherNum')) ? $request->input('teacherNum') : null;
        //$dateOfBirth = $request->input('dob');
        //return $dateOfBirth;
        //$dayOfBirth = date_format(date_create($dateOfBirth), 'j');        
        //return $dateOfBirth;
        //return $dayOfBirth;
        
        $employee = ModelsEmployee::create([
            'last_name' => $lastName,
            'first_name' => $firstName,
            //'teacher_num' => $teacherNum,
            //'date_of_birth' => $dateOfBirth
        ]);
        if($employee->wasRecentlyCreated){                    
            $userName = $firstName[0].$lastName;
            $appendDigit = 0;
            $employee_id = $employee->id;
            //return $employee_id;
            // if(UserEmployee::whereName($userName)->exists()){
            //     $appendDigit++;
            //     $userName = $userName.$appendDigit;
            // }
            while(UserEmployee::whereName($userName)->exists())
            {                
                $appendDigit++;
                $userName = $userName.$appendDigit;
            }       
            //$dateOfBirth = date_format(date_create($dateOfBirth), 'Ymd');
            $user = UserEmployee::create([
                'name' => $userName,
                'employee_id' => $employee_id,
                'password' => Hash::make($userName),
                
            ]);
            return $user;
        }
        else{
            return 'employee not created';
        } 
    }

    public function upload(){
        $file = './files/employees.xlsx';
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        //return $rows;            
        $records = 0;        
        for($i = 2; $i <= $rows; $i++){            
            $lastName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
            $firstName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
            //$email = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
            //$lastName = ucwords(strtolower($lastName));
            //$firstName = ucwords(strtolower($firstName));
            //$dateOfBirth = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
            //$dayOfBirth = date_format(date_create($dateOfBirth), 'j');
             
            $employee = ModelsEmployee::create([
                'last_name' => $lastName,
                'first_name' => $firstName
            ]);
            if($employee->exists()){
                
                $userName = str_replace('-','',$firstName[0].$lastName);
                $userName = str_replace(' ', '', $userName);
                $userName = str_replace('.', '', $userName);
                $appendDigit = 0;
                $employee_id = $employee->id;
                //return $employee_id;
                if(UserEmployee::whereName($userName)->exists()){
                    $appendDigit++;
                    $userName = $userName.$appendDigit;
                }
                while(UserEmployee::whereName($userName)->exists())
                {                
                    $appendDigit++;
                    $userName = $userName.$appendDigit;
                }       
                //$dateOfBirth = date_format(date_create($dateOfBirth), 'Ymd');
                $user = UserEmployee::create([
                    'name' => $userName,
                    'employee_id' => $employee_id,
                    'password' => Hash::make($userName),
                    
                ]);
                if($user->wasRecentlyCreated){
                    $records++;
                    for($j = 1; $j < 3; $j++){
                        UserPermissionsAssignment::create([
                            'user_id' => $user->id,
                            'permission_id' => $j
                        ]);
                    }
                } 
            }
            
        }
        //return $spreadsheet->getActiveSheet()->getHighestDataRow();
        return $records;
    }

    public function index($id){
        return ModelsEmployee::whereId($id)->get();
    }

    public function show(){        
        $employeeRecords = ModelsEmployee::all();
        foreach($employeeRecords as $employeeRecord){
            $employeeRecord->user;
        }
        return $employeeRecords;
    }
}
