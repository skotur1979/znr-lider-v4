<?php

namespace App\Http\Controllers\PublicQr;

use App\Http\Controllers\Controller;
use App\Mail\ObservationNotificationMail;
use App\Models\Observation;
use App\Models\QrCode;
use App\Models\User;
use App\Services\StorageQuotaService;
use App\Support\ObservationOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ObservationQrController extends Controller
{
    public function show(
        string $token
    ) {
        $qrCode =
            $this->findObservationQr(
                $token
            );

        $owner =
            $this->findOwner(
                $qrCode
            );

        /*
         * Brojimo otvaranje javnog obrasca.
         */
        $qrCode->recordScan();

        return view(
            'public.qr.observation-form',
            [
                'qrCode' =>
                    $qrCode,

                'owner' =>
                    $owner,

                'observationTypes' =>
                    ObservationOptions
                        ::publicTypes(),

                'priorities' =>
                    ObservationOptions
                        ::priorities(),

                'hazards' =>
                    ObservationOptions
                        ::hazards(),
            ]
        );
    }

    public function store(
        Request $request,
        string $token
    ): RedirectResponse {
        $qrCode =
            $this->findObservationQr(
                $token
            );

        $owner =
            $this->findOwner(
                $qrCode
            );

        $validated =
            $request->validate([
                'incident_date' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                ],

                'observation_type' => [
                    'required',
                    Rule::in(
                        array_keys(
                            ObservationOptions
                                ::publicTypes()
                        )
                    ),
                ],

                'priority' => [
                    'required',
                    Rule::in(
                        array_keys(
                            ObservationOptions
                                ::priorities()
                        )
                    ),
                ],

                'location' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'potential_incident_type' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'item' => [
                    'required',
                    'string',
                    'max:2000',
                ],

                'action' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],

                'comments' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],

                'reporter_contact' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'picture' => [
                    'nullable',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:10240',
                ],

                /*
                 * Jednostavan honeypot.
                 * Normalan korisnik ga nikad ne vidi.
                 */
                'website' => [
                    'nullable',
                    'max:0',
                ],
            ], [
                'incident_date.required' =>
                    'Datum je obavezan.',

                'incident_date.before_or_equal' =>
                    'Datum ne može biti u budućnosti.',

                'observation_type.required' =>
                    'Odaberite vrstu zapažanja.',

                'priority.required' =>
                    'Odaberite prioritet.',

                'location.required' =>
                    'Upišite lokaciju.',

                'potential_incident_type.required' =>
                    'Odaberite ili upišite vrstu opasnosti.',

                'item.required' =>
                    'Opis zapažanja je obavezan.',

                'picture.image' =>
                    'Priložena datoteka mora biti fotografija.',

                'picture.mimes' =>
                    'Dopušteni formati fotografije su JPG, JPEG, PNG i WEBP.',

                'picture.max' =>
                    'Fotografija može imati najviše 10 MB.',
            ]);

        $picturePath =
            null;

        if (
            $request->hasFile(
                'picture'
            )
        ) {
            $picture =
                $request->file(
                    'picture'
                );

            if (
                ! app(
                    StorageQuotaService::class
                )->canUpload(
                    $picture,
                    $owner->getKey()
                )
            ) {
                return back()
                    ->withErrors([
                        'picture' =>
                            'Dosegnut je maksimalni prostor za pohranu organizacije. Fotografiju nije moguće spremiti.',
                    ])
                    ->withInput();
            }

            $extension =
                strtolower(
                    $picture->getClientOriginalExtension()
                );

            $fileName =
                'qr-'
                . now()->format(
                    'Ymd-His'
                )
                . '-'
                . Str::random(12)
                . '.'
                . $extension;

            $picturePath =
                $picture->storeAs(
                    'observations',
                    $fileName,
                    'public'
                );
        }

        try {
            $observation =
                Observation::query()
                    ->create([
                        /*
                         * Ownership nikada ne prihvaćamo
                         * iz javnog HTTP obrasca.
                         */
                        'user_id' =>
                            $owner->getKey(),

                        'source' =>
                            'qr_public',

                        'source_qr_code_id' =>
                            $qrCode->getKey(),

                        'incident_date' =>
                            $validated[
                                'incident_date'
                            ],

                        'observation_type' =>
                            $validated[
                                'observation_type'
                            ],

                        'priority' =>
                            $validated[
                                'priority'
                            ],

                        'location' =>
                            $this->clean(
                                $validated[
                                    'location'
                                ]
                            ),

                        'potential_incident_type' =>
                            $this->clean(
                                $validated[
                                    'potential_incident_type'
                                ]
                            ),

                        'item' =>
                            $this->clean(
                                $validated[
                                    'item'
                                ]
                            ),

                        'action' =>
                            $this->cleanNullable(
                                $validated[
                                    'action'
                                ] ?? null
                            ),

                        'comments' =>
                            $this->cleanNullable(
                                $validated[
                                    'comments'
                                ] ?? null
                            ),

                        'reporter_contact' =>
                            $this->cleanNullable(
                                $validated[
                                    'reporter_contact'
                                ] ?? null
                            ),

                        'picture_path' =>
                            $picturePath,

                        /*
                         * Javna osoba NE određuje
                         * workflow zapažanja.
                         */
                        'responsible' =>
                            null,

                        'target_date' =>
                            null,

                        'status' =>
                            'Not started',

                        'notification_emails' =>
                            [],
                    ]);
        } catch (\Throwable $exception) {

            if (
                filled($picturePath)
                && Storage::disk('public')
                    ->exists(
                        $picturePath
                    )
            ) {
                Storage::disk('public')
                    ->delete(
                        $picturePath
                    );
            }

            throw $exception;
        }

        /*
        |--------------------------------------------------------------------------
        | HIGH / CRITICAL E-MAIL
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $observation->priority,
                [
                    'high',
                    'critical',
                ],
                true
            )
            && filled(
                $owner->email
            )
        ) {
            try {
                Mail::to(
                    $owner->email
                )->send(
                    new ObservationNotificationMail(
                        $observation
                    )
                );

                $observation
                    ->updateQuietly([
                        'notification_emails' =>
                            [
                                $owner->email,
                            ],

                        'sent_at' =>
                            now(),
                    ]);
            } catch (\Throwable $exception) {
                /*
                 * Zapažanje je važnije od maila.
                 * Ako mail ne uspije, zapis ostaje spremljen.
                 */
                report(
                    $exception
                );
            }
        }

        return redirect()
            ->route(
                'public.observation.success',
                [
                    'token' =>
                        $qrCode->token,
                ]
            );
    }

    public function success(
        string $token
    ) {
        $qrCode =
            $this->findObservationQr(
                $token
            );

        $this->findOwner(
            $qrCode
        );

        return view(
            'public.qr.observation-success',
            [
                'qrCode' =>
                    $qrCode,
            ]
        );
    }

    protected function findObservationQr(
        string $token
    ): QrCode {
        return QrCode::query()
            ->where(
                'token',
                $token
            )
            ->where(
                'type',
                'observation_report'
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                'qrable_type',
                User::class
            )
            ->firstOrFail();
    }

    protected function findOwner(
        QrCode $qrCode
    ): User {
        $owner =
            User::query()
                ->whereKey(
                    $qrCode->qrable_id
                )
                ->first();

        abort_unless(
            $owner,
            404
        );

        abort_unless(
            (int) $owner->getKey()
                === (int) $qrCode->owner_id,
            404
        );

        return $owner;
    }

    protected function clean(
        string $value
    ): string {
        return trim(
            strip_tags(
                $value
            )
        );
    }

    protected function cleanNullable(
        ?string $value
    ): ?string {
        if (blank($value)) {
            return null;
        }

        return $this->clean(
            $value
        );
    }
}
