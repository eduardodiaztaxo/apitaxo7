<?php

namespace App\Services\Dump\Tables;

use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class ConteoRegistroDumpService implements DumpSQLiteInterface
{
    /**
     * @var \PDO|null
     */
    protected $pdo = null;

    /**
     * @var int
     */
    protected $cycle = 0;

    public function __construct(PDO $pdo, int $cycle = 0)
    {
        $this->pdo = $pdo;
        $this->cycle = $cycle;
    }

    public function runFromController(): void
    {
        $this->createTable();

        $Registros = \App\Models\InvConteoRegistro::where('ciclo_id', $this->cycle)->where('status', 1)->get();

        $this->insert($Registros->toArray());
    }

    public function createTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS conteo_registro (
                id INTEGER,
                ciclo_id INTEGER,
                punto_id INTEGER,
                etiqueta TEXT,
                status INTEGER,
                audit_status INTEGER,
                cod_zona TEXT,
                cod_emplazamiento TEXT,
                cod_subemplazamiento TEXT,
                user_id INTEGER,
                created_at TEXT,
                updated_at TEXT
            );
        ");
    }

    /**
     * Inserta las categorías de ciclos en la tabla.
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection|array $Registros
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $Registros): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO conteo_registro (
                id,
                ciclo_id,
                punto_id,
                etiqueta,
                status,
                audit_status,
                cod_zona,
                cod_emplazamiento,
                cod_subemplazamiento,
                user_id,
                created_at,
                updated_at
            ) VALUES (
                :id,
                :ciclo_id,
                :punto_id,
                :etiqueta,
                :status,
                :audit_status,
                :cod_zona,
                :cod_emplazamiento,
                :cod_subemplazamiento,
                :user_id,
                :created_at,
                :updated_at
            )
        ");

        foreach ($Registros as $Reg) {
            $c = is_object($Reg) && method_exists($Reg, 'toJson')
                ? json_decode($Reg->toJson())
                : (object) $Reg;

            $stmt->execute([
                'id' => $c->id,
                'ciclo_id' => $this->cycle,
                'punto_id' => $c->punto_id,
                'etiqueta' => $c->etiqueta,
                'status' => $c->status,
                'audit_status' => $c->audit_status,
                'cod_zona' => $c->cod_zona,
                'cod_emplazamiento' => $c->cod_emplazamiento,
                'cod_subemplazamiento' => $c->cod_subemplazamiento,
                'user_id' => $c->user_id,
                'created_at' => $c->created_at,
                'updated_at' => $c->updated_at
            ]);
        }
    }
}
