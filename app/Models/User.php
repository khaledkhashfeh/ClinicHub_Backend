<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;


class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'password',
        'gender',
        'birth_date',
        'profile_photo_url',
        'status',
        'otp_code',
        'otp_expires_at',
        'otp_attempts',
        'otp_last_sent_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'otp_expires_at' => 'datetime',
        'otp_last_sent_at' => 'datetime',
    ];

    protected $appends = ['full_name'];

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Mutators
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    // Relations
    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function medicalCenter()
    {
        return $this->hasOne(MedicalCenter::class);
    }

    public function clinic()
    {
        return $this->hasOne(Clinic::class);
    }

    public function secretary()
    {
        return $this->hasOne(Secretary::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    
}
