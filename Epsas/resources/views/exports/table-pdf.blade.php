<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 14px 12px; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 7.4px; margin: 0; }
        h1 { font-size: 14px; margin: 0 0 10px; color: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 4px 4px;
            text-align: left;
            vertical-align: top;
            overflow-wrap: anywhere;
            word-break: break-word;
            line-height: 1.25;
        }
        th { background: #eff6ff; color: #1e3a8a; font-size: 6.8px; text-transform: uppercase; }
        .empty { text-align: center; color: #64748b; padding: 18px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    @php($headers = array_keys(($rows->first() ?? [])))
    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="{{ max(count($headers), 1) }}">Sin datos para exportar.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
