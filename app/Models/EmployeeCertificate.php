<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCertificate extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'Edukacije zaposlenika';

    protected $table = 'employee_certificates';

    protected $fillable = [
        'employee_id',
        'title',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}