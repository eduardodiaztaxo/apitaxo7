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
        /** edualejandro */
        'eficiencia',
        'texto_abierto_1',
        'texto_abierto_2',
        'texto_abierto_3',
        'texto_abierto_4',
        'texto_abierto_5',
        /** edualejandro */
        'cantidad_img',
        'id_img',
        'id_ciclo',
        'idUbicacionGeo',
        'idUbicacionN2',
        'codigoUbicacion_N2',
        'codigoUbicacion_N1',
        'idUbicacionN3',
        'codigoUbicacionN4',
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
        return $this->hasMany(Inv_imagenes::class, 'id_img', 'id_img');
    }

    public function imagenes()
    {
        return $this->hasMany(Inv_imagenes::class, 'id_img', 'id_img');
    }


    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'id_grupo', 'id_grupo');
    }

    public function familia()
    {
        return $this->belongsTo(Familia::class, 'id_familia', 'id_familia');
    }

    public function estadoBien()
    {
        return $this->belongsTo(IndiceLista13::class, 'idLista', 'estado');
    }

    public function addressPunto()
    {
        return $this->belongsTo(UbicacionGeografica::class, 'idUbicacionGeo', 'idUbicacionGeo');
    }

    public function zonaN1()
    {
        return $this->belongsTo(ZonaPunto::class, 'idUbicacionN1', 'idUbicacionN1');
    }

    public function emplazamientoN2()
    {
        return $this->belongsTo(EmplazamientoN2::class, 'idUbicacionN2', 'idUbicacionN2');
    }

    public function emplazamientoN3()
    {
        return $this->belongsTo(EmplazamientoN3::class, 'idUbicacionN3', 'idUbicacionN3');
    }
}
