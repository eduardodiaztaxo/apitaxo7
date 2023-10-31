<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespActivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'tratamiento',
        'numero_af',
        'centro_costo',
        'localizacion',
        'fecha_compra',
        'valor_compra',
        'descripcion',
        'etiqueta',
        'serie',
        'marca',
        'modelo',
        'unidad_negocio',
        'elemento_pep',
        'adicionales',
    ];
}
