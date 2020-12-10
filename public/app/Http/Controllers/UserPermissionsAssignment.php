<?php

namespace App\Http\Controllers;

use App\Models\UserEmployee;
use App\Models\UserPermissionsAssignment as ModelsUserPermissionsAssignment;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class UserPermissionsAssignment extends Controller
{
    public function store(Request $request){
        $employee_id = $request->employee_id;
        $permission = $request->permission;
        
        $user_employee = UserEmployee::whereEmployeeId($employee_id)->first();

        $user_id = $user_employee->id;

        //return $user_id;
        
        $userPermission = ModelsUserPermissionsAssignment::create([
            "user_id" => $user_id,
            "permission_id" => $permission
        ]);

        if($userPermission->exists) return 'Permission Added';
    }

    public function show($employee_id){
        $user_employee = UserEmployee::whereEmployeeId($employee_id)->first();

        $user_id = $user_employee->id;

        $user_permissions = ModelsUserPermissionsAssignment::whereUserId($user_id)
        ->select('permission_id')
        ->get();

        return $user_permissions;
    }

    public function upload(){
        $file = './files/user_permissions.xlsx';
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        //return $rows;
        $permissions = 0;
        for($i = 2; $i <= $rows; $i++)
        {  
            $user_id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
            $permission_id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();

            $permission_assignment = ModelsUserPermissionsAssignment::updateOrCreate(
                ['user_id' => $user_id, 'permission_id' => $permission_id],
                ['user_id' => $user_id, 'permission_id' => $permission_id]
            );

            if($permission_assignment->exists()) $permissions++;
        }
        
        return $permissions;
    }
}
