<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class CrudActivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'foto4',
        'marca',
        'modelo',
        'serie',
        'responsableN1',
        'apoyaBrazosRuedas',
        'descripcionTipo',
        'observacion',
        'latitud',
        'id_familia',
        'longitud'
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
            ->where(function ($query) {
                $query->where('idAtributo', '=', 2)
                    ->where('idLista', '=', $this->idIndice)
                    ->where('id_familia', '=', $this->id_familia);
            });
    }

    public function modeloRelation()
    {
        return $this->belongsTo(IndiceLista::class, 'marca', 'idLista')
            ->where(function ($query) {
                $query->where('idAtributo', '=', 1)
                    ->where('idLista', '=', $this->idIndice)
                    ->where('id_familia', '=', $this->id_familia);
            });
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
        return $this->belongsTo(IndiceLista13::class, 'apoyaBrazosRuedas', 'idLista');
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
}
