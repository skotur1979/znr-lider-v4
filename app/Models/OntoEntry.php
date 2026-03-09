<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OntoEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'onto_record_id',
        'entry_no',
        'entry_date',
        'entry_type',
        'input_kg',
        'output_kg',
        'method',
        'balance_after_kg',
        'note',
        'waste_tracking_form_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'input_kg' => 'decimal:2',
        'output_kg' => 'decimal:2',
        'balance_after_kg' => 'decimal:2',
    ];

    public function ontoRecord(): BelongsTo
    {
        return $this->belongsTo(OntoRecord::class, 'onto_record_id');
    }

    public function trackingForm(): BelongsTo
    {
        return $this->belongsTo(WasteTrackingForm::class, 'waste_tracking_form_id');
    }

    public function getQuantityAttribute(): float
    {
        return $this->entry_type === 'input'
            ? (float) $this->input_kg
            : (float) $this->output_kg;
    }
}
