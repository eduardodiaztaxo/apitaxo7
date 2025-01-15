<?php

namespace App\Http\Resources\V1;

use App\Services\ActivoService;
use Illuminate\Http\Resources\Json\JsonResource;

class CrudActivoLiteResource extends JsonResource
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

        $activo = [];
        $activo['etiqueta'] = $this->etiqueta;
        $activo['categoriaN3'] = $this->categoriaN3;
        $activo['id_familia'] = $this->id_familia;
        $activo['nombreActivo'] = $this->nombre_activo_origen;


        $activo['descripcionCategoria'] = $this->categoria ? $this->categoria->descripcionCategoria : '';

        $activo['descripcionFamilia'] = $this->familia ? $this->familia->descripcion_familia : '';


        $activo['fotoUrl'] = $this->activoService->getUrlAsset($this->resource, $request->user());


        return $activo;
    }
}
