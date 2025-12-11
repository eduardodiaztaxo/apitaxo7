<?php

namespace App\Http\Controllers\Api\V1\Maps;

use App\Http\Controllers\Controller;
use App\Models\Maps\MapLandMarker;
use App\Models\Maps\MapPolygonalArea;
use Illuminate\Http\Request;

class MapLandMarkerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $markers = MapLandMarker::all();

        return response()->json(
            $markers,
            200
        );
    }

    public function indexByArea($area_id)
    {
        //
        $area = MapPolygonalArea::find($area_id);


        while ($area->parent_id > 0 && $area->parent_id !== null) {
            $area = MapPolygonalArea::find($area->parent_id);
        }

        if (!$area) {
            return response()->json([
                'message' => 'Area not found'
            ], 404);
        }

        $markers = [];

        $landmarkers = MapLandMarker::all();



        foreach ($landmarkers as $marker) {
            if ($area->isPointInsidePolygon($marker->latitude, $marker->longitude, json_decode($area->area, true))) {
                $markers[] = $marker;
            }
        }

        return response()->json(
            $markers,
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
    }
}
