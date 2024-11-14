<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CrudActivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'foto4'
    ];


    protected $primaryKey = 'idActivo';

    const CREATED_AT = 'fechaCreacion';
    const UPDATED_AT = 'fechaModificacion';




    public function getCodigoActivoAttribute()
    {
        $sufix = str_pad($this->nombreActivo, 4, "0", STR_PAD_LEFT);
        return "{$this->categoriaN3}{$sufix}";
    }


    public function getNombreActivoOrigenAttribute()
    {

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
        return $this->belongsTo(IndiceLista::class, 'marca', 'idLista')->where('idAtributo', '=', 2)->where('idIndice', '=', $this->idIndice);
    }

    public function estadoBienRelation()
    {
        return $this->belongsTo(IndiceLista13::class, 'apoyaBrazosRuedas', 'idLista');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoriaN3', 'codigoCategoria');
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
