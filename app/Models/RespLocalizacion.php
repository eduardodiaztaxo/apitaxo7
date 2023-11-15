<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespLocalizacion extends Model
{
    use HasFactory;

    protected $fillable = [
        'tratamiento',
        'sociedad',
        'centro',
        'localizacion',
        'centro_costo',
        'denominacion_localizacion',
        'denominacion_ceco',
        'tipo',
        'status',
        'region',
        'comuna',
        'calle',
        'correo_resp',
    ];

    protected $table = 'resp_localizaciones';
}
