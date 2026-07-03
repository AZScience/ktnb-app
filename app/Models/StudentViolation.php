<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentViolation extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'full_name', 'class', 'student_id', 'violation_date', 'violation_type',
        'signed', 'officer', 'note', 'building', 'department', 'identifier',
        'signature_base64', 'portrait_photo', 'document_photo',
    ];

    /** Columns for list views — heavy blobs loaded on edit via show endpoint. */
    public const INDEX_COLUMNS = [
        'id', 'full_name', 'class', 'student_id', 'violation_date', 'violation_type',
        'signed', 'officer', 'note', 'building', 'department', 'identifier', 'created_at',
    ];
}
