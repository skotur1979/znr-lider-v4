<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class WasteTrackingForm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'onto_record_id',
        'document_number',
        'handover_date',
        'quantity_kg',
        'status',
        'description',
        'sender_name',
        'sender_oib',
        'sender_address',
        'carrier_name',
        'carrier_oib',
        'carrier_authorization',
        'carrier_vehicle_registration',
        'receiver_name',
        'receiver_oib',
        'receiver_authorization',
        'receiver_address',
        'processing_method',
        'note',
        'locked_at',
    ];

    protected $casts = [
        'handover_date' => 'date',
        'quantity_kg' => 'decimal:2',
        'locked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $record) {
            if (blank($record->user_id)) {
                $record->user_id = Auth::id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ontoRecord(): BelongsTo
    {
        return $this->belongsTo(OntoRecord::class, 'onto_record_id');
    }

    public function outputEntry(): HasOne
    {
        return $this->hasOne(OntoEntry::class, 'waste_tracking_form_id');
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }
}