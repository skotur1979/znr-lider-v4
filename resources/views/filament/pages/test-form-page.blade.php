<x-filament-panels::page>
<div class="max-w-5xl mx-auto p-6 space-y-6">

    <style>
        .force-light-input {
            background-color: #ffffff !important;
            color: #111827 !important;
            border-color: #D1D5DB !important;
        }
        .force-light-input::placeholder { color: #9CA3AF; }

        .force-light-input:-webkit-autofill,
        .force-light-input:-webkit-autofill:hover,
        .force-light-input:-webkit-autofill:focus {
            -webkit-text-fill-color: #111827;
            -webkit-box-shadow: 0 0 0px 1000px #ffffff inset;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>

    <div class="flex items-center gap-3">
        <div class="text-primary-400 text-2xl">📝</div>
        <h1 class="text-2xl font-semibold">Test: {{ $this->test->naziv }}</h1>
    </div>

    @if (session()->has('error'))
        <x-filament::card class="bg-gray-900/60 border-gray-800">
            <div class="text-danger-400 font-medium">
                {{ session('error') }}
            </div>
        </x-filament::card>
    @endif

    @if (! $submitted)
        <x-filament::card class="bg-gray-900/60 border-gray-800">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm text-gray-300">Ime i prezime</label>
                    <input type="text" wire:model="ime_prezime"
                           class="force-light-input mt-1 w-full rounded-lg border
                                  focus:ring-primary-500 focus:!border-primary-500" required>
                    @error('ime_prezime') <span class="text-danger-400 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="text-sm text-gray-300">Radno mjesto</label>
                    <input type="text" wire:model="radno_mjesto"
                           class="force-light-input mt-1 w-full rounded-lg border
                                  focus:ring-primary-500 focus:!border-primary-500">
                </div>

                <div>
                    <label class="text-sm text-gray-300">Datum rođenja</label>
                    <input type="date" wire:model="datum_rodjenja"
                           class="force-light-input mt-1 w-full rounded-lg border
                                  focus:ring-primary-500 focus:!border-primary-500">
                </div>
            </div>
        </x-filament::card>

        <form wire:submit.prevent="submit" class="space-y-4">
            @foreach ($this->test->questions as $question)
                <x-filament::card class="bg-gray-900/60 border-gray-800">
                    <p class="font-medium mb-3">
                        {{ $loop->iteration }}. {{ $question->tekst }}
                        @if ($question->visestruki_odgovori)
                            <span class="text-xs text-amber-400">(više točnih odgovora)</span>
                        @endif
                    </p>

                    @if ($question->slika_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($question->slika_path) }}"
                             alt="Slika uz pitanje"
                             class="mb-3 max-w-sm rounded-lg border border-gray-800">
                    @endif

                    <div class="flex flex-wrap gap-6">
                        @foreach ($question->answers as $answer)
                            @php
                                $inputId  = 'q'.$question->id.'_a'.$answer->id;
                                $group    = 'q'.$question->id;
                            @endphp

                            <label
                                wire:key="q-{{ $question->id }}-a-{{ $answer->id }}"
                                for="{{ $inputId }}"
                                class="flex items-start gap-3 w-full md:w-[45%] lg:w-[30%] p-3 rounded-lg border border-gray-700 hover:border-primary-500 cursor-pointer"
                            >
                                @if ($question->visestruki_odgovori)
                                    <input
                                        id="{{ $inputId }}"
                                        type="checkbox"
                                        name="{{ $group }}[]"
                                        wire:model.defer="odgovori.{{ $question->id }}.{{ $answer->id }}"
                                        class="h-4 w-4 text-primary-500 focus:ring-primary-600 bg-gray-900 border-gray-700 mt-1"
                                    >
                                @else
                                    <input
                                        id="{{ $inputId }}"
                                        type="radio"
                                        name="{{ $group }}"
                                        value="{{ $answer->id }}"
                                        wire:model.defer="odgovori.{{ $question->id }}"
                                        class="h-4 w-4 text-primary-500 focus:ring-primary-600 bg-gray-900 border-gray-700 mt-1"
                                    >
                                @endif

                                <div class="flex-1">
                                    @if ($answer->slika_path)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($answer->slika_path) }}"
                                             alt="Slika odgovora"
                                             class="w-32 h-32 object-contain border border-gray-800 rounded-md bg-gray-950 mb-2">
                                    @endif

                                    <span class="text-sm text-gray-200">{{ $answer->tekst }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </x-filament::card>
            @endforeach

            @php
                $neodgovorena = $this->test->questions->filter(function($q) use ($odgovori) {
                    $sel = $odgovori[$q->id] ?? null;
                    if ($q->visestruki_odgovori) {
                        return !collect($sel ?? [])->filter(fn($v)=> (bool)$v)->count();
                    }
                    return empty($sel);
                })->count();
            @endphp
            @if ($neodgovorena > 0)
                <div class="text-amber-400 text-sm">
                    Niste odgovorili na {{ $neodgovorena }} pitanja.
                </div>
            @endif

            <div class="flex items-center justify-end gap-3">
                <x-filament::button tag="a" color="gray" href="{{ \App\Filament\Pages\AvailableTestsPage::getUrl() }}">
                    ← Povratak na testove
                </x-filament::button>

                <x-filament::button type="submit" color="primary">
                    Pošalji test
                </x-filament::button>
            </div>
        </form>
    @else
        <x-filament::card class="bg-gray-900/60 border-gray-800">
            <div class="text-center space-y-3">
                <h2 class="text-2xl font-semibold">Rezultat: {{ round($rezultat, 2) }}%</h2>

                @if ($prolaz)
                    <p class="text-success-400 font-medium">🎉 Čestitamo! Test je položen.</p>
                @else
                    <p class="text-danger-400 font-medium">❌ Nažalost, test nije položen.</p>
                @endif

                <div class="pt-2">
                    <x-filament::button tag="a" color="gray" href="{{ \App\Filament\Pages\AvailableTestsPage::getUrl() }}">
                        ← Povratak na testove
                    </x-filament::button>
                </div>
            </div>
        </x-filament::card>
    @endif
</div>
</x-filament-panels::page>
