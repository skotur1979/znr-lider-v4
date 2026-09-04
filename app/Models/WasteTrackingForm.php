<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Services\FormVersionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class WasteTrackingForm extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule =
        'Prateći listovi otpada';

    protected $fillable = [
        'user_id',
        'form_version',
        'onto_record_id',

        // osnovno
        'document_number',
        'handover_date',
        'quantity_kg',
        'status',
        'description',
        'note',
        'locked_at',

        // POŠILJKA OTPADA (A)
        'waste_code_manual',
        'waste_kind',
        'waste_source_types',
        'hazard_properties',
        'physical_properties',
        'physical_properties_other',
        'packaging_types',
        'packaging_other',
        'package_count',
        'waste_description',
        'municipal_origin_note',

        // POŠILJATELJ (B)
        'sender_person_name',
        'sender_oib',
        'sender_nkd_code',
        'sender_contact_person',
        'sender_contact_data',
        'sender_name',
        'sender_address',

        // TOK OTPADA (F)
        'waste_owner_at_handover',
        'report_choice',
        'purpose_choice',
        'dispatch_point',
        'destination_point',
        'quantity_m3',
        'quantity_determination_choice',
        'handover_datetime',
        'handed_over_by',

        // PRIJEVOZNIK (C)
        'carrier_name',
        'carrier_oib',
        'carrier_authorization',
        'carrier_contact_person',
        'carrier_contact_data',
        'transport_modes',
        'carrier_vehicle_registration',
        'carrier_taken_over_by',
        'carrier_taken_over_at',
        'carrier_delivered_by',

        // PRIMATELJ (D)
        'receiver_name',
        'receiver_oib',
        'receiver_authorization',
        'receiver_address',
        'receiver_contact_person',
        'receiver_contact_data',
        'receiver_taken_over_by',
        'receiver_weighing_time',
        'receiver_measured_quantity_kg',

        // POSREDNIK / TRGOVAC (E)
        'trader_name',
        'trader_oib',
        'trader_authorization',
        'trader_contact_person',
        'trader_contact_data',

        // OBRAĐIVAČ (G)
        'processor_name',
        'processor_oib',
        'processor_authorization',
        'processing_completed_at',
        'final_processing_method',
        'processor_confirmed_by',

        // NAPOMENE I PRILOZI (H)
        'attachments',
    ];

    protected $casts = [
        'handover_date' => 'date',
        'quantity_kg' => 'decimal:2',
        'locked_at' => 'datetime',

        'waste_source_types' => 'array',
        'hazard_properties' => 'array',
        'physical_properties' => 'array',
        'packaging_types' => 'array',
        'transport_modes' => 'array',
        'attachments' => 'array',

        'quantity_m3' => 'decimal:3',
        'handover_datetime' => 'datetime',
        'carrier_taken_over_at' => 'datetime',
        'receiver_weighing_time' => 'datetime',
        'receiver_measured_quantity_kg' =>
            'decimal:2',
        'processing_completed_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(
            function (self $record): void {
                if (blank($record->user_id)) {
                    $user = Auth::user();

                    if (
                        $user
                        && ! $user->isSuperAdmin()
                    ) {
                        $record->user_id =
                            $user->ownerId();
                    }
                }

                if (blank($record->status)) {
                    $record->status = 'draft';
                }

                /*
                 * Verziju određujemo prema
                 * datumu predaje.
                 */
                if (blank($record->form_version)) {
                    $record->form_version =
                        FormVersionService::ploForDate(
                            $record->handover_date
                        );
                }

                /*
                 * Novi PL-O od 09.09.2026.
                 * nema report_choice.
                 */
                if (
                    FormVersionService::isCurrentPlo(
                        $record->form_version
                    )
                ) {
                    $record->report_choice = null;
                }
            }
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function ontoRecord(): BelongsTo
    {
        return $this->belongsTo(
            OntoRecord::class,
            'onto_record_id'
        );
    }

    public function outputEntry(): HasOne
    {
        return $this->hasOne(
            OntoEntry::class,
            'waste_tracking_form_id'
        )->where(
            'entry_type',
            'output'
        );
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function getDisplayNameAttribute(): string
    {
        $doc =
            $this->document_number ?: 'PL-O';

        $date =
            $this->handover_date
                ?->format('d.m.Y.')
            ?? '-';

        $waste =
            $this->ontoRecord
                ?->wasteType
                ?->waste_code
            ?? $this->waste_code_manual
            ?? '-';

        return "{$doc} / {$waste} / {$date}";
    }

    public static function formVersions(): array
    {
        return FormVersionService::ploVersions();
    }

    public function getFormVersionLabelAttribute(): string
    {
        return FormVersionService::ploVersions()[
            $this->form_version
        ]
            ?? $this->form_version
            ?? FormVersionService::currentPlo();
    }
}