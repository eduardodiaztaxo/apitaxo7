<?php

namespace App\Services\Dump\Tables;

use App\Http\Controllers\Api\V1\Comunes\DatosActivosController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class CargaTrabajoDumpService implements DumpSQLiteInterface
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
     * Run the assets dump from the controller.
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

       $response = $datsdActivosCtrl->cargaTrabajo();

        $jsonContent = $response->getContent();

        $data = json_decode($jsonContent);

        if (isset($data->status) && $data->status !== 'OK') {
            return;
        }


        $this->insert($data);
    }


    /**
     * Create the assets table if it does not exist.
     *
     * This method creates the assets table with the specified columns and their data types.
     *
     * @return void
     */
    public function createTable(): void
    {


        // Create "assets" table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS cargaTrabajo (
                idLista INTEGER PRIMARY KEY,
                id_atributo INTEGER NOT NULL,
                descripcion TEXT NOT NULL
            );
        ");
    }

    /**
     * Insert assets into the assets table.
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $cycles Array of cycle objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $carga): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT INTO cargaTrabajo (
                idLista,
                id_atributo,
                descripcion
            )
            VALUES (
               :idLista,
               :id_atributo,
               :descripcion
               
            )
        ");

        foreach ($carga as $ca) {

            $stmt->execute([
                ':idLista' => $ca->idLista,
                ':id_atributo' => $ca->id_atributo,
                ':descripcion' => $ca->descripcion
            ]);
        }
    }
}
