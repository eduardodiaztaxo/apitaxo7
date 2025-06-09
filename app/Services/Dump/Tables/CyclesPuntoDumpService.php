<?php

namespace App\Services\Dump\Tables;

use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class CyclesPuntoDumpService implements DumpSQLiteInterface
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

        $puntos = \App\Models\InvCicloPunto::where('idCiclo', $this->cycle)->get();

        $this->insert($puntos->toArray());
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
    $this->pdo->exec("
        CREATE TABLE IF NOT EXISTS ciclos_puntos (
            idCiclo INTEGER NOT NULL,
            idPunto INTEGER NOT NULL,
            id_estado INTEGER DEFAULT 0,
            auditoria_general INTEGER DEFAULT 0,
            PRIMARY KEY (idCiclo, idPunto)
        );
    ");
}
    /**
     * Insert cycles into the ciclos table.
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $cycles Array of cycle objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $cyclesPunto): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT INTO ciclos_puntos (
                idCiclo,
                idPunto,
                id_estado,
                auditoria_general
            )
            VALUES (
                :idCiclo,
                :idPunto,
                :id_estado,
                :auditoria_general
            )
        ");



        foreach ($cyclesPunto as $ciclo) {

          $cycle = (object) $ciclo;
            $stmt->execute([
                ':idCiclo'          => $cycle->idCiclo ?? null,
                ':idPunto'          => $cycle->idPunto ?? null,
                ':id_estado'        => $cycle->id_estado ?? 0,
                ':auditoria_general' => $cycle->auditoria_general ?? 0,
            ]);
        }
    }
}
