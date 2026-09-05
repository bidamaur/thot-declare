<?php

namespace App\Services;

use App\Http\Controllers\CdrEncoursController;
use App\Models\MonthlyStatistic;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MonthlyStatisticsService
{
    public function __construct(private CdrEncoursController $encoursController)
    {
    }

    public function snapshot(?Carbon $month = null): MonthlyStatistic
    {
        $month = ($month ?: now())->startOfMonth();
        $period = $month->format('Y-m');
        $monthParameter = $month->format('m-Y');
        $oraclePeriod = $month->format('mY');
        $oracle = DB::connection('oracle');

        $physicalClients = (int) ($oracle->selectOne(
            "SELECT COUNT(*) AS total FROM bkcli WHERE tcli = 1"
        )->total ?? 0);
        $moralClients = (int) ($oracle->selectOne(
            "SELECT COUNT(*) AS total FROM bkcli WHERE tcli <> 1"
        )->total ?? 0);

        $engagements = $oracle->selectOne(
            "SELECT COUNT(DISTINCT d.eve) AS total, NVL(SUM(d.mon), 0) AS amount
             FROM bkdosprt d
                         WHERE d.eta IN ('VA', 'DE')
                               AND TO_CHAR(TO_DATE(SUBSTR(TRIM(d.dmep), 1, 10), 'YYYY-MM-DD'), 'MMYYYY') = ?",
                        [$oraclePeriod]
        );

        $encoursResponse = $this->encoursController->GetEncours($monthParameter);
        $encoursRows = $encoursResponse->getData(true);
        if (!is_array($encoursRows) || isset($encoursRows[0]['Erreur'])) {
            throw new RuntimeException('Les encours Oracle ne peuvent pas être agrégés pour cette période.');
        }

        $outstandingAmount = 0.0;
        $unpaidClients = [];
        foreach ($encoursRows as $row) {
            $outstandingAmount += (float) ($row['MNTCRD'] ?? 0);
            if ((float) ($row['NBRECHIMP'] ?? 0) > 0 || (float) ($row['MNTCRESOUF'] ?? 0) > 0) {
                $client = trim((string) ($row['CLI'] ?? ''));
                if ($client !== '') {
                    $unpaidClients[$client] = true;
                }
            }
        }

        $values = [
            'physical_clients' => $physicalClients,
            'moral_clients' => $moralClients,
            'monthly_engagements' => (int) ($engagements->total ?? 0),
            'monthly_engagement_amount' => round((float) ($engagements->amount ?? 0), 2),
            'total_outstanding_amount' => round($outstandingAmount, 2),
            'unpaid_clients' => count($unpaidClients),
        ];

        $existing = MonthlyStatistic::where('period', $period)->first();
        if (!$existing) {
            return MonthlyStatistic::create($values + [
                'period' => $period,
                'extracted_at' => now(),
            ]);
        }

        $hasChanged = collect($values)->contains(function ($value, $key) use ($existing) {
            return (string) $existing->{$key} !== (string) $value;
        });

        if ($hasChanged) {
            $existing->fill($values);
            $existing->extracted_at = now();
            $existing->save();
        }

        return $existing->fresh();
    }

    public function dashboard(): array
    {
        $current = $this->snapshot();
        $history = MonthlyStatistic::orderBy('period')->get();

        return [
            'current' => $current,
            'history' => $history,
        ];
    }
}
