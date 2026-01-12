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
                'codigoUbicacionN4',
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

        // Determinar el nivel de asignación del bien
        $codigoN1 = !empty($activo->codigoUbicacion_N1) && $activo->codigoUbicacion_N1 != 0 && $activo->codigoUbicacion_N1 != '0' ? $activo->codigoUbicacion_N1 : null;
        $codigoN2 = !empty($activo->codigoUbicacion_N2) && $activo->codigoUbicacion_N2 != 0 && $activo->codigoUbicacion_N2 != '0' ? $activo->codigoUbicacion_N2 : null;
        $codigoN3 = !empty($activo->codigoUbicacionN3) && $activo->codigoUbicacionN3 != 0 && $activo->codigoUbicacionN3 != '0' ? $activo->codigoUbicacionN3 : null;
        $codigoN4 = !empty($activo->codigoUbicacionN4) && $activo->codigoUbicacionN4 != 0 && $activo->codigoUbicacionN4 != '0' ? $activo->codigoUbicacionN4 : null;

        // Determinar el nivel de asignación
        $bien_asignado = 'Sin asignación';
        if ($codigoN4) {
            $bien_asignado = 'Asignado en Nivel 4';
        } elseif ($codigoN3) {
            $bien_asignado = 'Asignado en Nivel 3';
        } elseif ($codigoN2) {
            $bien_asignado = 'Asignado en Nivel 2';
        } elseif ($codigoN1) {
            $bien_asignado = 'Asignado en Nivel 1';
        } elseif (!empty($activo->idUbicacionGeo)) {
            $bien_asignado = 'Asignado solo en Dirección';
        }

        // Verificar si hay emplazamiento asignado
        // Si todos los campos de emplazamiento están en 0 o vacíos, no hay emplazamiento
        $hasEmplazamiento = $codigoN1 || $codigoN2 || $codigoN3 || $codigoN4
            || (!empty($activo->idUbicacionN2) && $activo->idUbicacionN2 != 0 && $activo->idUbicacionN2 != '0')
            || (!empty($activo->idUbicacionN3) && $activo->idUbicacionN3 != 0 && $activo->idUbicacionN3 != '0');

        $subEmplazamiento = null;
        $emplazamiento = null;
        $idFinal = null;
        $codigoUbicacionFinal = '';
        $nombreEmplazamiento = '';
        $descripcionZoneAddress = '';
        $codigoZoneAddress = '';

        if ($hasEmplazamiento) {
            // Solo buscar emplazamientos si hay valores asignados
            if (!empty($activo->idUbicacionN3) && $activo->idUbicacionN3 != 0 && $activo->idUbicacionN3 != '0') {
                $subEmplazamiento = DB::table('ubicaciones_n3')
                    ->where('idUbicacionN3', $activo->idUbicacionN3)
                    ->where('idAgenda', $activo->idUbicacionGeo)
                    ->select('idUbicacionN3', 'descripcionUbicacion', 'codigoUbicacion')
                    ->first();
            } elseif (!empty($activo->idUbicacionN2) && $activo->idUbicacionN2 != 0 && $activo->idUbicacionN2 != '0') {
                $subEmplazamiento = DB::table('ubicaciones_n2')
                    ->where('idUbicacionN2', $activo->idUbicacionN2)
                    ->where('idAgenda', $activo->idUbicacionGeo)
                    ->select('idUbicacionN2', 'descripcionUbicacion', 'codigoUbicacion')
                    ->first();
            }

            $codigoUbicacionN1 = '';
            if ($codigoN1) {
                $codigoUbicacionN1 = $codigoN1;
            } elseif ($codigoN2) {
                $codigoUbicacionN1 = $codigoN2;
            } elseif ($codigoN3) {
                $codigoUbicacionN1 = $codigoN3;
            }

            if (!empty($codigoUbicacionN1)) {
                $emplazamiento = DB::table('ubicaciones_n1')
                    ->where('codigoUbicacion', 'like', '%' . $codigoUbicacionN1 . '%')
                    ->where('idAgenda', $activo->idUbicacionGeo)
                    ->select('idUbicacionN1', 'descripcionUbicacion', 'codigoUbicacion')
                    ->first();
            }

            if ($subEmplazamiento) {
                $idFinal = $subEmplazamiento->idUbicacionN2 ?? $subEmplazamiento->idUbicacionN3 ?? null;
                $codigoUbicacionFinal = $subEmplazamiento->codigoUbicacion ?? '';
                $descripcionZoneAddress = $subEmplazamiento->descripcionUbicacion ?? '';
                $codigoZoneAddress = $subEmplazamiento->codigoUbicacion ?? '';
                $nombreEmplazamiento = $subEmplazamiento->descripcionUbicacion ?? '';
            } elseif ($emplazamiento) {
                $idFinal = $emplazamiento->idUbicacionN1 ?? null;
                $codigoUbicacionFinal = $emplazamiento->codigoUbicacion ?? '';
                $nombreEmplazamiento = $emplazamiento->descripcionUbicacion ?? '';
            }
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
            'bien_asignado'        => $bien_asignado,

            'emplazamiento' => [
                'id'              => $idFinal,
                'nombre'          => $hasEmplazamiento ? ($nombreEmplazamiento ?: '') : 'Sin emplazamiento asignado',
                'codigoUbicacion' => $hasEmplazamiento ? $codigoUbicacionFinal : '',
                'idAgenda'        => $hasEmplazamiento ? ($activo->idUbicacionGeo ?? null) : null,
                'zone_address'    => [
                    'descripcionUbicacion' => $hasEmplazamiento ? $descripcionZoneAddress : '',
                    'codigoUbicacion'      => $hasEmplazamiento ? $codigoZoneAddress : '',
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
