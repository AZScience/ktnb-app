<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Petition extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'reception_date', 'building_block', 'recipient', 'citizen_name',
        'citizen_id', 'citizen_address', 'citizen_phone', 'summary', 'petition_type',
        'number_of_people', 'previous_authority', 'is_accepted', 'is_returned',
        'is_forwarded', 'resolution_follow_up', 'note',
    ];

    protected $casts = [
        'is_accepted' => 'boolean',
        'is_returned' => 'boolean',
        'is_forwarded' => 'boolean',
    ];
}
