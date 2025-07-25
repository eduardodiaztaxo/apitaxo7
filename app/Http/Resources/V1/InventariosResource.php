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
                'id_inventario',
                'id_ciclo',
                'descripcion_bien',
                'etiqueta',
                'id_familia',
                'id_grupo',
                'descripcion_marca',
                'modelo',
                'serie',
                'estado',
                'responsable',
                'idUbicacionN2',
                'idUbicacionN3',
                'update_inv',
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
            ->where('idUbicacionN2', $activo->idUbicacionN2)
            ->select('descripcionUbicacion', 'codigoUbicacion', 'idAgenda')
            ->first();
        if (!$subEmplazamiento) {
            $subEmplazamiento = DB::table('ubicaciones_n3')
            ->where('idUbicacionN3', $activo->idUbicacionN3)
            ->select('descripcionUbicacion', 'codigoUbicacion', 'idAgenda')
            ->first();
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

         $foto = DB::table('inv_imagenes')
        ->where('etiqueta', $activo->etiqueta)
        ->orderByDesc('id_img') 
        ->first(['url_imagen']);

        $fotoUrl = $foto->url_imagen ?? asset('img/notavailable.jpg');

        $imagenes = DB::table('inv_imagenes')
        ->where('etiqueta', $activo->etiqueta)
        ->orderByDesc('id_img')
        ->pluck('url_imagen') // devuelve array de strings
        ->toArray();
    
        return [
            'id_inventario'        => $activo->id_inventario,
            'cicle_id'             => $activo->id_ciclo,
            'nombreActivo'         => $activo->descripcion_bien,
            'descripcionCategoria' => $descFamilia->descripcion_familia ?? 'Desconocida',
            'marca'                => $activo->descripcion_marca ?: 'Sin Registros',
            'modelo'               => $activo->modelo ?: 'Sin Registros',
            'serie'                => $activo->serie ?: 'Sin Registros',
            'estadoBien'           => $estadoBien,
            'descripcionGrupo'    => $descGrupo->descripcion_grupo ?? 'Sin Registros',
            'descripcionFamilia'  => $descFamilia->descripcion_familia ?? 'Sin Registros',
            'id_familia'           => $activo->id_familia,
            'etiqueta'             => $activo->etiqueta,
            'responsable'          => $activo->responsable ?? 'Sin Registros',
            'imagenes'             => $imagenes ?? [],
            'fotoUrl'              => $fotoUrl,
            'update_inv'           => $activo->update_inv,
            'foto4'                => $fotoUrl,
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