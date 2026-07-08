<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class garanties extends Model
{
    use HasFactory;

    protected $table = 'garanties';
    public $timestamps = false;
    protected $guarded = [];
}
