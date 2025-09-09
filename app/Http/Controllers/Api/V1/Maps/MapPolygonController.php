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
            //EL nivel debe ser +1 superior
            if ($parent->level !== $request->level - 1 && !($parent->level == 1 && $request->level == 10)) {
                return response()->json(['error' => 'Parent area level does not match '], 422);
            }
        } else {
            $parent = null;
        }

        $min_lat = 0;
        $max_lat = 0;
        $min_lng = 0;
        $max_lng = 0;

        $area = json_decode($request->area, true);
        //ojo con los polígonos que pasan desde -180 hacia más a la izquierda
        //y desde 180 hacia más a la derecha, ya que pueden tener coordenadas negativas
        //y positivas, por lo que se debe calcular el mínimo y máximo de latitud y
        //longitud de forma adecuada.
        if (is_array($area) && count($area) > 0) {
            $min_lat = min(array_column($area, 'lat'));
            $max_lat = max(array_column($area, 'lat'));
            $min_lng = min(array_column($area, 'lng'));
            $max_lng = max(array_column($area, 'lng'));
        }

        $mapAreaArr = [
            'name' => $request->name,
            'area' => $request->area,
            'parent_id' => $parent ? $parent->id : null,
            'level' => $request->level,
            'min_lat' => $min_lat,
            'max_lat' => $max_lat,
            'min_lng' => $min_lng,
            'max_lng' => $max_lng,
        ];

        $mapArea = MapPolygonalArea::create($mapAreaArr);

        return response()->json(MapPolygonalAreaResource::make($mapArea), 200);
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
        //
        return MapPolygonalArea::find($id)->delete() ?
            response()->json(['message' => 'Map area deleted successfully'], 200) :
            response()->json(['error' => 'Map area not found'], 404);
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

        $asset->latitud = $request->lat;
        $asset->longitud = $request->lng;

        $asset->save();

        return response()->json($asset, 200);
    }
}
