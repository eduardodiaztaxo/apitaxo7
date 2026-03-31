<?php

namespace App\Services\Dump\Tables;

use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Controllers\Api\V1\InventariosOfflineController;

use PDO;


class InventarioDumpService
{
    protected PDO $pdo;

    protected $cycle = 0;

    public function __construct(PDO $pdo, int $cycle = 0)
    {
        $this->pdo = $pdo;

        $this->cycle = $cycle;
    }
    /**
     * Crea la tabla bienesInventario si no existe.
     */
    public function runFromController(): void
    {

        $this->createTable();

        $request = new \Illuminate\Http\Request();
        $request->setMethod('GET');

        $datsdActivosCtrl = new InventariosOfflineController();

        $response = $datsdActivosCtrl->inventarioPorCicloOfflineInventario($this->cycle);

        $jsonContent = $response->getContent();

        // Decodificar el JSON
        $data = json_decode($jsonContent);

        if (isset($data->status) && $data->status !== 'OK') {
            return;
        }

        // Si los datos están envueltos en "data", extraerlos
        $inventario = isset($data->data) ? $data->data : $data;

        $this->insert($inventario);
    }

    public function createTable(): void
    {

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS inventario (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                id_inventario INTEGER,
                id_grupo INTEGER NOT NULL,
                id_familia INTEGER NOT NULL,
                descripcion_bien TEXT,
                id_bien INTEGER,
                descripcion_marca TEXT ,
                id_marca INTEGER,
                idForma INTEGER,
                idMaterial INTEGER,
                etiqueta TEXT,
                etiqueta_padre,
                idResponsable INTEGER DEFAULT 0,
                modelo TEXT,
                serie TEXT,
                capacidad TEXT,
                estado INTEGER,
                color INTEGER,
                tipo_trabajo INTEGER,
                carga_trabajo INTEGER,
                estado_operacional INTEGER,
                estado_conservacion INTEGER,
                condicion_Ambiental INTEGER,
                cantidad_img INTEGER NOT NULL,
                id_img TEXT NOT NULL,
                id_ciclo INTEGER NOT NULL,
                idUbicacionGeo INTEGER NOT NULL,
                codigoUbicacion_N1 TEXT,
                idUbicacionN2 TEXT NOT NULL,
                codigoUbicacion_N2 TEXT,
                codigoUbicacionN3 TEXT,
                idUbicacionN3 TEXT,
                descripcionTipo TEXT,
                observacion TEXT,
                latitud TEXT,
                longitud TEXT,
                offline INTEGER,
                update_inv INTEGER,
                creado_por TEXT,
                crud_activo_estado INTEGER,
                -- edualejandro
                eficiencia TEXT,
                texto_abierto_1 TEXT,
                texto_abierto_2 TEXT,
                texto_abierto_3 TEXT,
                texto_abierto_4 TEXT,
                texto_abierto_5 TEXT,
                texto_abierto_6 TEXT,
                texto_abierto_7 TEXT,
                texto_abierto_8 TEXT,
                texto_abierto_9 TEXT,
                texto_abierto_10 TEXT,
                texto_abierto_11 TEXT,
                texto_abierto_12 TEXT,
                texto_abierto_13 TEXT,
                texto_abierto_14 TEXT,
                texto_abierto_15 TEXT,
                texto_abierto_16 TEXT,
                texto_abierto_17 TEXT,
                texto_abierto_18 TEXT,
                texto_abierto_19 TEXT,
                texto_abierto_20 TEXT,
                texto_abierto_21 TEXT,
                texto_abierto_22 TEXT,
                texto_abierto_23 TEXT,
                texto_abierto_24 TEXT,
                texto_abierto_25 TEXT,
                texto_abierto_26 TEXT,
                texto_abierto_27 TEXT,
                texto_abierto_28 TEXT,
                texto_abierto_29 TEXT,
                texto_abierto_30 TEXT,
                texto_abierto_31 TEXT,
                texto_abierto_32 TEXT,
                texto_abierto_33 TEXT,
                texto_abierto_34 TEXT,
                texto_abierto_35 TEXT,
                texto_abierto_36 TEXT,
                texto_abierto_37 TEXT,
                texto_abierto_38 TEXT,
                texto_abierto_39 TEXT,
                texto_abierto_40 TEXT,
                texto_abierto_41 TEXT,
                texto_abierto_42 TEXT,
                texto_abierto_43 TEXT,
                texto_abierto_44 TEXT,
                texto_abierto_45 TEXT,
                texto_abierto_46 TEXT,
                texto_abierto_47 TEXT,
                texto_abierto_48 TEXT,
                texto_abierto_49 TEXT,
                texto_abierto_50 TEXT
                
            );
        ");
    }

