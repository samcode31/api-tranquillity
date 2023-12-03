<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentDataFile extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $guarded = [];
}
