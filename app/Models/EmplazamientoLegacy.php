<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmplazamientoLegacy extends Model
{
    use HasFactory;

    protected $table = 'emplazamientos';

    protected $primaryKey = 'id_emplazamiento';

    public $timestamps = false;

    protected $fillable = [
        'id_proyecto',
        'id_agenda',
        'id_padre',
        'nivel',
        'codigo_ubicacion',
        'descripcion',
        'estado',
        'activo',
        'fecha_creacion',
        'fecha_actualizacion',
        'usuario',
        'codigo',
        'ciclo_auditoria',
        'new_app',
        'modo',
    ];
}