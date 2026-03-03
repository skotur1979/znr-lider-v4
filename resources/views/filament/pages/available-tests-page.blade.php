<x-filament-panels::page>
    <div class="max-w-5xl mx-auto space-y-4">
        @forelse ($tests as $test)
            <x-filament::card>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="font-semibold text-base">
                            {{ $test->naziv }}
                        </div>

                        <div class="text-sm opacity-70">
                            Minimalni prolaz: {{ $test->minimalni_prolaz ?? '—' }}%
                        </div>
                    </div>

                    <x-filament::button
                        tag="a"
                        color="primary"
                        href="{{ \App\Filament\Pages\TestFormPage::getUrl(parameters: ['test' => $test->id]) }}"
                    >
                        Pokreni test
                    </x-filament::button>
                </div>
            </x-filament::card>
        @empty
            <x-filament::card>
                <div class="text-sm opacity-70">
                    Nema dostupnih testova.
                </div>
            </x-filament::card>
        @endforelse
    </div>
</x-filament-panels::page>