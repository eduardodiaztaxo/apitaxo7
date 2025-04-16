<?php

namespace App\Services\Documents;

use App\Models\Responsable;
use App\Models\SecScUser;
use App\Models\SolicitudAsignacion;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;

use EasyLegalPdfDocuments\QRCode\QRCode;
use EasyLegalPdfDocuments\Documents\ActaEntregaBienes;

class ActaHelperService
{
    public function createActa(
        Collection $assets,
        Responsable $responsable,
        SecScUser $user,
        SolicitudAsignacion $solicitud,
        String $responsible_signature = ''
    ) {
        // Implement the logic to create an "Acta" document
        // Example: Validate data, process it, and return the result

        // Placeholder logic


        $subdir = "/actas/documents/acta-entrega/";

        $filename = $solicitud->n_solicitud . "_acta_entrega.pdf";

        $path = $subdir . $filename;

        $dir = storage_path('app') . $subdir;



        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0777, true, true);
        }




        $_bienes = [];

        foreach ($assets as $key => $asset) {


            $qr = QRCode::getMinimumQRCode($asset->etiqueta, QR_ERROR_CORRECT_LEVEL_L);

            $im = $qr->createImage(2, 4);

            $qrdir = $dir . $asset->etiqueta . ".png";

            imagepng($im, $qrdir);

            $_bienes[] = [

                'nombre'            => ($asset->modeloRelation->descripcion ?? 'S/N') . '-' . ($asset->marcaRelation->descripcion ?? 'S/M') . ($asset->modelo ?? 'S/M'),
                'serie'             => $asset->serie . '-' . $asset->etiqueta,
                'caracteristicas'   => $asset->capacidadUnidadMedida,
                'adicionales'       => $asset->opcional1,
                'valor_aprox'       => $asset->valorCompra ? $asset->valorCompra : 1,
                'observacion'       => $asset->opcional3,
                'etiqueta'          => $asset->etiqueta,
                'qr'                => $qrdir

            ];
        }


        $_numero = $solicitud->n_solicitud;

        $address = $responsable->ubicacionGeografica;

        $_direccion = $address->direccion;
        $_comuna    = $address->comuna()->first()->descripcion;
        $_telefono = '';

        $_fecha = \Carbon\Carbon::parse($solicitud->fecha_mov)->format('d/m/Y');


        $_nombre_entregador = $responsable->name;
        $_rut_entregador = format_chilean_rut($responsable->rut);


        $_nombre_receptor = $user->name;
        $_rut_receptor = format_chilean_rut($user->rut);
        $cargo_receptor = 'Quien Entrega';

        $_observaciones = [
            'Sin observaciones',
            'Con observaciones',
        ];





        foreach ($_bienes as $key => $bien) {

            $qr = QRCode::getMinimumQRCode($bien['serie'], QR_ERROR_CORRECT_LEVEL_L);

            $im = $qr->createImage(2, 4);

            $_bienes[$key]['qr'] = $dir . $bien['serie'] . ".png";

            imagepng($im, $_bienes[$key]['qr']);
        }





        $path_quien_entrega = '';

        if ($user->firma && !empty($user->firma)) {

            $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $user->firma));

            file_put_contents($dir . 'firma_representante.png', $data);

            $path_quien_entrega = $dir . 'firma_representante.png';
        }

        $path_quien_recive = '';

        if (preg_match('/^data:image\/png;base64,/', $responsible_signature)) {
            $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $responsible_signature));
            file_put_contents($dir . 'firma_responsable.png', $data);
            $path_quien_recive = $dir . 'firma_responsable.png';
        }




        $acta = new ActaEntregaBienes();








        $acta->setLogo(public_path('img/logo-safin.png'));

        $acta->setNumero($_numero);
        $acta->setDireccion($_direccion);
        $acta->setComuna($_comuna);
        $acta->setTelefono($_telefono);
        $acta->setFecha($_fecha);

        $acta->setNombreQuienEntrega($_nombre_entregador);
        $acta->setRutQuienEntrega($_rut_entregador);
        $acta->setCargoQuienEntrega('');
        $acta->setNombreReceptor($_nombre_receptor);
        $acta->setRutReceptor($_rut_receptor);
        $acta->setCargoReceptor($cargo_receptor);
        $acta->setObservaciones($_observaciones);
        $acta->setBienes($_bienes);

        $acta->setPathQuienEntrega($path_quien_entrega);
        $acta->setPathReceptor($path_quien_recive);








        $acta->save($dir . $filename);












        return $path;
    }


    public function saveFilePaths(array $doctos, SolicitudAsignacion $solicitud)
    {
        $solicitud->acta = json_encode($doctos);
        $solicitud->save();
    }
}
