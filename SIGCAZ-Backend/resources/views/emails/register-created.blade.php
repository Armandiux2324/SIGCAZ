<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Registro confirmado</title>
</head>
<body style="margin:0; padding:0; background-color:#f0f0f3; font-family:Arial, Helvetica, sans-serif; color:#333333;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f0f3; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden;">

                    <tr>
                        <td style="padding:32px 32px 16px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td valign="top" width="140" align="center">
                                        @if ($qrBinary)
                                            <img src="{{ $message->embedData($qrBinary, $participant->folio . '.png', 'image/png') }}"
                                                 alt="QR {{ $participant->folio }}"
                                                 width="130" height="130"
                                                 style="border:1px solid #eeeeee; border-radius:4px;">
                                        @endif
                                        <div style="text-align:center; font-size:11px; color:#888888; margin-top:6px;">
                                            {{ $participant->folio }}
                                        </div>
                                    </td>
                                    <td valign="middle" style="padding-left:20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td valign="middle" style="width:60%;">
                                                    <div style="font-size:11px; color:#888888; text-transform:uppercase;">Folio de registro</div>
                                                    <div style="font-size:16px; font-weight:bold; color:#222222; margin-top:2px;">
                                                        {{ $participant->folio }}
                                                    </div>
                                                    <div style="font-size:15px; font-weight:bold; color:#222222; margin-top:14px;">
                                                        {{ $participant->first_name }} {{ $participant->last_name }}
                                                    </div>
                                                    <div style="font-size:12px; color:#888888; margin-top:2px;">
                                                        {{ $participant->email }}
                                                    </div>
                                                </td>
                                                <td valign="middle" align="right" style="width:40%;">
                                                    <table role="presentation" style="width:auto; margin-left:auto;" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td valign="top" style="width:18px; padding-top:2px;">
                                                                <img src="{{ asset('images/icon-calendar.png') }}" width="14" height="14">
                                                            </td>
                                                            <td valign="top" style="font-size:12px; color:#333333; padding-left:6px; white-space:nowrap;">
                                                                {{ $eventDate ?? 'Por confirmar' }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td valign="top" style="width:18px; padding-top:8px;">
                                                                <img src="{{ asset('images/icon-pin.png') }}" width="14" height="14">
                                                            </td>
                                                            <td valign="top" style="font-size:12px; color:#888888; padding-left:6px; padding-top:8px; text-align:right; max-width:170px;">
                                                                @if ($eventAddress)
                                                                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($eventAddress) }}" style="color:#888888; text-decoration:underline;">{{ $eventAddress }}</a>
                                                                @else
                                                                    Por confirmar
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr><td style="padding:0 32px;"><hr style="border:none; border-top:1px solid #eeeeee;"></td></tr>

                    <tr>
                        <td style="padding:24px 32px 0 32px; font-size:15px; line-height:1.5;">
                            <p style="margin:0 0 8px 0;">Hola {{ $participant->first_name }},</p>
                            <p style="margin:0;">Confirmamos tu registro a la cabalgata. Estos son los detalles:</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px 0 32px;">
                            <div style="font-size:13px; font-weight:bold; color:#888888; text-transform:uppercase; margin-bottom:8px;">
                                Información general del registro
                            </div>
                            <table role="presentation" width="100%" cellpadding="4" cellspacing="0" style="font-size:14px;">
                                <tr>
                                    <td style="color:#888888; width:45%;">Cuadrilla</td>
                                    <td>{{ $register->group }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Origen</td>
                                    <td>{{ $register->origin_type_label }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Estado</td>
                                    <td>{{ $register->state }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Municipio</td>
                                    <td>{{ $register->municipality }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Tipo de asistencia</td>
                                    <td>{{ $register->attendance_type_label }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Total de participantes</td>
                                    <td>{{ $register->participant_count }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Tipo de hospedaje</td>
                                    <td>{{ $register->accommodation_type_label }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Hospedaje</td>
                                    <td>{{ $register->lodging }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Días de estancia</td>
                                    <td>{{ $register->stay_days }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Método de transporte</td>
                                    <td>{{ $register->transport_method_label }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px 0 32px;">
                            <div style="font-size:13px; font-weight:bold; color:#888888; text-transform:uppercase; margin-bottom:8px;">
                                Tus datos
                            </div>
                            <table role="presentation" width="100%" cellpadding="4" cellspacing="0" style="font-size:14px;">
                                <tr>
                                    <td style="color:#888888; width:45%;">Nombre completo</td>
                                    <td>{{ $participant->first_name }} {{ $participant->last_name }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Teléfono</td>
                                    <td>{{ $participant->phone }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Correo</td>
                                    <td>{{ $participant->email }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Talla de playera</td>
                                    <td>{{ $participant->shirt_size }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Género</td>
                                    <td>{{ $participant->gender_label }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Primera vez participando</td>
                                    <td>{{ $participant->is_first_time_label }}</td>
                                </tr>
                                <tr>
                                    <td style="color:#888888;">Veces que ha participado</td>
                                    <td>{{ $participant->participation_count }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr><td style="padding:24px 32px 0 32px;"><hr style="border:none; border-top:1px solid #eeeeee;"></td></tr>

                    <tr>
                        <td style="padding:16px 32px 32px 32px; font-size:13px; color:#888888;">
                            Presenta este código QR el día del evento. Gracias por tu participación.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>