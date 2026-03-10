<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_record_id',
        'test_id',
        'test_name',
        'priority',
        'instructions',
        'status',
        'reminders_sent',
        'completed_at',
        'result_file_url',
        'result_file_type',
    ];

    protected $casts = [
        'reminders_sent' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function visitRecord()
    {
        return $this->belongsTo(VisitRecord::class);
    }
}
