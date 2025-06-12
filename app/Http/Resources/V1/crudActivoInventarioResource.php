<?php

namespace App\Http\Resources\V1;

use App\Services\ActivoService;
use App\Models\Inventario;
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

        $activo['marca'] = !empty($marcaResult) ? $marcaResult[0]->descripcion : 'Sin marca';

        $activo = [];
        $activo['etiqueta'] = $this->etiqueta;
        $activo['categoriaN3'] = $this->categoriaN3;
        $activo['id_familia'] = $this->id_familia;
        $activo['id_grupo'] = $this->id_grupo;
       $activo['nombreActivo'] = $this->descripcion_bien ?? '';
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
    

    $activo['audit_status'] = 0;
    
        $activo['audit_status_name'] = '';


        $activo['descripcionCategoria'] = $this->categoria ? $this->categoria->descripcionCategoria : '';

        $activo['descripcionFamilia'] = $this->familia ? $this->familia->descripcion_familia : '';
        $activo['descripcion_grupo'] = $grupoDescripcion ?? ''; 

        $activo['fotoUrl'] = null;

        // En tu controlador o wherever lo traes
        $activos = Inventario::with('imagen')->where(...)->get();

       $activo['fotoUrl'] = $this->imagen && !empty($this->imagen->url_imagen)
    ? $this->imagen->url_imagen
    : asset('img/notavailable.jpg');


        return $activo;
    }
}