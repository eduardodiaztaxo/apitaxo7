<?php

namespace App\Http\Resources\V1;

use App\Services\ActivoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\JsonResource;

class crudActivoInventarioResource extends JsonResource
{
    private $activoService;
    private $cycle_id;
    private $idAgenda;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
  public function __construct($resource, $cycle_id = null, $idAgenda = null)
    {
        $this->activoService = new ActivoService();
        $this->cycle_id = $cycle_id;
        $this->idAgenda = $idAgenda;
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
        
        $marcaResult = [];
        if (!empty($this->marca) && !empty($this->id_familia)) {
            $marcaResult = DB::select("SELECT descripcion 
                FROM `indices_listas`
                WHERE idLista = :idLista
                AND id_familia = :idFamilia
                AND idAtributo = :idAtributo", [
                    'idLista' => $this->marca,
                    'idFamilia' => $this->id_familia,
                    'idAtributo' => 2,
                ]);
        }
        $grupoDescripcion = DB::table('dp_grupos')
        ->where('id_grupo', $this->id_grupo)
        ->value('descripcion_grupo');

         $familiaDescripcion = DB::table('dp_familias')
        ->where('id_familia', $this->id_familia)
        ->value('descripcion_familia');

        $activo['marca'] = !empty($marcaResult) ? $marcaResult[0]->descripcion : 'Sin marca';
        $activo = [];
        $activo['etiqueta'] = $this->etiqueta;
        $activo['categoriaN3'] = $this->categoriaN3;
        $activo['id_familia'] = $this->id_familia;
        $activo['id_grupo'] = $this->id_grupo;
        $activo['nombreActivo'] = $this->nombreActivo;
        $activo['modelo'] = $this->modelo;
        $activo['serie'] = $this->serie;
        $activo['marca'] = !empty($marcaResult) ? $marcaResult[0]->descripcion : ''; 
        $activo['ubicacionOrganicaN2'] = $this->ubicacionOrganicaN2;
        $activo['update_inv'] = $this->update_inv;
        $activo['id_ciclo'] = $this->id_ciclo;
        $activo['id_inventario'] = $this->id_inventario;
        $activo['audit_status'] = 0;
        $activo['audit_status_name'] = '';
        $activo['descripcionCategoria'] = $this->categoria ? $this->categoria->descripcionCategoria : '';
        $activo['descripcionFamilia'] = $familiaDescripcion ?? '';
        $activo['descripcion_grupo'] = $grupoDescripcion ?? ''; 
        $activo['fotoUrl'] = null;

        if ($request->user()) {
            $activo['fotoUrl'] = $this->activoService->getUrlAssetInventario($this->resource, $request->user());
        }


        return $activo;
    }
}