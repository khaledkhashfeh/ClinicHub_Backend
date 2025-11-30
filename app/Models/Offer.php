<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'clinic_id',
        'title',
        'description',
        'start_date',
        'valid_until',
        'discount_type',
        'discount_value',
        'is_active',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'valid_until'  => 'date',
        'discount_value' => 'float',
        'is_active'    => 'boolean',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}
