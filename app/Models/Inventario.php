<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\IndiceLista;
use Illuminate\Database\Query\JoinClause;

class Inventario extends Model
{
    use HasFactory;

    protected $table = 'inv_inventario';
    protected $primaryKey = 'id_inventario';
    public $timestamps = false;


    protected $fillable = [
        'descripcion_marca',
        'modelo',
        'serie',
        'responsable',
        'estado',
        'descripcionTipo',
        'observacion',
        'latitud',
        'longitud',
    ];
public function marcasDisponibles()
    {



        // $queryBuilder = IndiceLista::select('indices_listas.*')
        //     ->join('categoria_n3', function (JoinClause $join) {
        //         $join->on('indices_listas.idIndice', '=', 'categoria_n3.idIndice');
        //     })
        //     ->join('crud_activos', 'categoria_n3.codigoCategoria', '=', 'crud_activos.categoriaN3')
        //     ->where('indices_listas.idAtributo', '=', '2')
        //     ->where('crud_activos.etiqueta', '=', $this->etiqueta);

        //se agrego las nuevas tablas

        $queryBuilder = IndiceLista::select('indices_listas.*')
        ->join('categoria_n3', function (JoinClause $join) {
            $join->on('indices_listas.idIndice', '=', 'categoria_n3.idIndice');
        })
        ->join('inv_marcas_nuevos', function (JoinClause $join) {
            $join->on('indices_listas.idIndice', '=', 'inv_marcas_nuevos.idIndice');
        })

        ->where('inv_marcas_nuevos.idAtributo', '=', '2')
        ->where('indices_listas.idAtributo', '=', '2')
        ->where('indices_listas.id_familia', '=', $this->id_familia);   



        return $queryBuilder;




        // "SELECT
        // categoria_n3.idIndice, 
        // crud_activos.categoriaN3,
        // indices_listas.idLista,
        // indices_listas.descripcion 
        // FROM crud_activos 
        // INNER JOIN categoria_n3 ON crud_activos.categoriaN3 = categoria_n3.codigoCategoria
        // INNER JOIN indices_listas ON categoria_n3.idIndice = indices_listas.idIndice AND indices_listas.idAtributo = 2
        // WHERE etiqueta = 'AF100001'"
    }
}