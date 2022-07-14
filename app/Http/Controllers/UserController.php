<?php

namespace App\Http\Controllers;
ini_set('max_execution_time', '900');
use App\Models\Student;
use App\Models\User;
use App\Models\UserAdmin;
use App\Models\UserEmployee;
use App\Models\UserStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function register(){
        $students = Student::all();
        //$students = Student::where('id', 'like', '20%')->get();
        //return $students;
        $usersCreated = 0;
        foreach($students as $student){
            $id = $student->id;
            $name = $student->first_name.' '.$student->last_name;
            $password = $student->birth_certificate_pin;
            $user = UserStudent::updateOrCreate(
                ['student_id' => $id],
                ['name' => $name, 'student_id' => $id, 'password' => Hash::make($password)]
            );

            if($user->exists()) $usersCreated++;
        }
        return "Users created: ".$usersCreated;

    }

    public function registerAdmin(){
        $user = UserAdmin::create([
            'name' => 'Admin',
            'password' =>  Hash::make('Adm1n1$trat0r')
        ]);

        return $user->id;
    }

    public function user($id)
    {
        return UserStudent::whereStudentId($id)->get();
    }

    public function userEmployee($name)
    {
        //return UserEmployee::whereName($name)->firstOrFail();
        $userEmployee = UserEmployee::whereName($name);
        if($userEmployee->exists())
        {
            $userEmployee = $userEmployee->first();
            $employee = $userEmployee->employee;
            //return $userEmployee->employee;
            return [
                'employee_id' => $userEmployee->employee_id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'password_reset' => $userEmployee->password_reset
            ];
        }
        return response('Username not found', 404);
    }

    public function resetPassword(Request $request){
        $id = $request->input('student_id');
        $student = Student::whereId($id)->first();
        //return $student;
        $password = date_format(date_create($student->date_of_birth), 'Ymd');
        $user = UserStudent::whereStudentId($id)->first();
        $user->password = Hash::make($password);
        $user->save();
        if($user->wasChanged('password')) return ["change" => true, "message" => "Password Changed Successfully."];
        return ["change" => false, "message"=> "Password Not Changed"];
    }

    public function changePassword(Request $request){
        $password = $request->input('password');
        $studentID = $request->input('student_id');
        $user = UserStudent::whereStudentId($studentID)->first();
        $user->password = Hash::make($password);
        $user->save();
        if($user->wasChanged('password')) return ["change" => true, "message" => "Password Changed Successfully."];
        return ["change" => false, "message"=> "Password Not Changed"];
    }

    public function defaultPassword($id){
        $student = Student::whereId($id)->get();
        $password = date_format(date_create($student->date_of_birth), 'Ymd');
        $user = UserStudent::whereStudentId($id)->first();
        $user->password = Hash::make($password);
        $user->save();
        if($user->wasChanged('password')) return ["change" => true, "message" => "Password Changed Successfully."];
        return ["change" => false, "message"=> "Password Not Changed"];
    }

    public function changeResetPassword(Request $request){
        $resetPassword = $request->input('reset_password');
        $studentID = $request->input('student_id');
        $user = UserStudent::whereStudentId($studentID)->first();
        $user->reset_password = $resetPassword;
        $user->save();
        if($user->wasChanged('reset_password')) return ["reset_password" => $resetPassword];
        return ["error" => "Reset not changed"];
    }

    public function employeeChangePassword(Request $request)
    {
        $name = $request->input('name');
        $password = $request->input('password');
        $userEmployee= UserEmployee::whereName($name)->first();
        $userEmployee->password = Hash::make($password);
        $userEmployee->password_reset = 0;
        $userEmployee->save();
        if($userEmployee->wasChanged('password')) return ["change" => true, "message" => "Password Changed Successfully."];
        return ["change" => false, "message"=> "Password Not Changed"];

    }

    public function resetEmployeePassword(Request $request)
    {
        $employee_id = $request->input('employee_id');
        $length = 6;
        $password = substr(str_shuffle(
            str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x))
        )),1,$length);
        $userEmployee = UserEmployee::whereEmployeeId($employee_id)->first();
        $userEmployee->password = Hash::make($password);
        $userEmployee->password_reset = 1;
        $userEmployee->remember_token = $password;
        $userEmployee->save();
        if($userEmployee->wasChanged('password')) return $userEmployee;
        return response('Password not changed', 417);
    }
}
