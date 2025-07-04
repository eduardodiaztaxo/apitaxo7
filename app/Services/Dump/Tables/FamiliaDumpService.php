<?php

namespace App\Services\Dump\Tables;

use App\Http\Controllers\Api\V1\InventariosOfflineController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class FamiliaDumpService implements DumpSQLiteInterface
{


    /**
     * @var \PDO|null PDO connection instance
     */
    protected $pdo = null;

    /**
     * @var int Cycle number
     */
    protected $cycle = 0;

    protected $codigo_grupo = '';

 public function __construct(PDO $pdo, int $cycle = 0, string $codigo_grupo = '')
{
    $this->pdo = $pdo;
    $this->cycle = $cycle;
    $this->codigo_grupo = $codigo_grupo;
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

        $response = $datsdActivosCtrl->familia($this->cycle, [$this->codigo_grupo]);

        $jsonContent = $response->getContent();

        $data = json_decode($jsonContent);

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


        // Create "assets" table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS familias (
                id_familia INTEGER PRIMARY KEY,
                id_grupo INTEGER NOT NULL,
                codigo_familia TEXT NOT NULL,
                descripcion_familia TEXT NOT NULL
            );
        ");
    }

    /**
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $cycles Array of cycle objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $familias): void
    {
        // Insertar datos
       $stmt = $this->pdo->prepare("
            INSERT OR IGNORE INTO familias (
                id_familia,
                id_grupo,
                codigo_familia,
                descripcion_familia
            )
            VALUES (
                :id_familia,
                :id_grupo,
                :codigo_familia,
                :descripcion_familia
            )
        ");

        foreach ($familias as $familia) {

            $stmt->execute([
                ':id_familia' => $familia->id_familia,
                ':id_grupo' => $familia->id_grupo,
                ':codigo_familia' => $familia->codigo_familia,
                ':descripcion_familia' => $familia->descripcion_familia
            ]);
        }
    }
}
