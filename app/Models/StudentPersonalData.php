<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPersonalData extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'student_data_personal';

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',        
    ];
}
