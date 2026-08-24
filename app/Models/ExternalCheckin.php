<?php

namespace App\Models;

use App\Models\Concerns\NormalizesDisplayDateColumns;
use Illuminate\Database\Eloquent\Model;

class ExternalCheckin extends Model
{
    use NormalizesDisplayDateColumns;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'class_id', 'class_name', 'class', 'schedule_date', 'lecturer',
        'building', 'room', 'period', 'student_count', 'actual_student_count',
        'photo_urls', 'location', 'incident', 'incident_detail', 'is_notification',
        'status', 'submitted_by', 'submitted_by_email', 'source',
    ];

    protected $casts = [
        'photo_urls' => 'array',
        'location' => 'array',
        'is_notification' => 'boolean',
    ];

    protected static function displayDateIsoColumns(): array
    {
        return [
            'schedule_date' => 'schedule_date_iso',
        ];
    }
}
