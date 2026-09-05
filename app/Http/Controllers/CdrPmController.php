<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\cdr_pm;
use Illuminate\Http\Request;

class CdrPmController extends Controller
{
    public function index(Request $request, $DateArr = null, $clientId = null)
    {
        $dateFilter = '';
        $bindings = [];

        $dateParam = trim((string) $request->query('date', $DateArr ?? ''));
        $clientParam = trim((string) $request->query('client_id', $request->query('clientId', $clientId ?? '')));
        $searchTerm = trim((string) $request->query('q', ''));

        if ($clientParam === '' && preg_match('/^\d+$/', $searchTerm)) {
            $clientParam = $searchTerm;
            $searchTerm = '';
        }

        if ($dateParam !== '') {
            $DateArr = $dateParam;
        }

        if ($DateArr) {
            $DateArr = trim($DateArr);
            if (preg_match('/^\d{8}$/', $DateArr)) {
                $dateFilter = "AND TO_CHAR(c.dou, 'DDMMYYYY') = ?";
                $bindings[] = $DateArr;
            } elseif (preg_match('/^(\d{2})[-\/](\d{4})$/', $DateArr, $matches)) {
                $dateFilter = "AND TO_CHAR(c.dou, 'MMYYYY') = ?";
                $bindings[] = $matches[1] . $matches[2];
            } elseif (preg_match('/^(\d{4})[-\/](\d{2})$/', $DateArr, $matches)) {
                $dateFilter = "AND TO_CHAR(c.dou, 'MMYYYY') = ?";
                $bindings[] = $matches[2] . $matches[1];
            } elseif (preg_match('/^\d{6}$/', $DateArr)) {
                $dateFilter = "AND TO_CHAR(c.dou, 'MMYYYY') = ?";
                $bindings[] = $DateArr;
            }
        }

        $customerFilter = '';
        if ($clientParam !== '') {
            $customerFilter = "AND TRIM(c.cli) = ?";
            $bindings[] = $clientParam;
        }

        $searchFilter = '';
        if ($searchTerm !== '') {
            $like = '%' . strtoupper($searchTerm) . '%';
            $searchFilter = "AND (
                UPPER(TRIM(c.rso)) LIKE ? OR
                UPPER(TRIM(c.sig)) LIKE ? OR
                TRIM(c.cli) LIKE ? OR
                UPPER(TRIM(c.nidf)) LIKE ?
            )";
            $bindings = array_merge($bindings, [$like, $like, $like, $like]);
        }

