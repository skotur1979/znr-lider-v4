<?php

namespace App\Filament\Pages;

use App\Models\AttemptAnswer;
use App\Models\Test;
use App\Models\TestAttempt;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TestFormPage extends Page
{
    protected string $view = 'filament.pages.test-form-page';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'testovi/{test}';

    protected static ?string $title = 'Rješavanje testa';

    public Test $test;

    public string $ime_prezime = '';
    public string $oib = '';
    public string $radno_mjesto = '';
    public ?string $datum_rodjenja = null;

    public array $odgovori = [];

    public bool $submitted = false;
    public ?float $rezultat = null;
    public bool $prolaz = false;

    public function mount(Test $test): void
    {
        abort_unless(Auth::check(), 401);

        if (
            ! Auth::user()?->isSuperAdmin()
            && ! is_null($test->user_id)
            && (int) $test->user_id !== (int) Auth::id()
        ) {
            abort(403);
        }

        $this->test = $test->load('questions.answers');

        // Ručni unos podataka kandidata - bez povlačenja iz korisnika
        $this->ime_prezime = '';
        $this->oib = '';
        $this->radno_mjesto = '';
        $this->datum_rodjenja = null;

        foreach ($this->test->questions as $q) {
            if ($q->visestruki_odgovori) {
                $this->odgovori[$q->id] = [];

                foreach ($q->answers as $a) {
                    $this->odgovori[$q->id][$a->id] = false;
                }
            } else {
                $this->odgovori[$q->id] = null;
            }
        }
    }

    public function submit(): void
    {
        $this->validate([
            'ime_prezime' => ['required', 'string', 'max:255'],
            'oib' => ['required', 'digits:11'],
            'radno_mjesto' => ['nullable', 'string', 'max:255'],
            'datum_rodjenja' => ['nullable', 'date'],
        ], [
            'ime_prezime.required' => 'Ime i prezime je obavezno.',
            'datum_rodjenja.date' => 'Datum rođenja nije ispravan.',
        ]);

        $unanswered = $this->test->questions->filter(function ($q) {
            $sel = $this->odgovori[$q->id] ?? null;

            if ($q->visestruki_odgovori) {
                return ! collect($sel ?? [])->filter(fn ($v) => (bool) $v)->count();
            }

            return empty($sel);
        });

        if ($unanswered->isNotEmpty()) {
            Notification::make()
                ->title('Niste odgovorili na sva pitanja.')
                ->danger()
                ->send();

            return;
        }

        try {
            $bodovi = 0;
            $ukupnoPitanja = $this->test->questions->count();
            $rows = [];

            foreach ($this->test->questions as $pitanje) {
                $tocni = $pitanje->answers
                    ->where('is_correct', true)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->sort()
                    ->values();

                if ($pitanje->visestruki_odgovori) {
                    $selectedIds = collect($this->odgovori[$pitanje->id] ?? [])
                        ->filter(fn ($v) => (bool) $v)
                        ->keys()
                        ->map(fn ($id) => (int) $id)
                        ->sort()
                        ->values();
                } else {
                    $selectedIds = collect([(int) ($this->odgovori[$pitanje->id] ?? 0)])
                        ->filter()
                        ->values();
                }

                if ($tocni->count() > 0 && $tocni->all() === $selectedIds->all()) {
                    $bodovi++;
                }

                foreach ($selectedIds as $answerId) {
                    $rows[] = [
                        'test_attempt_id' => 0,
                        'question_id' => (int) $pitanje->id,
                        'answer_id' => (int) $answerId,
                    ];
                }
            }

            $postotak = $ukupnoPitanja > 0
                ? round(($bodovi / $ukupnoPitanja) * 100, 2)
                : 0.0;

            $prolaz = $postotak >= (float) ($this->test->minimalni_prolaz ?? 75);

            DB::transaction(function () use ($postotak, $prolaz, $bodovi, &$rows) {
                $attempt = TestAttempt::create([
                    'user_id' => Auth::id(),
                    'test_id' => $this->test->id,
                    'ime_prezime' => $this->ime_prezime,
                    'radno_mjesto' => $this->radno_mjesto,
                    'datum_rodjenja' => $this->datum_rodjenja,
                    'bodovi_osvojeni' => $bodovi,
                    'oib' => $this->oib,
                    'rezultat' => $postotak,
                    'prolaz' => $prolaz,
                ]);

                foreach ($rows as &$r) {
                    $r['test_attempt_id'] = $attempt->id;
                }

                if (! empty($rows)) {
                    AttemptAnswer::insert($rows);
                }
            });

            $this->rezultat = $postotak;
            $this->prolaz = $prolaz;
            $this->submitted = true;

            Notification::make()
                ->title('Test je uspješno poslan.')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title('Dogodila se greška prilikom spremanja testa.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}