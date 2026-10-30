<?php

namespace App\Domains\Parking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ParkingSpot extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'lat',
        'lng',
        'parking_type',
        'access_type',
        'capacity',
        'osm_id',
        'osm_fee',
        'osm_charge',
        'city',
        'source',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function reports(): HasMany
    {
        return $this->hasMany(OccupancyReport::class, 'parking_spot_id');
    }

    public function latestActiveReport(): HasOne
    {
        return $this->hasOne(OccupancyReport::class, 'parking_spot_id')
            ->where('expires_at', '>', now())
            ->latestOfMany('created_at');
    }

    public function feeRules(): HasMany
    {
        return $this->hasMany(FeeRule::class, 'parking_spot_id');
    }
}
