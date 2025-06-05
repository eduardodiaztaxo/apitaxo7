<?php

namespace App\Services\Dump\Tables;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\V1\ZonaEmplazamientosController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class SubZonesDumpService implements DumpSQLiteInterface
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
     * @var array IDs de ubicaciones geográficas
     */
    protected $idsUbicacionGeo = [];

    public function __construct(PDO $pdo, int $cycle = 0)
    {
        $this->pdo = $pdo;
        $this->cycle = $cycle;
    }

    /**
     * Run the subzones dump from the controller.
     *
     * @return void
     */

  

    public function runFromController(): void
    {
        $this->createTable();

        $subzonas = \DB::table('ubicaciones_n2')->get();

        $this->insert($subzonas->toArray());
    }


    /**
     * Create the subzones table if it does not exist.
     *
     * @return void
     */
    public function createTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS subzonas (
                idUbicacionN2 INTEGER PRIMARY KEY,
                idProyecto INTEGER,
                idAgenda INTEGER,
                codigoUbicacion TEXT,
                descripcionUbicacion TEXT,
                estado INTEGER,
                fechaCreacion TEXT,
                fechaActualizacion TEXT,
                usuario TEXT,
                codigo TEXT,
                idResponsable INTEGER,
                idCentroCosto INTEGER,
                usar INTEGER,
                totalBienes INTEGER,
                fechaPlancheta TEXT,
                usuarioPlancheta TEXT,
                ciclo_auditoria INTEGER
            );
        ");
    }

    /**
     * Insert subzones into the subzonas table.
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection|array $subzones
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $subzones): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO subzonas (
                idUbicacionN2,
                idProyecto,
                idAgenda,
                codigoUbicacion,
                descripcionUbicacion,
                estado,
                fechaCreacion,
                fechaActualizacion,
                usuario,
                codigo,
                idResponsable,
                idCentroCosto,
                usar,
                totalBienes,
                fechaPlancheta,
                usuarioPlancheta,
                ciclo_auditoria
            ) VALUES (
                :idUbicacionN2,
                :idProyecto,
                :idAgenda,
                :codigoUbicacion,
                :descripcionUbicacion,
                :estado,
                :fechaCreacion,
                :fechaActualizacion,
                :usuario,
                :codigo,
                :idResponsable,
                :idCentroCosto,
                :usar,
                :totalBienes,
                :fechaPlancheta,
                :usuarioPlancheta,
                :ciclo_auditoria
            )
        ");

        foreach ($subzones as $subzone) {
            $sz = is_object($subzone) && method_exists($subzone, 'toJson')
                ? json_decode($subzone->toJson())
                : (object) $subzone;

            $stmt->execute([
                ':idUbicacionN2'      => $sz->idUbicacionN2 ?? null,
                ':idProyecto'         => $sz->idProyecto ?? null,
                ':idAgenda'           => $sz->idAgenda ?? null,
                ':codigoUbicacion'    => $sz->codigoUbicacion ?? null,
                ':descripcionUbicacion' => $sz->descripcionUbicacion ?? null,
                ':estado'             => $sz->estado ?? null,
                ':fechaCreacion'      => $sz->fechaCreacion ?? null,
                ':fechaActualizacion' => $sz->fechaActualizacion ?? null,
                ':usuario'            => $sz->usuario ?? null,
                ':codigo'             => $sz->codigo ?? null,
                ':idResponsable'      => $sz->idResponsable ?? null,
                ':idCentroCosto'      => $sz->idCentroCosto ?? null,
                ':usar'               => $sz->usar ?? null,
                ':totalBienes'        => $sz->totalBienes ?? null,
                ':fechaPlancheta'     => $sz->fechaPlancheta ?? null,
                ':usuarioPlancheta'   => $sz->usuarioPlancheta ?? null,
                ':ciclo_auditoria'    => $sz->ciclo_auditoria ?? null,
            ]);
        }
    }
}