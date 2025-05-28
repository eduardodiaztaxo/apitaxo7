<?php

namespace App\Services\Dump\Tables;

use App\Http\Controllers\Api\V1\CiclosController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class CyclesDumpService implements DumpSQLiteInterface
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
     * Run the cycle dump from the controller.
     *
     * This method creates the ciclos table and inserts cycles data into it from controller.
     *
     * @return void
     */
    public function runFromController(): void
    {

        $this->createTable();

        $cyclesCtrl = new CiclosController();

        $cycles = $cyclesCtrl->index();

        $this->insert($cycles);
    }


    /**
     * Create the ciclos table if it does not exist.
     *
     * This method creates the ciclos table with the specified columns and their data types.
     *
     * @return void
     */
    public function createTable(): void
    {


        // Create "ciclos" table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS ciclos (
                idCiclo INTEGER PRIMARY KEY,
                status INTEGER DEFAULT NULL,
                tipoCiclo INTEGER DEFAULT 0,
                status_name TEXT,
                title TEXT,
                date DATE,
                date_end DATE,
                assets_cycle INTEGER DEFAULT 0,
                puntos_count INTEGER DEFAULT 0,
                audith_count INTEGER DEFAULT 0
            );
        ");
    }

    /**
     * Insert cycles into the ciclos table.
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $cycles Array of cycle objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $cycles): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT INTO ciclos (
                idCiclo,
                status,
                tipoCiclo,
                status_name,
                title,
                date,
                date_end,
                assets_cycle,
                puntos_count,
                audith_count
            )
            VALUES (
                :idCiclo,
                :status,
                :tipoCiclo,
                :status_name,
                :title,
                :date,
                :date_end,
                :assets_cycle,
                :puntos_count,
                :audith_count
            )
        ");



        foreach ($cycles as $ciclo) {

            $cycle = json_decode($ciclo->toJson());


            $stmt->execute([
                ':idCiclo' => $cycle->idCiclo,
                ':status' => $cycle->status,
                ':tipoCiclo' => $cycle->tipoCiclo,
                ':status_name' => $cycle->status_name,
                ':title' => $cycle->title,
                ':date' => $cycle->date,
                ':date_end' => $cycle->date_end,
                ':assets_cycle' => $cycle->assets_cycle,
                ':puntos_count' => $cycle->puntos_count,
                ':audith_count' => $cycle->audith_count
            ]);
        }
    }
}
