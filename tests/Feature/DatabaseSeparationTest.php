<?php

namespace Tests\Feature;

use App\Http\Middleware\ApplyConfiguredDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class DatabaseSeparationTest extends TestCase
{
    protected function tearDown(): void
    {
        $path = base_path('storage/app/admin-config.json');
        if (file_exists($path)) {
            unlink($path);
        }

        parent::tearDown();
    }

    public function test_sqlite_remains_default_for_authentication_and_oracle_is_kept_separate(): void
    {
        $path = base_path('storage/app/admin-config.json');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, json_encode([
            'database' => [
                'driver' => 'oci8',
                'host' => '192.168.2.241',
                'port' => 1521,
                'database' => 'propme',
                'username' => 'read_only',
                'password' => 'secret',
                'service' => '192.168.2.241:1521/propme',
            ],
        ], JSON_PRETTY_PRINT));

        $middleware = new ApplyConfiguredDatabase();
        $request = Request::create('/api/test', 'GET');

        $response = $middleware->handle($request, function () {
            return response()->json(['ok' => true]);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame('oracle', config('database.connections.oracle.driver'));
        $this->assertSame('propme', config('database.connections.oracle.database'));
    }
}
