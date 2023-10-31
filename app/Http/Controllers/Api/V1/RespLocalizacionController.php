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
            'tratamiento'   => $request->tratamiento, 
            'descripcion'   => $request->descripcion,  
            'adicionales'   => $request->adicionales ? $request->adicionales : null,
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
                    'tratamiento'   => $item->tratamiento, 
                    'descripcion'   => $item->descripcion,
                    'adicionales'   => isset($item->adicionales) ? $item->adicionales : null,
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
            'tratamiento'   => 'required|integer|min:1',  
            'descripcion'   => 'required|max:255',
            'adicionales'   => 'sometimes|json'
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
