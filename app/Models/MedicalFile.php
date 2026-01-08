<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicalFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'blood_type',
        'has_chronic_diseases',
        'past_medical_history',
    ];

    protected $casts = [
        'has_chronic_diseases' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visitRecords()
    {
        return $this->hasMany(VisitRecord::class);
    }
}
