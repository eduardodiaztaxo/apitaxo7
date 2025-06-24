<?php

namespace App\Services\Dump\Tables;

use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;


class MarcasInventarioDumpService
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function runFromController(): void
    {

        $this->createTable();

    }
    
    public function createTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS marcasInventario (
                idLista INTEGER PRIMARY KEY,
                idIndice INTEGER NOT NULL,
                descripcion TEXT NOT NULL,
                observacion TEXT NOT NULL,
                idAtributo INTEGER NOT NULL,
                id_familia INTEGER NOT NULL,
                ciclo_inventario INTEGER NOT NULL
            )
        ");
    }
}
