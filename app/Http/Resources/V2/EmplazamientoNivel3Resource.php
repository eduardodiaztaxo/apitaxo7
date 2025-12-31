<?php

namespace App\Http\Resources\V2;

use App\Http\Resources\V1\UbicacionGeograficaResource;
use App\Http\Resources\V1\ZonaPuntoResource;
use App\Models\Inventario;
use App\Services\ActivoFinderService;
use App\Services\ProyectoUsuarioService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class EmplazamientoNivel3Resource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $num_activos_inv = $this->inv_activos()->where('inv_inventario.id_ciclo', $this->cycle_id)->where('inv_inventario.id_proyecto', $id_proyecto)->count();

        $ubicacionN2 = $this->emplazamientoN2()->first();

        $emplazamiento = [
            'ciclo' => $this->cycle_id,
            'id_proyecto' => $id_proyecto,
            'id' => $this->idUbicacionN3,
            'codigo' => $this->codigo,
            'codigoUbicacion' => $this->codigoUbicacion,
            'nombre' => $this->descripcionUbicacion,
            'idAgenda' => $this->idAgenda,
            'idUbicacionN3' => $this->idUbicacionN3,
            'idUbicacionN2' => $ubicacionN2 ? $ubicacionN2->idUbicacionN2 : null,
            'detalle' => 'Detalle Emplazamiento (N3)',
            'num_nivel' => 'N3',
            'next_level' => '',
            'newApp' => $this->newApp,
            'modo' => $this->modo,
            'habilitadoNivel3' => 0,
            'num_activos' => 0,
            'num_activos_audit' => 0,
            'num_activos_inv' => $num_activos_inv,
            'num_activos_N1' => null,
            'num_activos_N2' => null,
            'num_activos_N3' => $num_activos_inv,
            'num_activos_cats_by_cycle' => 0,
            'ciclo_auditoria' => 0,
            'num_categorias' => $this->activos()->select('categoriaN3')->groupBy('categoriaN3')->get()->count(),
            'id_ciclo' => $this->cycle_id,
            'zone_address' => ZonaPuntoResource::make($this->zonaPunto()->first()),

        ];

        if (isset($this->requirePunto) && $this->requirePunto) {
            $emplazamiento['ubicacionPunto'] = UbicacionGeograficaResource::make($this->ubicacionPunto()->first());
        }

        return $emplazamiento;
    }
}
