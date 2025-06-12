<?php

namespace App\Http\Resources\V1;

use App\Services\ActivoService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class CrudActivoResource extends JsonResource
{


    private $activoService;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource)
    {
        $this->activoService = new ActivoService();
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $activo = parent::toArray($request);

        $grupoDescripcion = DB::table('dp_grupos')
            ->where('id_grupo', $this->id_grupo)
            ->value('descripcion_grupo');

        $activo['idUbicacionGeografica'] = (int)$activo['ubicacionGeografica'];

        $activo['codigo_activo'] = $this->codigo_activo;

        $activo['tipoAlta'] = $this->tipoAltaRelation ? $this->tipoAltaRelation->descripcion : '';

        $activo['nombreActivo'] = $this->nombre_activo_origen;

        $activo['marca'] = $this->marcaRelation ? $this->marcaRelation->descripcion : '';

        $activo['estadoBien'] = $this->estadoBienRelation ? $this->estadoBienRelation->descripcion : '';

        $activo['descripcionCategoria'] = $this->categoria ? $this->categoria->descripcionCategoria : '';

        $activo['descripcionFamilia'] = $this->familia ? $this->familia->descripcion_familia : '';

        $activo['descripcionGrupo'] = $grupoDescripcion;

        $activo['responsable'] = $this->responsable ? $this->responsable->name : null;

        $activo['organica_n1'] = $this->zona;

        $activo['organica_n2'] = $this->emplazamiento;

        $activo['depreciable'] = $this->depreciableRelation->descripcion;

        $activo['fotoUrl'] = null;

        if ($request->user())
            $activo['fotoUrl'] = $this->activoService->getUrlAsset($this->resource, $request->user());


        $ubicacion = $this->ubicacionGeografica()->first();

        if ($this->requireUbicacion && $ubicacion) {
            $activo['ubicacion'] = $ubicacion->toArray();
            $activo['ubicacion']['region'] = $ubicacion->region()->first()->descripcion;
            $activo['ubicacion']['comuna'] = $ubicacion->comuna()->first()->descripcion;
        } else {
            $activo['ubicacion'] = [];
        }

        if (isset($this->requireEmplazamiento) && $this->requireEmplazamiento) {
            $activo['emplazamiento'] = EmplazamientoResource::make($this->emplazamientoZona()->first());
        }


        return $activo;
    }
}
