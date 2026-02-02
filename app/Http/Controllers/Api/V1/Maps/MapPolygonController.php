<?php

namespace App\Http\Controllers\Api\V1\Maps;

use App\Http\Controllers\Controller;
use App\Http\Resources\Maps\MapMarkerAssetResource;
use App\Http\Resources\V1\Maps\MapPolygonalAreaResource;
use App\Models\Inventario;
use App\Models\Maps\MapPolygonalArea;
use Illuminate\Http\Request;

class MapPolygonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $areas = MapPolygonalArea::all();


        return response()->json(
            MapPolygonalAreaResource::collection($areas),
            200
        );
    }

    /**
     * Display a listing of the base resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function indexBase()
    {
        $areas = MapPolygonalArea::whereIn('level', [0, 1])->get();


        return response()->json(
            MapPolygonalAreaResource::collection($areas),
            200
        );
    }

    /**
     * Display a listing of the base resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getDescendants($parent_id)
    {

        $parent_area = MapPolygonalArea::find($parent_id);

        if (!$parent_area) {
            return response()->json(['error' => 'Parent area not found'], 404);
        }
    
    
        $childs_areas = MapPolygonalArea::where('parent_id', '=', $parent_id)->get();

        $descendants = $childs_areas;

        $childs = $descendants;

        while ($childs->count() > 0) {

            $parents = $childs;

            $childs = collect([]);

            foreach ($parents as $area) {

                $child_areas = MapPolygonalArea::where('parent_id', '=', $area->id)->get();

                if ($child_areas->count() > 0)
                    $childs = $childs->merge($child_areas);
            }

            if ($childs->count() > 0)
                $descendants = $descendants->merge($childs);
        }

        //Special descendants for level 1 areas (free areas) to include also level 2 areas (shared areas)
        if($parent_area->level === 1){
            $shared_areas = $parent_area->nolimit_shared_areas()->get();
            // $shared_areas = MapPolygonalArea::whereHas('nolimit_shared_areas', function ($query) use ($parent_id) {
            //     $query->where('free_area_id', $parent_id);
            // })->get();

            if ($shared_areas->count() > 0)
                $descendants = $descendants->merge($shared_areas);
        }


        return response()->json(
            MapPolygonalAreaResource::collection($descendants),
            200
        );
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'name' => 'required|string|max:255',
            'area' => 'required|json',
            'parent_id' => 'nullable|exists:map_polygonal_areas,id',
            'level' => 'required|integer|min:0',
        ]);

        if ($request->parent_id) {
            $parent = MapPolygonalArea::find($request->parent_id);
            if (!$parent) {
                return response()->json(['error' => 'Parent area not found'], 404);
            }

            //In this case, level 2 (green areas) only can be under level 0 (city area)
            if($request->level === 2){
                if($parent->level!==0){
                    return response()->json(['error' => 'Green Areas level 2 is under area city level 0'], 422);
                }
            } 
            //El resto de niveles debe ser +1 superior
            else if ($parent->level !== $request->level - 1 && !($parent->level == 1 && $request->level == 10)) {
                return response()->json(['error' => 'Parent area level does not match '], 422);
            }
        } else {
            $parent = null;
        }


        $mapAreaArr = [
            'name' => $request->name,
            'area' => $request->area,
            'parent_id' => $parent ? $parent->id : null,
            'level' => $request->level
        ];

        $mapArea = MapPolygonalArea::create($mapAreaArr);

        $mapArea->updateMinMaxLatLng();

        if($mapArea->level === 2 && $parent){
            //If level 2 area (green area) is created, link it to its parent level 1 area (subcity area)
            $mapArea->make_as_nolimit_shared_area($parent->level+1);
        }

        return response()->json(MapPolygonalAreaResource::make($mapArea), 200);
    }


    /**
     * Display the specified polygon by address id.
     *
     * @param  int  $address_id
     * @return \Illuminate\Http\Response
     */
    public function showPolygonByAddress(Request $request, int $address_id)
    {


        $area = MapPolygonalArea::where('address_id', '=', $address_id)->first();

        if (!$area) {
            return response()->json(['error' => 'Map area not found for the given address'], 404);
        }

        return response()->json(
            MapPolygonalAreaResource::make($area),
            200
        );
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $request->validate([

            'area' => 'required|json',

        ]);


        $mapArea = MapPolygonalArea::find($id);

        if (!$mapArea) {
            return response()->json(['error' => 'Map area not found'], 404);
        }





        $mapAreaArr = [
            'area' => $request->area
        ];

        $mapArea->update($mapAreaArr);

        $mapArea->updateMinMaxLatLng();

        //shared areas need to be re-linked to free areas
        if($mapArea->level === 2){
            $parent = MapPolygonalArea::find($mapArea->parent_id);

            //cicy area is level 0
            if($parent && $parent->level===0){
                //If level 2 area (green area) is updated, re-link it to its parent level 1 area (subcity area)
                $mapArea->make_as_nolimit_shared_area($parent->level+1);
            }
        }

        return response()->json(MapPolygonalAreaResource::make($mapArea), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        if(MapPolygonalArea::find($id)->delete()){
            MapPolygonalArea::deleteNolimitSharedAreasByFreeAreaId($id);
            return response()->json(['message' => 'Map area deleted successfully'], 200);
        } else {
            return response()->json(['error' => 'Map area not found'], 404);
        }
        
    }

    public function showMarkers($id)
    {
        $mapArea = MapPolygonalArea::find($id);

        if (!$mapArea) {
            return response()->json(['error' => 'Map area not found'], 404);
        }

        $markers = $mapArea->markers();

        return response()->json(MapMarkerAssetResource::collection($markers), 200);
    }

    public function showInventoryMarkers($id)
    {
        $mapArea = MapPolygonalArea::find($id);

        if (!$mapArea) {
            return response()->json(['error' => 'Map area not found'], 404);
        }

        $markers = $mapArea->inventory_markers();

        return response()->json(MapMarkerAssetResource::collection($markers), 200);
    }


    public function updateInventoryMarker(Request $request, $id)
    {

        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric'
        ]);

        $asset = Inventario::find($id);

        if (!$asset) {
            return response()->json(['error' => 'asset not found'], 404);
        }

        $asset->adjusted_lat = $request->lat;
        $asset->adjusted_lng = $request->lng;

        $asset->adjusted_at = date('Y-m-d H:i:s');

        $usuario = $request->user()->name;

        $asset->adjusted_origin = 'map_tool';

        $asset->adjusted_by = $usuario;
        
        $asset->save();

        return response()->json($asset, 200);
    }
}
