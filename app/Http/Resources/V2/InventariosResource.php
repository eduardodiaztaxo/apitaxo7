<?php

namespace App\Http\Resources\V2;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\JsonResource;

class InventariosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {


        $descFamilia = $this->familia->descripcion_familia;



        $descGrupo = $this->grupo->descripcion_grupo;

        $estadoObj = $this->estadoBien;

        $estadoBien = $estadoObj ? $estadoObj->descripcion : '';


        $subEmplazamiento =  $this->emplazamientoN2()->first();

        if (!$subEmplazamiento) {
            $subEmplazamiento =  $this->emplazamientoN3()->first();
        }


        $emplazamiento = $this->zonaN1()->first();

        $direccion = $this->addressPunto()->first();

        if (!$direccion) {
            return [];
        }

        $regionObj = $direccion->region()->first();

        $region = $regionObj ? $regionObj->descripcion : 'No disponible';

        $comunaObj = $direccion->comuna()->first();

        $comuna = $comunaObj ? $comunaObj->descripcion : 'No disponible';

        $fotos = $this->imagenes()->get();

        $foto = $fotos->first();

        $fotoUrl = $foto->url_imagen ?? asset('img/notavailable.jpg');

        $imagenes = $fotos
            ->sortByDesc('id_img')
            ->pluck('url_imagen') // devuelve array de strings
            ->toArray();

        return [
            'id_inventario'        => $this->id_inventario,
            'cicle_id'             => $this->id_ciclo,
            'nombreActivo'         => $this->descripcion_bien,
            'descripcionCategoria' => $descFamilia->descripcion_familia ?? 'Desconocida',
            'marca'                => $this->descripcion_marca ?: 'Sin Registros',
            'modelo'               => $this->modelo ?: 'Sin Registros',
            'serie'                => $this->serie ?: 'Sin Registros',
            'estadoBien'           => $estadoBien,
            'descripcionGrupo'    => $descGrupo ?? 'Sin Registros',
            'descripcionFamilia'  => $descFamilia ?? 'Sin Registros',
            'id_familia'           => $this->id_familia,
            'etiqueta'             => $this->etiqueta,
            'responsable'          => $this->responsable ?? 'Sin Registros',
            'imagenes'             => $imagenes ?? [],
            'fotoUrl'              => $fotoUrl,
            'update_inv'           => $this->update_inv,
            'foto4'                => $fotoUrl,
            'emplazamiento'        => [
                'nombre' => $subEmplazamiento->descripcionUbicacion ?? 'No disponible',
                'zone_address' => [
                    'descripcionUbicacion' => $emplazamiento->descripcionUbicacion ?? 'No disponible',
                ],
            ],

            'ubicacion' => [
                'direccion' => $direccion->direccion ?? 'No disponible',
                'region'    => $region,
                'comuna'    => $comuna,
            ],
        ];
    }
}
