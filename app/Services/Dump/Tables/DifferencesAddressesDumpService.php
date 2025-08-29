<?php

namespace App\Services\Dump\Tables;


use App\Http\Controllers\Api\V1\InventariosOfflineController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class DifferencesAddressesDumpService implements DumpSQLiteInterface
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
     * Run the address dump from the controller.
     *
     * This method creates the ubicaciones table and inserts address data into it from controller.
     *
     * @return void
     */
    public function runFromController(): void
    {

        $this->createTable();

        $request = new \Illuminate\Http\Request();
        $request->setMethod('GET');

        $cyclesUbiCtrl = new InventariosOfflineController();

        $response = $cyclesUbiCtrl->DiferenciasDirecionesMapa($this->cycle);

        $jsonContent = $response->getContent();

        $data = json_decode($jsonContent);

        if (isset($data->status) && $data->status !== 'OK') {
            return;
        }

        $this->insert($data);
    }


    /**
     * Create the ubicaciones table if it does not exist.
     *
     * This method creates the ubicaciones table with the specified columns and their data types.
     *
     * @return void
     */
    public function createTable(): void
    {


        // Create "ubicaciones" table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS differencesAddresses (
                id_direccion INTEGER,
                categoria TEXT,
                q_teorico INTEGER,
                q_fisico INTEGER,
                diferencia INTEGER
            );
        ");
    }

    /**
     * Insert addresses into the ubicaciones table.
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $addresses Array or Collection of address objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $addresses): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT INTO differencesAddresses (
                id_direccion,
                categoria,
                q_teorico,
                q_fisico,
                diferencia
            )
            VALUES (
                :id_direccion,
                :categoria,
                :q_teorico,
                :q_fisico,
                :diferencia
            )
        ");

        foreach ($addresses as $a) {

            $stmt->execute([
                ':id_direccion' => $a->id_direccion,
                ':categoria' => $a->categoria,
                ':q_teorico' => $a->q_teorico,
                ':q_fisico' => $a->q_fisico,
                ':diferencia' => $a->diferencia
             ]);
        }
    }

}