<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Specialization extends Model
{
    protected $fillable = ['name_ar', 'name_en', 'icon', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_specialization');
    }
}
