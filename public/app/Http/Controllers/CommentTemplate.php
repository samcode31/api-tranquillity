<?php

namespace App\Http\Controllers;

use App\Models\CommentTemplate as ModelsCommentTemplate;
use Illuminate\Http\Request;

class CommentTemplate extends Controller
{
    public function show(){       
        return ModelsCommentTemplate::all(); 
    }
}
