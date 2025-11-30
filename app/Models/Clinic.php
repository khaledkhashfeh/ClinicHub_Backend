<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'medical_center_id',
        'name',
        'floor',
        'room_number',
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

    public function doctors()
    {
        return $this->belongsToMany(Doctor::class)
                    ->withTimestamps()
                    ->withPivot('is_primary');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function visitRecords()
    {
        return $this->hasMany(VisitRecord::class);
    }

    public function secretaries()
    {
        return $this->hasMany(Secretary::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