    /**
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $cycles Array of cycle objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $invt): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT OR IGNORE INTO inventario (
                id_inventario,
                id_grupo,
                id_familia,
                descripcion_bien,
                id_bien,
                descripcion_marca,
                id_marca,
                idForma,
                idMaterial,
                etiqueta,
                etiqueta_padre,
                idResponsable,
                modelo,
                serie,
                capacidad,
                estado,
                color,
                tipo_trabajo,
                carga_trabajo,
                estado_operacional,
                estado_conservacion,
                condicion_Ambiental,
                cantidad_img,
                id_img,
                id_ciclo,
                idUbicacionGeo,
                codigoUbicacion_N1,
                idUbicacionN2,
                codigoUbicacion_N2,
                codigoUbicacionN3,
                idUbicacionN3,
                descripcionTipo,
                observacion,
                latitud,
                longitud,
                offline,
                update_inv,
                creado_por,
                crud_activo_estado,
                -- edualejandro
                eficiencia,
                texto_abierto_1,
                texto_abierto_2,
                texto_abierto_3,
                texto_abierto_4,
                texto_abierto_5,
                texto_abierto_6,
                texto_abierto_7,
                texto_abierto_8,
                texto_abierto_9,
                texto_abierto_10,
                texto_abierto_11,
                texto_abierto_12,
                texto_abierto_13,
                texto_abierto_14,
                texto_abierto_15,
                texto_abierto_16,
                texto_abierto_17,
                texto_abierto_18,
                texto_abierto_19,
                texto_abierto_20,
                texto_abierto_21,
                texto_abierto_22,
                texto_abierto_23,
                texto_abierto_24,
                texto_abierto_25,
                texto_abierto_26,
                texto_abierto_27,
                texto_abierto_28,
                texto_abierto_29,
                texto_abierto_30,
                texto_abierto_31,
                texto_abierto_32,
                texto_abierto_33,
                texto_abierto_34,
                texto_abierto_35,
                texto_abierto_36,
                texto_abierto_37,
                texto_abierto_38,
                texto_abierto_39,
                texto_abierto_40,
                texto_abierto_41,
                texto_abierto_42,
                texto_abierto_43,
                texto_abierto_44,
                texto_abierto_45,
                texto_abierto_46,
                texto_abierto_47,
                texto_abierto_48,
                texto_abierto_49,
                texto_abierto_50

            )
            VALUES (
                :id_inventario,
                :id_grupo,
                :id_familia,
                :descripcion_bien,
                :id_bien,
                :descripcion_marca,
                :id_marca,
                :idForma,
                :idMaterial,
                :etiqueta,
                :etiqueta_padre,
                :idResponsable,
                :modelo,
                :serie,
                :capacidad,
                :estado,
                :color,
                :tipo_trabajo,
                :carga_trabajo,
                :estado_operacional,
                :estado_conservacion,
                :condicion_Ambiental,
                :cantidad_img,
                :id_img,
                :id_ciclo,
                :idUbicacionGeo,
                :codigoUbicacion_N1,
                :idUbicacionN2,
                :codigoUbicacion_N2,
                :codigoUbicacionN3,
                :idUbicacionN3,
                :descripcionTipo,
                :observacion,
                :latitud,
                :longitud,
                :offline,
                :update_inv,
                :creado_por,
                :crud_activo_estado,
                -- edualejandro
                :eficiencia,
                :texto_abierto_1,
                :texto_abierto_2,
                :texto_abierto_3,
                :texto_abierto_4,
                :texto_abierto_5,
                :texto_abierto_6,
                :texto_abierto_7,
                :texto_abierto_8,
                :texto_abierto_9,
                :texto_abierto_10,
                :texto_abierto_11,
                :texto_abierto_12,
                :texto_abierto_13,
                :texto_abierto_14,
                :texto_abierto_15,
                :texto_abierto_16,
                :texto_abierto_17,
                :texto_abierto_18,
                :texto_abierto_19,
                :texto_abierto_20,
                :texto_abierto_21,
                :texto_abierto_22,
                :texto_abierto_23,
                :texto_abierto_24,
                :texto_abierto_25,
                :texto_abierto_26,
                :texto_abierto_27,
                :texto_abierto_28,
                :texto_abierto_29,
                :texto_abierto_30,
                :texto_abierto_31,
                :texto_abierto_32,
                :texto_abierto_33,
                :texto_abierto_34,
                :texto_abierto_35,
                :texto_abierto_36,
                :texto_abierto_37,
                :texto_abierto_38,
                :texto_abierto_39,
                :texto_abierto_40,
                :texto_abierto_41,
                :texto_abierto_42,
                :texto_abierto_43,
                :texto_abierto_44,
                :texto_abierto_45,
                :texto_abierto_46,
                :texto_abierto_47,
                :texto_abierto_48,
                :texto_abierto_49,
                :texto_abierto_50
            )
        ");

        foreach ($invt as $i) {

            $stmt->execute([
                ':id_inventario' => $i->id_inventario ?? 0,
                ':id_grupo' => $i->id_grupo ?? 0,
                ':id_familia' => $i->id_familia ?? 0,
                ':descripcion_bien' => $i->descripcion_bien ?? '',
                ':id_bien' => $i->id_bien ?? 0,
                ':descripcion_marca' => $i->descripcion_marca ?? '',
                ':id_marca' => $i->id_marca ?? 0,
                ':idForma' => $i->idForma ?? 0,
                ':idMaterial' => $i->idMaterial ?? 0,
                ':etiqueta' => $i->etiqueta ?? '',
                ':etiqueta_padre' => $i->etiqueta_padre ?? '',
                ':idResponsable' => $i->idResponsable ?? 0,
                ':modelo' => $i->modelo ?? '',
                ':serie' => $i->serie ?? '',
                ':capacidad' => $i->capacidad ?? '',
                ':estado' => $i->estado ?? 0,
                ':color' => $i->color ?? 0,
                ':tipo_trabajo' => $i->tipo_trabajo ?? 0,
                ':carga_trabajo' => $i->carga_trabajo ?? 0,
                ':estado_operacional' => $i->estado_operacional ?? 0,
                ':estado_conservacion' => $i->estado_conservacion ?? 0,
                ':condicion_Ambiental' => $i->condicion_Ambiental ?? 0,
                ':cantidad_img' => $i->cantidad_img ?? 0,
                ':id_img' => $i->id_img ?? '',
                ':id_ciclo' => $i->id_ciclo ?? 0,
                ':idUbicacionGeo' => $i->idUbicacionGeo ?? 0,
                ':codigoUbicacion_N1' => $i->codigoUbicacion_N1 ?? '',
                ':idUbicacionN2' => $i->idUbicacionN2 ?? '',
                ':codigoUbicacion_N2' => $i->codigoUbicacion_N2 ?? '',
                ':codigoUbicacionN3' => $i->codigoUbicacionN3 ?? '',
                ':idUbicacionN3' => $i->idUbicacionN3 ?? '',
                ':descripcionTipo' => $i->descripcionTipo ?? '',
                ':observacion' => $i->observacion ?? '',
                ':latitud' => $i->latitud ?? '',
                ':longitud' => $i->longitud ?? '',
                ':offline' => $i->offline ?? 0,
                ':update_inv' => $i->update_inv ?? 0,
                ':creado_por' => $i->creado_por ?? '',
                ':crud_activo_estado' => $i->crud_activo_estado ?? 0,
                // edualejandro
                ':eficiencia' => $i->eficiencia ?? null,
                ':texto_abierto_1' => $i->texto_abierto_1 ?? null,
                ':texto_abierto_2' => $i->texto_abierto_2 ?? null,
                ':texto_abierto_3' => $i->texto_abierto_3 ?? null,
                ':texto_abierto_4' => $i->texto_abierto_4 ?? null,
                ':texto_abierto_5' => $i->texto_abierto_5 ?? null,
                ':texto_abierto_6' => $i->texto_abierto_6 ?? null,
                ':texto_abierto_7' => $i->texto_abierto_7 ?? null,
                ':texto_abierto_8' => $i->texto_abierto_8 ?? null,
                ':texto_abierto_9' => $i->texto_abierto_9 ?? null,
                ':texto_abierto_10' => $i->texto_abierto_10 ?? null,
                ':texto_abierto_11' => $i->texto_abierto_11 ?? null,
                ':texto_abierto_12' => $i->texto_abierto_12 ?? null,
                ':texto_abierto_13' => $i->texto_abierto_13 ?? null,
                ':texto_abierto_14' => $i->texto_abierto_14 ?? null,
                ':texto_abierto_15' => $i->texto_abierto_15 ?? null,
                ':texto_abierto_16' => $i->texto_abierto_16 ?? null,
                ':texto_abierto_17' => $i->texto_abierto_17 ?? null,
                ':texto_abierto_18' => $i->texto_abierto_18 ?? null,
                ':texto_abierto_19' => $i->texto_abierto_19 ?? null,
                ':texto_abierto_20' => $i->texto_abierto_20 ?? null,
                ':texto_abierto_21' => $i->texto_abierto_21 ?? null,
                ':texto_abierto_22' => $i->texto_abierto_22 ?? null,
                ':texto_abierto_23' => $i->texto_abierto_23 ?? null,
                ':texto_abierto_24' => $i->texto_abierto_24 ?? null,
                ':texto_abierto_25' => $i->texto_abierto_25 ?? null,
                ':texto_abierto_26' => $i->texto_abierto_26 ?? null,
                ':texto_abierto_27' => $i->texto_abierto_27 ?? null,
                ':texto_abierto_28' => $i->texto_abierto_28 ?? null,
                ':texto_abierto_29' => $i->texto_abierto_29 ?? null,
                ':texto_abierto_30' => $i->texto_abierto_30 ?? null,
                ':texto_abierto_31' => $i->texto_abierto_31 ?? null,
                ':texto_abierto_32' => $i->texto_abierto_32 ?? null,
                ':texto_abierto_33' => $i->texto_abierto_33 ?? null,
                ':texto_abierto_34' => $i->texto_abierto_34 ?? null,
                ':texto_abierto_35' => $i->texto_abierto_35 ?? null,
                ':texto_abierto_36' => $i->texto_abierto_36 ?? null,
                ':texto_abierto_37' => $i->texto_abierto_37 ?? null,
                ':texto_abierto_38' => $i->texto_abierto_38 ?? null,
                ':texto_abierto_39' => $i->texto_abierto_39 ?? null,
                ':texto_abierto_40' => $i->texto_abierto_40 ?? null,
                ':texto_abierto_41' => $i->texto_abierto_41 ?? null,
                ':texto_abierto_42' => $i->texto_abierto_42 ?? null,
                ':texto_abierto_43' => $i->texto_abierto_43 ?? null,
                ':texto_abierto_44' => $i->texto_abierto_44 ?? null,
                ':texto_abierto_45' => $i->texto_abierto_45 ?? null,
                ':texto_abierto_46' => $i->texto_abierto_46 ?? null,
                ':texto_abierto_47' => $i->texto_abierto_47 ?? null,
                ':texto_abierto_48' => $i->texto_abierto_48 ?? null,
                ':texto_abierto_49' => $i->texto_abierto_49 ?? null,
                ':texto_abierto_50' => $i->texto_abierto_50 ?? null
            ]);
        }
    }
}
