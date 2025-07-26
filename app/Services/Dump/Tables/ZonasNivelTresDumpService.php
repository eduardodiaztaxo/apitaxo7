<?php

namespace App\Services\Dump\Tables;


use App\Http\Controllers\Api\V1\InventariosOfflineController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class ZonasNivelTresDumpService implements DumpSQLiteInterface
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

        $response = $zonasEmplaCtrl->zonasN3($this->cycle);

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
            CREATE TABLE IF NOT EXISTS zonasNivel3 (
                idUbicacionN3 INTEGER PRIMARY KEY,
                idAgenda INTIGER,
                codigoUbicacion TEXT,
                descripcionUbicacion TEXT,
                estado TEXT,
                newApp INTEGER,
                num_activos_invN3 INTIGET,
                num_activos_cats_by_cycleN3 INTIGER
            );
        ");
    }

    /**
     * Insert empla into the emplazamientos table.
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $emplazamientos Array or Collection of zones objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $N3): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT INTO zonasNivel3 (
                idUbicacionN3,
                idAgenda,
                codigoUbicacion,
                descripcionUbicacion,
                estado,
                newApp,
                num_activos_invN3,
                num_activos_cats_by_cycleN3
            )
            VALUES (
                :idUbicacionN3,
                :idAgenda,
                :codigoUbicacion,
                :descripcionUbicacion,
                :estado,
                :newApp,
                :num_activos_invN3,
                :num_activos_cats_by_cycleN3
            )  
        ");

        foreach ($N3 as $Zona) {

            $stmt->execute([
                ':idUbicacionN3' => $Zona->idUbicacionN3,
                ':idAgenda' => $Zona->idAgenda,
                ':codigoUbicacion' => $Zona->codigoUbicacion,
                ':descripcionUbicacion' => $Zona->descripcionUbicacion,
                ':estado' => $Zona->estado,
                ':newApp' => $Zona->newApp,
                ':num_activos_invN3' => $Zona->num_activos_invN3,
                ':num_activos_cats_by_cycleN3' => $Zona->num_activos_cats_by_cycleN3
            ]);
        }
    }
}
