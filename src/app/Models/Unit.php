<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'unit';

    protected $fillable = [
        'name_unit',
        'abbreviation',
        'type_unit',
        'conversion_to_grams',
    ];

    public $timestamps = false;
}