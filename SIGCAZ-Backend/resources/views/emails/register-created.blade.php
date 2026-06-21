<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Registro confirmado</title>
</head>
<body>
    <h2>¡Registro exitoso!</h2>

    <p>Tu registro a la cabalgata ha sido confirmado.</p>

    <h3>Detalles del registro:</h3>

    <ul>
        <li>Origen: {{ $register->origin_type }}</li>
        <li>Estado: {{ $register->state }}</li>
        <li>Municipio: {{ $register->municipality }}</li>
        <li>Participantes: {{ $register->participant_count }}</li>
        <li>Tipo de asistencia: {{ $register->attendance_type }}</li>
    </ul>

    <p>Gracias por tu participación.</p>
</body>
</html>