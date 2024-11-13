<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvCiclo extends Model
{
    use HasFactory;

    protected $primaryKey = 'idCiclo';

    public function puntos()
    {
        //return $this->hasMany(UbicacionGeografica::class, 'idPunto', 'idPunto');

        return $this->hasManyThrough(UbicacionGeografica::class, InvCicloPunto::class, 'idCiclo', 'idPunto', 'idCiclo', 'idPunto');
    }

    public function ciclo_users()
    {
        return $this->hasMany(InvCicloUser::class, 'ciclo_id', 'idCiclo');
    }
}
