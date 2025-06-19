<?php

namespace App\Services\Dump\Tables;

use App\Http\Controllers\Api\V1\Comunes\DatosActivosController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class MarcasDumpService implements DumpSQLiteInterface
{


    /**
     * @var \PDO|null PDO connection instance
     */
    protected $pdo = null;

    /**
     * @var int Cycle number
     */
    protected $cycle = 0;

    protected $id_familia = 0;

    public function __construct(PDO $pdo, int $cycle = 0, int $id_familia = 0)
    {
        $this->pdo = $pdo;

        $this->cycle = $cycle;

        $this->id_familia = $id_familia;
    }

    /**
     * Run the assets dump from the controller.
     *
     * This method creates the ciclos table and inserts assets data into it from controller.
     *
     * @return void
     */
    public function runFromController(): void
    {

        $this->createTable();

        $request = new \Illuminate\Http\Request();
        $request->setMethod('GET');

        $datsdActivosCtrl = new DatosActivosController();

        $response = $datsdActivosCtrl->bienes_Marcas($this->cycle, $this->id_familia);

        $jsonContent = $response->getContent();

        // Decodificar el JSON a un arreglo asociativo
        $data = json_decode($jsonContent);

        if (isset($data->status) && $data->status !== 'OK') {
            return;
        }


        $this->insert($data);
    }


    /**
     * Create the assets table if it does not exist.
     *
     * This method creates the assets table with the specified columns and their data types.
     *
     * @return void
     */
    public function createTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS marcas (
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
     * Insert assets into the assets table.
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $cycles Array of cycle objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $marca): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT INTO marcas (
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

        foreach ($marca as $m) {

            $stmt->execute([
                ':idLista' => $m->idLista,
                ':idAtributo' => $m->idAtributo,
                ':idIndice' => $m->idIndice,
                ':descripcion' => $m->descripcion,
                ':id_familia' => $m->id_familia,
                ':ciclo_inventario' => $m->ciclo_inventario,
                ':descripcion_familia' => $m->descripcion_familia,
                ':descripcion_grupo' => $m->descripcion_grupo,
                ':id_grupo' => $m->id_grupo
            ]);
        }
    }
}
