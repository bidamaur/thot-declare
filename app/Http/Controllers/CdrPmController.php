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

        $sql = "WITH
          FUNCTION cdr_parseutf8(p_str IN VARCHAR2) RETURN VARCHAR2 IS
            l_result VARCHAR2(4000);
          BEGIN
            IF p_str IS NULL THEN
              RETURN NULL;
            END IF;
            l_result := TRIM(p_str);
            l_result := REPLACE(l_result, 'à', 'a');
            l_result := REPLACE(l_result, 'á', 'a');
            l_result := REPLACE(l_result, 'â', 'a');
            l_result := REPLACE(l_result, 'ä', 'a');
            l_result := REPLACE(l_result, 'ã', 'a');
            l_result := REPLACE(l_result, 'å', 'a');
            l_result := REPLACE(l_result, 'ç', 'c');
            l_result := REPLACE(l_result, 'é', 'e');
            l_result := REPLACE(l_result, 'è', 'e');
            l_result := REPLACE(l_result, 'ê', 'e');
            l_result := REPLACE(l_result, 'ë', 'e');
            l_result := REPLACE(l_result, 'í', 'i');
            l_result := REPLACE(l_result, 'ì', 'i');
            l_result := REPLACE(l_result, 'î', 'i');
            l_result := REPLACE(l_result, 'ï', 'i');
            l_result := REPLACE(l_result, 'ñ', 'n');
            l_result := REPLACE(l_result, 'ó', 'o');
            l_result := REPLACE(l_result, 'ò', 'o');
            l_result := REPLACE(l_result, 'ô', 'o');
            l_result := REPLACE(l_result, 'ö', 'o');
            l_result := REPLACE(l_result, 'õ', 'o');
            l_result := REPLACE(l_result, 'ú', 'u');
            l_result := REPLACE(l_result, 'ù', 'u');
            l_result := REPLACE(l_result, 'û', 'u');
            l_result := REPLACE(l_result, 'ü', 'u');
            l_result := REPLACE(l_result, 'ý', 'y');
            l_result := REPLACE(l_result, 'ÿ', 'y');
            l_result := REPLACE(l_result, '!', '');
            l_result := REPLACE(l_result, '@', '');
            l_result := REPLACE(l_result, '#', '');
            l_result := REPLACE(l_result, '$', '');
            l_result := REPLACE(l_result, '%', '');
            l_result := REPLACE(l_result, '^', '');
            l_result := REPLACE(l_result, '&', '');
            l_result := REPLACE(l_result, '*', '');
            l_result := REPLACE(l_result, '(', '');
            l_result := REPLACE(l_result, ')', '');
            l_result := REPLACE(l_result, '_', '');
            l_result := REPLACE(l_result, '+', '');
            l_result := REPLACE(l_result, '{', '');
            l_result := REPLACE(l_result, '}', '');
            l_result := REPLACE(l_result, '[', '');
            l_result := REPLACE(l_result, ']', '');
            l_result := REPLACE(l_result, '|', '');
            l_result := REPLACE(l_result, ';', '');
            l_result := REPLACE(l_result, ':', '');
            l_result := REPLACE(l_result, '\"', '');
            l_result := REPLACE(l_result, '-', '');
            l_result := REPLACE(l_result, '<', '');
            l_result := REPLACE(l_result, '>', '');
            l_result := REPLACE(l_result, ',', '');
            l_result := REPLACE(l_result, '.', '');
            l_result := REPLACE(l_result, '?', '');
            l_result := REPLACE(l_result, '/', '');
            l_result := REPLACE(l_result, ' ', '');
            RETURN UPPER(l_result);
          END;
