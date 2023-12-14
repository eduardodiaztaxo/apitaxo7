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
        'sociedad',
        'fecha',
        'numero_af',
        'sub_numero',
        'centro_costo',
        'localizacion',
        'fecha_compra',
        'descripcion',
        'etiqueta',
        'serie',
        'marca',
        'modelo',
        'catalogo',
        'clasificacion_op',
        'valor_compra',
        'fecha_baja',
        'motivo_baja',
        'fecha_baja',
        'elemento_pep',
        'adicionales',
        'lista_bien_id',//nuevo
        'lista_marca_id',//nuevo
        'centro_costo_id',//nuevo
        'localizacion_id',//nuevo

    ];

    public function generate_catalog(){

        $descripcion = preg_replace('/\s+/', ' ', trim($this->descripcion) );
        $descripcion = strtr($descripcion, array('.' => '', ',' => ''));

        $descripcion = $this->eliminar_tildes($descripcion);
        $descripcion = strtoupper($descripcion);



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
        $sql .= "WHERE REPLACE(REPLACE(TRIM(descripcion), '.', ''), ',', '')  = REPLACE(REPLACE(TRIM('".$descripcion."'), '.', ''), ',', '') "; 

        $indice = DB::selectOne($sql);

        if( isset($indice->idLista) ){
            $lista_bien_id = $indice->idLista;
            $sufix = str_pad($indice->idLista, 4, '0', STR_PAD_LEFT);
            $catalog = $prefix . $sufix;

        } else {
            $maxLista = IndicesLista::max('idLista');
            
            $newIdLista = $maxLista + 1;

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

    public function set_ubicacion_geografica_id(){

        
        $sql  = "SELECT * FROM ubicaciones_geograficas ";
        $sql .= "WHERE codigoCliente = '".$this->localizacion."' "; 

        $id_direccion = DB::selectOne($sql);

        if( isset($id_direccion->idUbicacionGeo) ){

            $this->localizacion_id = $id_direccion->idUbicacionGeo;
            $this->save();

        }
        
        return $this->localizacion_id;
    }


    public function eliminar_tildes($cadena){

        //Codificamos la cadena en formato utf8 en caso de que nos de errores
        $cadena = utf8_encode($cadena);
    
        //Ahora reemplazamos las letras
        $cadena = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $cadena
        );
    
        $cadena = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $cadena );
    
        $cadena = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $cadena );
    
        $cadena = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $cadena );
    
        $cadena = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $cadena );
    
        $cadena = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç'),
            array('n', 'N', 'c', 'C'),
            $cadena
        );
    
        return $cadena;
    }

}
