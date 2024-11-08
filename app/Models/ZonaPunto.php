<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZonaPunto extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones_n1';

    protected $primaryKey = 'idUbicacionN1';

    public function activos()
    {
        //return $this->hasMany(CrudActivo::class, 'ubicacionOrganicaN1,ubicacionGeografica', 'idUbicacionN1,idAgenda');
        return $this->hasMany(CrudActivo::class, 'ubicacionOrganicaN1', 'codigoUbicacion')->where('ubicacionGeografica', $this->idAgenda);
    }


    public function emplazamientos()
    {
        return $this->hasMany(Emplazamiento::class, 'idAgenda', 'idAgenda')->where('codigoUbicacion', 'LIKE', $this->codigoUbicacion . '%');
    }
}
