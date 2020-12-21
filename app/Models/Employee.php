<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'last_name',
        'first_name',
        'teacher_num',
        'date_of_birth',        
    ];

    protected $hidden = [        
        'created_at',
        'updated_at',        
    ];

    public function user(){
        return $this->hasOne('App\Models\UserEmployee');
    }
}
