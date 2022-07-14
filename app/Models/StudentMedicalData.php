<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentMedicalData extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'student_data_medical';

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',        
    ];
}
