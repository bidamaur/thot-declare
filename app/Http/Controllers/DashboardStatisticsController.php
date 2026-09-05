<?php

namespace App\Http\Controllers;

use App\Services\MonthlyStatisticsService;
use Throwable;

class DashboardStatisticsController extends Controller
{
    public function __construct(private MonthlyStatisticsService $statistics)
    {
    }

    public function index()
    {
        try {
            return response()->json($this->statistics->dashboard());
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Impossible de calculer les statistiques depuis Oracle.',
            ], 503);
        }
    }
}
