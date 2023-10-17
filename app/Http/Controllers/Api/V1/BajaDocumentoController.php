<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\BajaDocumentoResource;
use App\Models\BajaDocumento;
use Illuminate\Http\Request;

class BajaDocumentoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return BajaDocumentoResource::collection( BajaDocumento::paginate() );
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
     * @param  \App\Models\BajaDocumento  $bajaDocumento
     * @return \Illuminate\Http\Response
     */
    public function show(BajaDocumento $bajaDocumento)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BajaDocumento  $bajaDocumento
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BajaDocumento $bajaDocumento)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BajaDocumento  $bajaDocumento
     * @return \Illuminate\Http\Response
     */
    public function destroy(BajaDocumento $bajaDocumento)
    {
        //
    }
}
