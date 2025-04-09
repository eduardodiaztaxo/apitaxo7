<?php

namespace App\Services\Documents;

use App\Services\Documents\PdfClass\PDF;



class DocActa
{

    protected $pdf;
    protected $widthPage;
    protected $heightPage;
    protected $ml;

    protected $hln = 1.0;


    protected $numero = '';
    protected $direccion = '';
    protected $comuna = '';
    protected $telefono = '';
    protected $fecha = '';
    protected $nombre_entregador = '';
    protected $rut_entregador = '';
    protected $nombre_receptor = '';
    protected $rut_receptor = '';
    protected $cargo_receptor = '';
    protected $observaciones = [];
    protected $bienes = [];







    protected $nombre_representante;
    protected $rut_representante;

    protected $rut_trabajador;

    protected $nombres_trabajador;
    protected $apellidos_trabajador;

    protected $direccion_trabajador;

    protected $comuna_trabajador;

    protected $dia;
    protected $mes;
    protected $year;

    protected $email_trabajador;


    protected $firma_entregador;
    protected $firma_receptor;

    protected $dest = '';

    protected $namefile = '';


    protected $font = 'Arial';


    public function __construct(string $titulo, int $h_primer_p)
    {
        $this->pdf = new PDF();

        $this->setFont('Inter-Regular', 'inter.php', __DIR__ . '/fonts/');
        $this->pdf->setTableFont('Inter-Regular', 'inter.php', __DIR__ . '/fonts/');

        $this->pdf->SetFont($this->font, '', 12);


        // Stylesheet
        $this->pdf->SetStyle("p", "Inter-Regular", "N", 11, "64,64,64", 15);
        $this->pdf->SetStyle("h1", "Inter-Regular", "", 18, "63,35,135", 0);
        $this->pdf->SetStyle("h2", "Inter-Regular", "", 15, "63,35,135", 0);
        $this->pdf->SetStyle("h3", "Inter-Regular", "", 11, "63,35,135", 0);
        $this->pdf->SetStyle("a", "times", "BU", 9, "0,0,255");
        $this->pdf->SetStyle("pers", "times", "I", 0, "255,0,0");
        $this->pdf->SetStyle("place", "arial", "U", 0, "153,0,0");
        $this->pdf->SetStyle("vb", "arial", "B", 0, "37,37,37");
        $this->pdf->SetStyle("strong", "arial", "B", 0, "37,37,37");


        $this->widthPage = $this->pdf->getFullWidth();
        $this->ml = $this->pdf->getLM();
        $this->heightPage = $this->pdf->getHeight();

        //$this->pdf->setTitleTable('Contrato de Prestación de Servicios');



        $this->pdf->AddPage();

        //$this->pdf->Ln(20);


    }

    public function setFont($font, $file, $dir)
    {
        $this->pdf->AddFont($font, '', $file, $dir);

        $this->font = $font;
    }

    public function addTitulo($titulo, $h_primer_p, $proceso = false)
    {
        $this->pdf->Image(__DIR__ . '/safin-logo-nuevo.png', 10, 2, 50, 16);

        $this->pdf->SetFont($this->font, '', 18);
        #3f2087
        $this->pdf->SetTextColor(63, 35, 135);
        $this->pdf->Cell(0, 6, $this->_conv_str($titulo), 0, 1, 'C');

        $this->pdf->SetTextColor(53, 190, 232);

        $dataProceso =  [
            [
                'col1'  => $this->_conv_str('Número'),
                'col2'  => ': ' . $this->_conv_str($this->numero),

            ],
            [
                'col1'   => $this->_conv_str('Dirección'),
                'col2'  =>  ': ' . $this->_conv_str($this->direccion),
            ],
            [
                'col1'   =>  'Comuna',
                'col2'  =>  ': ' . $this->_conv_str($this->comuna),
            ],
            [
                'col1'  => $this->_conv_str('Teléfono'),
                'col2'  =>  ': ' . $this->telefono

            ],


        ];

        $props = [];

        $props['width'] = $this->widthPage / 3;
        $props['align'] = 'R';
        $props['multicell'] = true;
        $props['linecell'] = 0;

        if ($proceso) {
            $this->pdf->AddCol(0, 20);
            $this->pdf->AddCol(1, 50);
            $this->pdf->Table($dataProceso, $props);
            $this->pdf->resetCols();
        }

        // Salto de línea
        $this->pdf->Ln($h_primer_p);
    }

