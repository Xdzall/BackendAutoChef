<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'country'; // penting karena nama tabel bukan 'countries'
    protected $fillable = ['name_country'];
    public $timestamps = false; // tidak ada created_at / updated_at pada migration
}
