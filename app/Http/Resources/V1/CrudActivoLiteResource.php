<?php

namespace App\Http\Resources\V1;

use App\Services\ActivoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\JsonResource;

class CrudActivoLiteResource extends JsonResource
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
        
        $activo['marca'] = !empty($marcaResult) ? $marcaResult[0]->descripcion : 'Sin marca';

        $activo = [];
        $activo['etiqueta'] = $this->etiqueta;
        $activo['categoriaN3'] = $this->categoriaN3;
        $activo['id_familia'] = $this->id_familia;
        $activo['nombreActivo'] = $this->nombre_activo_origen;
        $activo['modelo'] = $this->modelo;
        $activo['serie'] = $this->serie;
        $activo['marca'] = !empty($marcaResult) ? $marcaResult[0]->descripcion : ''; 
       
        $activo['ubicacionOrganicaN2'] = $this->ubicacionOrganicaN2;
        
        $auditStatus = DB::table('inv_conteo_registro')
        ->where('etiqueta', $this->etiqueta)
        ->where('cod_emplazamiento', $this->ubicacionOrganicaN2)
        ->where('ciclo_id', $this->cycle_id)
        ->where('punto_id', $this->idAgenda)
        ->value('audit_status');

        $activo['id_ciclo'] = $this->cycle_id;
        $activo['id_agenda'] = $this->idAgenda;
        

    if (is_null($auditStatus)) {
        $auditStatus = DB::table('inv_conteo_registro')
            ->where('etiqueta', $this->etiqueta)
            ->where('cod_emplazamiento', $this->ubicacionOrganicaN2)
            ->where('ciclo_id', $this->cycle_id)
            ->where('punto_id', $this->idAgenda)
            ->value('audit_status');
    }
    

    $activo['audit_status'] = $auditStatus ?? 2;

        $statusDescriptions = [
        1 => 'coincidente',
        2 => 'faltante',
        3 => 'sobrante',
        ];

        $activo['audit_status_name'] = $auditStatus && isset($statusDescriptions[$auditStatus])
        ? $statusDescriptions[$auditStatus]
        : 'faltante';


        $activo['descripcionCategoria'] = $this->categoria ? $this->categoria->descripcionCategoria : '';

        $activo['descripcionFamilia'] = $this->familia ? $this->familia->descripcion_familia : '';


        $activo['fotoUrl'] = $this->activoService->getUrlAsset($this->resource, $request->user());


        return $activo;
    }
}
