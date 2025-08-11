<?php

namespace App\Services\Dump\Tables;

use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Controllers\Api\V1\InventariosOfflineController;
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

        $datsdActivosCtrl = new InventariosOfflineController();

        $response = $datsdActivosCtrl->BienesNuevosOfflineInventario($this->cycle);

       $jsonContent = $response->getContent();
       
        // Decodificar el JSON a un arreglo asociativo
        $data = json_decode($jsonContent);

        if (isset($data->status) && $data->status !== 'OK') {
            return;
        }


        $this->insert($data);
    }

    public function createTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS bienesInventario (
                idLista INTEGER PRIMARY KEY AUTOINCREMENT,
                idIndice INTEGER NOT NULL,
                descripcion TEXT NOT NULL,
                observacion TEXT NOT NULL,
                idAtributo INTEGER NOT NULL,
                id_familia INTEGER NOT NULL,
                id_grupo INTEGER NOT NULL,
                ciclo_inventario INTEGER NOT NULL,
                creadoPor TEXT,
                fechaCreacion TEXT,
                modo TEXT
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
                modo
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
                :modo
    )
");

        foreach ($bienes as $b) {

            $stmt->execute([
                'idLista' => $b->idLista,
                'idIndice' => $b->idIndice,
                'descripcion' => $b->descripcion,
                'observacion' => $b->observacion,
                'idAtributo' => $b->idAtributo,
                'id_familia' => $b->id_familia,
                'id_grupo' => $b->id_grupo,
                'ciclo_inventario' => $b->ciclo_inventario,
                'creadoPor' => $b->creadoPor,
                'fechaCreacion' => $b->fechaCreacion,
                'modo' => $b->modo
            ]);
        }
    }
}

