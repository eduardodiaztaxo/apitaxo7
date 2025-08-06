<?php

namespace App\Services\Dump\Tables;


use App\Http\Controllers\Api\V1\InventariosOfflineController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class ComunasDumpService implements DumpSQLiteInterface
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

        $zonasEmplaCtrl = new InventariosOfflineController();

        $response = $zonasEmplaCtrl->showAllComunas();

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
            CREATE TABLE IF NOT EXISTS comunas (
                idComuna INTEGER PRIMARY KEY,
                idRegion INTEGER,
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
    public function insert(array|AnonymousResourceCollection $comuna): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT INTO comunas (
                idComuna,
                idRegion,
                descripcion
            )
            VALUES (
                :idComuna,
                :idRegion,
                :descripcion
            )  
        ");



        foreach ($comuna as $c) {

            $stmt->execute([
                ':idComuna' => $c->idComuna,
                ':idRegion' => $c->idRegion,
                ':descripcion' => $c->descripcion
            ]);
        }
    }
}
