<?php

namespace App\Http\Resources\V2;

use App\Http\Resources\V1\UbicacionGeograficaResource;
use App\Models\UbicacionGeografica;
use Illuminate\Http\Resources\Json\JsonResource;

class EmplazamientoGenericoResource extends JsonResource
{
    protected $nivel;

    /**
     * Constructor
     *
     * @param mixed $resource El modelo del Emplazamiento
     * @param string $nivel El nivel jerárquico (N1, N2, N3, N4, N5, etc)
     */
    public function __construct($resource, $nivel = 'N2')
    {
        parent::__construct($resource);
        $this->nivel = $nivel;
    }

    /**
     * Crear una instancia con un nivel específico
     *
     * @param mixed $resource El modelo del Emplazamiento
     * @param string $nivel El nivel jerárquico (N1, N2, N3, etc)
     * @return static
     */
    public static function makeWithNivel($resource, $nivel = 'N2')
    {
        $instance = new static($resource, $nivel);
        return $instance;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $emplazamiento = [
            'id' => $this->getIdByNivel(),
            'codigo' => $this->getPropertyValue('codigo'),
            'codigoUbicacion' => $this->getPropertyValue('codigoUbicacion'),
            'nombre' => $this->getPropertyValue('descripcionUbicacion'),
            'idAgenda' => $this->getPropertyValue('idAgenda'),
            $this->getIdFieldByNivel() => $this->getIdFieldValue(),
            'detalle' => "Detalle Emplazamiento ({$this->nivel})",
            'num_nivel' => $this->nivel,
            'next_level' => $this->getNextLevel(),
            'newApp' => $this->getPropertyValue('newApp'),
            'modo' => $this->getPropertyValue('modo'),
            'habilitadoNivel3' => $this->getHabilitadoNivel3(),
            'id_ciclo' => $this->getPropertyValue('cycle_id'),
        ];

        // Agregar ciclo_auditoria solo para N2
        if ($this->nivel === 'N2') {
            $emplazamiento['ciclo_auditoria'] = $this->getPropertyValue('ciclo_auditoria');
        }

        if (isset($this->requirePunto) && $this->requirePunto) {
            $emplazamiento['ubicacionPunto'] = UbicacionGeograficaResource::make(UbicacionGeografica::find($this->getPropertyValue('idAgenda')));
        }

        return $emplazamiento;
    }

    /**
     * Obtén el ID según el nivel
     */
    private function getIdByNivel()
    {
        $idField = $this->getIdFieldByNivel();
        return $this->getPropertyValue($idField);
    }

    /**
     * Obtén el nombre del campo de ID según el nivel
     */
    private function getIdFieldByNivel()
    {
        return 'idUbicacionN' . substr($this->nivel, 1);
    }

    /**
     * Obtén el valor del campo de ID según el nivel
     */
    private function getIdFieldValue()
    {
        $idField = $this->getIdFieldByNivel();
        return $this->getPropertyValue($idField);
    }

    /**
     * Obtén una propiedad del recurso de forma segura
     */
    private function getPropertyValue($property)
    {
        if (isset($this->{$property})) {
            return $this->{$property};
        }

        // Si es un objeto stdClass, intentar acceder directamente
        if (is_object($this->resource) && property_exists($this->resource, $property)) {
            return $this->resource->{$property};
        }

        return null;
    }

    /**
     * Obtén el nivel siguiente
     */
    private function getNextLevel()
    {
        return match ($this->nivel) {
            'N1' => 'N2',
            'N2' => 'N3',
            'N3' => 'N4',
            'N4' => 'N5',
            'N5' => 'N6',
            default => 'N3',
        };
    }

    /**
     * Obtén el valor de habilitadoNivel3 según el nivel
     */
    private function getHabilitadoNivel3()
    {
        return match ($this->nivel) {
            'N1', 'N2' => 1,
            'N3' => 0,
            default => 1,
        };
    }
}
