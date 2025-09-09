<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\CiclosEstados;
use App\Models\InvConteoRegistro;
use Illuminate\Support\Facades\Auth;

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
        $numAudith = InvConteoRegistro::Where('ciclo_id', $this->idCiclo)->where('audit_status', 1)->where('status', 1)->count();
        $numAudithSobrante = InvConteoRegistro::Where('ciclo_id', $this->idCiclo)->where('audit_status', 3)->where('status', 1)->count();
        $numAudithFaltante = InvConteoRegistro::Where('ciclo_id', $this->idCiclo)->where('audit_status', 2)->where('status', 1)->count();
        $assetsCycle = $this->activos_with_cats()->count(); 
        $assetsCycleInv = $this->activos_with_cats_inv()->count();
 
        $user = Auth::user();

        $usuario = $user?->name;
        $puntos = $this->ciclo_puntos_users($usuario, $this->idCiclo)->count();
      

        if ($puntos === 0) {
            $puntos = $this->puntos()->count();
        }

       
        return [
            'idCiclo'           => $this->idCiclo,
            'status'            => $this->estadoCiclo,
            'tipoCiclo'         => $this->idTipoCiclo,
            'status_name'       => $estadoDescripcion ?? 'Desconocido',
            'title'             => $this->descripcion,
            'date'              => $this->fechaInicio,
            'date_end'          => $this->fechaTermino,
            'assets_cycle'      => $assetsCycle,
            'assets_cycle_inv'  => $assetsCycleInv,
            'assets_count'      => $this->audit_activos_address_cats()->count(),
            'puntos_count'      => $puntos,
            'audith_count'      => $numAudith,
            'audith_sobrante'   => $numAudithSobrante,
            'audith_faltante'   => $assetsCycle - $numAudith,
            // 'audith_faltante'  => $numAudithFaltante,
            'offline_db'    => $this->dump()->where('status', 1)->count(),
            'offline_db_version' => $this->dump()->where('status', 1)->latest()->first()?->version ?? 'N/A',
        ];
    }
}
