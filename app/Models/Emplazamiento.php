<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Emplazamiento extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones_n2';

    public function activos()
    {
        return $this->hasMany(CrudActivo::class, 'ubicacionOrganicaN2', 'codigoUbicacion')->where('ubicacionGeografica', $this->idAgenda);
    }

    public function zonaPunto()
    {
        return $this->belongsTo(ZonaPunto::class, 'idAgenda', 'idAgenda')->where('codigoUbicacion', '=', substr($this->codigoUbicacion, 0, 2));
    }
}
