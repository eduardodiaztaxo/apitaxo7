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
        $newCodigo = DB::table('ubicaciones_n2')
            // ->where('idAgenda', $zona->idAgenda)
            ->where('codigoUbicacion', 'like', "{$zona->codigoUbicacion}%") 
            ->selectRaw("
                IFNULL(
                    CONCAT(
                        '{$zona->codigoUbicacion}', 
                        LPAD(MAX(CAST(SUBSTRING(codigoUbicacion, 3, 2) AS UNSIGNED)) + 1, 2, '0')
                    ), 
                    '{$zona->codigoUbicacion}01'
                ) AS newCodigo
            ")
            ->value('newCodigo');
    
        return $newCodigo;
    }
    


    public function getNewZoneCode(UbicacionGeografica $punto) 
    {

        DB::beginTransaction();
    
        try {

            $zona = ZonaPunto::select(DB::raw('(MAX(codigoUbicacion) + 1) AS max_code'))
                ->where('idAgenda', '=', $punto->idUbicacionGeo)
                ->lockForUpdate()  
                ->first();
    

            $newCode = $zona->max_code ? str_pad($zona->max_code, 2, '0', STR_PAD_LEFT) : '01';

            DB::commit();
    
            return $newCode;
    
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; 
        }
    }
    
}
