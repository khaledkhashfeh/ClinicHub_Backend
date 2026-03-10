<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_record_id',
        'medication_name',
        'instructions',
        'dosage',
        'issued_at',
        'total_quantity',
        'dose_description',
        'daily_frequency',
        'hourly_interval',
        'food_relation',
        'duration',
        'special_instructions',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function visitRecord()
    {
        return $this->belongsTo(VisitRecord::class);
    }

    public function medicationTracker()
    {
        return $this->hasOne(MedicationTracker::class);
    }
}
