<?php

namespace App\Http\Controllers;

use App\Models\UserEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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
        //return $credentials;       
        if(Auth::guard('employee')->attempt($credentials)){
            return UserEmployee::whereName($request->name)->get();
        }
        else{
            throw ValidationException::withMessages([
                'message' => [trans('auth.failed')]
            ]);
        }
    }
}
