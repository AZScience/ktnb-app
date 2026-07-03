<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'department_id', 'name', 'head', 'deputy_head',
        'secretary', 'spokesperson', 'phone', 'email', 'note',
    ];
}
