<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\RespActivo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RespActivoResource;
use DateTime;
use Illuminate\Support\Facades\Validator;

class RespActivoController extends Controller
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

        $date = DateTime::createFromFormat('d-m-Y', $request->fecha_compra);
        $usableDate = $date->format('Y-m-d');
        
        //
        $activo = [
            'tratamiento'       => $request->tratamiento, 
            'numero_af'         => $request->numero_af,  
            'centro_costo'      => $request->centro_costo,  
            'localizacion'      => $request->localizacion,  
            'fecha_compra'      => $usableDate,  
            'valor_neto'        => $request->valor_neto,
            'valor_actual'      => $request->valor_actual,  
            'descripcion'       => $request->descripcion,  
            'etiqueta'          => $request->etiqueta,  
            'serie'             => $request->serie,   
            'marca'             => $request->marca,   
            'modelo'            => $request->modelo,  
            'clasificacion_op'  => $request->clasificacion_op,  
            'elemento_pep'      => $request->elemento_pep,  
            'adicionales'       => $request->adicionales ? $request->adicionales : null,
        ];

        $asset = RespActivo::create($activo);

        $asset->generate_catalog();
        $asset->set_brand_id();
        $asset->set_centro_costo_id();
        $asset->set_ubicacion_geografica_id();

        return new RespActivoResource($asset);
        
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

                $date = DateTime::createFromFormat('d-m-Y', $item->fecha_compra);
                $usableDate = $date->format('Y-m-d');

                $activo = [
                    'tratamiento'       => $item->tratamiento, 
                    'numero_af'         => $item->numero_af,  
                    'centro_costo'      => $item->centro_costo,  
                    'localizacion'      => $item->localizacion,  
                    'fecha_compra'      => $usableDate,  
                    'valor_neto'        => $item->valor_neto,
                    'valor_actual'      => $item->valor_actual,   
                    'descripcion'       => $item->descripcion,  
                    'etiqueta'          => $item->etiqueta,  
                    'serie'             => $item->serie,   
                    'marca'             => $item->marca,   
                    'modelo'            => $item->modelo,  
                    'clasificacion_op'  => $item->clasificacion_op,  
                    'elemento_pep'      => $item->elemento_pep,  
                    'adicionales'       => isset($item->adicionales) ? $item->adicionales : null,
                    'created_at'        => date('Y-m-d H:i:s'),
                    'updated_at'        => date('Y-m-d H:i:s'),
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

            foreach($assets as $activo){

                $asset = RespActivo::create($activo);

                $asset->generate_catalog();
                $asset->set_brand_id();
                $asset->set_centro_costo_id();
                $asset->set_ubicacion_geografica_id();
            }

            //$assets = RespActivo::insert($assets);

            return response()->json(['status'=>'OK', 'message' => 'items created sucssessfuly']);
        }


        
    }

    protected function rules(){

        return [
            'tratamiento'   => 'required|integer|min:1', 
            'numero_af'     => 'required|max:124',  
            'centro_costo'  => 'required|max:64|exists:centro_costos,codigoCliente',  
            'localizacion'  => 'required|integer|min:1|exists:ubicaciones_geograficas,codigoCliente',  
            'fecha_compra'  => 'required|date|date_format:d-m-Y',  
            //'valor_compra'=> 'required|numeric|min:0', -> ahora valor_neto
            'valor_neto'    => 'required|numeric|min:0',   
            'valor_actual'  => 'required|numeric|min:0',//nuevo
            'descripcion'   => 'required|max:255',  
            'etiqueta'      => 'required|max:64',  
            'serie'         => 'required|max:64',   
            'marca'         => 'required|max:64',   
            'modelo'        => 'required|max:64',  
            //'unidad_negocio'=> 'required|max:64', -> ahora clasificacion_op
            'clasificacion_op'=> 'required|max:64',  
            'elemento_pep'  => 'required|max:64',
            'adicionales'   => 'sometimes|json'
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RespActivo  $respActivo
     * @return \Illuminate\Http\Response
     */
    public function show(RespActivo $respActivo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RespActivo  $respActivo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, RespActivo $respActivo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RespActivo  $respActivo
     * @return \Illuminate\Http\Response
     */
    public function destroy(RespActivo $respActivo)
    {
        //
    }
}
