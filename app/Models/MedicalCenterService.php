<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalCenterService extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_center_id',
        'name',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function medicalCenter(): BelongsTo
    {
        return $this->belongsTo(MedicalCenter::class);
    }
}
