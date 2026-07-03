<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'title', 'class_id', 'course_name', 'type',
        'duration', 'questions', 'source', 'active',
    ];

    protected $casts = [
        'questions' => 'array',
        'active' => 'boolean',
    ];

    public function toApiFormat(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'classId' => $this->class_id,
            'courseName' => $this->course_name,
            'type' => $this->type,
            'duration' => $this->duration,
            'questions' => $this->questions ?? [],
            'source' => $this->source,
            'active' => $this->active,
        ];
    }
}
