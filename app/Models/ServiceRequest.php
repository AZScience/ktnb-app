<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
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
}
