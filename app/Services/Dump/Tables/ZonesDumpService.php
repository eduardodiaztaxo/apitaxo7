<?php

namespace App\Services\Dump\Tables;


use App\Http\Controllers\Api\V1\CiclosUbicacionesController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class ZonesDumpService implements DumpSQLiteInterface
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
     * Run the zones dump from the controller.
     *
     * This method creates the zonas table and inserts zones data into it from controller.
     *
     * @return void
     */
    public function runFromController(): void
    {

        $this->createTable();

        $request = new \Illuminate\Http\Request();
        $request->setMethod('GET');

        $cyclesUbiCtrl = new CiclosUbicacionesController();

        $response = $cyclesUbiCtrl->showByCycleCats($request, $this->cycle);

        $jsonContent = $response->getContent();

        // Decodificar el JSON a un arreglo asociativo
        $data = json_decode($jsonContent);

        $zonas = [];

        foreach ($data as $address) {

            $zonas = array_merge($address->zonas_punto, $zonas);
        }

        $this->insert($zonas);
    }


    /**
     * Create the zonas table if it does not exist.
     *
     * This method creates the zonas table with the specified columns and their data types.
     *
     * @return void
     */
    public function createTable(): void
    {


        // Create "zonas" table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS zonas (
                codigo TEXT,
                codigoUbicacion TEXT,
                descripcionUbicacion TEXT,
                idAgenda INTEGER,
                idUbicacionN1 INTEGER,
                ciclo_auditoria INTEGER,
                totalBienes INTEGER DEFAULT 0,
                num_activos INTEGER DEFAULT 0,
                num_activos_cats_by_cycle INTEGER DEFAULT 0,
                num_activos_inv INTEGER DEFAULT 0,
                num_activos_orphans INTEGER DEFAULT 0,
                num_total_orphans INTEGER DEFAULT 0
            );
        ");
    }

    /**
     * Insert zones into the zonas table.
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $zones Array or Collection of zones objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $zones): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT INTO zonas (
                codigo,
                codigoUbicacion,
                descripcionUbicacion,
                idAgenda,
                idUbicacionN1,
                ciclo_auditoria,
                totalBienes,
                num_activos,
                num_activos_cats_by_cycle,
                num_activos_inv,
                num_activos_orphans,
                num_total_orphans
            )
            VALUES (
                :codigo,
                :codigoUbicacion,
                :descripcionUbicacion,
                :idAgenda,
                :idUbicacionN1,
                :ciclo_auditoria,
                :totalBienes,
                :num_activos,
                :num_activos_cats_by_cycle,
                :num_activos_inv,
                :num_activos_orphans,
                :num_total_orphans
            )
        ");



        foreach ($zones as $zona) {

            $stmt->execute([
                ':codigo' => $zona->codigo,
                ':codigoUbicacion' => $zona->codigoUbicacion,
                ':descripcionUbicacion' => $zona->descripcionUbicacion,
                ':idAgenda' => $zona->idAgenda,
                ':idUbicacionN1' => $zona->idUbicacionN1,
                ':ciclo_auditoria' => $zona->ciclo_auditoria,
                ':totalBienes' => $zona->totalBienes,
                ':num_activos' => $zona->num_activos,
                ':num_activos_cats_by_cycle' => $zona->num_activos_cats_by_cycle,
                ':num_activos_inv' => $zona->num_activos_inv,
                ':num_activos_orphans' => $zona->num_activos_orphans,
                ':num_total_orphans' => $zona->num_total_orphans
            ]);
        }
    }
}
