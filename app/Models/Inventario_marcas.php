<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventario_marcas extends Model
{
    use HasFactory;

    protected $table = 'inv_marcas_nuevos';
    public $timestamps = false;
}