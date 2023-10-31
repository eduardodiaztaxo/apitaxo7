<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespLocalizacion extends Model
{
    use HasFactory;

    protected $fillable = [
        'tratamiento',
        'descripcion',
        'adicionales',
    ];

    protected $table = 'resp_localizaciones';
}