    public function setNumero($numero)
    {
        $this->numero = $numero;
    }

    public function setDireccion($direccion)
    {
        $this->direccion = $direccion;
    }

    public function setComuna($comuna)
    {
        $this->comuna = $comuna;
    }

    public function setTelefono($telefono)
    {
        $this->telefono = $telefono;
    }

    public function setFecha($fecha)
    {
        $this->fecha = $fecha;
    }

    public function setNombreEntregador($nombre)
    {
        $this->nombre_entregador = $nombre;
    }

    public function setRutEntregador($rut)
    {
        $this->rut_entregador = $rut;
    }

    public function setNombreReceptor($nombre)
    {
        $this->nombre_receptor = $nombre;
    }

    public function setRutReceptor($rut)
    {
        $this->rut_receptor = $rut;
    }

    public function setCargoReceptor($cargo)
    {
        $this->cargo_receptor = $cargo;
    }

    public function setObservaciones($observaciones)
    {
        $this->observaciones = $observaciones;
    }

    public function setBienesEntregados($bienes)
    {
        $this->bienes = $bienes;
    }

    public function setRepresentante($nombre, $rut)
    {
        $this->nombre_representante = $nombre;
        $this->rut_representante = $rut;
    }

    public function setTrabajador($nombres, $apellidos, $rut)
    {
        $this->nombres_trabajador = $nombres;
        $this->apellidos_trabajador = $apellidos;
        $this->rut_trabajador = $rut;
    }

    public function setUbicacionTrabajador($direccion, $comuna, $email)
    {
        $this->direccion_trabajador = $direccion;
        $this->comuna_trabajador = $comuna;
        $this->email_trabajador = $email;
    }

    public function setFechaCelebracion($dia, $mes, $year)
    {
        $this->dia = $dia;
        $this->mes = $mes;
        $this->year = $year;
    }

    public function setFirmaEntregador($firma)
    {
        $this->firma_entregador = $firma;
    }

    public function setFirmaReceptor($firma)
    {
        $this->firma_receptor = $firma;
    }


    function setDestFile($dest)
    {

        $this->dest = $dest;
    }

    function setNameFile($name)
    {

        $this->namefile = $name;
    }

    //string to UTF-8
    function _conv_str($str)
    {

        $str = preg_replace("/\s+/", " ", $str);

        $newStr = iconv('UTF-8', 'ISO-8859-1', $str);
        return !$newStr ? $str : $newStr;
    }




    public function makeContent()
    {

        $parrafos = [];


        return $parrafos;
    }

    public function makeAnexo()
    {

        $parrafos = [];


        return $parrafos;
    }


    private function getAspectRatioImage($imagePath)
    {
        list($width, $height) = getimagesize($imagePath);


        return $width / $height;
    }

    public function drawHeaderUbicacion()
    {
        // $this->pdf->SetFillColor(255, 255, 255);

        // $this->pdf->RoundedRect($this->ml, 26, $this->widthPage, 10, 2, '12', 'DF');
        // $this->pdf->RoundedRect($this->ml, 36, $this->widthPage, 16, 0, '12', 'DF');

        // $this->pdf->Ln(2);

        //Titulo del acta
        // $this->pdf->SetFont('Arial', '', 14);
        // $this->pdf->Cell(0, 6, $this->_conv_str('Ubicación'), 0, 1, 'C');
        // $this->pdf->Ln(5);

        // $this->pdf->SetTextColor(47, 79, 82);


    }


