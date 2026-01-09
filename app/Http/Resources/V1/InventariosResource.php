<?php

namespace App\Http\Resources\V1;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\JsonResource;

class InventariosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        // Obtener el registro principal del inventario
        $activo = DB::table('inv_inventario')
            ->where('id_inventario', $this->id_inventario)
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
                'creado_por',
                'idUbicacionGeo',
                'codigoUbicacion_N1',
                'idUbicacionN2',
                'codigoUbicacion_N2',
                'idUbicacionN3',
                'codigoUbicacionN3',
                'update_inv',
                'id_img',
                'latitud',
                'longitud',
                'adjusted_lat',
                'adjusted_lng',
                'creado_el',
                'precision_geo',
                'calidad_geo'
            )
            ->first();

        if (!$activo) {
            return [];
        }

        $descFamilia = DB::table('dp_familias')
            ->where('id_familia', $activo->id_familia)
            ->value('descripcion_familia');

        $descGrupo = DB::table('dp_grupos')
            ->where('id_grupo', $activo->id_grupo)
            ->value('descripcion_grupo');

        $estadoBien = DB::table('ind_list_estado')
            ->where('idLista', $activo->estado)
            ->value('descripcion');

        if (!empty($activo->idUbicacionN3) && $activo->idUbicacionN3 != 0) {
            $subEmplazamiento = DB::table('ubicaciones_n3')
                ->where('idUbicacionN3', $activo->idUbicacionN3)
                ->where('idAgenda', $activo->idUbicacionGeo)
                ->select('idUbicacionN3', 'descripcionUbicacion', 'codigoUbicacion')
                ->first();
        } else {
            $subEmplazamiento = DB::table('ubicaciones_n2')
                ->where('idUbicacionN2', $activo->idUbicacionN2)
                ->where('idAgenda', $activo->idUbicacionGeo)
                ->select('idUbicacionN2', 'descripcionUbicacion', 'codigoUbicacion')
                ->first();
        }

        $codigoUbicacionN1 = $activo->codigoUbicacion_N1
            ?? $activo->codigoUbicacion_N2
            ?? $activo->codigoUbicacionN3
            ?? '';

        $emplazamiento = DB::table('ubicaciones_n1')
            ->where('codigoUbicacion', 'like', '%' . $codigoUbicacionN1 . '%')
            ->where('idAgenda', $activo->idUbicacionGeo)
            ->select('idUbicacionN1', 'descripcionUbicacion', 'codigoUbicacion')
            ->first();

        $idFinal = 0;
        $codigoUbicacionFinal = '';

        if ($subEmplazamiento) {
            $idFinal = $subEmplazamiento->idUbicacionN2 ?? $subEmplazamiento->idUbicacionN3 ?? 0;
            $codigoUbicacionFinal = $subEmplazamiento->codigoUbicacion ?? '';
        } elseif ($emplazamiento) {
            $idFinal = $emplazamiento->idUbicacionN1 ?? 0;
            $codigoUbicacionFinal = $emplazamiento->codigoUbicacion ?? '';
        }

        $direccion = DB::table('ubicaciones_geograficas')
            ->where('idUbicacionGeo', $activo->idUbicacionGeo)
            ->select('direccion', 'region', 'comuna', 'codigoCliente')
            ->first();
            

        if (!$direccion) {
            return [];
        }

        $region = DB::table('regiones')
            ->where('idRegion', $direccion->region)
            ->value('descripcion');

        $comuna = DB::table('comunas')
            ->where('idComuna', $direccion->comuna)
            ->value('descripcion');

        $imagenes = DB::table('inv_imagenes')
            ->where('etiqueta', $activo->etiqueta)
            ->orderByDesc('id_img')
            ->pluck('url_imagen')
            ->toArray();

        $fotoUrl = $imagenes[0] ?? asset('img/notavailable.jpg');

        return [
            'id_inventario'        => $activo->id_inventario,
            'cicle_id'             => $activo->id_ciclo,
            'nombreActivo'         => $activo->descripcion_bien,
            'descripcionCategoria' => $descFamilia ?? 'Desconocida',
            'descripcion_familia'  => $descFamilia ?? 'Desconocida',
            'descripcionGrupo'     => $descGrupo ?? 'Sin Registros',
            'descripcion_grupo'    => $descGrupo ?? 'Sin Registros',
            'marca'                => $activo->descripcion_marca ?: 'Sin Registros',
            'modelo'               => $activo->modelo ?: 'Sin Registros',
            'serie'                => $activo->serie ?: 'Sin Registros',
            'estadoBien'           => $estadoBien ?? 'No definido',
            'etiqueta'             => $activo->etiqueta,
            'responsable'          => $activo->responsable ?? 'Sin Registros',
            'creado_por'           => $activo->creado_por ?? 'Sin Registros',
            'creado_el'            => $activo->creado_el ? date('d/m/Y H:i:s', strtotime($activo->creado_el)) : null,
            'update_inv'           => $activo->update_inv,
            'latitud'              => $activo->latitud,
            'longitud'             => $activo->longitud,
            'adjusted_lat'         => $activo->adjusted_lat,
            'adjusted_lng'         => $activo->adjusted_lng,
            'accuracyStr'          => $activo->precision_geo,
            'calidadGeo'           => $activo->calidad_geo,
            'fotoUrl'              => $fotoUrl,
            'codigoCliente'       => $direccion->codigoCliente,
            'imagenes'             => $imagenes ?? [],

            'emplazamiento' => [
                'id'              => $idFinal,
                'nombre'          => $emplazamiento->descripcionUbicacion ?? '',
                'codigoUbicacion' => $codigoUbicacionFinal,
                'idAgenda'        => $activo->idUbicacionGeo,
                'zone_address'    => [
                    'descripcionUbicacion' => $subEmplazamiento->descripcionUbicacion ?? '',
                    'codigoUbicacion'      => $subEmplazamiento->codigoUbicacion ?? '',
                ],
            ],

            'ubicacion' => [
                'idUbicacionGeo' => $activo->idUbicacionGeo,
                'direccion'      => $direccion->direccion ?? 'No disponible',
                'region'         => $region ?? 'No disponible',
                'comuna'         => $comuna ?? 'No disponible',
            ],
        ];
    }
}
