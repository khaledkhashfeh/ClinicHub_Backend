<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'city',
        'area',
        'loyalty_points_balance',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function medicalFile()
    {
        return $this->hasOne(MedicalFile::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }
}
