<?php

namespace App\Services\Dump\Tables;


use App\Http\Controllers\Api\V1\CiclosUbicacionesController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class AddressesDumpService implements DumpSQLiteInterface
{


    /**
     * @var \PDO|null PDO connection instance
     */
    protected $pdo = null;

    /**
     * @var int Cycle number
     */
    protected $cycle = 0;


    protected $zones = [];


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

        $cyclesUbiCtrl = new CiclosUbicacionesController();

        $response = $cyclesUbiCtrl->showByCycleCats($request, $this->cycle);

        $jsonContent = $response->getContent();

        // Decodificar el JSON a un arreglo asociativo
        $data = json_decode($jsonContent);



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
            CREATE TABLE IF NOT EXISTS ubicaciones (
                idUbicacionGeo INTEGER PRIMARY KEY,
                codigoCliente TEXT,
                descripcion TEXT,
                zona TEXT,
                region TEXT,
                ciudad TEXT DEFAULT NULL,
                comuna TEXT DEFAULT NULL,
                direccion TEXT,
                idPunto TEXT DEFAULT NULL,
                estadoGeo INTEGER DEFAULT 1,
                id_estado INTEGER DEFAULT 1,
                estado_punto TEXT,
                auditoria_general INTEGER DEFAULT 0,
                num_activos INTEGER DEFAULT 0,
                num_activos_cats_by_cycle INTEGER DEFAULT 0,
                num_cats_by_cycle INTEGER DEFAULT 0,        
                num_subcats_n2_by_cycle INTEGER DEFAULT 0,
                num_subcats_n3_by_cycle INTEGER DEFAULT 0,
                num_activos_audit INTEGER DEFAULT 0
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
            INSERT INTO ubicaciones (
                idUbicacionGeo,
                codigoCliente,
                descripcion,
                zona,
                region,
                ciudad,
                comuna,
                direccion,
                idPunto,
                estadoGeo,
                id_estado,
                estado_punto,
                auditoria_general,
                num_activos,
                num_activos_cats_by_cycle,
                num_cats_by_cycle,
                num_subcats_n2_by_cycle,
                num_subcats_n3_by_cycle,
                num_activos_audit
            )
            VALUES (
                :idUbicacionGeo,
                :codigoCliente,
                :descripcion,
                :zona,
                :region,
                :ciudad,
                :comuna,
                :direccion,
                :idPunto,
                :estadoGeo,
                :id_estado,
                :estado_punto,
                :auditoria_general,
                :num_activos,
                :num_activos_cats_by_cycle,
                :num_cats_by_cycle,
                :num_subcats_n2_by_cycle,
                :num_subcats_n3_by_cycle,
                :num_activos_audit
            )
        ");



        $zonas = [];

        foreach ($addresses as $address) {

            $zonas = array_merge($address->zonas_punto, $zonas);

            $stmt->execute([
                ':idUbicacionGeo' => $address->idUbicacionGeo,
                ':codigoCliente' => $address->codigoCliente,
                ':descripcion' => $address->descripcion,
                ':zona' => $address->zona,
                ':region' => $address->region,
                ':ciudad' => $address->ciudad,
                ':comuna' => $address->comuna,
                ':direccion' => $address->direccion,
                ':idPunto' => $address->idPunto,
                ':estadoGeo' => $address->estadoGeo,
                ':id_estado' => $address->id_estado,
                ':estado_punto' => $address->estado_punto,
                ':auditoria_general' => $address->auditoria_general,
                ':num_activos' => $address->num_activos,
                ':num_activos_cats_by_cycle' => $address->num_activos_cats_by_cycle,
                ':num_cats_by_cycle' => $address->num_cats_by_cycle,
                ':num_subcats_n2_by_cycle' => $address->num_subcats_n2_by_cycle,
                ':num_subcats_n3_by_cycle' => $address->num_subcats_n3_by_cycle,
                ':num_activos_audit' => $address->num_activos_audit
            ]);
        }

        $this->zones = $zonas;
    }

    public function getZones(): array
    {
        return $this->zones;
    }
}
