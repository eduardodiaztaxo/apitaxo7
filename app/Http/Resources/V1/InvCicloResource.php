<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\CiclosEstados;
use App\Models\InvCicloPunto;
class InvCicloResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Obtener la descripción del estado desde la tabla `estados`
        $estadoDescripcion = CiclosEstados::where('id_estado', $this->estadoCiclo)->value('descripcion');
        $numAudith = InvCicloPunto::Where('idCiclo', $this->idCiclo)->value('totalPunto');

        return [
            'idCiclo'       => $this->idCiclo,
            'status'        => $this->estadoCiclo,
            'tipoCiclo'     => $this->idTipoCiclo,
            'status_name'   => $estadoDescripcion ?? 'Desconocido', // Mostrar descripción desde la tabla o un valor por defecto
            'title'         => $this->descripcion,
            'date'          => $this->fechaInicio,
            'date_end'      => $this->fechaTermino,
            'assets_cycle'  => $this->activos_with_cats()->count(), // activos a auditar
            'assets_count'  => $this->audit_activos_address_cats()->count(), // activos auditados
            'puntos_count'  => $this->puntos()->count(), // direcciones
            'audith_count'  => $numAudith, //total de auditados
        ];
    }
    
}
