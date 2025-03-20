<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndiceLista extends Model
{
    use HasFactory;

    protected $table = 'indices_listas';
    
    public $timestamps = false;
}