<?php

namespace App\Http\Resources\V2;

use App\Http\Resources\V1\UbicacionGeograficaResource;
use App\Http\Resources\V1\ZonaPuntoResource;
use App\Models\Inv_ciclos_categorias;
use App\Models\InvConteoRegistro;
use App\Models\Inventario;
use App\Services\ProyectoUsuarioService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class EmplazamientoNivel2Resource extends JsonResource
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

        $num_activos_inv = $this->inv_activos()
            ->where('inv_inventario.id_proyecto', $id_proyecto)
            ->count();

        $num_activos_N2 = Inventario::where('inv_inventario.idUbicacionN2', $this->idUbicacionN2)
            ->where('inv_inventario.id_ciclo', $this->cycle_id)
            ->where('inv_inventario.id_proyecto', $id_proyecto)
            ->where('codigoUbicacionN3', '<', 2)
            ->count();

        $num_activos_N3 = Inventario::where('inv_inventario.codigoUbicacionN3', 'LIKE', $this->codigoUbicacion . '%')
            ->where('inv_inventario.idUbicacionGeo', $this->idAgenda)
            ->where('inv_inventario.id_proyecto', $id_proyecto)
            ->where('inv_inventario.id_ciclo', $this->cycle_id)
            ->count();

        $emplazamiento = [
            'id' => $this->idUbicacionN2,
            'codigo' => $this->codigo,
            'codigoUbicacion' => $this->codigoUbicacion,
            'nombre' => $this->descripcionUbicacion,
            'idAgenda' => $this->idAgenda,
            'idUbicacionN2' => $this->idUbicacionN2,
            'detalle' => 'Detalle Emplazamiento (N2)',
            'num_nivel' => 'N2',
            'next_level' => 'N3',
            'newApp' => $this->newApp,
            'modo' => $this->modo,
            'habilitadoNivel3' => 1,
            'num_activos' => 0,
            'num_activos_audit' => 0,
            'num_activos_inv' => $num_activos_inv,
            'num_activos_N1' => null,
            'num_activos_N2' => $num_activos_N2,
            'num_activos_N3' => $num_activos_N3,
            'num_activos_cats_by_cycle' => 0,
            'ciclo_auditoria' => $this->ciclo_auditoria,
            'num_categorias' => $this->activos()->select('categoriaN3')->groupBy('categoriaN3')->get()->count(),
            'id_ciclo' => $this->cycle_id,
            'zone_address' => ZonaPuntoResource::make($this->zonaPunto()->first())
        ];

        if (isset($this->requirePunto) && $this->requirePunto) {
            $emplazamiento['ubicacionPunto'] = UbicacionGeograficaResource::make($this->ubicacionPunto()->first());
        }

        //Transitorio
        if (isset($this->cycle_id) && $this->cycle_id && isset($this->type_cycle) && $this->type_cycle == 2) {
            $emplazamiento['num_activos_audit'] = InvConteoRegistro::where('ciclo_id', '=', $this->cycle_id)
                ->where('cod_emplazamiento', '=', $this->codigoUbicacion)
                ->whereIn('audit_status', [1, 3])
                ->count();
            $emplazamiento['num_activos_cats_by_cycle'] = isset($emplazamiento['activos']) ? count($emplazamiento['activos']) : $this->activos_with_cats_by_cycle($this->cycle_id)->count();
        }



        return $emplazamiento;
    }
}
