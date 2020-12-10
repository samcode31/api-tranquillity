<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'last_name',
        'first_name',
        'teacher_num',
        'date_of_birth',        
    ];

    public function user(){
        return $this->hasOne('App\Models\UserEmployee');
    }
}
