<?php

namespace App\Services\Dump\Tables;

use App\Http\Controllers\Api\V1\Comunes\DatosActivosController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class TipoTrabajoDumpService implements DumpSQLiteInterface
{


    /**
     * @var \PDO|null PDO connection instance
     */
    protected $pdo = null;


 public function __construct(PDO $pdo)
{
    $this->pdo = $pdo;

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

       $response = $datsdActivosCtrl->tipoTrabajo();

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
            CREATE TABLE IF NOT EXISTS tipoTrabajo (
                idLista INTEGER PRIMARY KEY,
                descripcion TEXT NOT NULL
            );
        ");
    }

    /*
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $cycles Array of cycle objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $tipo): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT INTO tipoTrabajo (
                idLista,
                descripcion
            )
            VALUES (
               :idLista,
               :descripcion
            )
        ");

        foreach ($tipo as $t) {

            $stmt->execute([
                ':idLista' => $t->idLista,
                ':descripcion' => $t->descripcion
            ]);
        }
    }
}
