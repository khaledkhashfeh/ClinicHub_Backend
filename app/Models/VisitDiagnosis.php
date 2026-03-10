<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitDiagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_record_id',
        'condition_id',
        'condition_name',
        'classification',
        'notes',
    ];

    public function visitRecord()
    {
        return $this->belongsTo(VisitRecord::class);
    }
}
