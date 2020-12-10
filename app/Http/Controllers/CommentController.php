<?php

namespace App\Http\Controllers;

use App\Models\CommentTemplate;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function show(){       
        return CommentTemplate::all();
    }

}
