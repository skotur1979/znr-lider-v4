<?php

namespace App\Filament\Pages;

use App\Models\AttemptAnswer;
use App\Models\Employee;
use App\Models\Test;
use App\Models\TestAttempt;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TestFormPage extends Page
{
    // ✅ Filament v4: view je NON-static
    protected string $view = 'filament.pages.test-form-page';

    // ✅ Ne prikazuj u navigaciji (jer ima {test} parametar)
    protected static bool $shouldRegisterNavigation = false;

    /**
     * ✅ VAŽNO:
     * Ne smije biti "tests/{test}" jer TestResource koristi /tests i /tests/create.
     * Ovo ti uzrokuje 404 na /admin/tests/create.
     */
    protected static ?string $slug = 'testovi/{test}';

    // (opcionalno) naslov taba/breadcrumba
    protected static ?string $title = 'Rješavanje testa';

    public Test $test;

    public string $ime_prezime = '';
    public string $radno_mjesto = '';
    public ?string $datum_rodjenja = null;

    public array $odgovori = [];

    public bool $submitted = false;
    public ?float $rezultat = null;
    public bool $prolaz = false;

    public function mount(Test $test): void
    {
        abort_unless(Auth::check(), 401);

        $this->test = $test->load('questions.answers');

        $employee = Employee::where('user_id', Auth::id())->first();

        if ($employee) {
            $this->ime_prezime  = (string) ($employee->ime_prezime ?? '');
            $this->radno_mjesto = (string) ($employee->radno_mjesto ?? '');

            $this->datum_rodjenja = $employee->datum_rodjenja
                ? Carbon::parse($employee->datum_rodjenja)->format('Y-m-d')
                : null;
        } else {
            $this->ime_prezime = (string) (Auth::user()->name ?? '');
        }

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
            'ime_prezime'    => 'required|string',
            'radno_mjesto'   => 'nullable|string',
            'datum_rodjenja' => 'nullable|string',
        ]);

        $parsedDate = null;

        if (! empty($this->datum_rodjenja)) {
            $formats = ['Y-m-d', 'd.m.Y', 'd/m/Y'];

            foreach ($formats as $format) {
                try {
                    $parsedDate = Carbon::createFromFormat($format, $this->datum_rodjenja);
                    break;
                } catch (\Throwable $e) {}
            }

            if (! $parsedDate) {
                $this->dispatch('notify', type: 'danger', message: 'Datum rođenja nije u ispravnom formatu.');
                return;
            }
        }

        $unanswered = $this->test->questions->filter(function ($q) {
            $sel = $this->odgovori[$q->id] ?? null;

            if ($q->visestruki_odgovori) {
                return ! collect($sel ?? [])->filter(fn ($v) => (bool) $v)->count();
            }

            return empty($sel);
        });

        if ($unanswered->isNotEmpty()) {
            $this->dispatch('notify', type: 'danger', message: 'Niste odgovorili na sva pitanja.');
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
                        'question_id'     => (int) $pitanje->id,
                        'answer_id'       => (int) $answerId,
                    ];
                }
            }

            $postotak = $ukupnoPitanja > 0
                ? round(($bodovi / $ukupnoPitanja) * 100, 2)
                : 0.0;

            $prolaz = $postotak >= (float) $this->test->minimalni_prolaz;

            DB::transaction(function () use ($postotak, $prolaz, $bodovi, $parsedDate, &$rows) {
                $attempt = TestAttempt::create([
                    'user_id'         => Auth::id(),
                    'test_id'         => $this->test->id,
                    'ime_prezime'     => $this->ime_prezime,
                    'radno_mjesto'    => $this->radno_mjesto,
                    'datum_rodjenja'  => $parsedDate?->format('Y-m-d'),
                    'bodovi_osvojeni' => $bodovi,
                    'rezultat'        => $postotak,
                    'prolaz'          => $prolaz,
                ]);

                foreach ($rows as &$r) {
                    $r['test_attempt_id'] = $attempt->id;
                }

                if (! empty($rows)) {
                    AttemptAnswer::insert($rows);
                }

                $this->rezultat  = $postotak;
                $this->prolaz    = $prolaz;
                $this->submitted = true;
            });

        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', type: 'danger', message: 'Greška: ' . $e->getMessage());
        }
    }
}