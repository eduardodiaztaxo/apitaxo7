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
                'id_grupo',
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


        $descGrupo = DB::table('dp_grupos')
            ->where('id_grupo', $activo->id_grupo)
            ->select('descripcion_grupo')
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
    
        $foto = DB::table('inv_inventario')
            ->leftJoin('categoria_n3', 'inv_inventario.id_familia', '=', 'categoria_n3.id_familia')
            ->leftJoin('dp_familias', 'inv_inventario.id_familia', '=', 'dp_familias.id_familia')
            ->leftJoin('inv_imagenes', 'inv_inventario.id_img', '=', 'inv_imagenes.id_img')
            ->where('inv_inventario.codigoUbicacion', 31116)
            ->where('inv_inventario.id_ciclo', 25)
            ->first(['inv_imagenes.url_imagen']);

            if ($foto == null) {
                return asset('img/notavailable.jpg');
            }

         
        // $firstImageUrl = "https://api.taxochile.cl/img/notavailable.jpg";
    
        // if (!empty($img->url_imagen)) {
        //     $folderPath = str_replace('http://apitaxo7.cl/storage/', '', $img->url_imagen);
        //     $localFolderPath = public_path('storage/' . $folderPath);
    
        //     if (is_dir($localFolderPath)) {
        //         $files = scandir($localFolderPath);
    
        //         $imageFiles = array_filter($files, function ($file) {
        //             return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']);
        //         });
    
        //         if (!empty($imageFiles)) {
        //             $firstImageUrl = asset('storage/' . $folderPath . '/' . reset($imageFiles));
        //         }
        //     }
        // }
    
        return [
            'nombreActivo'         => $activo->descripcion_bien,
            'descripcionCategoria' => $descFamilia->descripcion_familia ?? 'Desconocida',
            'marca'                => $activo->descripcion_marca ?: 'Sin Registros',
            'modelo'               => $activo->modelo ?: 'Sin Registros',
            'serie'                => $activo->serie ?: 'Sin Registros',
            'estadoBien'           => $estadoBien,
            'descripcionGrupo'    => $descGrupo->descripcion_grupo ?? 'Sin Registros',
            'descripcionFamilia'  => $descFamilia->descripcion_familia ?? 'Sin Registros',
            'etiqueta'             => $activo->etiqueta,
            'responsable'          => $activo->responsable ?? 'Sin Registros',
            'fotoUrl'              => $foto->url_imagen ?? asset('img/notavailable.jpg'),
            'foto4'                => $foto->url_imagen ?? asset('img/notavailable.jpg'),
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