        $sql = "SELECT 
        TRIM(c.cli) AS IDINTCLI,
        TRIM(c.nidf) AS NIF_NIU,
       REPLACE(TRIM(c.rso),'&',' et ') AS RAISOC,
        TO_CHAR(c.datc, 'DDMMYYYY') AS DATCRE,
        TRIM(c.sig) AS SIGLE,
        '01' AS RESIDENT,
        'CM' AS PAYSSIEGE,
        TRIM(ad.ville) AS ADRESSE_VILLE,
        TRIM(c.nrc) AS RCCM,
        CASE
            WHEN c.fju='01' THEN '00'
            WHEN c.fju='16' THEN '15'
            WHEN c.fju='03' THEN '04'
            WHEN c.fju='03' THEN '02'
            WHEN c.fju='20' THEN '19'
            WHEN c.fju='31' THEN '30'
            WHEN c.fju='04' THEN '03'
            ELSE c.fju
        END AS FORJURID,
        CASE
            WHEN c.sec IN (SELECT sect FROM cdr_naema) THEN 
                (SELECT val FROM cdr_naema WHERE sect = c.sec)
            ELSE c.sec
        END AS SECACT,
        (
            CASE
    -- 1. Entreprises Individuelles / TPE (catn = 2203 ou tcli = 2)
    WHEN c.catn = 2203 OR tcli = 2 THEN 1061

    -- 2. Autres sociétés non financières privées (catn = 2202 avec tcli = 3)
    WHEN c.catn = 2202 AND tcli = 3 THEN 1062

    -- 3. Sociétés non financières publiques (catn = 2201)
    WHEN c.catn = 2201 THEN 1010

    -- 4. Assurances (catn = 1401, 1402)
    WHEN c.catn IN (1401, 1402) THEN 1130

    -- 5. Banques & Établissements financiers (catn = 1101, 1102, 1201, 1202, 1301, 1302, 1501, 1502, 1601, 1602, 1603, 1701)
    WHEN c.catn IN (1101, 1102, 1201, 1202, 1301, 1302, 1501, 1502, 1601, 1602, 1603, 1701) THEN 1210

    -- 6. Administrations centrales & locales (catn = 2101, 2102, 2103)
    WHEN c.catn IN (2101, 2102, 2103) THEN 1010

    -- 7. Institutions sans but lucratif / ONG (catn = 2301)
    WHEN c.catn = 2301 THEN 1070

    -- 8. Personnes physiques / Particuliers (catn = 2401 ou tcli = 1)
    WHEN c.catn = 2401 OR tcli = 1 THEN 1100

    -- Valeur de repli sécurisée pour sociétés morales privées par défaut (1062 = PME/Société)
    ELSE 1062
        END
        ) AS AGEECO,
        '01' AS STALEG, -- EN ACTIVITE A CONTROLLER AVANT DECLARATION
        TO_CHAR(c.dou, 'DDMMYYYY') AS DATENTRELPAR,
        '' AS CHIAFFAIRE,
        '' AS TOTBILAN,
        '' AS EFFECTIF,
        TRIM(em.email) AS EMAIL,
        TRIM(
    REPLACE(
        CASE
            WHEN SUBSTR(
                COALESCE(
                    NULLIF(TRIM(t.num), ''),
                    (
                        SELECT MAX(TRIM(t2.tel))
                        FROM bkcntcli t2
                        WHERE t2.cli = c.cli
                          AND TRIM(t2.tel) IS NOT NULL
                    )
                ),
                1, 3
            ) = '237'
            THEN '00'

            WHEN SUBSTR(
                COALESCE(
                    NULLIF(TRIM(t.num), ''),
                    (
                        SELECT MAX(TRIM(t2.tel))
                        FROM bkcntcli t2
                        WHERE t2.cli = c.cli
                          AND TRIM(t2.tel) IS NOT NULL
                    )
                ),
                1, 3
            ) = '002'
            THEN ''

            ELSE '00237'
        END
        ||
        COALESCE(
            NULLIF(TRIM(t.num), ''),
            (
                SELECT MAX(TRIM(t2.tel))
                FROM bkcntcli t2
                WHERE t2.cli = c.cli
                  AND TRIM(t2.tel) IS NOT NULL
            )
        ),
        ' ',
        ''
    )
) AS TEL,
        0 AS SITJUD,
        TO_CHAR('', 'DDMMYYYY') AS DATDEBINT,
        TO_CHAR('', 'DDMMYYYY') AS DATFININT,
        TO_CHAR(c.dou, 'DDMMYYYY') AS DATEVE,
        '03' AS TYPADR,
        TRIM(ad.adr1) AS ADRESSE,
        'CM' AS PAYS,
        '0' AS REGION,
        '0' AS VILLE,
        '' AS CODPOST,
        '' AS IDINTMAND,
        '' AS TYPMAND,
        '' AS DATDEBMAND,
        '' AS DATFINMAND,
        '' AS IDINTACT,
        '' AS NOMACT,
        '' AS PCTACT,
        '' AS DATDEBACT,
        '' AS DATEFINACT,
        '' AS DATMAJACT,
        '' AS TELACT
    FROM 
        bkcli c
    LEFT JOIN 
        (SELECT cli, MAX(ville) AS ville, MAX(adr1) AS adr1 FROM bkadcli GROUP BY cli) ad 
        ON ad.cli = c.cli
    LEFT JOIN 
        (SELECT cli, MAX(email) AS email FROM bkadcli GROUP BY cli) em 
        ON em.cli = c.cli
    LEFT JOIN 
        (SELECT cli, MAX(num) AS num FROM bktelcli GROUP BY cli) t 
        ON t.cli = c.cli
        WHERE 
            c.tcli IN (2, 3)
            AND c.cli NOT IN (000020, 100500)
            " . $customerFilter . "
            " . $searchFilter . "
            " . $dateFilter . "
            --and c.cli>100924
        ORDER BY 1
        ";

        try {
            $results = DB::connection('oracle')->select($sql, $bindings);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'ORA-00942') !== false || stripos($msg, 'table or view does not exist') !== false) {
                $simpleSql = preg_replace(
                    ['/CASE\s+WHEN\s+c\.sec\s+IN\s*\(SELECT\s+sect\s+FROM\s+cdr_naema\).*?\bELSE\s+c\.sec\s+END\s+AS\s+SECACT/s',
                     '/\(SELECT\s+MAX\(TRIM\(em\.email\)\)\s+FROM\s+bkemacli.*?\)\s+AS\s+EMAIL/s',
                     '/TRIM\(REPLACE\(.*?bkcntcli.*?END.*?\)\)\s+AS\s+TEL/s'],
                    ['c.sec AS SECACT', "'' AS EMAIL", "'' AS TEL"],
                    $sql
                );
                $results = DB::connection('oracle')->select($simpleSql, $bindings);
            } elseif (stripos($msg, 'ORA-00904') !== false || stripos($msg, 'invalid identifier') !== false) {
                return response()->json([]);
            } else {
                return response()->json([[ 'type' => 'Erreur', 'Description' => $msg ]]);
            }
        }

        try {
            $villeRegionRows = DB::connection('oracle')->select("SELECT nom_ville, code_region, code_ville FROM dbprod.cdr_ville_region");
            $villeMap = [];
            foreach ($villeRegionRows as $row) {
                $key = parseUtf8($row->nom_ville);
                $villeMap[$key] = [
                    'REGION' => $row->code_region,
                    'VILLE' => $row->code_ville
                ];
            }
        } catch (\Exception $e) {
            $villeMap = [];
        }

        $results = array_map(function ($row) use ($villeMap) {
            $row = array_change_key_case((array) $row, CASE_UPPER);
            $villeKey = parseUtf8($row['ADRESSE_VILLE'] ?? '');
            if (isset($villeMap[$villeKey])) {
                $row['REGION'] = $villeMap[$villeKey]['REGION'];
                $row['VILLE'] = $villeMap[$villeKey]['VILLE'];
            }
            return $row;
        }, $results);

        return response()->json($results);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(cdr_pm $cdr_pm)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, cdr_pm $cdr_pm)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(cdr_pm $cdr_pm)
    {
        //
    }
}
