<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Method extends Model
{
    protected $fillable = [
        'name',
    ];

    public function clinicDoctors() {
        return $this->hasMany(ClinicDoctor::class);
    }
}