    function drawFooterFirma()
    {
        //FIRMAS
        $nombre_entregador = $this->nombre_entregador;
        $rut_entregador = $this->rut_entregador;

        $rut_receptor = $this->rut_receptor;

        $nombre_receptor = $this->nombre_receptor;

        $iniX = $this->ml * 2;

        $wPage = $this->widthPage + $this->ml * 2 - $iniX * 2;

        $sepCol = $wPage / 3;

        $widthCol = $sepCol;

        $hline = $this->heightPage - 40;

        $hfirma = $widthCol * 0.5924;

        if ($this->firma_entregador) {
            $wfirma1 = $hfirma * $this->getAspectRatioImage($this->firma_entregador);
            $xfirma1 = $iniX + $widthCol / 2 - $wfirma1 / 2;
        }

        if ($this->firma_receptor) {
            $wfirma2 = $hfirma * $this->getAspectRatioImage($this->firma_receptor);
            $xfirma2 = $iniX + $widthCol + $sepCol + $widthCol / 2 - $wfirma2 / 2;
        }

        $yfirma = $hline - $hfirma - 2;


        //Linea Izquierda
        $this->pdf->Line($iniX, $hline, $iniX + $widthCol, $hline);

        if ($this->firma_entregador)
            $this->pdf->Image($this->firma_entregador, $xfirma1, $yfirma, $wfirma1, $hfirma);


        //Linea Derecha
        $this->pdf->Line($iniX + $widthCol + $sepCol, $hline, $iniX + $widthCol + $sepCol + $widthCol, $hline);

        if ($this->firma_receptor)
            $this->pdf->Image($this->firma_receptor, $xfirma2, $yfirma, $wfirma2, $hfirma);


        //Text Izquierda
        $this->pdf->SetFont($this->font, '', 11);
        $this->pdf->SetTextColor(124, 151, 171);

        $this->pdf->SetY($hline + 4);
        $this->pdf->SetX($iniX);

        $this->pdf->Cell($widthCol, 0, $this->_conv_str($nombre_entregador), 0, 0, 'C');

        $this->pdf->SetY($hline + 8);
        $this->pdf->SetX($iniX);
        $this->pdf->Cell($widthCol, 0, $this->_conv_str($rut_entregador), 0, 0, 'C');


        $this->pdf->SetY($hline + 12);
        $this->pdf->SetX($iniX);
        $this->pdf->SetTextColor(53, 190, 232);
        $this->pdf->Cell($widthCol, 0, $this->_conv_str('Entregado Por'), 0, 0, 'C');


        //Text Derecha
        $this->pdf->SetFont($this->font, '', 11);
        $this->pdf->SetTextColor(124, 151, 171);

        $this->pdf->SetY($hline + 4);
        $this->pdf->SetX($iniX + $widthCol + $sepCol);

        $this->pdf->Cell($widthCol, 0, $this->_conv_str($nombre_receptor), 0, 0, 'C');

        $this->pdf->SetY($hline + 8);
        $this->pdf->SetX($iniX + $widthCol + $sepCol);
        $this->pdf->Cell($widthCol, 0, $this->_conv_str($rut_receptor), 0, 0, 'C');


        $this->pdf->SetY($hline + 12);
        $this->pdf->SetX($iniX + $widthCol + $sepCol);
        #35bee8
        $this->pdf->SetTextColor(53, 190, 232);
        $this->pdf->Cell($widthCol, 0, $this->_conv_str('Recibido Por'), 0, 0, 'C');


        $this->pdf->SetTextColor(37, 37, 37);
    }


