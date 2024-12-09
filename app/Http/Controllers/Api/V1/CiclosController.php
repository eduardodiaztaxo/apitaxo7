<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\InvCicloResource;
use App\Models\InvCiclo;
use App\Models\InvCicloUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CiclosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return InvCicloResource::collection(InvCiclo::all());
    }


    public function indexByUser(Request $request)
    {




        $username = $request->user()->name;

        $ciclos_ids = InvCicloUser::where('usuario', $username)->get()->pluck('ciclo_id');


        //no tomar cerrados
        $inventarios = InvCiclo::where('estadoCiclo', '<>', 2)->whereIn('idCiclo', $ciclos_ids->toArray())->get();



        return InvCicloResource::collection($inventarios);
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
    public function show($ciclo)
    {
        //
        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'message' => 'Not Found', 'code' => 404], 404);
        }





        return response()->json(InvCicloResource::make($cicloObj), 200);
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
