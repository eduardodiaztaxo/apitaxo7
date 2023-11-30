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
            'sociedad'          => $request->sociedad, 
            'fecha'             => $request->fecha, 
            'numero_af'         => $request->numero_af,  
            'sub_numero'        => $request->sub_numero,  
            'centro_costo'      => $request->centro_costo,  
            'localizacion'      => $request->localizacion,  
            'fecha_compra'      => $usableDate,  
            'descripcion'       => $request->descripcion,  
            'etiqueta'          => $request->etiqueta,  
            'serie'             => $request->serie,   
            'marca'             => $request->marca,   
            'modelo'            => $request->modelo,  
            'catalogo'          => $request->catalogo,  
            'clasificacion_op'  => $request->clasificacion_op,  
            'valor_compra'      => $request->valor_compra,  
            'fecha_baja'        => $usableDate, 
            'motivo_baja'       => $request->motivo_baja,  
            'status'            => $request->status,  
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

                $date = isset($item->fecha_compra) ? DateTime::createFromFormat('d-m-Y', $item->fecha_compra) : false;

                if( !$date ){
                    $usableDate = null;
                } else {
                    $usableDate = $date->format('Y-m-d');
                }
                

                $activo = [
                    'tratamiento'       => isset($item->tratamiento) ? $item->tratamiento : 0, 
                    'sociedad'          => isset($item->sociedad) ? $item->sociedad : null,  
                    'fecha'             => isset($item->fecha) ? $item->fecha : null,  
                    'numero_af'         => isset($item->numero_af) ? $item->numero_af : null,  
                    'sub_numero'        => isset($item->sub_numero) ? $item->sub_numero : null,  
                    'centro_costo'      => isset($item->centro_costo) ? $item->centro_costo : null,  
                    'localizacion'      => isset($item->localizacion) ? $item->localizacion : null,  
                    'fecha_compra'      => $usableDate,  
                    'descripcion'       => isset($item->descripcion) ? $item->descripcion : null,  
                    'etiqueta'          => isset($item->etiqueta) ? $item->etiqueta : null,  
                    'serie'             => isset($item->serie) ? $item->serie : null,   
                    'marca'             => isset($item->marca) ? $item->marca : null,   
                    'modelo'            => isset($item->modelo) ? $item->modelo : null,  
                    'catalogo'          => isset($item->catalogo) ? $item->catalogo : null,  
                    'clasificacion_op'  => isset($item->clasificacion_op) ? $item->clasificacion_op : null,  
                    'valor_compra'      => isset($item->valor_compra) ? $item->valor_compra : null,  
                    'fecha_baja'        => isset($item->fecha_baja) ? $item->fecha_baja : null,  
                    'motivo_baja'       => isset($item->motivo_baja) ? $item->motivo_baja : null,  
                    'status'            => isset($item->status) ? $item->status : null,  
                    'elemento_pep'      => isset($item->elemento_pep) ? $item->elemento_pep : null,  
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
            'tratamiento'       => 'max:20', 
            'sociedad'          => 'max:20',  
            'fecha'             => 'max:64',  
            'numero_af'         => 'max:15',  
            'sub_numero'        => 'max:20',  
            'centro_costo'      => 'max:12',  
            'localizacion'      => 'max:50',  
            'fecha_compra'      => 'max:255',  
            'descripcion'       => 'max:255',  
            'etiqueta'          => 'max:64',  
            'serie'             => 'max:64',   
            'marca'             => 'max:64',   
            'modelo'            => 'max:64',  
            'catalogo'          => 'max:64',  
            'clasificacion_op'  => 'max:64',  
            'valor_compra'      => 'max:64',   
            'fecha_baja'        => 'max:255',  
            'motivo_baja'       => 'max:255',  
            'status'            => 'max:124',  
            'elemento_pep'      => 'max:64',
            'adicionales'       => 'sometimes|json'
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
