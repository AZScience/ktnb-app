<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetReception extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'entry_number', 'reception_date', 'building_block',
        'giver_name', 'giver_employee_code', 'giver_id', 'giver_class', 'giver_unit', 'giver_phone',
        'content', 'evidence', 'asset_state', 'return_status', 'is_gratitude',
        'receiving_staff', 'witness', 'resolution_date',
        'return_staff', 'receiver_name', 'receiver_id', 'receiver_class',
        'receiver_unit', 'receiver_phone', 'return_asset_state',
        'receiver_feedback', 'return_witness', 'return_evidence',
        'gratitude_number', 'gratitude_gift', 'gratitude_date',
        'gratitude_staff', 'gratitude_status', 'gratitude_evidence',
        'note',
    ];
    protected $casts = [
        'is_gratitude' => 'boolean',
    ];
}
