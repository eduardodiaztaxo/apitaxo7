<?php

namespace App\Http\Controllers\Api\V1\Maps;

use App\Http\Controllers\Controller;
use App\Http\Resources\Maps\MapMarkerAssetResource;
use App\Models\Maps\MapMarkerAsset;
use Illuminate\Http\Request;

class MapMarkerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $markers = MapMarkerAsset::all();


        return response()->json(
            MapMarkerAssetResource::collection($markers),
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
            'category_id' => 'required|integer',
            // 'lat' => 'required|numeric|min:-90|max:90',
            // 'lng' => 'required|numeric|min:-180|max:180',
        ]);

        $makerArr = $request->only(['name', 'category_id', 'lat', 'lng']);
        $marker = MapMarkerAsset::create($makerArr);

        return response()->json(
            MapMarkerAssetResource::make($marker),
            201
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
            'lat' => 'required|numeric|min:-90|max:90',
            'lng' => 'required|numeric|min:-180|max:180',
        ]);

        $marker = MapMarkerAsset::find($id);

        if (!$marker) {
            return response()->json(['error' => 'Marker not found'], 404);
        }

        $marker->update($request->only(['lat', 'lng']));

        return response()->json(MapMarkerAssetResource::make($marker), 200);
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
        return MapMarkerAsset::find($id)->delete() ?
            response()->json(['message' => 'Marker deleted successfully'], 200) :
            response()->json(['error' => 'Marker not found'], 404);
    }
}
