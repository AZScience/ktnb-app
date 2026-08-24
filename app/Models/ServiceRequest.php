<?php

namespace App\Models;

use App\Models\Concerns\NormalizesDisplayDateColumns;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use NormalizesDisplayDateColumns;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'service_requests';

    protected $fillable = [
        'id', 'ticket_number', 'request_type', 'building_block', 'student_name', 'student_id', 'class',
        'department', 'phone', 'content', 'attachments', 'request_date', 'reception_date', 'recipient',
        'is_processed_immediately', 'appointment_date', 'resolution_date', 'resolver_name',
        'feedback', 'status', 'note',
    ];

    protected $casts = ['is_processed_immediately' => 'boolean'];

    protected static function displayDateIsoColumns(): array
    {
        return [
            'request_date' => 'request_date_iso',
            'reception_date' => 'reception_date_iso',
            'appointment_date' => 'appointment_date_iso',
            'resolution_date' => 'resolution_date_iso',
        ];
    }
}
