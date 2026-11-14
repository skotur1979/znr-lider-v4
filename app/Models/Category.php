<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use LogsActivity;

    protected static string $activityModule =
        'Kategorije';

    protected $fillable = [
        'user_id',
        'name',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn ($value) =>
                trim((string) $value)
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function miscellaneouses(): HasMany
    {
        return $this->hasMany(
            Miscellaneous::class,
            'category_id'
        );
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(
            Expense::class,
            'category_id'
        );
    }
}