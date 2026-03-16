<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrudActivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'foto4',
        'marca',
        'modelo',
        'serie',
        'nombreActivo',
        'responsableN1',
        'apoyaBrazosRuedas',
        'descripcionTipo',
        'observacion',
        'latitud',
        'longitud',
        'creado_por',
    ];


    protected $primaryKey = 'idActivo';

    const CREATED_AT = 'fechaCreacion';
    const UPDATED_AT = 'fechaModificacion';


    const AUDIT_STATUS_COINCIDENTE = 1;
    const AUDIT_STATUS_FALTANTE = 2;
    const AUDIT_STATUS_SOBRANTE = 3;



    public function getCodigoActivoAttribute()
    {
        $sufix = str_pad($this->nombreActivo, 4, "0", STR_PAD_LEFT);
        return "{$this->categoriaN3}{$sufix}";
    }


    public function getNombreActivoOrigenAttribute()
    {
        // Validar que las propiedades no estén vacías
        if (empty($this->nombreActivo) || empty($this->idIndice)) {
            return null; // Retorna null si los valores necesarios están vacíos
        }

        // Construir la consulta solo si los valores son válidos
        $indice = DB::table('indices_listas')
            ->where('idLista', '=', $this->nombreActivo)
            ->where('idAtributo', '=', 1)
            ->where('idIndice', '=', $this->idIndice)
            ->first();

        return $indice ? $indice->descripcion : null;
    }

    public function getZonaAttribute()
    {

        $zona = DB::table('ubicaciones_n1')
            ->where('idAgenda', '=', $this->ubicacionGeografica)
            ->where('codigoUbicacion', '=', $this->ubicacionOrganicaN1)
            ->first();


        return $zona ? $zona->descripcionUbicacion : null;
    }

    public function getEmplazamientoAttribute()
    {

        $emplazamiento = DB::table('ubicaciones_n2')
            ->where('idAgenda', '=', $this->ubicacionGeografica)
            ->where('codigoUbicacion', '=', $this->ubicacionOrganicaN2)
            ->first();


        return $emplazamiento ? $emplazamiento->descripcionUbicacion : null;
    }


    public function emplazamientoZona()
    {
        return $this->belongsTo(Emplazamiento::class, 'ubicacionOrganicaN2', 'codigoUbicacion')->where('idAgenda', $this->ubicacionGeografica);
    }



    public function ubicacionGeografica()
    {
        return $this->belongsTo(UbicacionGeografica::class, 'ubicacionGeografica', 'idUbicacionGeo');
    }

    public function tipoAltaRelation()
    {
        return $this->belongsTo(TipoAlta::class, 'tipoAlta', 'idTipo');
    }

    // public function indiceListaRelation()
    // {
    //     return $this->belongsTo(IndiceLista::class, ['nombreActivo', 'idIndice'], ['idLista', 'idIndice']);
    // }



    public function marcaRelation()
    {
        return $this->belongsTo(IndiceLista::class, 'marca', 'idLista')
            ->where('idAtributo', 2)
            ->where('id_familia', $this->id_familia);
    }

    public function modeloRelation()
    {
        return $this->belongsTo(IndiceLista::class, 'nombreActivo', 'idLista')
            ->where('idAtributo', '=', 1)
            ->where('id_familia', '=', $this->id_familia);
    }


    public function marcasDisponibles()
    {



        $queryBuilder = IndiceLista::select('indices_listas.*')
            ->join('categoria_n3', function (JoinClause $join) {
                $join->on('indices_listas.idIndice', '=', 'categoria_n3.idIndice');
            })
            ->join('crud_activos', 'categoria_n3.codigoCategoria', '=', 'crud_activos.categoriaN3')
            ->where('indices_listas.idAtributo', '=', '2')
            ->where('crud_activos.etiqueta', '=', $this->etiqueta);



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
    public function Localizacion()
    {
        $queryBuilder = IndiceLista::select('latitud', 'longitud')
            ->from('crud_activos')
            ->where('crud_activos.etiqueta', '=', $this->etiqueta);

        return $queryBuilder;
    }


    public function estadoBienRelation()
    {
        return $this->belongsTo(IndiceLista13::class, 'apoyaBrazosRuedas', 'idLista')
            ->limit(1);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoriaN3', 'codigoCategoria');
    }

    public function familia()
    {
        return $this->belongsTo(Familia::class, 'id_familia', 'id_familia');
    }

    public function responsable()
    {
        return $this->belongsTo(Responsable::class, 'responsableN1', 'idResponsable');
    }

    public function depreciableRelation()
    {
        return $this->belongsTo(ActivoDepreciableStatus::class, 'depreciable');
    }

    /**
     * Build a query to find activos in crud_activos by group and family with pagination and keyword search.
     * 
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function queryBuilderCrudActivo_FindInGroupFamily_Pagination($model, Request $request)
    {
        $queryBuilder = $model->activos();

        if (!!keyword_is_searcheable($request->keyword)) {
            $complete_word = trim($request->keyword);
            $possible_name_words = keyword_search_terms_from_keyword($request->keyword);

            $queryBuilder = $queryBuilder->join('dp_familias', 'crud_activos.id_familia', 'dp_familias.id_familia');

            $queryBuilder = $queryBuilder
                ->where(function ($query) use ($complete_word) {
                    $query->where('crud_activos.descripcionTipo', 'LIKE', "%$complete_word%");
                    $query->orWhere('crud_activos.etiqueta', 'LIKE', "%$complete_word%");
                    $query->orWhere('dp_familias.descripcion_familia', 'LIKE', "%$complete_word%");
                });

            if (count($possible_name_words) > 1) {
                $queryBuilder = $queryBuilder->orWhere(function ($query) use ($possible_name_words) {
                    foreach ($possible_name_words as $palabra) {
                        $query->where('crud_activos.descripcionTipo', 'LIKE', "%$palabra%");
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



    public static function queryBuilderAsset_Audit_ConfigCycle_FindInAddressGroupFamily_Pagination(
        InvCiclo $cicloObj,
        int $punto,
        string $codigo,
        int $sublevel,
        string $keyword = '',
        int $from = 0,
        int $rows = 0
    ) {



        $queryBuilder = $cicloObj->activos_with_cats()->where('ubicacionGeografica', '=', $punto);

        if ($sublevel > 0 && strlen($codigo) > 1) {
            $placeField = 'ubicacionOrganicaN' . $sublevel;
            $nexPlaceField = 'ubicacionOrganicaN' . ($sublevel + 1);
            $queryBuilder = $queryBuilder->where($placeField, '=', $codigo);

            //Si el siguiente campo de ubicación es cero, vacío o nulo,
            //se entiende que el activo está en ese nivel, no en un de menor jerarquía.

            //la tabla no tiene más de 6 campos que representan niveles de ubicación orgánica, 
            //por lo que si el subnivel es mayor a 6, NO se aplica filtro o condición de que el siguiente campo de 
            //ubicación orgánica esté vacío o nulo o sea '0' 
            if ($sublevel < 6) {
                $queryBuilder = $queryBuilder->where(function ($query) use ($nexPlaceField) {
                    $query->whereNull($nexPlaceField)->orWhere($nexPlaceField, '=', '')->orWhere($nexPlaceField, '=', '0');
                });
            }
        }

        if (!!keyword_is_searcheable($keyword)) {



            $complete_word = trim($keyword);
            $possible_name_words = keyword_search_terms_from_keyword($keyword);

            $idsFamilias = Familia::query()->where('descripcion_familia', 'LIKE', "%$complete_word%")->get()->pluck('id_familia')->toArray();

            $queryBuilder = $queryBuilder
                ->where(function ($query) use ($complete_word, $idsFamilias) {
                    $query->where('crud_activos.descripcionTipo', 'LIKE', "%$complete_word%");
                    $query->orWhere('crud_activos.etiqueta', 'LIKE', "%$complete_word%");
                    $query->orWhereIn('crud_activos.id_familia', $idsFamilias);
                });

            if (count($possible_name_words) > 1) {
                $queryBuilder = $queryBuilder->orWhere(function ($query) use ($possible_name_words) {
                    foreach ($possible_name_words as $palabra) {
                        $query->where('crud_activos.descripcionTipo', 'LIKE', "%$palabra%");
                    }
                });

                $queryBuilder = $queryBuilder->orWhere(function ($query) use ($possible_name_words) {
                    foreach ($possible_name_words as $palabra) {
                        $idsFamilias = Familia::query()->where('descripcion_familia', 'LIKE', "%$palabra%")->get()->pluck('id_familia')->toArray();
                        $query->whereIn('crud_activos.id_familia', $idsFamilias);
                    }
                });
            }
        }

        if ($from && $rows) {
            $offset = $from - 1;
            $limit = $rows;
            $queryBuilder->offset($offset)->limit($limit);
        }

        return $queryBuilder;
    }
}
