<?php

namespace App\Filament\Pages;

use App\Models\AttemptAnswer;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
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
        $user = Auth::user();

        abort_unless($user, 401);

        /*
         * Superadmin može otvoriti sve testove.
         *
         * Organizacijski korisnici mogu koristiti:
         * - globalni test (user_id = NULL)
         * - test svoje organizacije
         *
         * organizationUserIds uključuje glavnog korisnika
         * i podkorisnike radi kompatibilnosti sa starijim
         * zapisima koji su možda spremani na Auth::id().
         */
        if (! $user->isSuperAdmin()) {
            $ownerId = $user->ownerId();

            $organizationUserIds = User::query()
                ->where('id', $ownerId)
                ->orWhere('parent_user_id', $ownerId)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $canUseTest =
                $test->user_id === null
                || in_array(
                    (int) $test->user_id,
                    $organizationUserIds,
                    true
                );

            abort_unless($canUseTest, 403);
        }

        $this->test = $test->load(
            'questions.answers'
        );

        /*
         * Podaci kandidata unose se ručno.
         */
        $this->ime_prezime = '';
        $this->oib = '';
        $this->radno_mjesto = '';
        $this->datum_rodjenja = null;

        foreach ($this->test->questions as $question) {
            if ($question->visestruki_odgovori) {
                $this->odgovori[$question->id] = [];

                foreach ($question->answers as $answer) {
                    $this->odgovori[$question->id][$answer->id] = false;
                }
            } else {
                $this->odgovori[$question->id] = null;
            }
        }
    }

    public function submit(): void
    {
        $user = Auth::user();

        abort_unless($user, 401);

        /*
         * Ponovna autorizacija pri submitu.
         *
         * Ne oslanjamo se samo na mount(), nego ponovno
         * provjeravamo smije li korisnik koristiti ovaj test.
         */
        if (! $user->isSuperAdmin()) {
            $ownerId = $user->ownerId();

            $organizationUserIds = User::query()
                ->where('id', $ownerId)
                ->orWhere('parent_user_id', $ownerId)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $canUseTest =
                $this->test->user_id === null
                || in_array(
                    (int) $this->test->user_id,
                    $organizationUserIds,
                    true
                );

            abort_unless($canUseTest, 403);
        }

        $this->validate(
            [
                'ime_prezime' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'oib' => [
                    'required',
                    'digits:11',
                ],

                'radno_mjesto' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'datum_rodjenja' => [
                    'nullable',
                    'date',
                ],
            ],
            [
                'ime_prezime.required' =>
                    'Ime i prezime je obavezno.',

                'oib.required' =>
                    'OIB je obavezan.',

                'oib.digits' =>
                    'OIB mora sadržavati točno 11 znamenki.',

                'datum_rodjenja.date' =>
                    'Datum rođenja nije ispravan.',
            ]
        );

        /*
         * Provjera jesu li odgovorena sva pitanja.
         */
        $unanswered = $this->test->questions
            ->filter(function ($question) {
                $selected =
                    $this->odgovori[$question->id]
                    ?? null;

                if ($question->visestruki_odgovori) {
                    return ! collect($selected ?? [])
                        ->filter(
                            fn ($value): bool =>
                                (bool) $value
                        )
                        ->count();
                }

                return empty($selected);
            });

        if ($unanswered->isNotEmpty()) {
            Notification::make()
                ->title(
                    'Niste odgovorili na sva pitanja.'
                )
                ->danger()
                ->send();

            return;
        }

        try {
            /*
             * Ponovno učitavamo pitanja i odgovore
             * prije izračuna rezultata.
             */
            $this->test->load(
                'questions.answers'
            );

            $bodovi = 0;

            $ukupnoPitanja =
                $this->test->questions->count();

            $rows = [];

            foreach (
                $this->test->questions
                as $pitanje
            ) {
                /*
                 * ID-evi točnih odgovora.
                 */
                $tocni = $pitanje->answers
                    ->where('is_correct', true)
                    ->pluck('id')
                    ->map(
                        fn ($id): int =>
                            (int) $id
                    )
                    ->sort()
                    ->values();

                /*
                 * ID-evi odgovora koje je kandidat odabrao.
                 */
                if ($pitanje->visestruki_odgovori) {
                    $selectedIds = collect(
                        $this->odgovori[$pitanje->id]
                        ?? []
                    )
                        ->filter(
                            fn ($value): bool =>
                                (bool) $value
                        )
                        ->keys()
                        ->map(
                            fn ($id): int =>
                                (int) $id
                        )
                        ->sort()
                        ->values();
                } else {
                    $selectedIds = collect([
                        (int) (
                            $this->odgovori[$pitanje->id]
                            ?? 0
                        ),
                    ])
                        ->filter()
                        ->values();
                }

                /*
                 * Pitanje vrijedi jedan bod samo ako je
                 * odabran točno cijeli skup ispravnih odgovora.
                 */
                if (
                    $tocni->count() > 0
                    && $tocni->all()
                        === $selectedIds->all()
                ) {
                    $bodovi++;
                }

                /*
                 * Spremamo svaki odabrani odgovor.
                 */
                foreach ($selectedIds as $answerId) {
                    $rows[] = [
                        'test_attempt_id' => 0,
                        'question_id' =>
                            (int) $pitanje->id,
                        'answer_id' =>
                            (int) $answerId,
                    ];
                }
            }

            $postotak =
                $ukupnoPitanja > 0
                    ? round(
                        ($bodovi / $ukupnoPitanja)
                        * 100,
                        2
                    )
                    : 0.0;

            $prolaz =
                $postotak >=
                (float) (
                    $this->test
                        ->minimalni_prolaz
                    ?? 75
                );

            /*
             * Ključna multi-tenant logika:
             *
             * organizacijski korisnik ->
             * rezultat pripada ownerId() organizacije
             *
             * superadmin ->
             * rezultat ostaje vezan uz superadmina ako
             * eventualno sam riješi test.
             */
            $attemptOwnerId =
                $user->isSuperAdmin()
                    ? $user->id
                    : $user->ownerId();

            DB::transaction(
                function () use (
                    $postotak,
                    $prolaz,
                    $bodovi,
                    $attemptOwnerId,
                    &$rows
                ): void {
                    $attempt =
                        TestAttempt::create([
                            'user_id' =>
                                $attemptOwnerId,

                            'test_id' =>
                                $this->test->id,

                            'ime_prezime' =>
                                trim(
                                    $this->ime_prezime
                                ),

                            'radno_mjesto' =>
                                filled(
                                    $this->radno_mjesto
                                )
                                    ? trim(
                                        $this
                                            ->radno_mjesto
                                    )
                                    : null,

                            'datum_rodjenja' =>
                                $this->datum_rodjenja,

                            'bodovi_osvojeni' =>
                                $bodovi,

                            'oib' =>
                                $this->oib,

                            'rezultat' =>
                                $postotak,

                            'prolaz' =>
                                $prolaz,
                        ]);

                    foreach ($rows as &$row) {
                        $row['test_attempt_id'] =
                            $attempt->id;
                    }

                    unset($row);

                    if (! empty($rows)) {
                        AttemptAnswer::insert(
                            $rows
                        );
                    }
                }
            );

            $this->rezultat = $postotak;
            $this->prolaz = $prolaz;
            $this->submitted = true;

            Notification::make()
                ->title(
                    'Test je uspješno poslan.'
                )
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(
                    'Dogodila se greška prilikom spremanja testa.'
                )
                ->body(
                    $exception->getMessage()
                )
                ->danger()
                ->send();
        }
    }
}