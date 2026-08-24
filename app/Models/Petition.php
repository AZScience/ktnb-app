<?php

namespace App\Models;

use App\Models\Concerns\NormalizesDisplayDateColumns;
use Illuminate\Database\Eloquent\Model;

class Petition extends Model
{
    use NormalizesDisplayDateColumns;

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

    protected static function displayDateIsoColumns(): array
    {
        return [
            'reception_date' => 'reception_date_iso',
        ];
    }
}
