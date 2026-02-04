<?php

namespace App\Http\Controllers\Api\V1\Maps;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapReportMarkerController extends Controller
{
    
    /**
     * Get Overlapping Markers From Inventory Geolocalization Data
     * 
     * @return \Illuminate\Http\Response 
     */
    public function indexOverlappingInventoryMarkers(Request $request){

        $duplicates = DB::table('inv_inventario')
        ->select(
            'adjusted_lat',
            'adjusted_lng',
            DB::raw('COUNT(*) as total')
        )
        ->whereNotNull('adjusted_lat')
        ->whereNotNull('adjusted_lng')
        ->groupBy('adjusted_lat', 'adjusted_lng')
        ->having('total', '>', 1)
        ->get();

        return response()->json(
            $duplicates,
            200
        );

    }

    public function indexUsersInventoryMarkers(){

        $usersMarkers = DB::table('inv_inventario')
        ->select(
            'adjusted_by',
            DB::raw('COUNT(*) as total_markers')
        )
        ->whereNotNull('adjusted_by')
        ->groupBy('adjusted_by')
        ->get();

        return response()->json(
            $usersMarkers,
            200
        );

    }
}
