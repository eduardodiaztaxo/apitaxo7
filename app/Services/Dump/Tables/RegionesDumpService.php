<?php

namespace App\Services\Dump\Tables;


use App\Http\Controllers\Api\V1\ZonaEmplazamientosController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class RegionesDumpService implements DumpSQLiteInterface
{


    /**
     * @var \PDO|null PDO connection instance
     */
    protected $pdo = null;

    /**
     * @var int Cycle number
     */


    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

    }

    /**
     * Run the empla dump from the controller.
     *
     * This method creates the empla table and inserts empla data into it from controller.
     *
     * @return void
     */
    public function runFromController(): void
    {

        $this->createTable();

        $request = new \Illuminate\Http\Request();
        $request->setMethod('GET');

        $zonasEmplaCtrl = new ZonaEmplazamientosController();

        $response = $zonasEmplaCtrl->regiones();

        $jsonContent = $response->getContent();

        // Decodificar el JSON a un arreglo asociativo
        $data = json_decode($jsonContent);

        if (isset($data->status) && $data->status !== 'OK') {
            return;
        }


        $this->insert($data);
    }


    /**
     * Create the emplazamientos table if it does not exist.
     *
     * This method creates the emplazamientos table with the specified columns and their data types.
     *
     * @return void
     */
    public function createTable(): void
    {


        // Create "emplazamientos" table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS regiones (
                idRegion INTEGER PRIMARY KEY,
                descripcion TEXT
            );
        ");
    }

    /**
     * 
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection 
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $region): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT INTO regiones (
                idRegion,
                descripcion
            )
            VALUES (
                :idRegion,
                :descripcion
            )  
        ");



        foreach ($region as $r) {

            $stmt->execute([
                ':idRegion' => $r->idRegion,
                ':descripcion' => $r->descripcion
            ]);
        }
    }
}
