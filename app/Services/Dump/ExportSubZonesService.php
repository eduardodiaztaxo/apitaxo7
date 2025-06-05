<?php

namespace App\Services\Dump;

use App\Services\Dump\Tables\SubZonesDumpService;
use PDO;

class ExportSubZonesService
{
    /**
     * Exporta subzonas a una base SQLite usando los IDs de ubicación geo.
     *
     * @param array $idsUbicacionGeo
     * @param string $sqlitePath Ruta absoluta al archivo SQLite
     * @return void
     */
    public function export(array $idsUbicacionGeo, string $sqlitePath): void
    {
        $pdo = new PDO('sqlite:' . $sqlitePath);
        $subZonesService = new SubZonesDumpService($pdo);
        $subZonesService->setIdsUbicacionGeo($idsUbicacionGeo);
        $subZonesService->runFromController();
    }
}