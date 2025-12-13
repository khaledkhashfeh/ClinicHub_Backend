<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'governorate_id',
        'name_ar',
        'name_en',
    ];

    // Relations
    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }

    public function clinics()
    {
        return $this->hasMany(Clinic::class);
    }

    public function medicalCenters()
    {
        return $this->hasMany(MedicalCenter::class);
    }
}

