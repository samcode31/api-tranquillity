<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\UserEmployee;
use App\Models\UserPermission as ModelsUserPermission;
use Illuminate\Http\Request;

class UserPermission extends Controller
{
    public function store(Request $request){
        $employee_id = $request->employee_id;
        $permission = $request->permission;

        $user_employee = UserEmployee::whereEmployeeId($employee_id)->first();

        $user_id = $user_employee->id;

        //return $user_id;

        $userPermission = ModelsUserPermission::create([
            "user_id" => $user_id,
            "permission_id" => $permission
        ]);

        if($userPermission->exists) return 'Permission Added';
    }

    public function show($employee_id){
        $user_employee = UserEmployee::whereEmployeeId($employee_id)->first();

        $user_id = $user_employee->id;

        $user_permissions = ModelsUserPermission::whereUserId($user_id)
        ->select('permission_id')
        ->get();

        return $user_permissions;
    }

    public function assign(){
        $employees = Employee::all();
        //return $employees;
        foreach($employees as $employee){
            $user = UserEmployee::whereEmployeeId($employee->id)->first();
            $user_id = $user->id;
            for($i = 1; $i < 4; $i++){
                ModelsUserPermission::create([
                    "user_id" => $user_id,
                    "permission_id" => $i
                ]);
            }
            
        }
    }
}
