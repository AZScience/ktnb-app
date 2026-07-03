<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'name', 'department', 'position', 'birth_date',
        'address', 'phone', 'email', 'note', 'avatar_url',
    ];
}
