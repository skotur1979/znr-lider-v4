<x-filament-panels::page>
    <style>
        .test-page-wrap{
            max-width: 1120px;
            margin: 0 auto;
        }

        .test-header{
            display:flex;
            align-items:flex-start;
            gap:16px;
            margin-bottom:24px;
            padding-bottom:18px;
            border-bottom:1px solid #e5e7eb;
        }

        .dark .test-header{
            border-bottom:1px solid rgba(255,255,255,.08);
        }

        .test-header-icon{
            width:48px;
            height:48px;
            border-radius:999px;
            background:#1d4ed8;
            color:#fff;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:20px;
            flex-shrink:0;
            box-shadow:0 10px 25px rgba(29,78,216,.18);
        }

        .test-header-title{
            font-size:2.2rem;
            line-height:1.1;
            font-weight:800;
            color:#0f172a;
            margin:0 0 6px 0;
            letter-spacing:-0.02em;
        }

        .dark .test-header-title{
            color:#fff;
        }

        .test-header-subtitle{
            font-size:15px;
            color:#f97316;
            font-weight:700;
        }

        .test-card{
            background:#ffffff;
            border:1px solid #e5e7eb;
            border-radius:20px;
            padding:24px;
            box-shadow:0 10px 26px rgba(15,23,42,.05);
        }

        .dark .test-card{
            background:#111827;
            border-color:#374151;
            box-shadow:none;
        }

        .intro-title{
            font-size:1.05rem;
            font-weight:800;
            color:#111827;
            margin-bottom:16px;
        }

        .dark .intro-title{
            color:#fff;
        }

        .candidate-grid{
        display:grid;
        grid-template-columns:repeat(4, minmax(0,1fr));
        gap:18px;
    }

        @media (max-width: 900px){
            .candidate-grid{
                grid-template-columns:1fr;
            }
        }

        .field-wrap label{
            display:block;
            font-size:14px;
            font-weight:700;
            color:#334155;
            margin-bottom:8px;
        }

        .dark .field-wrap label{
            color:#e5e7eb;
        }

        .field-input{
            width:100%;
            height:46px;
            border-radius:12px;
            border:1px solid #d1d5db;
            background:#fff;
            color:#111827;
            padding:0 14px;
            outline:none;
            transition:.15s ease;
        }

        .field-input:focus{
            border-color:#f59e0b;
            box-shadow:0 0 0 4px rgba(245,158,11,.12);
        }

        .dark .field-input{
            background:#0f172a;
            border-color:#374151;
            color:#fff;
        }

        .error-text{
            color:#dc2626;
            font-size:13px;
            margin-top:6px;
        }

        .question-card{
            margin-bottom:18px;
        }

        .question-head{
            display:flex;
            align-items:flex-start;
            gap:14px;
            margin-bottom:16px;
        }

        .question-number{
            width:38px;
            height:38px;
            border-radius:999px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:#e2e8f0;
            color:#0f172a;
            font-weight:800;
            font-size:15px;
            flex-shrink:0;
        }

        .dark .question-number{
            background:#334155;
            color:#fff;
        }

        .question-text{
            font-size:1.65rem;
            font-weight:800;
            line-height:1.3;
            color:#0f172a;
            letter-spacing:-0.02em;
        }

        .dark .question-text{
            color:#fff;
        }

        .question-help{
            color:#f97316;
            font-size:14px;
            font-weight:700;
            margin-left:6px;
        }

        .question-image{
            max-width:320px;
            margin:0 0 16px 52px;
            border-radius:12px;
            border:1px solid #e5e7eb;
            background:#fff;
            padding:6px;
        }

        .dark .question-image{
            border-color:#374151;
            background:#0f172a;
        }

        .answers-wrap{
            margin-left:52px;
        }

        @media (max-width: 640px){
            .question-image,
            .answers-wrap{
                margin-left:0;
            }

            .question-text{
                font-size:1.25rem;
            }

            .test-header-title{
                font-size:1.7rem;
            }
        }

        .answer-option{
            display:flex;
            gap:12px;
            align-items:flex-start;
            border:1px solid #d1d5db;
            border-radius:14px;
            padding:14px 16px;
            cursor:pointer;
            margin-bottom:12px;
            transition:all .15s ease;
            background:#fff;
        }

        .answer-option:hover{
            border-color:#f59e0b;
            box-shadow:0 8px 20px rgba(245,158,11,.10);
        }

        .dark .answer-option{
            border-color:#374151;
            background:#0f172a;
        }

        .answer-option input{
            width:20px;
            height:20px;
            margin-top:2px;
            accent-color:#f59e0b;
            flex-shrink:0;
        }

        .answer-option-content{
            font-size:16px;
            line-height:1.5;
            color:#111827;
            width:100%;
        }

        .dark .answer-option-content{
            color:#f9fafb;
        }

        .answer-option:has(input:checked){
            border-color:#f59e0b;
            background:#fff7ed;
        }

        .dark .answer-option:has(input:checked){
            background:rgba(245,158,11,.12);
        }

        .answer-option-content img{
            width:120px;
            height:120px;
            object-fit:contain;
            border-radius:10px;
            margin:0 0 8px 0;
            border:1px solid #e5e7eb;
            background:#fff;
            padding:4px;
        }

        .dark .answer-option-content img{
            border-color:#374151;
            background:#111827;
        }

        .actions-row{
            display:flex;
            justify-content:flex-end;
            gap:12px;
            margin-top:26px;
            flex-wrap:wrap;
        }

        .result-shell{
            max-width: 820px;
            margin: 28px auto 0;
        }

        .result-card{
            position: relative;
            overflow: hidden;
            text-align: center;
            padding: 34px 26px;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            background:
                radial-gradient(circle at top left, rgba(249,115,22,.08), transparent 34%),
                radial-gradient(circle at top right, rgba(59,130,246,.07), transparent 36%),
                linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow:
                0 14px 34px rgba(15,23,42,.07),
                0 1px 0 rgba(255,255,255,.8) inset;
        }

        .dark .result-card{
            border-color: rgba(255,255,255,.08);
            background:
                radial-gradient(circle at top left, rgba(249,115,22,.16), transparent 34%),
                radial-gradient(circle at top right, rgba(59,130,246,.13), transparent 36%),
                linear-gradient(180deg, rgba(17,24,39,.98) 0%, rgba(15,23,42,.98) 100%);
            box-shadow: 0 16px 34px rgba(0,0,0,.28);
        }

        .result-badge{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:.45rem .85rem;
            border-radius:999px;
            font-size:.82rem;
            font-weight:800;
            margin-bottom:1rem;
            border:1px solid #fed7aa;
            background:#fff7ed;
            color:#c2410c;
        }

        .dark .result-badge{
            border-color:rgba(249,115,22,.25);
            background:rgba(249,115,22,.12);
            color:#fdba74;
        }

        .result-percent{
            font-size:3rem;
            line-height:1.05;
            font-weight:900;
            color:#0f172a;
            margin-bottom:10px;
            letter-spacing:-0.03em;
        }

        .dark .result-percent{
            color:#fff;
        }

        .result-status{
            font-size:1.2rem;
            font-weight:800;
            margin-bottom:1rem;
        }

        .result-pass{
            color:#15803d;
        }

        .result-fail{
            color:#dc2626;
        }

        .result-meta{
            margin: 0 auto;
            max-width: 540px;
            font-size: .97rem;
            color: #64748b;
            line-height: 1.6;
        }

        .dark .result-meta{
            color:#94a3b8;
        }

        .result-actions{
            margin-top:26px;
            display:flex;
            justify-content:center;
            gap:12px;
            flex-wrap:wrap;
        }
    </style>

    <div class="test-page-wrap">
        <div class="test-header">
            <div class="test-header-icon">📝</div>

            <div>
                <h1 class="test-header-title">{{ $this->test->naziv }}</h1>

                @if (! empty($this->test->minimalni_prolaz))
                    <div class="test-header-subtitle">
                        Minimalni prolaz: {{ $this->test->minimalni_prolaz }}%
                    </div>
                @endif
            </div>
        </div>

        @if (! $this->submitted)
            <form wire:submit="submit">
                <div class="test-card" style="margin-bottom:20px;">
                    <div class="intro-title">Podaci kandidata</div>

                    <div class="candidate-grid">
                    <div class="field-wrap">
                        <label for="ime_prezime">Ime i prezime</label>
                        <input
                            id="ime_prezime"
                            type="text"
                            wire:model.live="ime_prezime"
                            class="field-input"
                        >
                        @error('ime_prezime')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field-wrap">
                        <label for="oib">OIB</label>
                        <input
                            id="oib"
                            type="text"
                            wire:model.live="oib"
                            maxlength="11"
                            inputmode="numeric"
                            placeholder="Upišite OIB"
                            class="field-input"
                        >
                        @error('oib')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field-wrap">
                        <label for="radno_mjesto">Radno mjesto</label>
                        <input
                            id="radno_mjesto"
                            type="text"
                            wire:model.live="radno_mjesto"
                            class="field-input"
                        >
                        @error('radno_mjesto')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="field-wrap">
                        <label for="datum_rodjenja">Datum rođenja</label>
                        <input
                            id="datum_rodjenja"
                            type="date"
                            wire:model.live="datum_rodjenja"
                            class="field-input"
                        >
                        @error('datum_rodjenja')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                </div>

                @foreach ($this->test->questions as $question)
                    <div class="test-card question-card">
                        <div class="question-head">
                            <div class="question-number">{{ $loop->iteration }}</div>

                            <div class="question-text">
                                {{ $question->tekst }}

                                @if ($question->visestruki_odgovori)
                                    <span class="question-help">(više točnih odgovora)</span>
                                @endif
                            </div>
                        </div>

                        @if ($question->slika_path)
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::url($question->slika_path) }}"
                                class="question-image"
                                alt="Slika pitanja"
                            >
                        @endif

                        <div class="answers-wrap">
                            @foreach ($question->answers as $answer)
                                @php
                                    $inputId = 'q' . $question->id . '_a' . $answer->id;
                                @endphp

                                <label class="answer-option" for="{{ $inputId }}">
                                    @if ($question->visestruki_odgovori)
                                        <input
                                            id="{{ $inputId }}"
                                            type="checkbox"
                                            wire:model.defer="odgovori.{{ $question->id }}.{{ $answer->id }}"
                                        >
                                    @else
                                        <input
                                            id="{{ $inputId }}"
                                            type="radio"
                                            value="{{ $answer->id }}"
                                            wire:model.defer="odgovori.{{ $question->id }}"
                                        >
                                    @endif

                                    <div class="answer-option-content">
                                        @if ($answer->slika_path)
                                            <img
                                                src="{{ \Illuminate\Support\Facades\Storage::url($answer->slika_path) }}"
                                                alt="Slika odgovora"
                                            >
                                        @endif

                                        {{ $answer->tekst }}
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="actions-row">
                    <x-filament::button
                        tag="a"
                        color="gray"
                        href="{{ \App\Filament\Pages\AvailableTestsPage::getUrl() }}"
                    >
                        ← Povratak
                    </x-filament::button>

                    <x-filament::button type="submit">
                        Pošalji test
                    </x-filament::button>
                </div>
            </form>
        @else
            <div class="result-shell">
                <div class="result-card">
                    <div class="result-badge">Rezultat testa</div>

                    <div class="result-percent">
                        {{ round($this->rezultat, 2) }}%
                    </div>

                    @if ($this->prolaz)
                        <div class="result-status result-pass">
                            Test je položen
                        </div>
                    @else
                        <div class="result-status result-fail">
                            Test nije položen
                        </div>
                    @endif

                    <div class="result-meta">
                        Minimalni prag prolaza za ovaj test je
                        <strong>{{ $this->test->minimalni_prolaz ?? 75 }}%</strong>.
                    </div>

                    <div class="result-actions">
                        <x-filament::button
                            tag="a"
                            color="gray"
                            href="{{ \App\Filament\Pages\AvailableTestsPage::getUrl() }}"
                        >
                            ← Povratak na testove
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>