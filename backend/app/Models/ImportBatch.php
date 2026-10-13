<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    protected $fillable = ['uploaded_by', 'original_filename', 'status', 'total_rows', 'success_count', 'error_count'];
}
