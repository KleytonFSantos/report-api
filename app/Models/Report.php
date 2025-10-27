<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    /**
     * @use HasFactory<ReportFactory>
     */
    use HasFactory;

    protected $fillable = [
        'status',
        'original_filename',
        'input_path',
        'output_path',
        'error_message',
    ];
}
