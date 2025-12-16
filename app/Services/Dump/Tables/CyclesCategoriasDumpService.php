<?php

namespace App\Services\Dump\Tables;

use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Services\ActivoFinderService;
use App\Services\ProyectoUsuarioService;
use Illuminate\Support\Facades\DB;
use PDO;

class CyclesCategoriasDumpService implements DumpSQLiteInterface
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

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $categorias = \App\Models\Inv_ciclos_categorias::where('idCiclo', $this->cycle)->where('id_proyecto', $id_proyecto)->get();

        $this->insert($categorias->toArray());
    }

    /**
     * Crea la tabla de categorías de ciclos si no existe.
     */
    public function createTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS ciclos_categorias (
                idCiclo INTEGER,
                id_proyecto INTEGER,
                categoria1 TEXT,
                categoria2 TEXT,
                categoria3 TEXT,
                fechaAsignacion TEXT,
                usuario TEXT,
                id_grupo INTEGER,
                id_familia INTEGER
            );
        ");
    }

    /**
     * Inserta las categorías de ciclos en la tabla.
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection|array $categorias
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $categorias): void
    {
        $stmt = $this->pdo->prepare("
            INSERT OR REPLACE INTO ciclos_categorias (
                idCiclo,
                id_proyecto,
                categoria1,
                categoria2,
                categoria3,
                fechaAsignacion,
                usuario,
                id_grupo,
                id_familia
            ) VALUES (
                :idCiclo,
                :id_proyecto,
                :categoria1,
                :categoria2,
                :categoria3,
                :fechaAsignacion,
                :usuario,
                :id_grupo,
                :id_familia
            )
        ");

        foreach ($categorias as $cat) {
            $c = is_object($cat) && method_exists($cat, 'toJson')
                ? json_decode($cat->toJson())
                : (object) $cat;

            $stmt->execute([
                ':idCiclo'         => $c->idCiclo ?? null,
                ':id_proyecto'     => $c->id_proyecto ?? null,
                ':categoria1'      => $c->categoria1 ?? null,
                ':categoria2'      => $c->categoria2 ?? null,
                ':categoria3'      => $c->categoria3 ?? null,
                ':fechaAsignacion' => $c->fechaAsignacion ?? null,
                ':usuario'         => $c->usuario ?? null,
                ':id_grupo'        => $c->id_grupo ?? null,
                ':id_familia'      => $c->id_familia ?? null,
            ]);
        }
    }
}