<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;

class AdminConfigController extends Controller
{
    private const CONFIG_PATH = 'storage/app/admin-config.json';

    private const DRIVERS = ['oci8', 'mysql', 'sqlsrv', 'pgsql', 'sqlite'];

    private const RUNTIME_VARS = [
        'DateArr', 'DateDeb', 'DateArrYear', 'DateArrMonth', 'DateArrDay',
        'DateDebMois', 'DateMonthYear', 'MoisAnneeStr',
    ];

    private const NBR_FALLBACK_PATTERN = '/^(Nbr|mon|montant|provision|total|count|amount)/i';

    private const QUERY_CATALOG = [
        'cdr_pp' => ['label' => 'Personnes physiques', 'route' => '/api/cdr_pp', 'source' => 'CdrPpController@index', 'file' => 'app/Http/Controllers/CdrPpController.php', 'variable' => null],
        'cdr_pm' => ['label' => 'Personnes morales', 'route' => '/api/cdr_pm', 'source' => 'CdrPmController@index', 'file' => 'app/Http/Controllers/CdrPmController.php', 'variable' => null],
        'cdr_engagements' => ['label' => 'Engagements', 'route' => '/api/cdr_engagements/{DateArr}', 'source' => 'CdrEngagementsController@GetEngagements', 'file' => 'app/Http/Controllers/CdrEngagementsController.php', 'variable' => 'query'],
        'cdr_encours' => ['label' => 'Encours', 'route' => '/api/cdr_encours/{DateArr}', 'source' => 'CdrEncoursController@GetEncours', 'file' => 'app/Http/Controllers/CdrEncoursController.php', 'variable' => 'MyRequest'],
        'cdr_encours_ajust' => ['label' => 'Encours ajustés', 'route' => '/api/cdr_encours_ajust/{DateArr}', 'source' => 'CdrEncoursController@GetEncoursAjust', 'file' => 'app/Http/Controllers/CdrEncoursController.php', 'variable' => 'MyRequest', 'occurrence' => 2],
        'cdr_garanties' => ['label' => 'Garanties', 'route' => '/api/cdr_garanties/{DateArr}', 'source' => 'GarantiesController@getGaranties', 'file' => 'app/Http/Controllers/GarantiesController.php', 'variable' => 'MyQuery'],
    ];

    private const THEMES = [
        'thot_light' => [
            'name' => 'THOT Light',
            'class' => 'theme-light',
            'description' => 'Thème clair professionnel (fond #F8FAFC, bleu royal #2563EB).',
            'preview' => '#2563EB',
        ],
        'thot_dark' => [
            'name' => 'THOT Dark',
            'class' => 'theme-dark',
            'description' => 'Thème sombre professionnel (fond #0B1120, bleu ciel #60A5FA).',
            'preview' => '#0F172A',
        ],
    ];

