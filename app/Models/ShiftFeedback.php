<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftFeedback extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'shift_feedbacks';

    protected $fillable = [
        'id', 'email', 'employee_name', 'shift_date',
        'proof_printed', 'proof_online', 'proof_incident', 'proof_facility',
    ];
}
