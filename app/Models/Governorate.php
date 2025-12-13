<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Governorate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
    ];

    // Relations
    public function cities()
    {
        return $this->hasMany(City::class);
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

