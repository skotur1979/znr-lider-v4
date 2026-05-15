<?php

namespace App\Models;

use App\Models\Category;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Miscellaneous extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule = 'Ostala ispitivanja';

    protected $table = 'miscellaneouses';

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'examiner',
        'report_number',
        'examination_valid_from',
        'examination_valid_until',
        'remark',
        'pdf',
    ];

    protected $casts = [
        'examination_valid_from' => 'date',
        'examination_valid_until' => 'date',
        'pdf' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}