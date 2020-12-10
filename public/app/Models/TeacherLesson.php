<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherLesson extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    public function subject()
    {
        return $this->belongsTo('App\Models\Subject');
    }

    public function formClass()
    {
        return $this->belongsTo('App\Models\FormClass');
    }
}
