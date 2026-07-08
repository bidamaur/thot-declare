<?php

namespace App\Services;

class DatabaseConnection
{
    protected $connection;

    public function __construct()
    {
        $this->connection = null;
    }

    public function getConnectionConfig(): array
    {
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
