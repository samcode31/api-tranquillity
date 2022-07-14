<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentFamilyData extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'student_data_family';

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',        
    ];
}
