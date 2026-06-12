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

        $source = $request->query('source', 'inventario');
        $table = ($source === 'activos') ? 'map_crud_activos' : 'inv_inventario';

        $duplicates = DB::table($table)
            ->select('adjusted_lat', 'adjusted_lng', DB::raw('COUNT(*) as total'))
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

    /**
     * Get Users With Inventory Markers Adjusted
     * 
     * @return \Illuminate\Http\Response 
     */
    public function indexUsersInventoryMarkers(Request $request){

        $source = $request->query('source', 'inventario');
        $table = ($source === 'activos') ? 'map_crud_activos' : 'inv_inventario';

        $usersMarkers = DB::table($table)
            ->select('adjusted_by', DB::raw('COUNT(*) as total_markers'))
            ->whereNotNull('adjusted_by')
            ->groupBy('adjusted_by')
            ->get();


        return response()->json(
            $usersMarkers,
            200
        );

    }
}