SELECT 
        TRIM(c.cli) AS IDINTCLI,
        TRIM(c.nidf) AS NIF_NIU,
       REPLACE(TRIM(c.rso),'&',' et ') AS RAISOC,
        TO_CHAR(c.datc, 'DDMMYYYY') AS DATCRE,
        TRIM(c.sig) AS SIGLE,
        '01' AS RESIDENT,
        'CM' AS PAYSSIEGE,
        ad.ville,
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
                WHEN c.catn IN(1302,1201) AND tcli IN(2,3)
                THEN 1210
                WHEN c.catn= (2202) AND tcli IN(2,3)
                THEN 1062
                WHEN c.catn=1401 AND tcli IN(2,3)
                THEN 1130
                WHEN c.catn=2203 AND tcli IN(2,3)
                THEN 1061
                ELSE TO_NUMBER(c.catn)
            END 
        ) AS AGEECO,
        '01' AS STALEG, -- EN ACTIVITE A CONTROLLER AVANT DECLARATION
        TO_CHAR(c.dou, 'DDMMYYYY') AS DATENTRELPAR,
        '' AS CHIAFFAIRE,
        '' AS TOTBILAN,
        '' AS EFFECTIF,
        TRIM(em.email) AS EMAIL,
        TRIM(REPLACE((CASE WHEN SUBSTR(t.num, 1, 3)='237'  or
          SUBSTR((SELECT MAX(TRIM(t2.tel)) FROM bkcntcli t2 WHERE t2.cli = c.cli), 1, 3)='237'   THEN '00'
          WHEN  SUBSTR(t.num, 1, 3)='002' or
          SUBSTR((SELECT MAX(TRIM(t2.tel)) FROM bkcntcli t2 WHERE t2.cli = c.cli), 1, 3)='002' THEN ''
        ELSE '00237'  END ) || 
            CASE 
                WHEN tcli = 1 THEN 
                    t.num
                ELSE 
                    (SELECT MAX(TRIM(t2.tel)) FROM bkcntcli t2 WHERE t2.cli = c.cli)
            END, ' ', '')) AS TEL,
        0 AS SITJUD,
        TO_CHAR('', 'DDMMYYYY') AS DATDEBINT,
        TO_CHAR('', 'DDMMYYYY') AS DATFININT,
        TO_CHAR(c.dou, 'DDMMYYYY') AS DATEVE,
        '03' AS TYPADR,
        TRIM(ad.adr1) AS ADRESSE,
        'CM' AS PAYS,
        CASE
      WHEN cdr_parseutf8(ad.ville) IN
        (SELECT cdr_parseutf8(nom_ville) FROM cdr_ville_region
        )
      THEN
        (SELECT code_region
        FROM cdr_ville_region
        WHERE cdr_parseutf8(nom_ville) = cdr_parseutf8(ad.ville)
        )
      ELSE 0
    END AS  REGION,
    CASE
      WHEN cdr_parseutf8(ad.ville) IN
        (SELECT cdr_parseutf8(nom_ville) FROM cdr_ville_region
        )
      THEN
        (SELECT code_ville
        FROM cdr_ville_region
        WHERE cdr_parseutf8(nom_ville) = cdr_parseutf8(ad.ville)
        )
      ELSE 0
    END AS ville,
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
            $results = DB::select($sql, $bindings);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'ORA-00942') !== false || stripos($msg, 'table or view does not exist') !== false) {
                $simpleSql = preg_replace(
                    ['/WITH\s+FUNCTION\s+cdr_parseutf8.*?END;\s*/s',
                     '/CASE\s+WHEN\s+cdr_parseutf8.*?\bELSE\s+0\s+END\s+AS\s+REGION/s',
                     '/CASE\s+WHEN\s+cdr_parseutf8.*?\bELSE\s+0\s+END\s+AS\s+ville/s',
                     '/CASE\s+WHEN\s+c\.sec\s+IN\s*\(SELECT\s+sect\s+FROM\s+cdr_naema\).*?\bELSE\s+c\.sec\s+END\s+AS\s+SECACT/s',
                     '/\(SELECT\s+MAX\(TRIM\(em\.email\)\)\s+FROM\s+bkemacli.*?\)\s+AS\s+EMAIL/s',
                     '/TRIM\(REPLACE\(.*?bkcntcli.*?END.*?\)\)\s+AS\s+TEL/s'],
                    ['', '0 AS REGION', '0 AS ville', 'c.sec AS SECACT', "'' AS EMAIL", "'' AS TEL"],
                    $sql
                );
                $results = DB::select($simpleSql, $bindings);
            } elseif (stripos($msg, 'ORA-00904') !== false || stripos($msg, 'invalid identifier') !== false) {
                return response()->json([]);
            } else {
                return response()->json([[ 'type' => 'Erreur', 'Description' => $msg ]]);
            }
        }

        $results = array_map(function ($row) {
            return array_change_key_case((array) $row, CASE_UPPER);
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


