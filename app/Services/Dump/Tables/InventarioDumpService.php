<?php

namespace App\Services\Dump\Tables;

use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;


class InventarioDumpService
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crea la tabla bienesInventario si no existe.
     */
    public function createTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS inventario (
                id_inventario INTEGER PRIMARY KEY,
                id_grupo INTEGER NOT NULL,
                id_familia INTEGER NOT NULL,
                descripcion_bien TEXT NOT NULL,
                descripcion_marca TEXT NOT NULL,
                idForma INTEGER NOT NULL,
                idMaterial INTEGER NOT NULL,
                etiqueta TEXT NOT NULL,
                modelo TEXT NOT NULL,
                serie TEXT NOT NULL,
                capacidad TEXT NOT NULL,
                estado INTEGER NOT NULL,
                color INTEGER NOT NULL,
                tipo_trabajo INTEGER NOT NULL,
                carga_trabajo INTEGER NOT NULL,
                estado_operacional INTEGER NOT NULL,
                estado_conservacion INTEGER NOT NULL,
                condicion_Ambiental INTEGER NOT NULL,
                cantidad_img INTEGER NOT NULL,
                id_img TEXT NOT NULL,
                id_ciclo INTEGER NOT NULL,
                codigoUbicacion INTEGER NOT NULL,
                codigoUbicacion_N1 INTEGER NOT NULL,
                responsable TEXT NOT NULL,
                descripcionTipo TEXT NOT NULL,
                observacion TEXT NOT NULL,
                latitud TEXT NOT NULL,
                longitud TEXT NOT NULL
            )
        ");
    }
}
