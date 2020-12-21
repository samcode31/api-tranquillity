<?php

namespace App\Http\Controllers;

use App\Models\Employee as ModelsEmployee;
use App\Models\EmployeeStatus;
use App\Models\UserEmployee;
use App\Models\UserPermissionsAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class Employee extends Controller
{    

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
            $lastName = ucwords(strtolower($lastName));
            $firstName = ucwords(strtolower($firstName));
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
        $data = [];        
        $employeeRecords = ModelsEmployee::all();
        foreach($employeeRecords as $employeeRecord){
            $employee = [];
            $employee['id'] = $employeeRecord->id;
            $employee['last_name'] = $employeeRecord->last_name;
            $employee['first_name'] = $employeeRecord->first_name;
            $employee['teacher_num'] = $employeeRecord->teacher_num;
            $employee['date_of_birth'] = $employeeRecord->date_of_birth;
            $employee['employee_status_id'] = $employeeRecord->employee_status_id;
            $employee['user_name'] = $employeeRecord->user->name;
            //$employeeRecord->user;
            array_push($data, $employee);
        }
        return $data;
    }

    public function delete(Request $request)
    {
        $data = [];
        $user_employee = UserEmployee::where('employee_id', $request->id)->first();
        //return $user_employee;
        if($user_employee->exists()){
            $user_employee->delete();
            if($user_employee->trashed()){
                $data['user_employee'] = $user_employee;
            }
        }

        $employee = ModelsEmployee::where('id', $request->id)->first();
        if($employee->exists){
            $employee->employee_status_id = $request->employee_status_id;
            $employee->save();
            if($employee->wasChanged('employee_status_id')){
                $employee->delete();               
            }
            if($employee->trashed()){
                $data['employee'] = $employee;
            }

            return $data;            
        }

        abort(500);
    }

    public function store(Request $request)
    {
        $data = [];
        $employee_id = $request->id;
        $employee = ModelsEmployee::whereId($employee_id);
        //return $employee;
        if($employee->exists())
        {
            $employee = $employee->first();
            $employee->first_name = $request->first_name;
            $employee->last_name = $request->last_name;
            $employee->save();
        
            $data['employee'] = $employee;
            $user_employee = UserEmployee::where('employee_id', $employee_id)->first();
            if($user_employee->exists()){
                $user_name = UserEmployee::where([
                    ['name', $request->user_name],
                    ['employee_id', '<>', $employee_id]
                ]);
                if($user_name->exists()) 
                return response()->json([
                    'message' => 'Username already exists.'                    
                ], 500);
                else {
                    $user_employee->name = $request->user_name;
                    $user_employee->save();
                }
                $data['user_employee'] = $user_employee;
            }
        }
        else{
            $employee = ModelsEmployee::create([
                'teacher_num' => $request->teacher_num,
                'first_name' => $request->first_name,
                'last_name' =>  $request->last_name,
                'gender' => $request->gender,
                'email_address' => $request->email_address,
                'date_of_birth' => $request->date_of_birth,
            ]);

            if($employee->exists()){ 
                $data['employee'] = $employee;               
                $userName = $request->first_name[0].$request->last_name;
                $appendDigit = 0;
                $employee_id = $employee->id;
                //return $employee_id;                
                while(UserEmployee::whereName($userName)->exists())
                {                
                    $appendDigit++;
                    $userName = $userName.$appendDigit;
                }      
                
                $user = UserEmployee::create([
                    'name' => $userName,
                    'employee_id' => $employee_id,
                    'password' => Hash::make($userName),
                    
                ]);
                if($user->exists()){
                    $data['user'] = $user;
                    $user_id = $user->id;
                    $permissions = [];
                    for($i = 1; $i < 4; $i++){
                        $user_permissions = UserPermissionsAssignment::create([
                            'user_id' => $user_id,
                            'permission_id' => $i
                        ]);
                        array_push($permissions, $user_permissions);
                    }
                    $data['permissions'] = $permissions;
                }
            }
        }        

        return $data;
    }

    public function status()
    {
        return EmployeeStatus::all();
    }
}
