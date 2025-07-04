<?php

namespace App\Services\Dump\Tables;

use App\Http\Controllers\Api\V1\InventariosOfflineController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class MarcasDumpService implements DumpSQLiteInterface
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

        $response = $datsdActivosCtrl->MarcasPorCicloOfflineInventario($this->cycle);

       $jsonContent = $response->getContent();
       
        // Decodificar el JSON a un arreglo asociativo
        $data = json_decode($jsonContent);
// dd(count($data));

        if (isset($data->status) && $data->status !== 'OK') {
            return;
        }


        $this->insert($data);
    }

    /**
     * Create table if it does not exist.
     *
     * This method creates table with the specified columns and their data types.
     *
     * @return void
     */
    public function createTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS marcas (
                idLista INTEGER DEFAULT 0,
                idAtributo INTEGER DEFAULT 0,
                idIndice INTEGER DEFAULT 0,
                id_familia INTEGER DEFAULT 0,
                descripcion TEXT,
                ciclo_inventario INTEGER DEFAULT 0
            );
        ");
    }

    /**
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $cycles Array of cycle objects to insert.
     * @return void
     */
  public function insert(array|AnonymousResourceCollection $marca): void
    {
        $stmt = $this->pdo->prepare("
    INSERT INTO marcas (
        idLista,
        idAtributo,
        idIndice,
        id_familia,
        descripcion,
        ciclo_inventario
    )
    VALUES (
        :idLista,
        :idAtributo,
        :idIndice,
        :id_familia,
        :descripcion,
        :ciclo_inventario
    )
");



        foreach ($marca as $m) {

            $stmt->execute([
                ':idLista' => $m->idLista,
                ':idAtributo' => $m->idAtributo,
                ':idIndice' => $m->idIndice,
                ':id_familia' => $m->id_familia,
                ':descripcion' => $m->descripcion,
                ':ciclo_inventario' => $m->ciclo_inventario
            ]);
        }
    }
}
