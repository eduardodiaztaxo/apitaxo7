@component('mail::message')

<h1><center>Notificación Envío Documento en Blanco</center></h1>


<br>

Se te ha enviado en este correo el documento del acta en blanco para su revisión, firma y carga manual

N° de Solicitud Asignación <strong>{{$solicitud->n_solicitud}}</strong>

Tienes igualmente la opción de hacer el proceso completamente digital



Gracias,<br>
Equipo {{ config('app.name') }}
@endcomponent