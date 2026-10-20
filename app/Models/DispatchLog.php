<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'therapist_id',
        'customer_name',
        'customer_address',
        'customer_lat',
        'customer_lng',
        'distance_km',
        'est_minutes_motor',
        'status',
    ];

    protected $casts = [
        'customer_lat' => 'float',
        'customer_lng' => 'float',
        'distance_km' => 'float',
        'est_minutes_motor' => 'integer',
    ];

    public function therapist(): BelongsTo
    {
        return $this->belongsTo(Therapist::class);
    }
}
