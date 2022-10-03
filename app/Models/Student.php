<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $guarded = [];

    protected $dates = ['deleted_at'];

    public $incrementing = false;

    public function UserStudent()
    {
        return $this->belongsTo('App\UserStudent');
    }
}
