<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cdr_pp extends Model
{
    use HasFactory;

    protected $table = 'cdr_pp';
    public $timestamps = false;
    protected $guarded = [];
}
