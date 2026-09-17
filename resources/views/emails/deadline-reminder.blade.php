<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">

    <title>
        ZNR LIDER - {{ $data['label'] }}
    </title>
</head>

<body style="
    margin: 0;
    padding: 0;
    background: #f5f5f5;
    font-family: Arial, sans-serif;
    color: #222;
">

<div style="
    max-width: 760px;
    margin: 0 auto;
    padding: 24px;
">

    <div style="
        background: #ffffff;
        border-radius: 10px;
        padding: 28px;
    ">

        <h2 style="
            margin-top: 0;
        ">
            ZNR LIDER
        </h2>

        <p>
            Poštovani
            <strong>
                {{ $data['user']->name }}
            </strong>,
        </p>

        <p>
            ovo je automatski podsjetnik za rokove:
            <strong>
                {{ $data['label'] }}
            </strong>.
        </p>

        <p>
            Broj stavki:
            <strong>
                {{ $data['count'] }}
            </strong>
        </p>

        <table
            width="100%"
            cellpadding="8"
            cellspacing="0"
            style="
                border-collapse: collapse;
                margin-top: 20px;
            "
        >
            <thead>
            <tr>
                <th
                    align="left"
                    style="
                        border-bottom: 2px solid #ddd;
                    "
                >
                    Modul
                </th>

                <th
                    align="left"
                    style="
                        border-bottom: 2px solid #ddd;
                    "
                >
                    Stavka
                </th>

                <th
                    align="left"
                    style="
                        border-bottom: 2px solid #ddd;
                    "
                >
                    Opis
                </th>

                <th
                    align="left"
                    style="
                        border-bottom: 2px solid #ddd;
                    "
                >
                    Rok
                </th>
            </tr>
            </thead>

            <tbody>
            @foreach ($data['items'] as $item)
                <tr>
                    <td
                        style="
                            border-bottom: 1px solid #eee;
                        "
                    >
                        {{ $item['module'] }}
                    </td>

                    <td
                        style="
                            border-bottom: 1px solid #eee;
                        "
                    >
                        {{ $item['name'] }}
                    </td>

                    <td
                        style="
                            border-bottom: 1px solid #eee;
                        "
                    >
                        {{ $item['description'] ?? '-' }}
                    </td>

                    <td
                        style="
                            border-bottom: 1px solid #eee;
                            white-space: nowrap;
                        "
                    >
                        {{ \Carbon\Carbon::parse(
                            $item['deadline']
                        )->format('d.m.Y.') }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <p style="
            margin-top: 24px;
            color: #666;
            font-size: 13px;
        ">
            Ovo je automatska obavijest sustava
            ZNR LIDER.
        </p>

    </div>

</div>

</body>
</html>