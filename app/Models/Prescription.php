<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_record_id',
        'medication_name',
        'instructions',
        'dosage',
    ];

    public function visitRecord()
    {
        return $this->belongsTo(VisitRecord::class);
    }
}
