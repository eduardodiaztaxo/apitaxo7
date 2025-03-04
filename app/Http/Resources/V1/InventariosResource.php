<?php

namespace App\Http\Resources\V1;
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
        $activosInventario = DB::table('inv_inventario')
            ->where('etiqueta', $this->etiqueta)
            ->select(
                'descripcion_bien',
                'etiqueta',
                'id_familia',
                'descripcion_marca',
                'modelo',
                'serie',
                'estado',
                'responsable',
                'codigoUbicacion',
                'id_img'
            )
            ->get();
    
        $activo = $activosInventario->first();

        if (!$activo) {
            return [];
        }
    
        $descFamilia = DB::table('dp_familias')
            ->where('id_familia', $activo->id_familia)
            ->select('descripcion_familia')
            ->first();
    
        $img = DB::table('inv_imagenes')
            ->where('id_img', $activo->id_img)
            ->select('url_imagen')
            ->first();
    
            $estadoBien = DB::table('indices_listas_13')
            ->where('idLista', $activo->estado)
            ->value('descripcion');

        $subEmplazamiento = DB::table('ubicaciones_n2')
            ->where('idUbicacionN2', $activo->codigoUbicacion)
            ->select('descripcionUbicacion', 'codigoUbicacion', 'idAgenda')
            ->first();
        if (!$subEmplazamiento) {
            return [];
        }
    
        $codigoUbicacionN1 = substr($subEmplazamiento->codigoUbicacion, 0, 2);
        $emplazamiento = DB::table('ubicaciones_n1')
            ->where('codigoUbicacion', $codigoUbicacionN1)
            ->select('descripcionUbicacion')
            ->first();

        $direccion = DB::table('ubicaciones_geograficas')
            ->where('idUbicacionGeo', $subEmplazamiento->idAgenda)
            ->select('direccion', 'region', 'comuna')
            ->first();
        if (!$direccion) {
            return [];
        }
    
        $region = DB::table('regiones')
            ->where('idRegion', $direccion->region)
            ->select('descripcion')
            ->first();
    
        $comuna = DB::table('comunas')
            ->where('idComuna', $direccion->comuna)
            ->select('descripcion')
            ->first();
    
        $firstImageUrl = "https://api.taxochile.cl/img/notavailable.jpg";
    
        if (!empty($img->url_imagen)) {
            $folderPath = str_replace('http://apitaxo7.cl/storage/', '', $img->url_imagen);
            $localFolderPath = public_path('storage/' . $folderPath);
    
            if (is_dir($localFolderPath)) {
                $files = scandir($localFolderPath);
    
                $imageFiles = array_filter($files, function ($file) {
                    return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']);
                });
    
                if (!empty($imageFiles)) {
                    $firstImageUrl = asset('storage/' . $folderPath . '/' . reset($imageFiles));
                }
            }
        }
    
        return [
            'nombreActivo'         => $activo->descripcion_bien,
            'descripcionCategoria' => $descFamilia->descripcion_familia ?? 'Desconocida',
            'marca'                => $activo->descripcion_marca ?: 'Sin Registros',
            'modelo'               => $activo->modelo ?: 'Sin Registros',
            'serie'                => $activo->serie ?: 'Sin Registros',
            'estadoBien'           => $estadoBien,
            'etiqueta'             => $activo->etiqueta,
            'responsable'          => $activo->responsable ?? 'Sin Registros',
            'fotoUrl'              => $firstImageUrl,
            'foto4'                => $firstImageUrl,
            'emplazamiento'        => [
                'nombre' => $subEmplazamiento->descripcionUbicacion ?? 'No disponible',
                'zone_address' => [
                    'descripcionUbicacion' => $emplazamiento->descripcionUbicacion ?? 'No disponible',
                ],
            ],
           
            'ubicacion' => [
                'direccion' => $direccion->direccion ?? 'No disponible',
                'region'    => $region->descripcion ?? 'No disponible',
                'comuna'    => $comuna->descripcion ?? 'No disponible',
            ],
        ];
    }
}