<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentPersonalData extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $guarded = [];

    protected $table = 'student_data_personal';

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',        
    ];
}
