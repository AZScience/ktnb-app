<?php

namespace App\Models;

use App\Models\Concerns\NormalizesDisplayDateColumns;
use Illuminate\Database\Eloquent\Model;

class ShiftFeedback extends Model
{
    use NormalizesDisplayDateColumns;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'shift_feedbacks';

    protected $fillable = [
        'id', 'email', 'employee_name', 'shift_date',
        'proof_printed', 'proof_online', 'proof_incident', 'proof_facility',
    ];

    protected static function displayDateIsoColumns(): array
    {
        return [
            'shift_date' => 'shift_date_iso',
        ];
    }
}
