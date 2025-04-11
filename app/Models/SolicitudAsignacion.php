<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudAsignacion extends Model
{
    use HasFactory;

    protected $fillable = [
        'n_solicitud',
        'fecha',
        'fecha_mov',
        'usuario',
        'comentario',
        'estado_proceso',
        'estado_docto',
        'id_responsable',
        'acta',
        'tipo',
    ];

    protected $table = 'solicitud_asignacion';

    protected $primaryKey = 'id_solicitud';

    public $timestamps = false;
}
