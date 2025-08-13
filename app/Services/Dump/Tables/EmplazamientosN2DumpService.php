<?php

namespace App\Services\Dump\Tables;


use App\Http\Controllers\Api\V1\ZonaEmplazamientosController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class EmplazamientosN2DumpService implements DumpSQLiteInterface
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

        $zonasEmplaCtrl = new ZonaEmplazamientosController();

        $response = $zonasEmplaCtrl->showAllEmplaByCycleCats($request, $this->cycle);

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
            CREATE TABLE IF NOT EXISTS emplazamientosN2 (
                id INTEGER PRIMARY KEY,
                codigo TEXT,
                codigoUbicacion TEXT,
                nombre TEXT,
                idAgenda INTEGER,
                idUbicacionN2 INTEGER,
                num_activos INTEGER DEFAULT 0,
                num_activos_cats_by_cycle INTEGER DEFAULT 0,
                ciclo_auditoria INTEGER DEFAULT 0,
                num_categorias INTEGER DEFAULT 0,
                num_activos_audit INTEGER DEFAULT 0,
                habilitadoNivel3 INTIGER DEFAULT 0,
                detalle TEXT,
                num_nivel TEXT,
                newApp INTIGER,
                modo TEXT
            );
        ");
    }

    /**
     * Insert empla into the emplazamientos table.
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $emplazamientos Array or Collection of zones objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $emplazamientos): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT INTO emplazamientosN2 (
                id,
                codigo,
                codigoUbicacion,
                nombre,
                idAgenda,
                idUbicacionN2,
                num_activos,
                num_activos_cats_by_cycle,
                ciclo_auditoria,
                num_categorias,
                num_activos_audit,
                habilitadoNivel3,
                detalle,
                num_nivel,
                newApp,
                modo
            )
            VALUES (
                :id,
                :codigo,
                :codigoUbicacion,
                :nombre,
                :idAgenda,
                :idUbicacionN2,
                :num_activos,
                :num_activos_cats_by_cycle,
                :ciclo_auditoria,
                :num_categorias,
                :num_activos_audit,
                :habilitadoNivel3,
                :detalle,
                :num_nivel,
                :newApp,
                :modo
            )  
        ");



        foreach ($emplazamientos as $emplazamiento) {

            $stmt->execute([
                ':id' => $emplazamiento->id,
                ':codigo' => $emplazamiento->codigo,
                ':codigoUbicacion' => $emplazamiento->codigoUbicacion,
                ':nombre' => $emplazamiento->nombre,
                ':idAgenda' => $emplazamiento->idAgenda,
                ':idUbicacionN2' => $emplazamiento->idUbicacionN2,
                ':num_activos' => $emplazamiento->num_activos,
                ':num_activos_cats_by_cycle' => $emplazamiento->num_activos_cats_by_cycle,
                ':ciclo_auditoria' => $emplazamiento->ciclo_auditoria,
                ':num_categorias' => $emplazamiento->num_categorias,
                ':num_activos_audit' => $emplazamiento->num_activos_audit,
                ':detalle' => $emplazamiento->detalle,
                ':habilitadoNivel3' => $emplazamiento->habilitadoNivel3,
                ':num_nivel' => $emplazamiento->num_nivel,
                ':newApp' => $emplazamiento->newApp,
                ':modo' => $emplazamiento->modo
            ]);
        }
    }
}