    public function show()
    {
        $config = $this->readConfig();
        $dbConnection = env('DB_CONNECTION', 'sqlite');
        $isOracleDefault = ($dbConnection === 'oracle') || (!empty(env('ORACLE_TNS')) && $dbConnection !== 'sqlite');
        $database = array_merge([
            'driver' => $isOracleDefault ? 'oci8' : ($dbConnection === 'sqlite' ? 'sqlite' : 'mysql'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => (int) env('DB_PORT', 1521),
            'database' => env('DB_DATABASE', 'XEPDB1'),
            'username' => env('DB_USERNAME', 'dbprod'),
            'service' => env('ORACLE_TNS', ''),
        ], $config['database'] ?? []);
        $database['password'] = '';
        $database['has_password'] = !empty($config['database']['password']);

        return response()->json([
            'database' => $database,
            'application' => array_merge([
                'name' => config('app.name'),
                'language' => 'fr',
                'theme' => 'thot_light',
            ], $config['application'] ?? []),
            'themes' => collect(self::THEMES)->mapWithKeys(function ($theme, $key) {
                return [$key => [
                    'name' => $theme['name'],
                    'class' => $theme['class'],
                    'description' => $theme['description'],
                    'preview' => $theme['preview'],
                ]];
            }),
            'customThemeName' => $config['application']['custom_theme_name'] ?? null,
        ]);
    }

    public function update(Request $request)
    {
        $payload = $request->validate([
            'database' => ['sometimes', 'array'],
            'database.driver' => ['sometimes', 'string', 'in:' . implode(',', self::DRIVERS)],
            'database.host' => ['sometimes', 'nullable', 'string', 'max:255'],
            'database.port' => ['sometimes', 'nullable', 'integer', 'between:1,65535'],
            'database.database' => ['sometimes', 'nullable', 'string', 'max:255'],
            'database.username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'database.password' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'database.service' => ['sometimes', 'nullable', 'string', 'max:500'],
            'application' => ['sometimes', 'array'],
            'application.name' => ['sometimes', 'string', 'max:100'],
            'application.language' => ['sometimes', 'string', 'in:fr,en'],
            'application.theme' => ['sometimes', 'string', 'in:' . implode(',', array_merge(['light', 'dark', 'system'], array_keys(self::THEMES)))],
            'application.custom_theme_name' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $config = $this->readConfig();
        $savedPassword = $config['database']['password'] ?? '';
        $config = array_replace_recursive($config, $payload);
        if (array_key_exists('database', $payload) && empty($payload['database']['password'])) {
            $config['database']['password'] = $savedPassword;
        } elseif (!empty($payload['database']['password'])) {
            $config['database']['password'] = Crypt::encryptString($payload['database']['password']);
        }
        $this->writeConfig($config);
        if (array_key_exists('database', $payload)) {
            $this->writeDatabaseEnv($config['database']);
        }

        return $this->show()->setStatusCode(200);
    }

    public function testDatabase(Request $request)
    {
        $input = $request->all();
        if (isset($input['database']) && is_array($input['database'])) {
            $input = $input['database'];
        }
        if (!is_array($input)) {
            $input = [];
        }
        $payload = validator($input, [
            'driver' => ['required', 'string', 'in:' . implode(',', self::DRIVERS)],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'database' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:1000'],
            'service' => ['nullable', 'string', 'max:500'],
        ])->validate();

        if (empty($payload['password'])) {
            $storedConfig = $this->readConfig();
            $storedPassword = $storedConfig['database']['password'] ?? '';
            if ($storedPassword) {
                try {
                    $storedPassword = Crypt::decryptString($storedPassword);
                } catch (\Throwable $exception) {
                }
            }
            $payload['password'] = $storedPassword !== '' ? $storedPassword : (string) env('DB_PASSWORD', '');
        }

        if ($payload['driver'] === 'oci8' && !extension_loaded('oci8')) {
            return response()->json(['ok' => false, 'message' => "L'extension PHP OCI8 n'est pas chargée."], 422);
        }

        $name = 'admin_connection_test';
        $connection = $this->connectionConfig($payload);
        config(["database.connections.$name" => $connection]);

        try {
            DB::purge($name);
            DB::connection($name)->getPdo();
            return response()->json(['ok' => true, 'message' => 'Connexion établie avec succès.']);
        } catch (\Throwable $exception) {
            return response()->json(['ok' => false, 'message' => $this->safeMessage($exception)], 422);
        } finally {
            DB::disconnect($name);
        }
    }

     public function listThemes()
     {
        $config = $this->readConfig();
        return response()->json([
            'themes' => collect(self::THEMES)->mapWithKeys(function ($theme, $key) {
                return [$key => [
                    'name' => $theme['name'],
                    'class' => $theme['class'],
                    'description' => $theme['description'],
                    'preview' => $theme['preview'],
                ]];
            }),
            'current' => $config['application']['theme'] ?? 'thot_light',
            'customThemeName' => $config['application']['custom_theme_name'] ?? null,
        ]);
    }

    public function selectTheme(Request $request)
    {
        $payload = $request->validate([
            'theme' => ['required', 'string', 'in:' . implode(',', array_keys(self::THEMES))],
            'custom_name' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $config = $this->readConfig();
        if (!isset($config['application'])) {
            $config['application'] = [];
        }
        $config['application']['theme'] = $payload['theme'];
        if (isset($payload['custom_name'])) {
            $config['application']['custom_theme_name'] = $payload['custom_name'];
        }

        $this->writeConfig($config);

        return response()->json(['ok' => true, 'message' => 'Thème appliqué.']);
    }

    public function queries()
    {
        $config = $this->readConfig();
        $overrides = $config['queries'] ?? [];

        return response()->json(collect(self::QUERY_CATALOG)->map(function ($query, $key) use ($overrides) {
            $sourceSql = $this->extractQuery($query);
            $variables = [];
            if ($query['variable'] !== null) {
                $variables = $this->extractPhpVariables(
                    $query['file'],
                    $query['variable'],
                    $query['occurrence'] ?? 1
                );
            }
            return array_merge(
                ['key' => $key, 'sql' => $overrides[$key]['sql'] ?? $sourceSql, 'variables' => $variables],
                $query
            );
        })->values());
    }

    public function testQuery(Request $request, string $key)
    {
        abort_unless(array_key_exists($key, self::QUERY_CATALOG), 404);
        $payload = $request->validate([
            'sql' => ['required', 'string', 'max:100000'],
            'variables' => ['sometimes', 'array'],
        ]);
        $sql = trim($payload['sql']);
            if (!preg_match('/^(select|with)\b/i', $sql) || preg_match('/\b(insert|update|delete|drop|alter|truncate|grant|revoke|merge|begin|execute)\b/i', $sql)) {
            return response()->json(['ok' => false, 'message' => 'Seules les requêtes SELECT sont autorisées.'], 422);
        }

        $definition = self::QUERY_CATALOG[$key];
        $phpVariables = [];
        if ($definition['variable'] !== null) {
            $phpVariables = $this->extractPhpVariables(
                $definition['file'],
                $definition['variable'],
                $definition['occurrence'] ?? 1
            );
        }

        try {
            $sql = $this->resolveDynamicSql($sql, array_merge($phpVariables, $payload['variables'] ?? []));
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }
        $sql = preg_replace('/\?/', 'NULL', $sql);

        try {
            $rows = DB::connection('oracle')->select($sql);
            return response()->json(['ok' => true, 'message' => 'Requête exécutée avec succès.', 'rows' => count($rows), 'preview' => array_slice($rows, 0, 5)]);
        } catch (\Throwable $exception) {
            return response()->json(['ok' => false, 'message' => $this->safeMessage($exception)], 422);
        }
    }

    public function updateQuery(Request $request, string $key)
    {
        abort_unless(array_key_exists($key, self::QUERY_CATALOG), 404);
        $payload = $request->validate(['sql' => ['required', 'string', 'max:100000']]);
        $sql = trim($payload['sql']);
            if (!preg_match('/^(select|with)\b/i', $sql) || preg_match('/\b(insert|update|delete|drop|alter|truncate|grant|revoke|merge)\b/i', $sql)) {
            return response()->json(['message' => 'Seules les requêtes SELECT sont autorisées.'], 422);
        }

        $config = $this->readConfig();
        $config['queries'][$key] = ['sql' => $sql, 'updated_at' => now()->toIso8601String()];
        $this->writeConfig($config);
        return response()->json(['ok' => true, 'message' => 'Requête enregistrée.']);
    }

    private function connectionConfig(array $input): array
    {
        $driver = $input['driver'];
        if ($driver === 'oci8') {
            return [
                'driver' => 'oracle',
                'tns' => $input['service'] ?: (($input['host'] ?? 'localhost') . ':' . ($input['port'] ?? 1521) . '/' . ($input['database'] ?? 'XE')),
                'database' => $input['database'] ?? 'XE',
                'username' => $input['username'] ?? '',
                'password' => $input['password'] ?? '',
                'charset' => 'AL32UTF8',
                'prefix' => '',
            ];
        }
        return [
            'driver' => $driver,
            'host' => $input['host'] ?? '127.0.0.1',
            'port' => $input['port'] ?? ($driver === 'pgsql' ? 5432 : ($driver === 'sqlsrv' ? 1433 : 3306)),
            'database' => $input['database'] ?? '',
            'username' => $input['username'] ?? '',
            'password' => $input['password'] ?? '',
            'prefix' => '',
        ];
    }

    private function readConfig(): array
    {
        if (!File::exists(base_path(self::CONFIG_PATH))) return [];
        return json_decode(File::get(base_path(self::CONFIG_PATH)), true) ?: [];
    }

    private function writeConfig(array $config): void
    {
        File::put(base_path(self::CONFIG_PATH), json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function writeDatabaseEnv(array $database): void
    {
        $path = base_path('.env');
        if (!File::exists($path)) return;

        $driver = ($database['driver'] ?? 'oci8') === 'oci8' ? 'oracle' : ($database['driver'] ?? 'mysql');
        $values = [
            'DB_CONNECTION' => 'sqlite',
            'DB_HOST' => $database['host'] ?? '',
            'DB_PORT' => $database['port'] ?? '',
            'DB_DATABASE' => $database['database'] ?? '',
            'DB_USERNAME' => $database['username'] ?? '',
            'ORACLE_TNS' => $driver === 'oracle' ? ($database['service'] ?? '') : '',
        ];
        if (!empty($database['password'])) {
            $values['DB_PASSWORD'] = $database['password'];
        }

        $contents = File::get($path);
        foreach ($values as $key => $value) {
            $line = $key === 'DB_CONNECTION'
                ? $key . '=' . $value
                : $key . '="' . str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $value) . '"';
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, $line, $contents, 1);
            } else {
                $contents .= PHP_EOL . $line;
            }
        }
        File::put($path, $contents);
    }

    private function safeMessage(\Throwable $exception): string
    {
        return app()->hasDebugModeEnabled() ? $exception->getMessage() : 'Connexion impossible. Vérifiez les paramètres.';
    }

    private function extractQuery(array $definition): string
    {
        $contents = File::get(base_path($definition['file']));
        $variable = $definition['variable'];
        if ($variable === null && preg_match('/DB::select\("((?:SELECT|WITH)[\s\S]*?)",\s*\$bindings\)/i', $contents, $matches)) {
            return $this->cleanExtractedQuery($matches[1]);
        }
            $pattern = '/\$' . preg_quote($variable, '/') . '\s*=\s*"((?:SELECT|WITH)[\s\S]*?)";/i';
        preg_match_all($pattern, $contents, $matches);
        $occurrence = ($definition['occurrence'] ?? 1) - 1;
        return $this->cleanExtractedQuery($matches[1][$occurrence] ?? '');
    }

    private function cleanExtractedQuery(string $sql): string
    {
        $sql = str_replace('\\"', '"', $sql);
        $sql = preg_replace(
            '/\s*"\s*\.\s*\$(?:customerFilter|searchFilter|dateFilter)\s*\.\s*"\s*/i',
            "\n",
            $sql,
        );
        $sql = preg_replace('/\s*"\s*\.\s*(\$[A-Za-z_]\w*)\s*\.\s*"\s*/', '$1', $sql);
        return trim($sql);
    }

    private function extractPhpVariables(string $file, string $variable, int $occurrence = 1): array
    {
        $contents = File::get(base_path($file));

        $mainPattern = '/\$' . preg_quote($variable, '/') . '\s*=\s*"((?:SELECT|WITH)[\s\S]*?)";/i';
        $mainOccurrence = max(1, $occurrence);

        $offset = 0;
        $mainPos = null;
        for ($i = 0; $i < $mainOccurrence; $i++) {
            if (preg_match($mainPattern, $contents, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                $mainPos = $matches[0][1];
                $offset = $mainPos + strlen($matches[0][0]);
            } else {
                return [];
            }
        }

        if ($mainPos === null) {
            return [];
        }

        $beforeMain = substr($contents, 0, $mainPos);
        $varPattern = '/\$(\w+)\s*=\s*"((?:[^"\\\\]|\\\\.)*)"/s';
        preg_match_all($varPattern, $beforeMain, $varMatches, PREG_SET_ORDER);

        $vars = [];
        foreach ($varMatches as $match) {
            $name = $match[1];
            if (in_array($name, self::RUNTIME_VARS, true)) {
                continue;
            }
            $value = $this->cleanExtractedQuery($match[2]);
            $vars[$name] = $value;
        }

        return $vars;
    }

    private function resolveDynamicSql(string $sql, array $overrides = []): string
    {
        $dynamicValues = array_merge([
            'DateArr' => '31/12/2025',
            'DateDeb' => '01/01/2025',
            'DateDebMois' => '01/12/2025',
            'DateArrYear' => '2025',
            'DateArrMonth' => '12',
            'DateArrDay' => '31',
            'DateMonthYear' => '/12/2025',
            'MoisAnneeStr' => '12/2025',
        ], $overrides);

        for ($pass = 0; $pass < 10; $pass++) {
            $hasVar = false;
            $sql = preg_replace_callback('/\$([A-Za-z_]\w*)/', function (array $match) use ($dynamicValues, &$hasVar) {
                $name = $match[1];
                $hasVar = true;
                if (array_key_exists($name, $dynamicValues)) {
                    if (preg_match('/\$[A-Za-z_]\w/', $dynamicValues[$name])) {
                        $hasVar = true;
                    }
                    return $dynamicValues[$name];
                }
                if (preg_match(self::NBR_FALLBACK_PATTERN, $name)) {
                    return '0';
                }
                throw new \InvalidArgumentException("Variable PHP dynamique non prise en charge : \${$name}. Définissez-la comme valeur SQL avant le test.");
            }, $sql);

            if (!$hasVar || !preg_match('/\$[A-Za-z_]\w*/', $sql)) {
                break;
            }
        }

        return $sql;
    }
}

