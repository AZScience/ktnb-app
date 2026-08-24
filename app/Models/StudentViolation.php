<?php

namespace App\Models;

use App\Models\Concerns\NormalizesDisplayDateColumns;
use Illuminate\Database\Eloquent\Model;

class StudentViolation extends Model
{
    use NormalizesDisplayDateColumns;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'full_name', 'class', 'student_id', 'violation_date', 'violation_type',
        'signed', 'officer', 'note', 'building', 'department', 'identifier',
        'signature_base64', 'portrait_photo', 'document_photo',
    ];

    protected static function displayDateIsoColumns(): array
    {
        return [
            'violation_date' => 'violation_date_iso',
        ];
    }

    /** Columns for list views — heavy blobs loaded on edit via show endpoint. */
    public const INDEX_COLUMNS = [
        'id', 'full_name', 'class', 'student_id', 'violation_date', 'violation_type',
        'signed', 'officer', 'note', 'building', 'department', 'identifier', 'created_at',
    ];
}
