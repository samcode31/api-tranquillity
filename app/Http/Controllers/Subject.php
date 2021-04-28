<?php

namespace App\Http\Controllers;

use App\Models\Subject as ModelsSubject;
use Illuminate\Http\Request;

class Subject extends Controller
{
    public function show()
    {        
        return ModelsSubject::all();
    }

    public function store(Request $request)
    {
        $subject = ModelsSubject::updateOrCreate(
            [ 'id' => $request->id ],
            [
                'title' => $request->title,
                'abbr' => $request->abbr
            ]
        );

        return $subject;
    }

    public function delete(Request $request)
    {       
        return ModelsSubject::where('id', $request->id)->delete();
    }
}
