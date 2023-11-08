<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RespActivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'tratamiento',
        'numero_af',
        'catalogo',//nuevo
        'centro_costo',
        'localizacion',
        'fecha_compra',
        //'valor_compra', -> ahora valor_neto
        'valor_neto',
        'valor_actual',//nuevo
        'descripcion',
        'etiqueta',
        'serie',
        'marca',
        'modelo',
        //'unidad_negocio', -> ahora clasificacion_op
        'clasificacion_op',
        'elemento_pep',
        'adicionales',
        'lista_bien_id',//nuevo
        'lista_marca_id',//nuevo
        'centro_costo_id',//nuevo
        
    ];

    public function generate_catalog(){

        $prefix = '010101';

        $sql  = "SELECT * FROM ";
        $sql .= "( ";
            
        $sql .= "    SELECT idLista, ";
        $sql .= "    REPLACE( ";
        $sql .= "    REPLACE( ";
        $sql .= "        REPLACE(descripcion, ";
        $sql .= "        ' ','<>'), ";
        $sql .= "        '><',''), ";
        $sql .= "    '<>',' ') AS descripcion ";
        $sql .= "    FROM indices_listas "; 
        $sql .= "    WHERE idIndice = '1' AND idAtributo = 1 ";
            
        $sql .= ") AS tab1 ";
        $sql .= "WHERE REPLACE(REPLACE(TRIM(descripcion), '.', ''), ',', '')  = REPLACE(REPLACE(TRIM('".$this->descripcion."'), '.', ''), ',', '') "; 

        $indice = DB::selectOne($sql);

        if( isset($indice->idLista) ){
            $lista_bien_id = $indice->idLista;
            $sufix = str_pad($indice->idLista, 4, '0', STR_PAD_LEFT);
            $catalog = $prefix . $sufix;

        } else {
            $maxLista = IndicesLista::max('idLista');
            
            $newIdLista = $maxLista + 1;

            $descripcion = preg_replace('/\s+/', ' ', trim($this->descripcion) );
            $descripcion = strtr($descripcion, array('.' => '', ',' => ''));

            $indLista = IndicesLista::create([
                'idLista' => $newIdLista,
                'idAtributo' => '1',
                'idIndice'  => '1',
                'descripcion' => $descripcion,  
            ]);
            $lista_bien_id = $indLista->idLista;
            $sufix = str_pad($indLista->idLista, 4, '0', STR_PAD_LEFT);

            $catalog = $prefix . $sufix;

            
            
        }

        $this->catalogo = $catalog;
        $this->lista_bien_id = $lista_bien_id;
        $this->save();
        return $this->catalogo;
    }

    public function set_brand_id(){

        #este es mi comentario
        
        $sql  = "SELECT * FROM indices_listas ";
        $sql .= "WHERE TRIM(descripcion) = TRIM('".$this->marca."') "; 
        $sql .= "AND idIndice = '1' AND idAtributo = 2 "; 

        $indice = DB::selectOne($sql);

        if( isset($indice->idLista) ){

            $lista_marca_id = $indice->idLista;

        } else {

            $maxLista = IndicesLista::max('idLista');
            
            $newIdLista = $maxLista + 1;

            $marca = preg_replace('/\s+/', ' ', trim($this->marca) );

            $indLista = IndicesLista::create([
                'idLista' => $newIdLista,
                'idAtributo' => '2',
                'idIndice'  => '1',
                'descripcion' => $marca,  
            ]);

            $lista_marca_id = $indLista->idLista;
            

            
            
        }

        
        $this->lista_marca_id = $lista_marca_id;
        $this->save();
        return $this->lista_marca_id;

    }



    public function set_centro_costo_id(){

        
        $sql  = "SELECT * FROM centro_costos ";
        $sql .= "WHERE codigoCliente = '".$this->centro_costo."' "; 

        $centro = DB::selectOne($sql);

        if( isset($centro->idCentroCosto) ){

            $this->centro_costo_id = $centro->idCentroCosto;
            $this->save();

        }
        
        
        
        return $this->centro_costo_id;

    }


}
