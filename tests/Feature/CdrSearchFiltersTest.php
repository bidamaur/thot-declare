<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CdrSearchFiltersTest extends TestCase
{
    public function test_cdr_pp_index_applies_q_query_filter(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->withArgs(function ($query, $bindings) {
                $this->assertStringContainsString('UPPER(TRIM(c.nom)) LIKE ?', $query);
                $this->assertStringContainsString('TRIM(c.cli) LIKE ?', $query);
                $this->assertSame(['%GAKAM%', '%GAKAM%', '%GAKAM%', '%GAKAM%', '%GAKAM%'], $bindings);
                return true;
            })
            ->andReturn([
                [
                    'IDINTCLI' => '100003',
                    'NOM' => 'GAKAM',
                    'PRENOM' => 'COLETTE',
                    'SEXE' => 'F',
                ],
            ]);

        $response = $this->get('/api/cdr_pp?q=GAKAM');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.NOM', 'GAKAM');
    }
}
