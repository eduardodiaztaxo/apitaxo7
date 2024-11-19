<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public function activos_with_cats_by_cycle($cycle_id)
    {


        $sql = "SELECT 
        crud_activos.*
        FROM
        inv_ciclos
        INNER JOIN inv_ciclos_puntos ON inv_ciclos.idCiclo = inv_ciclos_puntos.idCiclo
        INNER JOIN inv_ciclos_categorias ON inv_ciclos.idCiclo = inv_ciclos_categorias.idCiclo
        INNER JOIN crud_activos 
            ON inv_ciclos_puntos.idPunto =  crud_activos.ubicacionGeografica 
                AND inv_ciclos_categorias.categoria1 = crud_activos.categoriaN1
                AND inv_ciclos_categorias.categoria2 = crud_activos.categoriaN2
                AND inv_ciclos_categorias.categoria3 = crud_activos.categoriaN3
        WHERE inv_ciclos.idCiclo = ? AND inv_ciclos_puntos.idPunto = ? AND crud_activos.ubicacionOrganicaN1 = ? AND crud_activos.ubicacionGeografica = ? ";

        return collect(DB::select($sql, [$cycle_id, $this->idAgenda, $this->codigoUbicacion, $this->idAgenda]));
    }


    public function emplazamientos()
    {
        return $this->hasMany(Emplazamiento::class, 'idAgenda', 'idAgenda')->where('codigoUbicacion', 'LIKE', $this->codigoUbicacion . '%');
    }
}
