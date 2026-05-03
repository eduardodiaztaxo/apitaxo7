<?php

namespace App\Services\Dump\Tables;


use App\Http\Controllers\Api\V1\InventariosOfflineController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class EmplazamientoNnDumpService implements DumpSQLiteInterface
{


    /**
     * @var \PDO|null PDO connection instance
     */
    protected $pdo = null;

    /**
     * @var int Cycle number
     */
    protected $cycle = 0;

    /**
     * @var int level number
     */
    protected $level = 1;


    public function __construct(PDO $pdo, int $cycle = 0)
    {
        $this->pdo = $pdo;

        $this->cycle = $cycle;
    }


    public function setLevel(int $level)
    {
        $this->level = $level;
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

        $response = $zonasEmplaCtrl->CycleCatsNn($this->cycle, $this->level);

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
            CREATE TABLE IF NOT EXISTS emplazamientosN" . $this->level . " (
                id INTEGER PRIMARY KEY,
                codigo TEXT,
                codigoUbicacion TEXT,
                nombre TEXT,
                idAgenda INTEGER,
                idUbicacionN" . $this->level . " INTEGER,
                num_activos INTEGER DEFAULT 0,
                num_activos_cats_by_cycle INTEGER DEFAULT 0,
                ciclo_auditoria INTEGER DEFAULT 0,
                num_categorias INTEGER DEFAULT 0,
                num_activos_audit INTEGER DEFAULT 0,
                habilitadoNivel3 INTIGER DEFAULT 0,
                detalle TEXT,
                num_nivel TEXT,
                next_level TEXT,
                newApp INTIGER DEFAULT 0,
                modo TEXT,
                offline INTEGER
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
            INSERT OR REPLACE INTO emplazamientosN" . $this->level . " (
                id,
                codigo,
                codigoUbicacion,
                nombre,
                idAgenda,
                idUbicacionN" . $this->level . ",
                num_activos,
                num_activos_cats_by_cycle,
                ciclo_auditoria,
                num_categorias,
                num_activos_audit,
                habilitadoNivel3,
                detalle,
                num_nivel,
                next_level,
                newApp,
                modo,
                offline
            )
            VALUES (
                :id,
                :codigo,
                :codigoUbicacion,
                :nombre,
                :idAgenda,
                :idUbicacionN" . $this->level . ",
                :num_activos,
                :num_activos_cats_by_cycle,
                :ciclo_auditoria,
                :num_categorias,
                :num_activos_audit,
                :habilitadoNivel3,
                :detalle,
                :num_nivel,
                :next_level,
                :newApp,
                :modo,
                :offline
            )  
        ");

        $idPropiedad = 'idUbicacionN' . $this->level . '';
        $habilitadoPropiedad = 'habilitadoNivel' . ($this->level + 1) . '';


        foreach ($emplazamientos as $emplazamiento) {



            $stmt->execute([
                ':id' => $emplazamiento->id,
                ':codigo' => $emplazamiento->codigo,
                ':codigoUbicacion' => $emplazamiento->codigoUbicacion,
                ':nombre' => $emplazamiento->nombre,
                ':idAgenda' => $emplazamiento->idAgenda,
                ':idUbicacionN' . $this->level . '' => $emplazamiento->{$idPropiedad},
                ':num_activos' => $emplazamiento->num_activos,
                ':num_activos_cats_by_cycle' => $emplazamiento->num_activos_cats_by_cycle,
                ':ciclo_auditoria' => $emplazamiento->ciclo_auditoria,
                ':num_categorias' => $emplazamiento->num_categorias,
                ':num_activos_audit' => $emplazamiento->num_activos_audit,
                ':habilitadoNivel3' => $emplazamiento->{$habilitadoPropiedad},
                ':detalle' => $emplazamiento->detalle,
                ':num_nivel' => $emplazamiento->num_nivel,
                ':next_level' => $emplazamiento->next_level,
                ':newApp' => $emplazamiento->newApp,
                ':modo' => $emplazamiento->modo,
                ':offline' => 0
            ]);
        }
    }
}
