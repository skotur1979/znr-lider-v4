<!DOCTYPE html>
<html lang="hr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="robots"
        content="noindex,nofollow,noarchive"
    >

    <title>
        Prijavi zapažanje
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 16px;

            background: #f3f4f6;
            color: #111827;

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }

        .container {
            width: 100%;
            max-width: 720px;

            margin: 0 auto;
        }

        .header {
            margin-bottom: 16px;

            padding: 22px;

            border-radius: 16px;

            background: #111827;
            color: #ffffff;
        }

        .brand {
            margin-bottom: 7px;

            font-size: 12px;
            font-weight: 800;

            letter-spacing: .08em;
        }

        .header h1 {
            margin: 0;

            font-size: 25px;
        }

        .header p {
            margin:
                9px
                0
                0;

            color: #d1d5db;

            font-size: 14px;

            line-height: 1.45;
        }

        .card {
            margin-bottom: 16px;

            padding: 20px;

            border:
                1px
                solid
                #e5e7eb;

            border-radius: 16px;

            background: #ffffff;

            box-shadow:
                0
                1px
                3px
                rgba(0, 0, 0, .06);
        }

        .card h2 {
            margin:
                0
                0
                18px;

            font-size: 18px;
        }

        .grid {
            display: grid;

            grid-template-columns:
                1fr
                1fr;

            gap: 16px;
        }

        .field {
            min-width: 0;
        }

        .field-full {
            grid-column: 1 / -1;
        }

        label {
            display: block;

            margin-bottom: 6px;

            font-size: 14px;
            font-weight: 700;
        }

        .required {
            color: #dc2626;
        }

        input,
        select,
        textarea {
            width: 100%;

            padding:
                12px
                13px;

            border:
                1px
                solid
                #d1d5db;

            border-radius: 9px;

            background: #ffffff;
            color: #111827;

            font: inherit;

            font-size: 16px;
        }

        textarea {
            min-height: 120px;

            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;

            border-color: #2563eb;

            box-shadow:
                0
                0
                0
                3px
                rgba(37, 99, 235, .13);
        }

        .helper {
            margin-top: 6px;

            color: #6b7280;

            font-size: 12px;

            line-height: 1.4;
        }

        .errors {
            margin-bottom: 16px;

            padding:
                15px
                18px;

            border:
                1px
                solid
                #fecaca;

            border-radius: 12px;

            background: #fef2f2;
            color: #991b1b;
        }

        .errors strong {
            display: block;

            margin-bottom: 7px;
        }

        .errors ul {
            margin:
                0
                0
                0
                20px;

            padding: 0;
        }

        .priority {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 8px;
        }

        .priority label {
            margin: 0;
        }

        .priority input {
            position: absolute;

            opacity: 0;
            pointer-events: none;
        }

        .priority span {
            display: block;

            padding:
                11px
                7px;

            border:
                2px
                solid
                #d1d5db;

            border-radius: 9px;

            text-align: center;

            cursor: pointer;

            font-size: 13px;
            font-weight: 700;
        }

        .priority input:checked + span {
            border-color: #111827;

            background: #111827;
            color: #ffffff;
        }

        .submit {
            width: 100%;

            padding:
                14px
                20px;

            border: 0;
            border-radius: 10px;

            background: #16a34a;
            color: #ffffff;

            font-size: 16px;
            font-weight: 800;

            cursor: pointer;
        }

        .privacy {
            margin-top: 12px;

            color: #6b7280;

            font-size: 12px;

            line-height: 1.5;

            text-align: center;
        }

        .honeypot {
            position: absolute !important;

            left: -10000px !important;
            top: -10000px !important;

            width: 1px !important;
            height: 1px !important;

            overflow: hidden !important;
        }

        @media (
            max-width: 620px
        ) {

            body {
                padding: 10px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .field-full {
                grid-column: auto;
            }

            .priority {
                grid-template-columns:
                    1fr
                    1fr;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <div class="header">

        <div class="brand">
            ZNR LIDER · JAVNA PRIJAVA
        </div>

        <h1>
            Prijavi zapažanje
        </h1>

        <p>
            Prijavite opasno stanje, opasnu radnju
            ili skoro nezgodu. Za slanje prijave nije
            potrebna prijava u sustav.
        </p>

    </div>


    @if($errors->any())

        <div class="errors">

            <strong>
                Provjerite unesene podatke:
            </strong>

            <ul>

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    <form
        method="POST"
        action="{{ route(
            'public.observation.store',
            [
                'token' =>
                    $qrCode->token,
            ]
        ) }}"
        enctype="multipart/form-data"
    >

        @csrf


        <div class="honeypot">

            <label for="website">
                Website
            </label>

            <input
                type="text"
                id="website"
                name="website"
                value=""
                autocomplete="off"
                tabindex="-1"
            >

        </div>


        <div class="card">

            <h2>
                Osnovni podatci
            </h2>

            <div class="grid">

                <div class="field">

                    <label for="incident_date">
                        Datum
                        <span class="required">*</span>
                    </label>

                    <input
                        type="date"
                        id="incident_date"
                        name="incident_date"
                        value="{{
                            old(
                                'incident_date',
                                now()->format('Y-m-d')
                            )
                        }}"
                        max="{{ now()->format('Y-m-d') }}"
                        required
                    >

                </div>


                <div class="field">

                    <label for="observation_type">
                        Vrsta zapažanja
                        <span class="required">*</span>
                    </label>

                    <select
                        id="observation_type"
                        name="observation_type"
                        required
                    >

                        <option value="">
                            Odaberite
                        </option>

                        @foreach(
                            $observationTypes
                            as $value => $label
                        )

                            <option
                                value="{{ $value }}"
                                @selected(
                                    old(
                                        'observation_type'
                                    ) === $value
                                )
                            >
                                {{ $label }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="field field-full">

                    <label>
                        Prioritet
                        <span class="required">*</span>
                    </label>

                    <div class="priority">

                        @foreach(
                            $priorities
                            as $value => $label
                        )

                            <label>

                                <input
                                    type="radio"
                                    name="priority"
                                    value="{{ $value }}"
                                    @checked(
                                        old(
                                            'priority',
                                            'medium'
                                        ) === $value
                                    )
                                    required
                                >

                                <span>
                                    {{ $label }}
                                </span>

                            </label>

                        @endforeach

                    </div>

                    <div class="helper">
                        Kritično odaberite samo ako je potrebna
                        hitna reakcija.
                    </div>

                </div>


                <div class="field">

                    <label for="location">
                        Lokacija
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="location"
                        name="location"
                        maxlength="255"
                        value="{{ old('location') }}"
                        placeholder="Npr. Alatnica, skladište, proizvodnja..."
                        required
                    >

                </div>


                <div class="field">

                    <label for="potential_incident_type">
                        Vrsta opasnosti
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="potential_incident_type"
                        name="potential_incident_type"
                        maxlength="255"
                        list="hazards"
                        value="{{
                            old(
                                'potential_incident_type'
                            )
                        }}"
                        placeholder="Odaberite ili upišite"
                        required
                    >

                    <datalist id="hazards">

                        @foreach($hazards as $hazard)

                            <option value="{{ $hazard }}">

                        @endforeach

                    </datalist>

                </div>

            </div>

        </div>


        <div class="card">

            <h2>
                Opis i potrebna radnja
            </h2>

            <div class="grid">

                <div class="field field-full">

                    <label for="item">
                        Opis zapažanja
                        <span class="required">*</span>
                    </label>

                    <textarea
                        id="item"
                        name="item"
                        maxlength="2000"
                        placeholder="Opišite što ste primijetili..."
                        required
                    >{{ old('item') }}</textarea>

                </div>


                <div class="field field-full">

                    <label for="action">
                        Potrebna radnja
                    </label>

                    <textarea
                        id="action"
                        name="action"
                        maxlength="2000"
                        placeholder="Ako imate prijedlog, opišite što bi trebalo poduzeti..."
                    >{{ old('action') }}</textarea>

                </div>

            </div>

        </div>


        <div class="card">

            <h2>
                Fotografija i dodatni podatci
            </h2>

            <div class="grid">

                <div class="field field-full">

                    <label for="picture">
                        Fotografija
                    </label>

                    <input
                        type="file"
                        id="picture"
                        name="picture"
                        accept="image/jpeg,image/png,image/webp"
                        capture="environment"
                    >

                    <div class="helper">
                        Možete fotografirati problem ili odabrati
                        postojeću fotografiju. Najviše 10 MB.
                    </div>

                </div>


                <div class="field field-full">

                    <label for="comments">
                        Komentar
                    </label>

                    <textarea
                        id="comments"
                        name="comments"
                        maxlength="2000"
                        placeholder="Dodatni komentar..."
                    >{{ old('comments') }}</textarea>

                </div>


                <div class="field field-full">

                    <label for="reporter_contact">
                        Kontakt / ime prijavitelja
                    </label>

                    <input
                        type="text"
                        id="reporter_contact"
                        name="reporter_contact"
                        maxlength="255"
                        value="{{
                            old(
                                'reporter_contact'
                            )
                        }}"
                        placeholder="Opcionalno"
                    >

                    <div class="helper">
                        Polje nije obavezno. Prijavu možete poslati
                        anonimno.
                    </div>

                </div>

            </div>

        </div>


        <button
            type="submit"
            class="submit"
        >
            Pošalji zapažanje
        </button>


        <div class="privacy">
            Odgovorna osoba i rok provedbe određuju se
            nakon pregleda prijave od strane ovlaštenog
            korisnika organizacije.
        </div>

    </form>

</div>

</body>

</html>