    function processContent($parrafos, $salto, $padding = 7)
    {
        foreach ($parrafos as $parrafo) {
            if (is_string($parrafo)) {
                $this->pdf->WriteTag(0, 8, $parrafo, 0, "J", 0, $padding);

                $this->pdf->Ln($salto);
            } else if (is_array($parrafo) && isset($parrafo['text']) && isset($parrafo['align'])) {

                $this->pdf->WriteTag(0, 8, $parrafo['text'], 0, $parrafo['align'], 0, $padding);

                $this->pdf->Ln($salto);
            } else if (is_array($parrafo) && isset($parrafo['addPage']) && $parrafo['addPage']) {
                $this->pdf->AddPage();
            } else if (is_array($parrafo) && isset($parrafo['table'])) {

                //$this->pdf->Ln($salto);

                //Subtotal
                $this->pdf->resetCols();

                $i = 0;

                $tabla = $parrafo['table'];

                $cols = count($tabla['header']);

                foreach ($tabla['header'] as $header) {
                    $w = isset($tabla['styleCols'][$i]['w']) ? ($this->widthPage * $tabla['styleCols'][$i]['w'] / 100) : $this->widthPage / $cols;
                    $align = isset($tabla['styleCols'][$i]['align']) ? $tabla['styleCols'][$i]['align'] : 'C';
                    $color = isset($tabla['styleCols'][$i]['color']) ? $tabla['styleCols'][$i]['color'] : null;
                    $this->pdf->AddCol($i, $w, $i, $align, $header, $color);
                    $i++;
                }



                $border = isset($tabla['styleTable']['border']) ? $tabla['styleTable']['border'] : 1;
                $width = isset($tabla['styleTable']['width']) ? $tabla['styleTable']['width'] : $this->widthPage - $this->ml - $this->ml;
                $hcell = isset($tabla['styleTable']['hcell']) ? $tabla['styleTable']['hcell'] : 6;

                $props = [
                    'align' => 'R',
                    'linecell' => $border,
                    'hcell' => $hcell,
                    'width' => $width
                ];

                if (isset($tabla['styleTable']['textStyleCell'])) {
                    $props['textStyleCell'] = $tabla['styleTable']['textStyleCell'];
                }

                if (isset($tabla['styleTable']['fontsize'])) {
                    $props['fontsize'] = $tabla['styleTable']['fontsize'];
                }

                if (isset($tabla['styleTable']['multicell'])) {
                    $props['multicell'] = $tabla['styleTable']['multicell'];
                }

                $printTableHeader = isset($tabla['styleTable']['printTableHeader']) ? $tabla['styleTable']['printTableHeader'] : true;

                $this->pdf->SetTextColor(64, 64, 64);
                $this->pdf->printHeader($printTableHeader);

                $this->pdf->Table($tabla['body'], $props);
                $this->pdf->printHeader(false);


                //$this->pdf->Ln($salto);
            }
        }
    }



    function makePDF()
    {

        $this->addTitulo('Acta Entrega', 1, true);

        $this->drawHeaderUbicacion();

        //$parrafos = $this->makeContent();

        // foreach ($parrafos as $parrafo) {
        //     if (is_string($parrafo)) {
        //         $this->pdf->WriteTag(0, 8, $parrafo, 0, "J", 0, 7);

        //         $this->pdf->Ln($this->hln);
        //     } else if (is_array($parrafo)) {

        //         $this->pdf->Ln($this->hln);

        //         //Subtotal
        //         $this->pdf->resetCols();

        //         $i = 0;

        //         $cols = count($parrafo['header']);

        //         foreach ($parrafo['header'] as $header) {
        //             $this->pdf->AddCol($i, $this->widthPage / $cols, $i, 'C', $header);
        //             $i++;
        //         }





        //         $props = [
        //             'align' => 'R',
        //             'linecell' => 1,
        //             'width' => $this->widthPage / 2
        //         ];



        //         $this->pdf->SetTextColor(47, 79, 82);
        //         $this->pdf->printHeader(true);
        //         $this->pdf->Table($parrafo['body'], $props);
        //         $this->pdf->printHeader(false);


        //         $this->pdf->Ln($this->hln);
        //     }
        // }

        $parrafos = $this->makeContent();

        $this->processContent($parrafos, $this->hln);

        $this->drawFooterFirma();

        $this->pdf->AddPage();

        $this->addTitulo('Info de Bienes Entregados', 10);

        $parrafos = $this->makeAnexo();

        $this->processContent($parrafos, 1, 1);

        $this->pdf->Output($this->dest, $this->namefile);
    }
}
