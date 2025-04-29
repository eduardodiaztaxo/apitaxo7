@component('mail::message')

<h1><center>Notificación Realización de Asignación de Bienes</center></h1>


<br>

Se informa que el proceso ha sido completado exitosamente, se ha adjuntado en este correo el acta para su revisión.

Cualquier duda no dude en comunicarse con nosotros.

N° de Solicitud Asignación: <strong>{{$solicitud->n_solicitud}}</strong>

@if(!empty($observaciones))

    @foreach ($observaciones as $observacion)
        {{$observacion}}

    @endforeach

@endif



Gracias,<br>
Equipo {{ config('app.name') }}
@endcomponent