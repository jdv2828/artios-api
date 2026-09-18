@component('mail::message')
# {{ $kind === 'confirmation' ? 'Confirmá tu turno' : 'Recordatorio de tu turno' }}

Hola {{ $recipientName }},

Tu turno para **{{ $serviceName }}** está programado para el **{{ $scheduledDate }}** a las **{{ $scheduledTime }}**.

@component('mail::button', ['url' => $confirmationUrl])
Confirmar asistencia
@endcomponent

Si ya confirmaste, podés ignorar este mensaje.

Saludos,<br>
{{ config('app.name') }}
@endcomponent
