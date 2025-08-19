<?php

namespace App\Services\Dump\Tables;

use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Controllers\Api\V1\InventariosOfflineController;
use PDO;


class MarcasInventarioDumpService
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

        $response = $datsdActivosCtrl->MarcasNuevasOfflineInventario($this->cycle);

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
            CREATE TABLE IF NOT EXISTS marcasInventario (
                idLista INTEGER,
                idIndice INTEGER NOT NULL,
                descripcion TEXT NOT NULL,
                observacion TEXT NOT NULL,
                idAtributo INTEGER NOT NULL,
                id_familia INTEGER NOT NULL,
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
    public function insert(array|AnonymousResourceCollection $marcas): void
    {
        $stmt = $this->pdo->prepare("
    INSERT INTO marcasInventario (
                idLista,
                idIndice,
                descripcion,
                observacion,
                idAtributo,
                id_familia,
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
                :ciclo_inventario,
                :creadoPor,
                :fechaCreacion,
                :modo
    )
");



        foreach ($marcas as $m) {

            $stmt->execute([
                'idLista' => $m->idLista,
                'idIndice' => $m->idIndice,
                'descripcion' => $m->descripcion,
                'observacion' => $m->observacion,
                'idAtributo' => $m->idAtributo,
                'id_familia' => $m->id_familia,
                'ciclo_inventario' => $m->ciclo_inventario,
                'creadoPor' => $m->creadoPor,
                'fechaCreacion' => $m->fechaCreacion,
                'modo' => $m->modo
            ]);
        }
    }
}
