<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comprobante de registro</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color:#333333; font-size:14px; }
        table { width:100%; border-collapse:collapse; }
        .label { color:#888888; width:45%; padding:4px 0; }
        .section-title { font-size:13px; font-weight:bold; color:#888888; text-transform:uppercase; margin-bottom:8px; margin-top:24px; }
        .divider { border:none; border-top:1px solid #eeeeee; margin:16px 0; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td valign="top" style="width:70%;">
                <div style="font-size:13px; color:#888888;">Comprobante de registro</div>
                <div style="font-size:22px; font-weight:bold; color:#222222; margin-top:4px;">
                    {{ $participant->first_name }} {{ $participant->last_name }}
                </div>
                <div style="font-size:13px; color:#888888; margin-top:8px;">
                    Folio: <strong style="color:#333333;">{{ $participant->folio }}</strong>
                </div>
            </td>
            <td valign="top" align="right" style="width:30%;">
                @if ($qrBase64)
                    <img src="data:image/png;base64,{{ $qrBase64 }}" width="130" height="130">
                @endif
            </td>
        </tr>
    </table>

    <hr class="divider">

    <div class="section-title">Información general del registro</div>
    <table>
        <tr><td class="label">Cuadrilla</td><td>{{ $register->group }}</td></tr>
        <tr><td class="label">Origen</td><td>{{ $register->origin_type_label }}</td></tr>
        <tr><td class="label">Estado</td><td>{{ $register->state }}</td></tr>
        <tr><td class="label">Municipio</td><td>{{ $register->municipality }}</td></tr>
        <tr><td class="label">Tipo de asistencia</td><td>{{ $register->attendance_type_label }}</td></tr>
        <tr><td class="label">Total de participantes</td><td>{{ $register->participant_count }}</td></tr>
        <tr><td class="label">Tipo de hospedaje</td><td>{{ $register->accommodation_type_label }}</td></tr>
        <tr><td class="label">Hospedaje</td><td>{{ $register->lodging }}</td></tr>
        <tr><td class="label">Días de estancia</td><td>{{ $register->stay_days }}</td></tr>
        <tr><td class="label">Método de transporte</td><td>{{ $register->transport_method_label }}</td></tr>
    </table>

    <div class="section-title">Tus datos</div>
    <table>
        <tr><td class="label">Nombre completo</td><td>{{ $participant->first_name }} {{ $participant->last_name }}</td></tr>
        <tr><td class="label">Teléfono</td><td>{{ $participant->phone }}</td></tr>
        <tr><td class="label">Correo</td><td>{{ $participant->email }}</td></tr>
        <tr><td class="label">Talla de playera</td><td>{{ $participant->shirt_size }}</td></tr>
        <tr><td class="label">Género</td><td>{{ $participant->gender_label }}</td></tr>
        <tr><td class="label">Primera vez participando</td><td>{{ $participant->is_first_time_label }}</td></tr>
        <tr><td class="label">Veces que ha participado</td><td>{{ $participant->participation_count }}</td></tr>
    </table>

    <hr class="divider">

    <p style="font-size:12px; color:#888888;">
        Presenta este código QR el día del evento. Gracias por tu participación.
    </p>
</body>
</html>