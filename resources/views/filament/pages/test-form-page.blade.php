<x-filament-panels::page>
    <style>
        /* Light inputs in dark mode (kao u v2) */
        .force-light-input {
            background-color: #ffffff !important;
            color: #111827 !important;
            border: 1px solid #D1D5DB !important;
            border-radius: 10px !important;
            padding: 10px 12px !important;
            width: 100% !important;
        }
        .force-light-input::placeholder { color: #9CA3AF; }

        .force-light-input:focus {
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.35) !important; /* primary-ish */
            border-color: rgba(59, 130, 246, 0.9) !important;
        }

        /* Answer card look (profi “tiles”) */
        .answer-tile {
            border: 1px solid rgb(55 65 81);
            border-radius: 12px;
            padding: 12px;
            display: flex;
            gap: 10px;
            align-items: flex-start;
            cursor: pointer;
            transition: all .15s ease;
            background: rgba(17, 24, 39, 0.35);
        }
        .answer-tile:hover {
            border-color: rgba(59, 130, 246, 0.9);
            background: rgba(17, 24, 39, 0.6);
        }

        .q-badge {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 13px;
            background: rgba(59,130,246,.20);
            border: 1px solid rgba(59,130,246,.35);
            color: #cfe3ff;
            flex: 0 0 34px;
        }

        .grid-answers {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        @media (min-width: 900px) {
            .grid-answers { grid-template-columns: 1fr 1fr; }
        }

        .meta-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        @media (min-width: 900px) {
            .meta-row { grid-template-columns: 1fr 1fr 1fr; }
        }

        .muted { color: rgba(229, 231, 235, 0.65); }
        .hint  { color: #fbbf24; font-size: 12px; }
    </style>

    <div style="max-width: 1100px; margin: 0 auto; display: grid; gap: 18px;">

        {{-- Header --}}
        <div style="display:flex; align-items:flex-end; justify-content:space-between; gap: 12px;">
            <div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="font-size: 22px;">📝</div>
                    <h1 style="font-size: 26px; font-weight: 800; margin: 0;">
                        {{ $this->test->naziv }}
                    </h1>
                </div>
                <div class="muted" style="margin-top: 6px; font-size: 13px;">
                    Minimalni prolaz: <b style="color: rgba(229,231,235,.9)">{{ $this->test->minimalni_prolaz ?? 75 }}%</b>
                </div>
            </div>

            <div class="muted" style="font-size: 13px;">
                Rješavanje testa
            </div>
        </div>

        {{-- Error --}}
        @if (session()->has('error'))
            <x-filament::card>
                <div style="color:#f87171; font-weight: 600;">
                    {{ session('error') }}
                </div>
            </x-filament::card>
        @endif

        @if (! $this->submitted)

            {{-- Candidate data --}}
            <x-filament::card>
                <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom: 14px;">
                    <div style="font-size: 16px; font-weight: 700;">Podaci kandidata</div>
                </div>

                <div class="meta-row">
                    <div>
                        <label class="muted" style="display:block; font-size:12px; margin-bottom:6px;">Ime i prezime</label>
                        <input type="text" wire:model="ime_prezime" class="force-light-input" required>
                        @error('ime_prezime') <div style="color:#f87171; font-size:12px; margin-top:6px;">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="muted" style="display:block; font-size:12px; margin-bottom:6px;">Radno mjesto</label>
                        <input type="text" wire:model="radno_mjesto" class="force-light-input">
                    </div>

                    <div>
                        <label class="muted" style="display:block; font-size:12px; margin-bottom:6px;">Datum rođenja</label>
                        <input type="date" wire:model="datum_rodjenja" class="force-light-input">
                    </div>
                </div>
            </x-filament::card>

            {{-- Questions --}}
            <form wire:submit.prevent="submit" style="display:grid; gap: 14px;">
                @foreach ($this->test->questions as $question)
                    <x-filament::card>
                        <div style="display:flex; gap: 12px; align-items:flex-start;">
                            <div class="q-badge">{{ $loop->iteration }}</div>

                            <div style="flex:1;">
                                <div style="font-size: 16px; font-weight: 700; line-height: 1.35;">
                                    {{ $question->tekst }}
                                </div>

                                @if ($question->visestruki_odgovori)
                                    <div class="hint" style="margin-top: 6px;">
                                        (više točnih odgovora)
                                    </div>
                                @endif

                                @if ($question->slika_path)
                                    <div style="margin-top: 12px;">
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::url($question->slika_path) }}"
                                            style="max-height: 260px; border-radius: 12px; border: 1px solid rgb(31 41 55);"
                                        >
                                    </div>
                                @endif

                                <div style="margin-top: 14px;" class="grid-answers">
                                    @foreach ($question->answers as $answer)
                                        @php $inputId = 'q'.$question->id.'_a'.$answer->id; @endphp

                                        <label for="{{ $inputId }}" class="answer-tile" wire:key="q-{{ $question->id }}-a-{{ $answer->id }}">
                                            @if ($question->visestruki_odgovori)
                                                <input
                                                    id="{{ $inputId }}"
                                                    type="checkbox"
                                                    wire:model.defer="odgovori.{{ $question->id }}.{{ $answer->id }}"
                                                    style="margin-top: 4px;"
                                                >
                                            @else
                                                <input
                                                    id="{{ $inputId }}"
                                                    type="radio"
                                                    value="{{ $answer->id }}"
                                                    wire:model.defer="odgovori.{{ $question->id }}"
                                                    style="margin-top: 4px;"
                                                >
                                            @endif

                                            <div style="flex:1;">
                                                @if ($answer->slika_path)
                                                    <img
                                                        src="{{ \Illuminate\Support\Facades\Storage::url($answer->slika_path) }}"
                                                        style="width: 120px; height: 120px; object-fit: contain; border-radius: 10px; border: 1px solid rgb(31 41 55); background: rgba(3,7,18,.5); margin-bottom: 10px;"
                                                    >
                                                @endif

                                                <div style="font-size: 14px; line-height: 1.45; color: rgba(229,231,235,.92);">
                                                    {{ $answer->tekst }}
                                                </div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </x-filament::card>
                @endforeach

                {{-- Actions --}}
                <div style="display:flex; align-items:center; justify-content:space-between; gap: 10px; margin-top: 4px;">
                    <x-filament::button
                        tag="a"
                        color="gray"
                        href="{{ \App\Filament\Pages\AvailableTestsPage::getUrl() }}"
                    >
                        ← Povratak
                    </x-filament::button>

                    <x-filament::button type="submit" color="primary">
                        Pošalji test
                    </x-filament::button>
                </div>
            </form>

        @else

            {{-- Result --}}
            <x-filament::card>
                <div style="text-align:center; padding: 18px 8px; display:grid; gap: 10px;">
                    <div style="font-size: 26px; font-weight: 800;">
                        Rezultat: {{ round($this->rezultat, 2) }}%
                    </div>

                    @if ($this->prolaz)
                        <div style="color:#34d399; font-weight: 700; font-size: 16px;">
                            ✔ Test je položen
                        </div>
                    @else
                        <div style="color:#f87171; font-weight: 700; font-size: 16px;">
                            ✖ Test nije položen
                        </div>
                    @endif

                    <div style="margin-top: 6px;">
                        <x-filament::button
                            tag="a"
                            color="gray"
                            href="{{ \App\Filament\Pages\AvailableTestsPage::getUrl() }}"
                        >
                            ← Povratak na testove
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::card>

        @endif
    </div>
</x-filament-panels::page>