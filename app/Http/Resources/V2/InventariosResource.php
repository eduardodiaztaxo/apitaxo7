<?php

namespace App\Http\Resources\V2;

use App\Services\ImageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\ActivoFinderService;
use App\Services\ProyectoUsuarioService;

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
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $descFamilia = $this->familia?->descripcion_familia ?? 'Sin familia';

        $descGrupo = $this->grupo->descripcion_grupo ?? 'Sin grupo';

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

        $fotos = $this->imagenes()->where('id_proyecto', $id_proyecto)->get();

        $imagenes = $fotos
            ->sortByDesc('id_img')
            ->map(function ($foto) {
                return [
                    'original_url' => ImageService::buildOriginalUrl($foto->url_imagen, $foto->url_picture, $foto->picture),
                    'thumb_url' => ImageService::getInventoryThumbnailUrlByPicture($foto->id_img, $foto->etiqueta, $foto->picture),
                    'picture' => $foto->picture,
                ];
            })
            ->values()
            ->toArray();

        $originalUrls = array_values(array_unique(array_filter(array_map(fn ($foto) => $foto['original_url'] ?? null, $imagenes))));
        $thumbUrls = array_values(array_unique(array_filter(array_map(fn ($foto) => $foto['thumb_url'] ?? null, $imagenes))));
        $primaryThumbUrl = $thumbUrls[0] ?? null;

        $fotoUrl = $originalUrls[0] ?? asset('img/notavailable.jpg');

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
            'imagenes'             => $originalUrls ?? [],
            'thumbnails'           => $thumbUrls,
            'fotoUrl'              => $fotoUrl,
            'originalUrl'          => $originalUrls[0] ?? $fotoUrl,
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
