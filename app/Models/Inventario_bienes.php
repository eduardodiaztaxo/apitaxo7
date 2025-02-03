<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventario_bienes extends Model
{
    use HasFactory;

    protected $table = 'inv_bienes_nuevos';
    public $timestamps = false;
}