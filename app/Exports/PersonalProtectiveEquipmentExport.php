<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PersonalProtectiveEquipmentExport implements FromView
{
    public $record;

    public function __construct($record)
    {
        $this->record = $record;
    }

    public function view(): View
    {
        return view('exports.ozo-excel', [
            'record' => $this->record,
        ]);
    }
}