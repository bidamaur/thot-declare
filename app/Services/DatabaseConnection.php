<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class DatabaseConnection
{
    protected $connection;

    public function __construct()
    {
        $this->connection = null;
    }

    public function getConnectionConfig(): array
    {
        $adminConfigPath = base_path('storage/app/admin-config.json');
        if (is_file($adminConfigPath)) {
            $adminConfig = json_decode((string) file_get_contents($adminConfigPath), true) ?: [];
            $database = $adminConfig['database'] ?? [];
            if (!empty($database['driver'])) {
                $password = $database['password'] ?? '';
                try {
                    $password = Crypt::decryptString($password);
                } catch (\Throwable $exception) {
                }
                return [
                    'connectionString' => $database['service'] ?: (($database['host'] ?? 'localhost') . ':' . ($database['port'] ?? 1521) . '/' . ($database['database'] ?? 'XE')),
                    'database' => $database['database'] ?? 'XE',
                    'username' => $database['username'] ?? '',
                    'password' => $password,
                ];
            }
        }

        $tns = env('ORACLE_TNS');

        if (empty($tns)) {
            $host = env('DB_HOST', 'localhost');
            $port = env('DB_PORT', '1521');
            $database = env('DB_DATABASE', 'XE');
            $tns = $host . ':' . $port . '/' . $database;
        }

        return [
            'connectionString' => $tns,
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
        ];
    }

    public function connectToDatabase()
    {
        if ($this->connection) {
            return $this->connection;
        }

        $config = $this->getConnectionConfig();
        $connection = @oci_connect($config['username'], $config['password'], $config['connectionString']);

        if (!$connection) {
            $e = oci_error();
            throw new \Exception('Connection failed: ' . ($e['message'] ?? 'Unknown Oracle error'));
        }

        $this->connection = $connection;

        return $this->connection;
    }

    public function getConnection()
    {
        return $this->connectToDatabase();
    }
}
