<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'question', 'options', 'duration', 'attendance_list',
        'class_id', 'lecturer', 'voters', 'status', 'end_time',
    ];

    protected $casts = [
        'options' => 'array',
        'attendance_list' => 'array',
        'voters' => 'array',
        'end_time' => 'datetime',
    ];

    public function toApiFormat(): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'options' => $this->options ?? [],
            'duration' => $this->duration,
            'attendanceList' => $this->attendance_list ?? [],
            'classId' => $this->class_id,
            'lecturer' => $this->lecturer,
            'voters' => $this->voters ?? [],
            'status' => $this->status,
            'endTime' => $this->end_time?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
