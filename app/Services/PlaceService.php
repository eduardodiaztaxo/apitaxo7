<?php

namespace App\Services;

use App\Models\CrudActivo;
use App\Models\Emplazamiento;
use App\Models\InvCiclo;
use App\Models\UbicacionGeografica;
use App\Models\User;
use App\Models\ZonaPunto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlaceService
{
    public function getNewEmplaCode(ZonaPunto $zona)
    {

        $empla = Emplazamiento::select(DB::raw('( MAX( SUBSTRING(codigoUbicacion,3,2) ) + 1 ) AS max_code'))
            ->where('idAgenda', '=', $zona->idAgenda)
            ->whereRaw('SUBSTRING(codigoUbicacion,1,2) = ?', [$zona->codigoUbicacion])
            ->first();

        return $empla->max_code ? $zona->codigoUbicacion . str_pad($empla->max_code, 2, '0', STR_PAD_LEFT) : $zona->codigoUbicacion . '01';
    }


    public function getNewZoneCode(UbicacionGeografica $punto)
    {

        $zona = ZonaPunto::select(DB::raw('( MAX( codigoUbicacion ) + 1 ) AS max_code'))
            ->where('idAgenda', '=', $punto->idUbicacionGeo)
            ->first();

        return $zona->max_code ? str_pad($zona->max_code, 2, '0', STR_PAD_LEFT) : '01';
    }
}
