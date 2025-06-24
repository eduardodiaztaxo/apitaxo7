<?php

namespace App\Services\Dump\Tables;

use App\Http\Controllers\Api\V1\Comunes\DatosActivosController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class BienGrupoFamiliaDumpService implements DumpSQLiteInterface
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
     * Run dump from the controller.
     *
     * This method creates the ciclos table and inserts data into it from controller.
     *
     * @return void
     */
    public function runFromController(): void
    {

        $this->createTable();

        $request = new \Illuminate\Http\Request();
        $request->setMethod('GET');

        $datsdActivosCtrl = new DatosActivosController();

        $response = $datsdActivosCtrl->bienesGrupoFamilia($this->cycle);

        $jsonContent = $response->getContent();

        $data = json_decode($jsonContent);

        if (isset($data->status) && $data->status !== 'OK') {
            return;
        }


        $this->insert($data);
    }


    /**
     * Create table if it does not exist.
     *
     * This method creates table with the specified columns and their data types.
     *
     * @return void
     */
    public function createTable(): void
    {

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS BienGrupoFamilia (
                idLista INTEGER PRIMARY KEY,
                idAtributo INTEGER DEFAULT 0,
                idIndice INTEGER DEFAULT 0,
                descripcion TEXT,
                id_familia INTEGER DEFAULT 0,
                ciclo_inventario INTEGER DEFAULT 0,
                descripcion_familia TEXT,
                descripcion_grupo TEXT,
                id_grupo INTEGER DEFAULT 0
            );
        ");
    }

    /**
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $cycles Array of cycle objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $BGF): void
    {

        $stmt = $this->pdo->prepare("
            INSERT INTO BienGrupoFamilia (
             idLista,
             idAtributo,
             idIndice,
             descripcion,
             id_familia,
             ciclo_inventario,
             descripcion_familia,
             descripcion_grupo,
             id_grupo
            )
            VALUES (
                :idLista,
                :idAtributo,
                :idIndice,
                :descripcion,
                :id_familia,
                :ciclo_inventario,
                :descripcion_familia,
                :descripcion_grupo,
                :id_grupo
            )
        ");

        foreach ($BGF as $B) {

            $stmt->execute([
                ':idLista' => $B->idLista,
                ':idAtributo' => $B->idAtributo,
                ':idIndice' => $B->idIndice,
                ':descripcion' => $B->descripcion,
                ':id_familia' => $B->id_familia,
                ':ciclo_inventario' => $B->ciclo_inventario,
                ':descripcion_familia' => $B->descripcion_familia,
                ':descripcion_grupo' => $B->descripcion_grupo,
                ':id_grupo' => $B->id_grupo
            ]);
        }
    }
}
