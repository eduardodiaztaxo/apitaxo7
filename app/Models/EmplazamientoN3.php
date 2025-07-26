<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class EmplazamientoN3 extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones_n3';

    protected $primaryKey = 'idUbicacionN3';

    const CREATED_AT = 'fechaCreacion';
    const UPDATED_AT = 'fechaActualizacion';

    protected $fillable = [
        'idAgenda',
        'descripcionUbicacion',
        'codigoUbicacion',
        'estado',
        'usuario',
        'newApp'
    ];

}
