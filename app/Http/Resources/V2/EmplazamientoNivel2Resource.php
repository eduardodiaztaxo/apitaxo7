<?php

namespace App\Http\Resources\V2;

use App\Http\Resources\V1\UbicacionGeograficaResource;
use App\Http\Resources\V1\ZonaPuntoResource;
use App\Models\Inv_ciclos_categorias;
use App\Models\InvConteoRegistro;
use App\Models\Inventario;
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


        $num_activos_inv = Inventario::where('inv_inventario.idUbicacionN2', $this->idUbicacionN2)
            ->where('inv_inventario.id_ciclo', $this->cycle_id)
            ->count();

        $num_activos_N3 = Inventario::where('inv_inventario.codigoUbicacionN3', 'LIKE', '%' . $this->codigoUbicacion . '%')
            ->where('inv_inventario.idUbicacionGeo', $this->idAgenda)
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
            'num_activos_N2' => null,
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



        return $emplazamiento;
    }
}
