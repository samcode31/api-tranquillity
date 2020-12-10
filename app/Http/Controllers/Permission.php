<?php

namespace App\Http\Controllers;

use App\Models\Permission as ModelsPermission;
use Illuminate\Http\Request;

class Permission extends Controller
{
    public function store(Request $request){
        $detail = $request->detail;
        $permission = ModelsPermission::create([
            'detail' => $detail
        ]);
        if($permission->exists) return 'Permission Added';
    }

}
