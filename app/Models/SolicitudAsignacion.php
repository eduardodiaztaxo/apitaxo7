<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudAsignacion extends Model
{
    use HasFactory;

    protected $table = 'solicitud_asignacion';

    protected $primaryKey = 'id_solicitud';
}
