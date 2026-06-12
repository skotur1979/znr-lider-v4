<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .meta {
            font-size: 10px;
            color: #374151;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #1f2937;
            color: #ffffff;
            padding: 6px;
            border: 1px solid #d1d5db;
            text-align: left;
        }

        td {
            padding: 5px;
            border: 1px solid #d1d5db;
            vertical-align: top;
        }

        .small {
            font-size: 8px;
            color: #374151;
        }
    </style>
</head>
<body>
    <h1>GDPR evidencija prihvaćanja</h1>

    <div class="meta">
        Ispisano: {{ now()->format('d.m.Y. H:i') }}<br>
        Ukupno zapisa: {{ $records->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Datum</th>
                <th>Korisnik</th>
                <th>E-mail</th>
                <th>Organizacija</th>
                <th>Uvjeti</th>
                <th>Privatnost</th>
                <th>DPA</th>
                <th>Sigurnost</th>
                <th>Newsletter</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $record)
                <tr>
                    <td>{{ optional($record->accepted_at)->format('d.m.Y. H:i') }}</td>
                    <td>{{ $record->user_name }}</td>
                    <td>{{ $record->user_email }}</td>
                    <td>{{ $record->organization_name ?: '-' }}</td>
                    <td>{{ $record->terms_version }}</td>

                    <td>{{ $record->privacy_version }}</td>

                    <td>{{ $record->dpa_version ?: '-' }}</td>

                    <td>{{ $record->security_version ?: '-' }}</td>

                    <td>{{ $record->newsletter_opt_in ? 'Da' : 'Ne' }}</td>

                    <td>{{ $record->ip_address ?: '-' }}</td>
                </tr>
                <tr>
                    <td colspan="10" class="small">
                        Preglednik / uređaj: {{ $record->user_agent ?: '-' }}
                        @if(!empty($record->accepted_documents))
                    <br>
                    Paket dokumenata:
                    {{ implode(', ', array_keys($record->accepted_documents)) }}
                @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>