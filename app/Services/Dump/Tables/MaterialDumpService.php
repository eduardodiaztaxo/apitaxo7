<?php

namespace App\Services\Dump\Tables;

use App\Http\Controllers\Api\V1\Comunes\DatosActivosController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class MaterialDumpService implements DumpSQLiteInterface
{


    /**
     * @var \PDO|null PDO connection instance
     */
    protected $pdo = null;


 public function __construct(PDO $pdo)
{
    $this->pdo = $pdo;

}
    /**
     * Run dump from the controller.
     *
     * This method creates the ciclos table and inserts assets data into it from controller.
     *
     * @return void
     */
    public function runFromController(): void
    {

        $this->createTable();

        $request = new \Illuminate\Http\Request();
        $request->setMethod('GET');

        $datsdActivosCtrl = new DatosActivosController();

       $response = $datsdActivosCtrl->material();

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
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS material (
                idLista INTEGER PRIMARY KEY,
                material TEXT NOT NULL
            );
        ");
    }

    /**
     * 
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $cycles Array of cycle objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $materiales): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
             INSERT INTO material (
                idLista,
                material
            )
            VALUES (
               :idLista,
               :material
               
            )
        ");

        foreach ($materiales as $m) {

            $stmt->execute([
                ':idLista' => $m->idLista,
                ':material' => $m->material
            ]);
        }
    }
}
