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

        $data = json_decode($jsonContent);

        if (isset($data->status) && $data->status !== 'OK') {
            return;
        }


        $this->insert($data);
    }

    public function createTable(): void
    {

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS inventario (
                id_inventario INTEGER PRIMARY KEY,
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
                texto_abierto_5 TEXT
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
            INSERT INTO inventario (
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
                texto_abierto_5
            )
            VALUES (
                :id_invetario,
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
                :texto_abierto_5
            )
        ");

        foreach ($invt as $i) {

            $stmt->execute([
                ':id_invetario' => $i->id_inventario ?? 0,
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
                ':codigoUbicacion_N1' => $i->codigoUbicacion_N1 ?? 0,
                ':idUbicacionN2' => $i->idUbicacionN2 ?? 0,
                ':codigoUbicacion_N2' => $i->codigoUbicacion_N2 ?? 0,
                ':codigoUbicacionN3' => $i->codigoUbicacionN3 ?? 0,
                ':idUbicacionN3' => $i->idUbicacionN3 ?? 0,
                ':descripcionTipo' => $i->descripcionTipo ?? '',
                ':observacion' => $i->observacion ?? '',
                ':latitud' => $i->latitud ?? '',
                ':longitud' => $i->longitud ?? '',
                ':offline' => $i->offline ?? 0,
                ':update_inv' => $i->update_inv ?? 0,
                ':creado_por' => $i->creado_por ?? 0,
                ':crud_activo_estado' => $i->crud_activo_estado ?? 0,
                // edualejandro
                ':eficiencia' => $i->eficiencia ?? null,
                ':texto_abierto_1' => $i->texto_abierto_1 ?? null,
                ':texto_abierto_2' => $i->texto_abierto_2 ?? null,
                ':texto_abierto_3' => $i->texto_abierto_3 ?? null,
                ':texto_abierto_4' => $i->texto_abierto_4 ?? null,
                ':texto_abierto_5' => $i->texto_abierto_5 ?? null
            ]);
        }
    }
}
