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
        'id_grupo',
        'id_familia',
        'descripcion_bien',
        'id_bien',
        'descripcion_marca',
        'id_marca',
        'idForma',
        'idMaterial',
        'etiqueta',
        'etiqueta_padre',
        'modelo',
        'serie',
        'capacidad',
        'estado',
        'color',
        'tipo_trabajo',
        'carga_trabajo',
        'estado_operacional',
        'estado_conservacion',
        'condicion_Ambiental',
        'cantidad_img',
        'id_img',
        'id_ciclo',
        'idUbicacionGeo',
        'idUbicacionN2',
        'codigoUbicacion_N1',
        'responsable',
        'idResponsable',
        'latitud',
        'longitud',
        'descripcionTipo',
        'observacion',
        'update_inv',
        'crud_activo_estado',

    ];
    public function marcasDisponibles()
    {

        $queryBuilder = IndiceLista::select('indices_listas.*')
            ->join('categoria_n3', function (JoinClause $join) {
                $join->on('indices_listas.idIndice', '=', 'categoria_n3.idIndice');
            })
            ->join('inv_inventario', 'categoria_n3.idIndice', '=', 'inv_inventario.id_familia')
            ->where('indices_listas.idAtributo', '=', '2')
            ->where('inv_inventario.etiqueta', '=', $this->etiqueta);


        return $queryBuilder;
    }


    public function imagen()
    {
        return $this->belongsTo(Inv_imagenes::class, 'id_img', 'id_img');
    }
}
