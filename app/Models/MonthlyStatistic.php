<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyStatistic extends Model
{
    protected $fillable = [
        'period',
        'physical_clients',
        'moral_clients',
        'monthly_engagements',
        'monthly_engagement_amount',
        'total_outstanding_amount',
        'unpaid_clients',
        'extracted_at',
    ];

    protected $casts = [
        'monthly_engagement_amount' => 'float',
        'total_outstanding_amount' => 'float',
        'extracted_at' => 'datetime',
    ];
}
