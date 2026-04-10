<?php

namespace App\Http\Resources\V2\Auditoria;

use App\Models\CrudActivo;
use App\Models\Inv_ciclos_categorias;
use App\Models\InvCiclo;
use App\Services\ProyectoUsuarioService;
use Illuminate\Http\Resources\Json\JsonResource;

class EmplazamientoNnResource extends JsonResource
{

    /** @var App\Models\InvCiclo */
    protected $cycle;


    protected $subnivel = 1;



    public function __construct($resource, InvCiclo $cycle, int $subnivel = 1)
    {
        if ($cycle->idTipoCiclo !== 2) {
            throw new \InvalidArgumentException("El ciclo debe ser de tipo auditoría (idTipoCiclo = 2)");
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


        $familias = Inv_ciclos_categorias::where('idCiclo', $this->cycle->idCiclo)->get()->pluck('id_familia')->toArray();


        $num_activos = $this->activos_with_cats_with_child_levels_by_cycle($this->cycle->idCiclo)->count();

        $num_activos_N1 = null;
        $num_activos_N2 = null;
        $num_activos_N3 = null;




        if ($this->subnivel === 1) {

            $num_activos_N1 = CrudActivo::where('ubicacionOrganicaN1', $this->codigoUbicacion)
                ->where('ubicacionGeografica', $this->idAgenda)
                ->where('ubicacionOrganicaN2', '<', 2)
                ->whereIn('id_familia', $familias)
                ->count();
        }

        if ($this->subnivel <= 2) {

            $num_activos_N2 = CrudActivo::where('ubicacionOrganicaN2', 'LIKE', $this->codigoUbicacion . '%')
                ->where('ubicacionGeografica', $this->idAgenda)
                ->where('ubicacionOrganicaN3', '<', 2)
                ->whereIn('id_familia', $familias)
                ->count();
        }

        if ($this->subnivel <= 3) {

            $num_activos_N3 = CrudActivo::where('ubicacionOrganicaN3', 'LIKE',  $this->codigoUbicacion . '%')
                ->where('ubicacionGeografica', $this->idAgenda)
                ->where('ubicacionOrganicaN4', '<', 2)
                ->whereIn('id_familia', $familias)
                ->count();
        }

        $emplazamiento = [
            'id' => $this->idUbicacionN1,
            'codigo' => $this->codigo,
            'codigoUbicacion' => $this->codigoUbicacion,
            'nombre' => $this->descripcionUbicacion,
            'idAgenda' => $this->idAgenda,
            'idUbicacionN1' => $this->idUbicacionN1,
            'detalle' => 'Detalle Emplazamiento (N' . $this->subnivel . ')',
            'num_nivel' => 'N' . $this->subnivel,
            'next_level' => 'N' . ($this->subnivel + 1),
            'newApp' => $this->newApp,
            'modo' => $this->modo,
            'habilitadoNivel3' => 1,
            'num_activos' => $num_activos,
            'num_activos_inv' => $num_activos,
            'num_activos_N1' => $num_activos_N1,
            'num_activos_N2' => $num_activos_N2,
            'num_activos_N3' => $num_activos_N3,
            'num_activos_audit' => 0,
            'num_activos_cats_by_cycle' => 0,
            'ciclo_auditoria' => $this->ciclo_auditoria,
            'num_categorias' => $this->activos()->select('categoriaN3')->groupBy('categoriaN3')->get()->count(),
            'id_ciclo' => $this->cycle->idCiclo,

        ];





        return $emplazamiento;
    }
}
