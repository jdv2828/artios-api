@component('mail::message')
# Tu turno fue reprogramado

Hola {{ $recipientName }},

Tu turno para **{{ $serviceName }}** fue reprogramado.

**Fecha anterior:** {{ $oldDate }} a las {{ $oldTime }}
**Nueva fecha:** {{ $newDate }} a las {{ $newTime }}

Si tenés alguna consulta, contactanos.

Saludos,<br>
{{ config('app.name') }}
@endcomponent
