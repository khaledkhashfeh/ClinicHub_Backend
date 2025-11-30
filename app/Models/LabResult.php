<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LabResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_record_id',
        'test_type',
        'result_data',
        'attachment_url',
    ];

    public function visitRecord()
    {
        return $this->belongsTo(VisitRecord::class);
    }
}
