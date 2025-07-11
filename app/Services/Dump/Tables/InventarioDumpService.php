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
                descripcion_bien TEXT NOT NULL,
                id_bien INTEGER NOT NULL,
                descripcion_marca TEXT NOT NULL,
                id_marca INTEGER NOT NULL,
                idForma INTEGER NOT NULL,
                idMaterial INTEGER NOT NULL,
                etiqueta TEXT NOT NULL,
                modelo TEXT NOT NULL,
                serie TEXT NOT NULL,
                capacidad TEXT NOT NULL,
                estado INTEGER NOT NULL,
                color INTEGER NOT NULL,
                tipo_trabajo INTEGER NOT NULL,
                carga_trabajo INTEGER NOT NULL,
                estado_operacional INTEGER NOT NULL,
                estado_conservacion INTEGER NOT NULL,
                condicion_Ambiental INTEGER NOT NULL,
                cantidad_img INTEGER NOT NULL,
                id_img TEXT NOT NULL,
                id_ciclo INTEGER NOT NULL,
                idUbicacionGeo INTEGER NOT NULL,
                idUbicacionN2 TEXT NOT NULL,
                codigoUbicacion_N1 TEXT NOT NULL,
                responsable TEXT NOT NULL,
                idResponsable INTEGER NOT NULL,
                descripcionTipo TEXT NOT NULL,
                observacion TEXT NOT NULL,
                latitud TEXT NOT NULL,
                longitud TEXT NOT NULL,
                offline INTEGER NOT NULL,
                update_inv INTEGER NOT NULL
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
                idUbicacionN2,
                codigoUbicacion_N1,
                responsable,
                idResponsable,
                descripcionTipo,
                observacion,
                latitud,
                longitud,
                offline,
                update_inv
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
                :idUbicacionN2,
                :codigoUbicacion_N1,
                :responsable,
                :idResponsable,
                :descripcionTipo,
                :observacion,
                :latitud,
                :longitud,
                :offline,
                :update_inv
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
                ':idUbicacionN2' => $i->idUbicacionN2 ?? 0,
                ':codigoUbicacion_N1' => $i->codigoUbicacion_N1 ?? 0,
                ':responsable' => $i->responsable ?? '',
                ':idResponsable' => $i->idResponsable ?? 0,
                ':descripcionTipo' => $i->descripcionTipo ?? '',
                ':observacion' => $i->observacion ?? '',
                ':latitud' => $i->latitud ?? '',
                ':longitud' => $i->longitud ?? '',
                ':offline' => $i->offline ?? 0,
                ':update_inv' => $i->update_inv ?? 0
            ]);
        }
    }
}
