<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class cdrEngagements extends Model
{
    use HasFactory;

    protected $table = 'cdr_engagements';
    public $timestamps = false;
    protected $guarded = [];
}
