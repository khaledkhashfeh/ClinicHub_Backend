<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LabTestCatalog extends Model
{
    use HasFactory;

    protected $table = 'lab_test_catalog';

    protected $fillable = ['name', 'name_en'];
}
