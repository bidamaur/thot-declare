<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cdr_cptdebiteur extends Model
{
    use HasFactory;

    protected $table = 'cdr_cptdebiteur';
    public $timestamps = false;
    protected $guarded = [];
}
