<?php

namespace App\Http\Resources\V1;

use App\Services\ActivoService;
use Illuminate\Support\Facades\DB;
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
        $marcaResult = DB::select("SELECT descripcion 
        FROM `indices_listas`
        WHERE idLista = $this->marca
        AND id_familia = $this->id_familia
        AND idAtributo = 2");

        $activo = [];
        $activo['etiqueta'] = $this->etiqueta;
        $activo['categoriaN3'] = $this->categoriaN3;
        $activo['id_familia'] = $this->id_familia;
        $activo['nombreActivo'] = $this->nombre_activo_origen;
        $activo['modelo'] = $this->modelo;
        $activo['serie'] = $this->serie;
        $activo['marca'] = !empty($marcaResult) ? $marcaResult[0]->descripcion : ''; 

        $activo['descripcionCategoria'] = $this->categoria ? $this->categoria->descripcionCategoria : '';

        $activo['descripcionFamilia'] = $this->familia ? $this->familia->descripcion_familia : '';


        $activo['fotoUrl'] = $this->activoService->getUrlAsset($this->resource, $request->user());


        return $activo;
    }
}
