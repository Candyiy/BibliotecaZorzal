<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }} · ZORZEL</title>
    <style>
        @page { margin: 14mm 12mm; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #24312c;
            margin: 0;
        }
        header {
            border-bottom: 2px solid #173f35;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        h1 {
            font-size: 17px;
            color: #173f35;
            margin: 0 0 3px 0;
        }
        .brand {
            font-size: 9px;
            letter-spacing: 2px;
            color: #b85c3e;
            font-weight: bold;
        }
        .periodo {
            color: #746f65;
            margin-top: 4px;
        }
        .resumen {
            background: #f4efe5;
            border-left: 3px solid #c29a52;
            padding: 7px 9px;
            margin-bottom: 12px;
        }
        h2 {
            font-size: 11px;
            color: #173f35;
            margin: 16px 0 6px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th {
            background: #173f35;
            color: #ffffff;
            text-align: left;
            padding: 5px 4px;
            font-size: 8px;
        }
        td {
            border-bottom: 1px solid #ded7c9;
            padding: 4px;
            vertical-align: top;
            word-wrap: break-word;
        }
        tr:nth-child(even) td { background: #faf7f1; }
        .vacio {
            color: #746f65;
            font-style: italic;
        }
        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #ded7c9;
            padding-top: 4px;
            color: #746f65;
            font-size: 7px;
        }
    </style>
</head>
<body>
    <header>
        <p class="brand">ZORZEL · BIBLIOTECA DIGITAL</p>
        <h1>{{ $titulo }}</h1>
        <p class="periodo">Periodo: {{ $periodo }}</p>
    </header>

    <div class="resumen">{{ $resumen }}</div>

    <h2>{{ $titulo }}</h2>
    <table>
        <thead>
            <tr>
                @foreach ($cabeceras as $cabecera)
                    <th>{{ $cabecera }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $fila)
                <tr>
                    @foreach ($fila as $celda)
                        <td>{{ $celda === null || $celda === '' ? '—' : $celda }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="vacio" colspan="{{ count($cabeceras) }}">No hay registros en el periodo seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($secundario)
        <h2>{{ $secundario['titulo'] }}</h2>
        <table>
            <thead>
                <tr>
                    @foreach ($secundario['cabeceras'] as $cabecera)
                        <th>{{ $cabecera }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($secundario['filas'] as $fila)
                    <tr>
                        @foreach ($fila as $celda)
                            <td>{{ $celda === null || $celda === '' ? '—' : $celda }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td class="vacio" colspan="{{ count($secundario['cabeceras']) }}">No hay suscripciones en el periodo seleccionado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <footer>
        Generado el {{ now()->format('d/m/Y H:i') }} · ZORZEL · Uso interno de la biblioteca
    </footer>
</body>
</html>
