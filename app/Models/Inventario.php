<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\IndiceLista;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Inventario extends Model
{
    use HasFactory;

    protected $table = 'inv_inventario';
    protected $primaryKey = 'id_inventario';
    public $timestamps = false;


    protected $fillable = [
        'id_inventario',
        'id_proyecto',
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
        'texto_abierto_6',
        'texto_abierto_7',
        'texto_abierto_8',
        'texto_abierto_9',
        'texto_abierto_10',
        /** edualejandro */
        'cantidad_img',
        'id_img',
        'id_ciclo',
        'idUbicacionGeo',
        'codigoUbicacion_N1',
        'idUbicacionN2',
        'codigoUbicacion_N2',
        'idUbicacionN3',
        'codigoUbicacionN3',
        'codigoUbicacionN4',
        'responsable',
        'idResponsable',
        'latitud',
        'longitud',
        'adjusted_lat',
        'adjusted_lng',
        'fix_quality',
        'satellites',
        'sd_lat',
        'sd_lon',
        'descripcionTipo',
        'observacion',
        'update_inv',
        'creado_por',
        'creado_el',
        'modificado_el',
        'modificado_por',
        'crud_activo_estado',
        'modo',

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

    public function emplazamienton1()
    {
        return EmplazamientoN1::join('inv_inventario', function (JoinClause $join) {
            $join->on('ubicaciones_n1.idAgenda', '=', 'inv_inventario.idUbicacionGeo')
                ->on('ubicaciones_n1.codigoUbicacion', '=', 'inv_inventario.codigoUbicacion_N1');
        })->where('ubicaciones_n1.idAgenda', '=', $this->idUbicacionGeo)->where('ubicaciones_n1.codigoUbicacion', '=', $this->codigoUbicacionN1);
    }

    public function emplazamientoN2()
    {
        return $this->belongsTo(EmplazamientoN2::class, 'idUbicacionN2', 'idUbicacionN2');
    }

    public function emplazamientoN3()
    {
        return $this->belongsTo(EmplazamientoN3::class, 'idUbicacionN3', 'idUbicacionN3');
    }

    public static function queryBuilderInventory_FindInGroupFamily_Pagination($model, InvCiclo $cicloObj, Request $request)
    {
        $queryBuilder = $model->inv_activos()->where('inv_inventario.id_ciclo', $cicloObj->idCiclo);

        if (!!keyword_is_searcheable($request->keyword)) {
            $complete_word = trim($request->keyword);
            $possible_name_words = keyword_search_terms_from_keyword($request->keyword);

            $queryBuilder = $queryBuilder->join('dp_familias', 'inv_inventario.id_familia', 'dp_familias.id_familia');

            $queryBuilder = $queryBuilder
                ->where(function ($query) use ($complete_word) {
                    $query->where('inv_inventario.descripcion_bien', 'LIKE', "%$complete_word%");
                    $query->orWhere('inv_inventario.etiqueta', 'LIKE', "%$complete_word%");
                    $query->orWhere('dp_familias.descripcion_familia', 'LIKE', "%$complete_word%");
                });

            if (count($possible_name_words) > 1) {
                $queryBuilder = $queryBuilder->orWhere(function ($query) use ($possible_name_words) {
                    foreach ($possible_name_words as $palabra) {
                        $query->where('inv_inventario.descripcion_bien', 'LIKE', "%$palabra%");
                    }
                });

                $queryBuilder = $queryBuilder->orWhere(function ($query) use ($possible_name_words) {
                    foreach ($possible_name_words as $palabra) {
                        $query->where('dp_familias.descripcion_familia', 'LIKE', "%$palabra%");
                    }
                });
            }
        }

        if ($request->from && $request->rows) {
            $offset = $request->from - 1;
            $limit = $request->rows;
            $queryBuilder->offset($offset)->limit($limit);
        }

        return $queryBuilder;
    }

    /**
     * Fill instance with codes and ids of places and levels  
     */
    public function fillCodeAndIDSEmplazamientos()
    {

        if (strlen($this->codigoUbicacionN4) === 8) {
            $this->codigoUbicacionN3 = substr($this->codigoUbicacionN4, 0, 6);
        }

        if (strlen($this->codigoUbicacionN3) === 6) {
            $this->codigoUbicacion_N2 = substr($this->codigoUbicacionN3, 0, 4);
        }

        if (strlen($this->codigoUbicacion_N2) === 4) {
            $this->codigoUbicacion_N1 = substr($this->codigoUbicacion_N2, 0, 2);
        }

        if (strlen($this->codigoUbicacionN3) === 6) {
            $idUbicacionN3 = DB::table('ubicaciones_n3')
                ->where('codigoUbicacion', $this->codigoUbicacionN3)
                ->where('idAgenda', $this->idUbicacionGeo)
                ->value('idUbicacionN3');

            $this->idUbicacionN3 = $idUbicacionN3;
        }

        if (strlen($this->codigoUbicacion_N2) === 4) {
            $idUbicacionN2 = DB::table('ubicaciones_n2')
                ->where('codigoUbicacion', $this->codigoUbicacion_N2)
                ->where('idAgenda', $this->idUbicacionGeo)
                ->value('idUbicacionN2');

            $this->idUbicacionN2 = $idUbicacionN2;
        }

        $this->save();

        return $this;
    }
}
