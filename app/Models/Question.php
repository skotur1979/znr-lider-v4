<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'Pitanja';

    protected $fillable = [
        'user_id',
        'test_id',
        'tekst',
        'slika_path',
        'visestruki_odgovori',
    ];

    protected $casts = [
        'visestruki_odgovori' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Question $question): void {
            if (! $question->test_id) {
                return;
            }

            $test = Test::query()
                ->find($question->test_id);

            if (! $test) {
                return;
            }

            // Question ownership uvijek mora biti jednak Test ownershipu.
            $question->user_id = $test->user_id;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}