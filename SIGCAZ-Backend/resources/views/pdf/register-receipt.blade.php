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
@foreach ($items as $index => $item)
    @php
        $participant = $item['participant'];
        $qrBase64 = $item['qrBase64'];
    @endphp

    <table>
        <tr>
            <td valign="top" width="140" align="center">
                @if ($qrBase64)
                    <img src="data:image/png;base64,{{ $qrBase64 }}" width="130" height="130">
                @endif
                <div style="text-align:center; font-size:11px; color:#888888; margin-top:6px;">
                    {{ $participant->folio }}
                </div>
            </td>
            <td valign="middle" style="padding-left:20px;">
                <table>
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
                            <table style="width:auto; margin-left:auto;">
                                <tr>
                                    <td valign="top" style="width:18px; padding-top:2px;">
                                        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAADu0lEQVR4nO1XPW8jZRB+ZvbD6/gjtk75D1ToGoSEdBKUV121WyEkGvIDiJAFxWYlJDuKxA/IHwCtG5AQxekqhCigpoguxfUR2b3Y2Jt9d2co4g2x469cEDT3SFt43308zzsz78y8wP8MWrcYhmGz0+nYaZqi0+kgTdNxFEXFpj+9D4+XvfR931JV8jzvW1V95XneSxF55XneB6pKYRjaqkqLTxzHa3lxHFsbBcRxbA2Hw5KIFEDLtu1dAI9s295lZiIijaKoICJdfIIgWMsDYKnqnNeXhuD4+LjRaDTcy8vL713XfZLnuXEcx8nz/BmAX5rNJo/HY1nkua5LeZ4rEc3xRORZWZY/93q914ucGwGqSoeHh7Szs/MlM38mIg0RaRGRc+ubMREZVa12NIdb71sA7FtLl5ZlXanqj8z8xcHBwZ+qCiK6dkccx1YQBOVgMPhqd3f369FoBAAQEajqjaGZK5c5bQ6LPMuySFXRbreRpunzLMueAkAURUIz1Tg6OmqKyJlt24+KogARMTNvtrYFKkFEVDiO4xhj3u/1er/FcWxVbtLJZOLU63VXVS3gegNlWY4AKBHh1oa2RsUjoh0isgFYRKSq2q6+uYmT53kKQIBrl4lIYlnWO+12O+l2u5Qkyb0VZFnGnudJkiTPa7Xah1mWlQAcZi7vCKhEA4CIKIBmURTfJUliLi4ulibdJqgqTadTVdV3jTHA7NiLyE1oFwVURDCzU6vVPtom6TYhz3OICJZl8FIBlYgsy8yDrc/srDo+KwUAQK1Wc9atb4uiKCByp26tFsDMJCIjY8zHAEaz3/c/BtcxFxH5xnXdx8YYwUL5XyqAiEBEZjKZ/LRN99uEfr9/zsxzxWmtgEpHq9XqxHGc7O3t0fn5+b09kCQJd7tdOTs7WxnKtTlgjCmDIChX1f5NCMNQ9/f3pd/vr+QunQf+S7wV8FbA2lOgqhyGIQ+HQwrD8I0KURiGWJwDtxWgV1dXoyiKltfQ7SAA0O/3VxazRQEK3LTjtud5L9aRtwURPTbGgIgYgC5tx1mWab1etwCUqspEZHue9+ShxgHAGFM1I6XrWe+fgYSIdDaUvh4MBj+0Wq1P0jQtVJWyLHuI+2+DAWij0bAnk8lL27Z/n+WF2ADg+74AwHQ6/ZyZ91zXfTpr33duMm8KIkKe53+IyKe9Xu+v8XjMURTJqovJe8zcnPXxB41EzKwAYNu2nJ6e/npycrLyXoHqjvcQg5sQhuFc7VlqzPd9y/f9f914EASC2Umr8DesAxxOJUtFNQAAAABJRU5ErkJggg==" width="14" height="14">
                                    </td>
                                    <td valign="top" style="font-size:12px; color:#333333; padding-left:6px; white-space:nowrap;">
                                        {{ $eventDate ?? 'Por confirmar' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="top" style="width:18px; padding-top:8px;">
                                        <img src="data:image/png;base64,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" width="14" height="14">
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

    @if (!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach
</body>
</html>