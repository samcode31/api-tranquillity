<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\UserEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\UserAdmin;

class LoginController extends Controller
{
    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return Response
     */

    public function authenticate(Request $request)
    {
        $credentials = $request->only('name', 'password');              

        if(Auth::guard('admin')->attempt($credentials)){
            //Authentication...
            return 'Authorized';
        }
        else{
            throw ValidationException::withMessages([
                'message' => [trans('auth.failed')]
            ]);
        }
    }

    public function authenticateEmployee(Request $request){
        $credentials = $request->only('name', 'password');

        if($request->input('name') === 'Admin' && Auth::guard('admin')->attempt($credentials)){
            return UserAdmin::where('name', 'Admin')->first();
        }

        if(Auth::guard('employee')->attempt($credentials)){
            return UserEmployee::whereName($request->name)->first();
        }        
        
        if(Auth::guard('admin')->attempt(['name' => 'Admin', 'password' => $request->password])){
            $userEmployee = UserEmployee::whereName($request->name)->first();
            if(!$userEmployee) abort(403, 'Employee not found');
            return $userEmployee;
        }

        throw ValidationException::withMessages([
            'message' => [trans('auth.failed')]
        ]);
    }

    public function authenticateStudent(Request $request)
    {
        $credentials =  $request->only('student_id', 'password');
        if(Auth::guard('student')->attempt($credentials)){                    
            return Student::where('id', $request->student_id)->first();
        }
        else{
            throw ValidationException::withMessages([
                'message' => [trans('auth.failed')]
            ]);
        }
    }
}
