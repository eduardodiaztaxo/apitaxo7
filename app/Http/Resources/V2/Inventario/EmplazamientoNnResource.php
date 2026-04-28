<?php

namespace App\Http\Resources\V2\Inventario;

use App\Http\Resources\V1\UbicacionGeograficaResource;
use App\Http\Resources\V1\ZonaPuntoResource;
use App\Models\InvCiclo;
use App\Models\Inventario;
use App\Services\ProyectoUsuarioService;
use Illuminate\Http\Resources\Json\JsonResource;

class EmplazamientoNnResource extends JsonResource
{



    /** @var App\Models\InvCiclo */
    protected $cycle;


    protected $subnivel = 1;



    public function __construct($resource, InvCiclo $cycle, int $subnivel = 1)
    {
        if ($cycle->idTipoCiclo !== 1) {
            throw new \InvalidArgumentException("El ciclo debe ser de tipo auditoría (idTipoCiclo = 1)");
        }

        parent::__construct($resource);
        $this->cycle = $cycle;
        $this->subnivel = $subnivel;
    }

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
            ->where('inv_inventario.id_ciclo', $this->cycle_id)
            ->count();


        $num_activos_inv_recursive = $this->inv_activos_with_child_levels()
            ->where('inv_inventario.id_proyecto', $id_proyecto)
            ->where('inv_inventario.id_ciclo', $this->cycle_id)
            ->count();

        $num_activos_N1 = null;
        $num_activos_N2 = null;
        $num_activos_N3 = null;
        $num_activos_N4 = null;
        $num_activos_N5 = null;
        $num_activos_N6 = null;

        if ($this->subnivel === 1) {

            $num_activos_N1 = Inventario::where('inv_inventario.codigoUbicacion_N1', 'LIKE', $this->codigoUbicacion . '%')
                ->where('inv_inventario.idUbicacionGeo', $this->idAgenda)
                ->where('inv_inventario.id_ciclo', $this->cycle_id)
                ->where('inv_inventario.id_proyecto', $id_proyecto)
                ->where('codigoUbicacion_N2', '<', 2)
                ->count();
        }

        if ($this->subnivel <= 2) {
            $num_activos_N2 = Inventario::where('inv_inventario.codigoUbicacion_N2', 'LIKE', $this->codigoUbicacion . '%')
                ->where('inv_inventario.idUbicacionGeo', $this->idAgenda)
                ->where('inv_inventario.id_ciclo', $this->cycle_id)
                ->where('inv_inventario.id_proyecto', $id_proyecto)
                ->where('codigoUbicacionN3', '<', 2)
                ->count();
        }


        if ($this->subnivel <= 3) {
            $num_activos_N3 = Inventario::where('inv_inventario.codigoUbicacionN3', 'LIKE', $this->codigoUbicacion . '%')
                ->where('inv_inventario.idUbicacionGeo', $this->idAgenda)
                ->where('inv_inventario.id_ciclo', $this->cycle_id)
                ->where('inv_inventario.id_proyecto', $id_proyecto)
                ->where('codigoUbicacionN4', '<', 2)
                ->count();
        }


        if ($this->subnivel <= 4) {

            $num_activos_N4 = Inventario::where('inv_inventario.codigoUbicacionN4', 'LIKE',  $this->codigoUbicacion . '%')
                ->where('inv_inventario.idUbicacionGeo', $this->idAgenda)
                ->where('inv_inventario.id_ciclo', $this->cycle_id)
                ->where('inv_inventario.id_proyecto', $id_proyecto)
                ->where('codigoUbicacionN5', '<', 2)
                ->count();
        }

        if ($this->subnivel <= 5) {

            $num_activos_N5 = Inventario::where('inv_inventario.codigoUbicacionN5', 'LIKE',  $this->codigoUbicacion . '%')
                ->where('inv_inventario.idUbicacionGeo', $this->idAgenda)
                ->where('inv_inventario.id_ciclo', $this->cycle_id)
                ->where('inv_inventario.id_proyecto', $id_proyecto)
                ->where('codigoUbicacionN5', '<', 2)
                ->count();
        }

        if ($this->subnivel <= 6) {

            $num_activos_N6 = Inventario::where('inv_inventario.codigoUbicacionN6', 'LIKE',  $this->codigoUbicacion . '%')
                ->where('inv_inventario.idUbicacionGeo', $this->idAgenda)
                ->where('inv_inventario.id_ciclo', $this->cycle_id)
                ->where('inv_inventario.id_proyecto', $id_proyecto)
                ->count();
        }

        $idPropiedad = 'idUbicacionN' . $this->subnivel;
        $idUbicacionNn = $this->{$idPropiedad};

        $emplazamiento = [
            'id' => $idUbicacionNn,
            'codigo' => $this->codigo,
            'codigoUbicacion' => $this->codigoUbicacion,
            'nombre' => $this->descripcionUbicacion,
            'idAgenda' => $this->idAgenda,
            $idPropiedad => $idUbicacionNn,
            'detalle' => 'Detalle Emplazamiento (N' . $this->subnivel . ')',
            'num_nivel' => 'N' . $this->subnivel,
            'next_level' => $this->subnivel < 6 ? 'N' . ($this->subnivel + 1) : '',
            'newApp' => $this->newApp,
            'modo' => $this->modo,
            'habilitadoNivel' . ($this->subnivel + 1) => $this->subnivel < 6 ? 1 : 0,
            'num_activos' => 0,
            'num_activos_audit' => 0,
            'num_activos_inv' => $num_activos_inv,
            'num_activos_inv_recursive' => $num_activos_inv_recursive,
            'num_activos_N1' => $num_activos_N1,
            'num_activos_N2' => $num_activos_N2,
            'num_activos_N3' => $num_activos_N3,
            'num_activos_N4' => $num_activos_N4,
            'num_activos_N5' => $num_activos_N5,
            'num_activos_N6' => $num_activos_N6,
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
