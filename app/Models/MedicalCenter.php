<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicalCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'city',
        'area',
        'address_details',
        'location_coords',
        'description',
        'logo_url',
        'status',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clinics()
    {
        return $this->hasMany(Clinic::class);
    }

    public function secretaries()
    {
        return $this->hasMany(Secretary::class);
    }
}
