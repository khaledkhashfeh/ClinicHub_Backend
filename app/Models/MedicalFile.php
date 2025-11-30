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
        'past_medical_history',
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
