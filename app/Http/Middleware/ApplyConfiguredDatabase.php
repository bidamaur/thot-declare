<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class ApplyConfiguredDatabase
{
    public function handle(Request $request, Closure $next)
    {
        $path = base_path('storage/app/admin-config.json');
        if (is_file($path)) {
            $config = json_decode((string) file_get_contents($path), true) ?: [];
            $database = $config['database'] ?? [];
            if (!empty($database['driver'])) {
                config(['database.connections.oracle' => $this->connectionConfig($database)]);
                config(['database.default' => 'sqlite']);
            }
        }

        return $next($request);
    }

    private function connectionConfig(array $input): array
    {
        $password = $input['password'] ?? '';
        try {
            $password = Crypt::decryptString($password);
        } catch (\Throwable $exception) {
        }

        if ($input['driver'] === 'oci8') {
            return [
                'driver' => 'oracle',
                'tns' => $input['service'] ?: (($input['host'] ?? 'localhost') . ':' . ($input['port'] ?? 1521) . '/' . ($input['database'] ?? 'XE')),
                'database' => $input['database'] ?? 'XE',
                'username' => $input['username'] ?? '',
                'password' => $password,
                'charset' => 'AL32UTF8',
                'prefix' => '',
            ];
        }

        return [
            'driver' => $input['driver'],
            'host' => $input['host'] ?? '127.0.0.1',
            'port' => $input['port'] ?? 3306,
            'database' => $input['database'] ?? '',
            'username' => $input['username'] ?? '',
            'password' => $password,
            'prefix' => '',
        ];
    }
}
