<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MedicalTerm extends Model
{
    use HasFactory;

    protected $table = 'medical_terms';

    protected $fillable = ['category', 'name', 'name_en'];

    public const CATEGORY_CHRONIC_DISEASE = 'chronic_disease';
    public const CATEGORY_MEDICATION = 'medication';
    public const CATEGORY_SURGERY = 'surgery';

    public static function allowedCategories(): array
    {
        return [
            self::CATEGORY_CHRONIC_DISEASE,
            self::CATEGORY_MEDICATION,
            self::CATEGORY_SURGERY,
        ];
    }
}
