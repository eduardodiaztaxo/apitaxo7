<?php

namespace App\Services\Dump\Tables;

use App\Http\Controllers\Api\V1\Comunes\DatosActivosController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class GruposDumpService implements DumpSQLiteInterface
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

        $response = $datsdActivosCtrl->grupo($this->cycle);

        $jsonContent = $response->getContent();

        // Decodificar el JSON a un arreglo asociativo
        $data = json_decode($jsonContent);

        if (isset($data->status) && $data->status !== 'OK') {
            return;
        }


        $this->insert($data);
    }


    /**
     *
     * This method creates the assets table with the specified columns and their data types.
     *
     * @return void
     */
    public function createTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS grupos (
                id_grupo INTEGER PRIMARY KEY,
                codigo_grupo TEXT NOT NULL,
                descripcion_grupo TEXT NOT NULL

            );
        ");
    }

    /**
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $cycles Array of cycle objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $grupos): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT OR IGNORE INTO grupos (
                id_grupo,
                codigo_grupo,
                descripcion_grupo
            )
            VALUES (
                :id_grupo,
                :codigo_grupo,
                :descripcion_grupo  
            )
        ");

        foreach ($grupos as $grupo) {

            $stmt->execute([
                ':id_grupo' => $grupo->id_grupo,
                ':codigo_grupo' => $grupo->codigo_grupo,
                ':descripcion_grupo' => $grupo->descripcion_grupo
            ]);
        }
    }
}
