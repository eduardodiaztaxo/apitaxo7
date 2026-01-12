<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\CiclosPunto;
use App\Models\InvConteoRegistro;
use App\Models\Inventario;
use App\Models\ZonaPunto;
use App\Models\UbicacionGeografica;
use App\Models\PuntosEstados;
use Illuminate\Support\Facades\Auth;

class UbicacionGeograficaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        if (
            isset($this->zonas_cats) && is_array($this->zonas_cats) &&
            isset($this->cycle_id) && $this->cycle_id
        ) {
            $zones = $this->zonasPunto()->whereIn('codigoUbicacion', $this->zonas_cats)->get();

            foreach ($zones as $zone) {
                $zone->cycle_id = $this->cycle_id;
            }

            $zonas_punto = ZonaPuntoResource::collection($zones);
        } elseif (isset($this->requireZonas) && $this->requireZonas) {
            $zones = $this->zonasPunto()->get();

            foreach ($zones as $zone) {
                $zone->cycle_id = $this->cycle_id;
            }

            $zonas_punto = ZonaPuntoResource::collection($zones);
        } else {
            $zones = collect(); // vacía
            $zonas_punto = [];
        }

        $user = Auth::user();
        $buscarRelacion = null;

        if ($user) {
            $buscarRelacion = CiclosPunto::where('usuario', $user->name)
                ->where('idCiclo', $this->cycle_id)
                ->where('idPunto', $this->idUbicacionGeo)
                ->first();
        }


        $idPunto = null;
        $id_estado = null;

        if ($buscarRelacion) {
            $id_estado = $buscarRelacion->id_estado;
            $idPunto = $buscarRelacion->idPunto;
        }

        $descripcionEstado = null;

        if ($idPunto == $this->idUbicacionGeo) {
            $estadoDirecciones = PuntosEstados::where('id_estado', $id_estado)->first();

            if ($estadoDirecciones) {
                $descripcionEstado = $estadoDirecciones->descripcion;
                $id_estado = $estadoDirecciones->id_estado;
            }
        }

        $auditoria_general = isset($this->auditoria_general) ? $this->auditoria_general : 0;


        $address = [
            'idUbicacionGeo' => $this->idUbicacionGeo,
            'codigoCliente' => $this->codigoCliente,
            'descripcion'   => $this->descripcion,
            'zona'          => $this->zona,
            'region'        => $this->region()->first()?->descripcion,
            'ciudad'        => $this->ciudad,
            'comuna'        => $this->comuna()->first()?->descripcion,
            'direccion'     => $this->direccion,
            'isPolygon'     => $this->is_polygon ? 1 : 0,
            'idPunto'       => $this->idPunto,
            'estadoGeo'     => $this->estadoGeo,
            'id_estado'     => ($id_estado && $descripcionEstado) ? $id_estado : 1,
            'estado_punto'  => ($id_estado && $descripcionEstado) ? $descripcionEstado : 'ABIERTO',
            'auditoria_general' => $auditoria_general,
            'newApp'        => $this->newApp,
            'modo'          => $this->modo,
            'zonas_punto'   => $zonas_punto,
            'num_activos'   => $this->activos_without_join()->count(),
            'num_activos_cats_by_cycle' => 0,
            'num_activos_inv_cats_by_cycle' => 0,
            'num_cats_by_cycle' => 0
        ];


        if ($this->general === 1) {
            $detalle = [
                'detalle' => 'Detalle General',
            ];

            $address['detalle'] = $detalle['detalle'];
        }


        if (isset($this->cycle_id) && $this->cycle_id) {

            if ($this->idTipoCiclo == 2) {
                $address['num_activos_cats_by_cycle'] = $this->activos_with_cats_by_cycle($this->cycle_id)->count();
                $address['num_activos_inv_cats_by_cycle'] = 0;

                /** Geo Adjust */
                $address['num_activos_geo_adjust'] = 0;

                $coll = $this->cats_by_cycle($this->cycle_id);

                $address['num_cats_by_cycle']       = $coll->pluck('categoriaN1')->unique()->count();
                $address['num_subcats_n2_by_cycle'] = $coll->pluck('categoriaN2')->unique()->count();
                $address['num_subcats_n3_by_cycle'] = $coll->pluck('categoriaN3')->unique()->count();

                $codigosZona = $zones->pluck('codigoUbicacion')->toArray();


                if ($auditoria_general === 1) {
                    $address['num_activos_audit'] = InvConteoRegistro::where('ciclo_id', '=', $this->cycle_id)
                        ->where('punto_id', '=', $this->idUbicacionGeo)
                        ->where('status', '=', 1)
                        ->whereIn('audit_status', [1, 3])
                        ->count();

                    $address['num_activos_audit_coincidentes'] = InvConteoRegistro::where('ciclo_id', '=', $this->cycle_id)
                        ->where('punto_id', '=', $this->idUbicacionGeo)
                        ->where('status', '=', 1)
                        ->where('audit_status', '=', 1)
                        ->count();

                    $address['num_activos_audit_sobrantes'] = InvConteoRegistro::where('ciclo_id', '=', $this->cycle_id)
                        ->where('punto_id', '=', $this->idUbicacionGeo)
                        ->where('status', '=', 1)
                        ->where('audit_status', '=', 3)
                        ->count();
                } else {
                    $address['num_activos_audit'] = InvConteoRegistro::where('ciclo_id', '=', $this->cycle_id)
                        ->where('punto_id', '=', $this->idUbicacionGeo)
                        ->whereIn('cod_zona', $codigosZona)
                        ->whereIn('audit_status', [1, 3])
                        ->count();

                    $address['num_activos_audit_coincidentes'] = InvConteoRegistro::where('ciclo_id', '=', $this->cycle_id)
                        ->where('punto_id', '=', $this->idUbicacionGeo)
                        ->whereIn('cod_zona', $codigosZona)
                        ->where('audit_status', '=', 1)
                        ->count();

                    $address['num_activos_audit_sobrantes'] = InvConteoRegistro::where('ciclo_id', '=', $this->cycle_id)
                        ->where('punto_id', '=', $this->idUbicacionGeo)
                        ->whereIn('cod_zona', $codigosZona)
                        ->where('audit_status', '=', 3)
                        ->count();
                }
            } else {

                $address['num_activos_cats_by_cycle'] = 0;
                $address['num_activos_inv_cats_by_cycle'] = $this->activos_with_cats_inv_by_cycle($this->cycle_id, $this->idUbicacionGeo)->count();

                /** Geo Adjust */
                $address['num_activos_geo_adjust'] = $this->activos_with_cats_inv_by_cycle($this->cycle_id, $this->idUbicacionGeo)
                    ->whereNotNull('adjusted_lat')
                    ->whereNotNull('adjusted_lng')
                    ->count();

                // $coll = $this->cats_by_cycle($this->cycle_id);

                // $address['num_cats_by_cycle']       = $coll->pluck('categoriaN1')->unique()->count();
                // $address['num_subcats_n2_by_cycle'] = $coll->pluck('categoriaN2')->unique()->count();
                // $address['num_subcats_n3_by_cycle'] = $coll->pluck('categoriaN3')->unique()->count();

                $address['num_cats_by_cycle']       = 0;
                $address['num_subcats_n2_by_cycle'] = 0;
                $address['num_subcats_n3_by_cycle'] = 0;
            }









            if (isset($this->requireActivos) && $this->requireActivos) {
                $address['activos'] = CrudActivoLiteResource::collection($this->activos_with_cats_by_cycle($this->cycle_id)->get());
            }
        }






        return $address;
    }

    public function activos_with_cats_inv_by_cycle($cycle_id, $idUbicacionGeo)
    {
        return Inventario::select('inv_inventario.*')
            ->where('inv_inventario.id_ciclo', '=', $cycle_id)
            ->where('idUbicacionGeo', $idUbicacionGeo);
    }
}
