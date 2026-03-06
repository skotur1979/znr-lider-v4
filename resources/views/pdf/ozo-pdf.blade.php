<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Izvještaj OZO</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            line-height: 1.1;
        }

        h3 {
            font-size: 12.5px;
            text-align: center;
            margin: 6px 0;
        }

        h4 {
            font-size: 11.5px;
            margin: 0 0 5px 0;
        }

        p {
            font-size: 9.5px;
            margin: 0 0 6px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #000;
            padding: 3px;
            vertical-align: middle;
            text-align: center;
            word-wrap: break-word;
        }

        th {
            background-color: #d0d0d0;
            font-weight: bold;
            font-size: 9.5px;
        }

        /* INFO TABLICA */
        .info-table td {
            border: 1px solid #000;
            padding: 4px;
            font-size: 10px;
            text-align: left;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        /* --- POTPIS --- */
        .sig-cell {
            height: 25px; /* uže redove za ~15 po stranici */
            overflow: hidden;
        }

        .signature-img {
            display: block;
            margin: 0 auto;
            width: 100%;
            max-height: 38px;
            object-fit: contain;
            background: #fff;
            /* jači i tamniji potpis */
            filter: contrast(250%) brightness(70%) saturate(150%) drop-shadow(0 0 1px #000);
            transform: scale(1.08);
            transform-origin: center;
        }

        .footer-text {
            font-size: 8.8px;
            border: 1px solid #000;
            padding: 3px;
            margin-top: 8px;
            line-height: 1.1;
        }

        .footer-signature td {
            border: 1px solid #000;
            padding: 3px;
            font-size: 8.8px;
        }
    </style>
</head>
<body>

<h4>UPISNIK UP-12<br>Broj Upisnika: _____</h4>

<h3>POSLOVI I RADNI ZADACI<br>
NA KOJIMA JE OBVEZNA UPORABA OSOBNE ZAŠTITNE OPREME (OZO)</h3>

<p>
    Osobnom zaštitnom opremom smatraju se predmeti i uređaji koje na sebi nose radnici, a služe za sprječavanje ozljeda,
    profesionalnih i drugih bolesti, kao i drugih štetnih posljedica, a vijek trajanja im je određen posebnim propisom
    ili uputom proizvođača. Poslodavac procjenom rizika utvrđuje poslove i radne zadatke koji se prema pravilima zaštite
    na radu moraju obavljati uz uporabu osobne zaštitne opreme ili zaštitnih pomagala s naznakom vrsta tih sredstava
    odnosno pomagala.
</p>

<table class="info-table">
    <tr>
        <td style="width: 25%;"><strong>Prezime i ime</strong></td>
        <td style="width: 75%; font-size: 11px;">{{ $record->user_last_name }}</td>
    </tr>
    <tr>
        <td><strong>OIB</strong></td>
        <td style="font-size: 11px;">{{ $record->user_oib }}</td>
    </tr>
    <tr>
        <td><strong>Naziv radnog mjesta / poslova</strong></td>
        <td style="font-size: 11px;">{{ $record->workplace }}</td>
    </tr>
    <tr>
        <td><strong>Organizacijska jedinica</strong></td>
        <td style="font-size: 11px;">{{ $record->organization_unit }}</td>
    </tr>
</table>

<h3>EVIDENCIJA O PREUZIMANJU OSOBNE ZAŠTITNE OPREME</h3>

<table>
    <thead>
        <tr>
            <th style="width: 4%;">R.b.</th>
            <th style="width: 25%;">Naziv osobne zaštitne opreme</th>
            <th style="width: 15%;">HRN EN</th>
            <th style="width:9%; white-space:nowrap;">Veličina</th>
            <th style="width: 9%;">Rok trajanja /mj</th>
            <th style="width: 12%;">Datum izdavanja</th>
            <th style="width: 13%;">Potpis radnika</th>
            <th style="width: 15%;">Datum vraćanja</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($record->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->equipment_name }}</td>
                <td>{{ $item->standard }}</td>
                <td>{{ $item->size }}</td>
                <td>{{ $item->duration_months }}</td>
                <td>{{ \Carbon\Carbon::parse($item->issue_date)->format('d.m.Y.') }}</td>
                <td class="sig-cell">
                    @if (!empty($item->signature))
                        @php
                            $path = storage_path('app/public/' . $item->signature);
                            $base64 = file_exists($path) ? base64_encode(file_get_contents($path)) : null;
                            $mime = file_exists($path) ? mime_content_type($path) : null;
                        @endphp
                        @if ($base64)
                            <img src="data:{{ $mime }};base64,{{ $base64 }}" class="signature-img" alt="potpis">
                        @else
                            -
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td>{{ $item->return_date ? \Carbon\Carbon::parse($item->return_date)->format('d.m.Y.') : '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="footer-text">
    Radniku/radnici je dodijeljena i zadužena, gore navedena osobna zaštitna oprema (OZO), objašnjena mu/joj je namjena,
    način čuvanja, održavanja OZO, te ukazano na obvezu korištenja u pojedinim situacijama te odgovornost u slučaju
    nepoštivanja.
    <br>
    <em>
        Ja gore potpisani/potpisana potvrđujem kako sam teorijski i praktično poučen/a i upućen/a u sve radne procese,
        u sve opasnosti, štetnosti i napore koje mogu izazvati ozljedu na radu ili profesionalno oboljenje, odnosno u
        siguran način rada te primjenu i korištenje gore navedene osobne zaštitne opreme čijim korištenjem u najvećoj
        mjeri opasnosti uklanjaju, ili svode na prihvatljivu razinu.
    </em>
</div>

<table class="footer-signature" style="margin-top: 8px; width: 100%;">
    <tr>
        <td style="width: 50%;">
            U _______________________, ______________________
            (mjesto i datum) <br>
        </td>
        <td style="width: 50%; text-align: left;">
            Voditelj upisnika:<br>
            _____________ <br>
            <span style="font-size: 8px;">(Prezime, ime i vlastoručni potpis)</span>
        </td>
    </tr>
</table>

</body>
</html>


