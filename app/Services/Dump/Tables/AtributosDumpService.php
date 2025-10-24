<?php

namespace App\Services\Dump\Tables;


use App\Http\Controllers\Api\V1\InventariosOfflineController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class AtributosDumpService implements DumpSQLiteInterface
{


    /**
     * @var \PDO|null PDO connection instance
     */
    protected $pdo = null;
    protected $cycle = 0;

    public function __construct(PDO $pdo, int $cycle = 0)
    {
        $this->pdo = $pdo;
        $this->cycle = $cycle;

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

        $response = $zonasEmplaCtrl->showNameInput($this->cycle);

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
            CREATE TABLE IF NOT EXISTS atributos (
                id_atributo INTEGER,
                descripcion TEXT,
                label_input TEXT
            );
        ");
    }

    /**
     * 
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection 
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $atributo): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT INTO atributos (
                id_atributo,
                descripcion,
                label_input
            )
            VALUES (
                :id_atributo,
                :descripcion,
                :label_input
            )  
        ");



        foreach ($atributo as $a) {

            $stmt->execute([
                ':id_atributo' => $a->id_atributo,
                ':descripcion' => $a->descripcion,
                ':label_input' => $a->label_input
            ]);
        }
    }
}
