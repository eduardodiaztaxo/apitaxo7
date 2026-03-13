<?php

namespace App\Services\Dump\Tables;

use App\Http\Controllers\Api\V1\Comunes\DatosActivosController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;


class BienesInventarioDumpService
{
    /**
     * @var \PDO|null PDO connection instance
     */
    protected $pdo = null;

    /**
     * @var int Cycle number
     */
    protected $cycle = 0;

    public function __construct(PDO $pdo, int $cycle = 0)
    {
        $this->pdo = $pdo;

        $this->cycle = $cycle;
    }

    /**
     * Run dump from the controller.
     *
     * This method creates the ciclos table and inserts data into it from controller.
     *
     * @return void
     */
    public function runFromController(): void
    {

        $this->createTable();

        $request = new \Illuminate\Http\Request();
        $request->setMethod('GET');

        $datsdActivosCtrl = new DatosActivosController();

        $response = $datsdActivosCtrl->bienesGrupoFamilia($this->cycle);

        $jsonContent = $response->getContent();

        // Decodificar el JSON
        $data = json_decode($jsonContent);

        if (isset($data->status) && $data->status !== 'OK') {
            return;
        }

        // Si los datos están envueltos en "data", extraerlos
        $bienes = isset($data->data) ? $data->data : $data;

        $this->insert($bienes);
    }

    public function createTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS bienesInventario (
                idLista INTEGER,
                idIndice INTEGER NOT NULL,
                descripcion TEXT NOT NULL,
                observacion TEXT NOT NULL,
                idAtributo INTEGER NOT NULL,
                id_familia INTEGER NOT NULL,
                id_grupo INTEGER NOT NULL,
                ciclo_inventario INTEGER NOT NULL,
                creadoPor TEXT,
                fechaCreacion TEXT,
                modo TEXT,
                offline INTEGER
            )
        ");
    }
    /**
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $cycles Array of cycle objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $bienes): void
    {
        $stmt = $this->pdo->prepare("
        INSERT INTO bienesInventario (
                idLista,
                idIndice,
                descripcion,
                observacion,
                idAtributo,
                id_familia,
                id_grupo,
                ciclo_inventario,
                creadoPor,
                fechaCreacion,
                modo,
                offline
    )
    VALUES (
                :idLista,
                :idIndice,
                :descripcion,
                :observacion,
                :idAtributo,
                :id_familia,
                :id_grupo,
                :ciclo_inventario,
                :creadoPor,
                :fechaCreacion,
                :modo,
                :offline
    )
");

        foreach ($bienes as $b) {

            $stmt->execute([
                'idLista' => $b->idLista,
                'idIndice' => $b->idIndice,
                'descripcion' => $b->descripcion ?? '',
                'observacion' => $b->observacion ?? '',
                'idAtributo' => $b->idAtributo,
                'id_familia' => $b->id_familia,
                'id_grupo' => $b->id_grupo ?? 0,
                'ciclo_inventario' => $b->ciclo_inventario ?? 0,
                'creadoPor' => $b->creadoPor ?? '',
                'fechaCreacion' => $b->fechaCreacion ?? '',
                'modo' => $b->modo ?? 'ONLINE',
                'offline' => 0,
            ]);
        }
    }
}
