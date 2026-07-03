<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'name', 'avatar_url', 'gender', 'birth_date', 'birth_place', 'hometown',
        'ethnicity', 'religion', 'class', 'permanent_address', 'temporary_address',
        'contact_address', 'region', 'address', 'major', 'department',
        'father_name', 'father_occupation', 'mother_name', 'mother_occupation', 'parent_phone',
        'phone', 'email', 'citizen_id', 'note',
    ];
}
