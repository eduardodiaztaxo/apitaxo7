<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RespLocalizacionResource;
use App\Models\RespLocalizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RespLocalizacionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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
        if($request->adicionales){
            $request->merge(['adicionales' => json_encode($request->adicionales)]);
        }

        $request->validate($this->rules());

        
        //
        $activo = [
            'tratamiento'                   => $request->tratamiento, 
            'sociedad'                      => $request->sociedad, 
            'centro'                        => $request->centro, 
            'localizacionFisica'            => $request->localizacionFisica, 
            'ccosto'                        => $request->ccosto, 
            'denominacionLocalizacion'      => $request->denominacionLocalizacion, 
            'denominacionCentroCosto'       => $request->denominacionCentroCosto, 
            'tipo'                          => $request->tipo, 
            'status'                        => $request->status, 
            'region'                        => $request->region, 
            'comuna'                        => $request->comuna, 
            'calle'                         => $request->calle, 
            'correoElectronicoResponsable'  => $request->correoElectronicoResponsable, 
            'adicionales'                   => $request->adicionales ? $request->adicionales : null,
        ];

        $asset = RespLocalizacion::create($activo);

        return new RespLocalizacionResource($asset);
    }

    /**
     * Store newly created resources in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeMultiple(Request $request)
    {

        if($request->items){
            $request->merge(['items' => json_encode($request->items)]);
        }


        $request->validate([
            'items'   => 'required|json'
        ]);


        $items = json_decode($request->items);

        $assets = [];
        $errors = [];

        foreach($items as $key => $item){
            
            if( isset($item->adicionales) ){
                $item->adicionales = json_encode($item->adicionales);
            }
            
            $validator = Validator::make((array)$item, $this->rules());

            if( $validator->fails() ){

                $errors[] = ['index' => $key, 'errors' => $validator->errors()->get("*")];

            } else if( empty($errors) ){


                
                $activo = [
                    'tratamiento'                   => isset($item->tratamiento) ? $item->tratamiento : null,
                    'sociedad'                      => isset($item->sociedad) ? $item->sociedad : null,
                    'centro'                        => isset($item->centro) ? $item->centro : null,
                    'localizacionFisica'            => isset($item->localizacionFisica) ? $item->localizacionFisica : null,
                    'ccosto'                        => isset($item->ccosto) ? $item->ccosto : null,
                    'denominacionLocalizacion'      => isset($item->denominacionLocalizacion) ? $item->denominacionLocalizacion : null,
                    'denominacionCentroCosto'       => isset($item->denominacionCentroCosto) ? $item->denominacionCentroCosto : null, 
                    'tipo'                          => isset($item->tipo) ? $item->tipo : null, 
                    'status'                        => isset($item->status) ? $item->status : null, 
                    'region'                        => isset($item->region) ? $item->region : null, 
                    'comuna'                        => isset($item->comuna) ? $item->comuna : null, 
                    'calle'                         => isset($item->calle) ? $item->calle : null, 
                    'correoElectronicoResponsable'  => isset($item->correoElectronicoResponsable) ? $item->correoElectronicoResponsable : null, 
                    'adicionales'                   => isset($item->adicionales) ? $item->adicionales : null,
                ];

                $assets[] = $activo;

            }

        }

        if(!empty($errors)){
            return response()->json([
                'status'=>'error', 
                'message' => 'There are some items with errors, fix them and try again', 
                'errors' => $errors
            ],422);
        } else {

            $assets = RespLocalizacion::insert($assets);

            return response()->json(['status'=>'OK', 'message' => 'items created sucssessfuly']);
        }


        
    }

    protected function rules(){

        return [
            'tratamiento'                   => 'max:50',  
            'sociedad'                      => 'max:50',
            'centro'                        => 'max:50',
            'localizacionFisica'            => 'max:50',
            'ccosto'                        => 'max:50',
            'denominacionLocalizacion'      => 'max:255',
            'denominacionCentroCosto'       => 'max:50',
            'tipo'                          => 'max:50',
            'status'                        => 'max:50',
            'region'                        => 'max:50',
            'comuna'                        => 'max:50',
            'calle'                         => 'max:50',
            'correoElectronicoResponsable'  => 'max:50',
            'adicionales'                   => 'sometimes|json'
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RespLocalizacion  $respLocalizacion
     * @return \Illuminate\Http\Response
     */
    public function show(RespLocalizacion $respLocalizacion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RespLocalizacion  $respLocalizacion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RespLocalizacion $respLocalizacion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RespLocalizacion  $respLocalizacion
     * @return \Illuminate\Http\Response
     */
    public function destroy(RespLocalizacion $respLocalizacion)
    {
        //
    }
}
