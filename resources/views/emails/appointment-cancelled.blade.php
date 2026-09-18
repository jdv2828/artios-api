@component('mail::message')
# Tu turno fue cancelado

Hola {{ $recipientName }},

Tu turno para **{{ $serviceName }}** programado para el **{{ $scheduledDate }}** a las **{{ $scheduledTime }}** fue cancelado.

Si tenés alguna consulta, contactanos.

Saludos,<br>
{{ config('app.name') }}
@endcomponent
