<?php

namespace Tests\Feature;

use App\Http\Controllers\CdrEncoursController;
use App\Models\MonthlyStatistic;
use App\Services\MonthlyStatisticsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MonthlyStatisticsDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::connection('sqlite')->create('monthly_statistics', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7)->unique();
            $table->unsignedInteger('physical_clients')->default(0);
            $table->unsignedInteger('moral_clients')->default(0);
            $table->unsignedInteger('monthly_engagements')->default(0);
            $table->decimal('monthly_engagement_amount', 20, 2)->default(0);
            $table->decimal('total_outstanding_amount', 20, 2)->default(0);
            $table->unsignedInteger('unpaid_clients')->default(0);
            $table->timestamp('extracted_at');
            $table->timestamps();
        });
    }

    public function test_dashboard_reads_latest_snapshot_from_sqlite_without_recalculating_oracle_data(): void
    {
        MonthlyStatistic::create([
            'period' => '2026-08',
            'extracted_at' => now(),
        ]);
        MonthlyStatistic::create([
            'period' => '2026-09',
            'physical_clients' => 42,
            'extracted_at' => now(),
        ]);

        $service = new MonthlyStatisticsService($this->createMock(CdrEncoursController::class));
        $dashboard = $service->dashboard();

        $this->assertSame('sqlite', $dashboard['source']);
        $this->assertSame('2026-09', $dashboard['current']->period);
        $this->assertSame(42, $dashboard['current']->physical_clients);
        $this->assertCount(2, $dashboard['history']);
    }
}