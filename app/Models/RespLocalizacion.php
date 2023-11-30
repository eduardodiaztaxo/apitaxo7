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
        'localizacionFisica',
        'ccosto',
        'denominacionLocalizacion',
        'denominacionCentroCosto',
        'tipo',
        'status',
        'region',
        'comuna',
        'calle',
        'correoElectronicoResponsable',
        'adicionales',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $table = 'resp_localizaciones';
}
