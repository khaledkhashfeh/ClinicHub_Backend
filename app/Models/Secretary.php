<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Secretary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'medical_center_id',
        'clinic_id',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function medicalCenter()
    {
        return $this->belongsTo(MedicalCenter::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }
}
