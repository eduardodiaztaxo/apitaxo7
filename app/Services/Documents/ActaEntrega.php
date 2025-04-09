<?php

namespace App\Services\Documents;

use App\Services\Documents\DocActa;




class ActaEntrega extends DocActa
{

    function __construct()
    {
        parent::__construct('Acta Entrega', 10);
    }



    function makeContent()
    {

        $parrafos = [];

        $p01 = '<p>Hoy en <strong>' . $this->comuna . '</strong> de Chile a <strong>' . $this->fecha . '</strong> en las oficinas de Junji, mediante el presente documento se realiza la entrega formal del o los siguiente(s) activos VER ANEXO DE BIENES ASIGNADOS al "Responsable", para el cumplimiento de las actividades laborales, quién declara recepción de los mismos en buen estado y se compromete a cuidar de los recursos y hacer uso de ellos para los fines establecidos.</p>';

        $parrafos[] = $this->_conv_str($p01);

        $p02 = '<p>Detalle de la responsabilidad sobre estos activos estará incluida en Reglamento interno y/o contrato de trabajo (Anexo).</p>';

        $parrafos[] = $this->_conv_str($p02);


        $p03 = '<h2>1.- Funcionario Responsable</h2>';

        $parrafos[] = $this->_conv_str($p03);

        $tabla = [];

        $tabla['table'] = [
            'header'    => ['order', 'item', 'value'],
            'body'      => [
                [' ',  'Nombre y Apellido', ': ' . $this->_conv_str($this->nombre_receptor)],
                [' ',  'Rut', ': ' . $this->_conv_str($this->rut_receptor)],
                [' ',  'Cargo', ': ' . $this->_conv_str($this->cargo_receptor)],
            ],
            'styleCols'     => [
                ['w' => 5, 'align' => 'L'],
                ['w' => 30, 'align' => 'L'],
                ['w' => 65, 'align' => 'L']
            ],
            'styleTable' => [
                'border' => 0,
                'fontsize' => 11,
                'printTableHeader' => false,
            ]
        ];


        $parrafos[] = $tabla;

        $p04 = '<h2>2.- Equipo Asignado</h2>';

        $parrafos[] = $this->_conv_str($p04);

        $p05 = '<p>En anexos detalles de los bienes asignados</p>';

        $parrafos[] = $this->_conv_str($p05);

        $p06 = '<h2>3.- Observación</h2>';

        $parrafos[] = $this->_conv_str($p06);

        $p07 = '<p>En anexos detalles de los bienes asignados</p>';

        $parrafos[] = $this->_conv_str($p07);

        $p08 = '<h2>4.- Entrega</h2>';

        $parrafos[] = $this->_conv_str($p08);




        return $parrafos;
    }

    public function makeAnexo()
    {

        $parrafos = [];

        $tabla = [];


        $bienes = [];



        foreach ($this->bienes as $key => $bien) {


            $p = '<h3>' . ($key + 1) . '.- ' . $this->_conv_str($bien['serie']) . ' - ' . $bien['nombre'] . '</h3>';

            $parrafos[] = $this->_conv_str($p);




            $tabla = [];

            // $tabla['table'] = [
            //     'header'    => ['order', 'item', 'value'],
            //     'body'      => [
            //         //[' ',  $this->_conv_str('Serie/Etiqueta'), ': ' . $this->_conv_str($bien['serie'])],
            //         //[' ',  $this->_conv_str('Características'), ': ' . $this->_conv_str($bien['caracteristicas'])],
            //         [' ',  'Adicionales', ': ' . $this->_conv_str($bien['adicionales'])],
            //         [' ',  'Valor Aproximado', ': ' . $this->_conv_str($bien['valor_aprox'])],
            //         [' ',  $this->_conv_str('Observación'), ': ' . $this->_conv_str($bien['observacion'])],
            //     ],
            //     'styleCols'     => [
            //         ['w' => 5, 'align' => 'L', 'color' => null],
            //         ['w' => 30, 'align' => 'L', 'color' => [53, 190, 232]],
            //         ['w' => 65, 'align' => 'L', 'color' => [64, 64, 64]]
            //     ],
            //     'styleTable' => [
            //         'border' => 0,
            //         'fontsize' => 10,
            //         'hcell' => 5,
            //         'width' => ($this->widthPage - $this->ml - $this->ml) * 2 / 3,
            //         'printTableHeader' => false,
            //     ]
            // ];


            //add row
            //add cell
            //add list as cell
            //add img as list

            //New Table
            //$row->addCell('string')
            //$row->addCell('string')

            $tabla['table'] = [
                'header'    => ['order', 'item', 'value', 'qr'],
                'body'      => [
                    [
                        ' ',
                        ['Adicionales', 'Valor Aproximado', $this->_conv_str('Observación')],
                        [': ' . $this->_conv_str($bien['adicionales']), ': ' . $this->_conv_str($bien['valor_aprox']), ': ' . $this->_conv_str($bien['observacion'])],
                        [
                            'img' => [
                                'path' => $bien['qr']
                            ],
                        ]

                    ],

                ],
                'styleCols'     => [
                    ['w' => 5, 'align' => 'L', 'color' => null],
                    ['w' => 25, 'align' => 'L', 'color' => [58, 190, 232]],
                    ['w' => 50, 'align' => 'L', 'color' => [64, 64, 64]],
                    ['w' => 20, 'align' => 'L', 'color' => [64, 64, 64]]
                ],
                'styleTable' => [
                    'border' => 0,
                    'fontsize' => 10,
                    'multicell' => true,
                    'hcell' => 5,
                    'width' => ($this->widthPage - $this->ml - $this->ml) * 2 / 3,
                    'printTableHeader' => false,
                ]
            ];


            $parrafos[] = $tabla;

            // if (($key + 1) % 8 == 0 && $key + 1 > 0 && count($this->bienes) % 8 != 0) {
            //     $parrafos[] = ['addPage' => true];
            // }
        }









        return $parrafos;
    }
}
