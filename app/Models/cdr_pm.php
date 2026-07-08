<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cdr_pm extends Model
{
    use HasFactory;

    protected $table = 'cdr_pm';
    public $timestamps = false;
    protected $guarded = [];
}
