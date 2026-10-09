<?php

namespace Tests\Feature;

use App\Http\Controllers\CdrEncoursController;
use App\Http\Controllers\CdrEngagementsController;
use App\Services\DatabaseConnection;
use Illuminate\Http\Request;
use Tests\TestCase;

class EveFilterModesTest extends TestCase
{
    public function test_engagement_filter_builds_in_and_not_in_clauses_with_bound_values(): void
    {
        $controller = new CdrEngagementsController(new DatabaseConnection());
        $method = new \ReflectionMethod($controller, 'eveFilterForQuery');

        [$includeSql, $includeBindings] = $method->invoke($controller, ['1001', '1002'], false);
        [$excludeSql, $excludeBindings] = $method->invoke($controller, ['1001', '1002'], true);

        $this->assertSame(' AND d.eve IN (:eve0, :eve1)', $includeSql);
        $this->assertSame(' AND d.eve NOT IN (:eve0, :eve1)', $excludeSql);
        $this->assertSame([':eve0' => '1001', ':eve1' => '1002'], $includeBindings);
        $this->assertSame($includeBindings, $excludeBindings);
    }

    public function test_encours_filter_uses_the_selected_eve_mode(): void
    {
        $controller = new CdrEncoursController(new DatabaseConnection());
        $method = new \ReflectionMethod($controller, 'eveFilterForQuery');

        $includeRequest = Request::create('/api/cdr_encours/09-2026');
        $includeRequest->query->set('eves', ['1001']);
        $excludeRequest = Request::create('/api/cdr_encours/09-2026');
        $excludeRequest->query->set('eves', ['1001']);
        $excludeRequest->query->set('excludeEves', '1');

        [$includeSql] = $method->invoke($controller, $includeRequest);
        [$excludeSql] = $method->invoke($controller, $excludeRequest);

        $this->assertSame(' AND d.eve IN (:eve0)', $includeSql);
        $this->assertSame(' AND d.eve NOT IN (:eve0)', $excludeSql);
    }
}