<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cdrEncours extends Model
{
    use HasFactory;

    protected $table = 'cdr_encours';
    public $timestamps = false;
    protected $guarded = [];
}
