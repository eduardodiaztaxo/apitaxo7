@component('mail::message')
# Comando Finalizado

El comando **{{ $command }}** ha finalizado su ejecución.

### Output:

{{ $output }}

Gracias,<br>
Equipo {{ config('app.name') }}
@endcomponent
