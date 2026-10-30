<?php

namespace App\Domains\Parking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeRule extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'city',
        'kind',
        'amount',
        'currency',
        'unit',
        'source_note',
        'effective_from',
        'parking_spot_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'effective_from' => 'date',
        ];
    }

    public function spot(): BelongsTo
    {
        return $this->belongsTo(ParkingSpot::class, 'parking_spot_id');
    }
}
