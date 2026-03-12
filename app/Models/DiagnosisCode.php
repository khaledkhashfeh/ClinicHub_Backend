<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiagnosisCode extends Model
{
    use HasFactory;

    protected $table = 'diagnosis_codes';

    protected $fillable = ['code', 'name', 'name_en'];
}
