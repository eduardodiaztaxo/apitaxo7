<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ResponsibleResource;
use App\Http\Resources\V1\UbicacionGeograficaResource;
use App\Models\Responsable;
use App\Models\UbicacionGeografica;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ResponsibleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\UbicacionGeografica  $punto
     * @return \Illuminate\Http\Response
     */
    public function showAllByPunto(Request $request, int $punto)
    {

        $objPunto = UbicacionGeografica::find($punto);


        if (!$objPunto) {
            return response()->json([
                'message' => 'Dirección/Punto no encontrado'
            ], 404);
        }

        if ($request->keyword && $request->keyword != '' && strlen($request->keyword) > 2) {

            //delete spaces and dots
            $possible_rut = str_replace([' ', '.'], '', $request->keyword);

            $responsibles = $objPunto->responsibles()
                ->where('rut', 'like', '%' . $possible_rut . '%');

            $possible_name_words = explode(' ', $request->keyword);

            $responsibles = $responsibles->orWhere(function ($query) use ($possible_name_words) {
                foreach ($possible_name_words as $palabra) {
                    $query->where('name', 'LIKE', "%$palabra%");
                }
            });


            $responsibles = $responsibles->get();
        } else {
            $responsibles = $objPunto->responsibles()->get();
        }




        return response()->json(ResponsibleResource::collection($responsibles), 200);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function showAll(Request $request)
    {

        $responsibles = Responsable::query();

        if ($request->keyword && $request->keyword != '' && strlen($request->keyword) > 2) {

            //delete spaces and dots
            $possible_rut = str_replace([' ', '.'], '', $request->keyword);

            $responsibles = $responsibles
                ->where('rut', 'like', '%' . $possible_rut . '%');

            $possible_name_words = explode(' ', $request->keyword);

            $responsibles = $responsibles->orWhere(function ($query) use ($possible_name_words) {
                foreach ($possible_name_words as $palabra) {
                    $query->where('name', 'LIKE', "%$palabra%");
                }
            });


            $responsibles = $responsibles->get();
        } else {
            $responsibles = $responsibles->get();
        }




        return response()->json(ResponsibleResource::collection($responsibles), 200);
    }


    /**
     * show a resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, int $id)
    {
        $responsable = Responsable::find($id);

        if (!$responsable) {
            return response()->json([
                'status' => 'error',
                'No se pudo encontrar el responsable',
                404
            ], 404);
        }

        return response()->json(
            ResponsibleResource::make($responsable),
            200
        );
    }




    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $request->validate([
            'name'      => 'required|string',
            'mail'      => 'required|email',
            'rut'       => 'required|unique:responsables,rut',
            'address'   => 'required|exists:ubicaciones_geograficas,idUbicacionGeo'
        ]);

        $address = UbicacionGeografica::find($request->address);

        $data = [
            'name'                  => strtoupper($request->name),
            'mail'                  => $request->mail,
            'rut'                   => $request->rut,
            'idUbicacionGeografica' => $request->address,
            'idRegion'              => $address->region,
            'idComuna'              => $address->comuna
        ];

        $responsable = Responsable::create($data);

        if (!$responsable) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo crear el responsable',
                422
            ], 422);
        }

        return response()->json(
            [
                'status' => 'OK',
                'message' => 'Creado Exitosamente',
                'code'  => 201,
                'data' => ResponsibleResource::make($responsable)
            ],
            201
        );
    }


    /**
     * update a resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $request->validate([

            'name'      => 'required|string',
            'mail'      => 'required|email',
            'rut'       => [
                'required',
                Rule::unique('responsables')->ignore($id, 'idResponsable')
            ],
            'address'   => 'required|exists:ubicaciones_geograficas,idUbicacionGeo'
        ]);


        $responsable = Responsable::find($id);



        if (!$responsable) {
            return response()->json([
                'status' => 'error',
                'No se pudo encontrar el responsable',
                404
            ], 404);
        }

        $address = UbicacionGeografica::find($request->address);

        $data = [
            'name'                  => strtoupper($request->name),
            'mail'                  => $request->mail,
            'rut'                   => $request->rut,
            'idUbicacionGeografica' => $request->address,
            'idRegion'              => $address->region,
            'idComuna'              => $address->comuna
        ];

        $responsable->update($data);

        $responsable->save();



        return response()->json(

            [
                'status' => 'OK',
                'message' => 'Actualizado Exitosamente',
                'code'  => 200,
                'data' => ResponsibleResource::make($responsable)
            ],
            200
        );
    }


    /**
     * Register Signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function registerSignature(Request $request, int $responsable_id)
    {

        $request->validate([


            'signature' => [
                'required',
                'string',
                'regex:/^data:image\/png;base64,/'
            ],

        ]);


        $responsable = Responsable::find($responsable_id);

        if (!$responsable) {
            return response()->json([
                'message' => 'Responsable not found'
            ], 404);
        }

        $responsable->signature = $request->signature;


        $responsable->save();

        return response()->json(
            $responsable,
            200
        );
    }
}
