<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Responsable extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'mail',
        'rut',
        'idUbicacionGeografica',
        'idRegion',
        'idComuna'
    ];

    protected $primaryKey = 'idResponsable';

    public $timestamps = false;

    public function ubicacionGeografica()
    {
        return $this->belongsTo(UbicacionGeografica::class, 'idUbicacionGeografica', 'idUbicacionGeo');
    }

    public function solicitudesAsignacion()
    {
        return $this->hasMany(SolicitudAsignacion::class, 'idResponsable', 'idResponsable');
    }
}
