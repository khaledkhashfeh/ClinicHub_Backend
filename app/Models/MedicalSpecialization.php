<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalSpecialization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_url',
        'image_file_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

