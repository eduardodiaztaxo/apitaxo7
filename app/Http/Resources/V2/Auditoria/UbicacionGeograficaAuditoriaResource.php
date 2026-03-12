<?php

namespace App\Http\Resources\V2\Auditoria;

use App\Models\CiclosPunto;
use App\Models\InvCiclo;
use App\Models\InvConteoRegistro;
use App\Models\PuntosEstados;
use Illuminate\Http\Resources\Json\JsonResource;

class UbicacionGeograficaAuditoriaResource extends JsonResource
{

    /** @var App\Models\InvCiclo */
    protected $cycle;

    public function __construct($resource, InvCiclo $cycle)
    {
        if ($cycle->idTipoCiclo !== 2) {
            throw new \InvalidArgumentException("El ciclo debe ser de tipo auditoría (idTipoCiclo = 2)");
        }

        parent::__construct($resource);
        $this->cycle = $cycle;
    }


    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        //Buscar esta relación
        $id_estado = $this->ciclos_puntos->where('idCiclo', $this->cycle->idCiclo)->pluck('id_estado')->first();



        $descripcionEstado = null;

        if ($id_estado) {
            $estadoDirecciones = PuntosEstados::where('id_estado', $id_estado)->first();
            if ($estadoDirecciones) {
                $descripcionEstado = $estadoDirecciones->descripcion;
            }
        }






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
            'auditoria_general' => 1,
            'newApp'        => $this->newApp,
            'modo'          => $this->modo,
            'zonas_punto'   => [],
            'num_activos'   => $this->activos_without_join()->count(),
            'num_activos_cats_by_cycle' => 0,
            'num_activos_inv_cats_by_cycle' => 0,
            'num_cats_by_cycle' => 0
        ];



        $detalle = [
            'detalle' => 'Detalle General',
        ];

        $address['detalle'] = $detalle['detalle'];



        //número de activos a auditar según el ciclo
        $address['num_activos_cats_by_cycle'] = $this->activos_with_cats_by_cycle($this->cycle->idCiclo)->count();
        $address['num_activos_inv_cats_by_cycle'] = 0;

        /** Geo Adjust */
        $address['num_activos_geo_adjust'] = 0;

        $coll = $this->cats_by_cycle($this->cycle->idCiclo);

        $address['num_cats_by_cycle']       = $coll->pluck('categoriaN1')->unique()->count();
        $address['num_subcats_n2_by_cycle'] = $coll->pluck('categoriaN2')->unique()->count();
        $address['num_subcats_n3_by_cycle'] = $coll->pluck('categoriaN3')->unique()->count();


        $address['num_activos_audit'] = InvConteoRegistro::where('ciclo_id', '=', $this->cycle->idCiclo)
            ->where('punto_id', '=', $this->idUbicacionGeo)
            ->where('status', '=', 1)
            ->whereIn('audit_status', [1, 3])
            ->count();

        $address['num_activos_audit_coincidentes'] = InvConteoRegistro::where('ciclo_id', '=', $this->cycle->idCiclo)
            ->where('punto_id', '=', $this->idUbicacionGeo)
            ->where('status', '=', 1)
            ->where('audit_status', '=', 1)
            ->count();

        $address['num_activos_audit_sobrantes'] = InvConteoRegistro::where('ciclo_id', '=', $this->cycle->idCiclo)
            ->where('punto_id', '=', $this->idUbicacionGeo)
            ->where('status', '=', 1)
            ->where('audit_status', '=', 3)
            ->count();




        return $address;
    }
}
