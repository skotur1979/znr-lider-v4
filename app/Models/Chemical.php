<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chemical extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'product_name',
        'cas_number',
        'ufi_number',
        'hazard_pictograms',
        'h_statements',
        'p_statements',
        'usage_location',
        'annual_quantity',
        'gvi_kgvi',
        'voc',
        'stl_hzjz',
        'attachments',
    ];

    protected $casts = [
        'hazard_pictograms' => 'array',
        'h_statements'      => 'array',
        'p_statements'      => 'array',
        'attachments'       => 'array',
        'stl_hzjz'          => 'date',
    ];
}