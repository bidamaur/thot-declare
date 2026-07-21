<?php

namespace App\Http\Controllers;
use App\Services\DatabaseConnection;
use Illuminate\Support\Facades\DB;
use App\Models\cdrEncours;
use Illuminate\Http\Request;
use Carbon\Carbon;



class CdrEncoursController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $dbConnection;
    public function __construct(DatabaseConnection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }
    public function index()
    {
        return '[{"Erreur": {
        "type": "Date",
        "Description": "Date attendu au format 01-05-1995"
    }}]';
    }
public function GetEncours($MyDateArr)
{
    try {
        $connection = $this->dbConnection->getConnection();
    } catch (\Throwable $e) {
        return response()->json([
            [
                'Erreur' => [
                    'type' => 'Database',
                    'Description' => $e->getMessage(),
                ],
            ],
        ], 500);
    }

    $GetPosition = explode('-', $MyDateArr);

    // Test de conformité de la date (format MM-YYYY)
    if (
        count($GetPosition) !== 2 ||
        strlen($GetPosition[0]) !== 2 ||
        (int) $GetPosition[0] < 1 ||
        (int) $GetPosition[0] > 12 ||
        strlen($GetPosition[1]) !== 4
    ) {
        return response()->json([
            [
                'Erreur' => [
                    'type' => 'Date',
                    'Description' => 'Format date erroné, format attendu MM-AAAA',
                ],
            ],
        ], 400);
    }

    // Variables de dates
    $dateArret     = Carbon::create((int) $GetPosition[1], (int) $GetPosition[0], 1)->endOfMonth();
    $DateArr       = $dateArret->format('d/m/y');
    $DateArrYear   = $dateArret->year;
    $DateArrMonth  = $dateArret->format('m');
    $DateMonthYear = '/' . $DateArrMonth . '/' . $DateArrYear;

    // --- BLOCS SQL DYNAMIQUES POUR LES CALCULS D'ÉCHÉANCES ---
    
    // 1. Nombre total d'échéances (avec décalage si num = 0 existe)
    $NbrEchTotal = "(CASE 
        WHEN EXISTS (SELECT 1 FROM C##DBPROD.bkechprt WHERE num = 0 AND eve = d.eve AND ave = d.ave) THEN 
            (SELECT COUNT(dva) FROM C##DBPROD.bkechprt WHERE eve = d.eve AND ave = d.ave) - 1
        ELSE 
            (SELECT COUNT(dva) FROM C##DBPROD.bkechprt WHERE eve = d.eve AND ave = d.ave)
    END)";

    // 2. Nombre d'échéances payées
    $NbrEchPay = "(CASE 
        WHEN EXISTS (SELECT 1 FROM C##DBPROD.bkechprt WHERE num = 0 AND eve = e.eve AND ave = e.ave) THEN 
            (SELECT COUNT(dva) FROM C##DBPROD.bkechprt WHERE eve = d.eve AND ave = d.ave AND ctr = 9 AND eta = 'VA') - 1
        ELSE 
            (SELECT COUNT(dva) FROM C##DBPROD.bkechprt WHERE eve = d.eve AND ave = d.ave AND ctr = 9 AND eta = 'VA')
    END)";

    // 3. Nombre d'échéances impayées (plafonné à 2 si non déclassé)
    $NbrEchImp = "(CASE
        WHEN NOT EXISTS (
            SELECT 1 FROM C##DBPROD.bkcom 
            WHERE (ncp LIKE '344%' OR (ncp LIKE '345%' AND sde != 0)) 
              AND cli = d.cli
        ) AND (
            SELECT COUNT(dva) FROM C##DBPROD.bkechprt 
            WHERE ctr = 8 AND eve = d.eve AND ave = d.ave AND CDR_DATE(dva) < CDR_DATE('$DateArr')
        ) > 2 THEN 2
        ELSE (
            SELECT COUNT(dva) FROM C##DBPROD.bkechprt 
            WHERE ctr = 8 AND eve = d.eve AND ave = d.ave AND CDR_DATE(dva) < CDR_DATE('$DateArr')
        )
    END)";

    // 4. Nombre d'échéances restantes = Total - (Payées + Impayées)
    $NbrEchRes = "($NbrEchTotal - ($NbrEchPay + $NbrEchImp))";

    // 5. Nombre de jours d'impayés = Impayées * 30
    $NbrJrsImp = "($NbrEchImp * 30)";

    // Subquery pour contrôle du déclassement douteux
    $doutx = "(SELECT SUM(mon) FROM C##DBPROD.bksld WHERE
        ((cha LIKE '344%' OR cha LIKE '345%') AND cli = d.cli) AND CDR_DATE(dco) < CDR_DATE('$DateArr'))";

    // --- REQUÊTE PRINCIPALE ---
    $MyRequest = "SELECT DISTINCT 
        d.eve,
        d.ave,
        e.dva,
        d.cli, 
        (SELECT CDR_PARCE_NCP(p.ncp)
            || (CASE
                WHEN CDR_DATE(d.dmep) > CDR_DATE('30/11/2023') THEN (SELECT MAX(clc) FROM C##DBPROD.bkcom WHERE ncp = p.ncp)
            END)
         FROM C##DBPROD.bkcptprt p
         WHERE p.eve = d.eve
           AND p.nat = '004'
           AND p.ave = (SELECT MAX(ave) FROM C##DBPROD.bkcptprt WHERE eve = p.eve)
        ) RefContCmpt,

        (SELECT MAX(aa.dco)
         FROM C##DBPROD.bkauxprt aa
         WHERE aa.sen = 'C'
           AND aa.eve = d.eve
           AND CDR_DATE(aa.dco) <= CDR_DATE('$DateArr')
        ) datPai,

        CDR_DATE(e.dva) DatEch,

        (SELECT MAX(mon)
         FROM C##DBPROD.bkauxprt
         WHERE sen = 'C'
           AND eve = d.eve
           AND TO_DATE(dco, 'DD/MM/YYYY') < TO_DATE('$DateArr', 'DD/MM/YYYY')
           AND TO_DATE(dco, 'DD/MM/YYYY') = (
               SELECT MAX(TO_DATE(dco, 'DD/MM/YYYY'))
               FROM C##DBPROD.bkauxprt
               WHERE sen = 'C'
                 AND eve = d.eve
                 AND TO_DATE(dco, 'DD/MM/YYYY') < TO_DATE('$DateArr', 'DD/MM/YYYY')
           )
        ) AS MNTPAY,

        '0' AS MNTAGI,

        (CASE
            WHEN (SELECT ctr FROM C##DBPROD.bkechprt WHERE num = e.num + 1 AND eve = e.eve AND ave = (SELECT MAX(ave) FROM C##DBPROD.bkechprt WHERE eve = e.eve)) = 3 THEN 0
            WHEN e.res != 0 AND (d.tech + 1) = (
                SELECT COUNT(dva)
                FROM C##DBPROD.bkechprt
                WHERE ave = (SELECT MAX(ave) FROM C##DBPROD.bkechprt WHERE eve = e.eve)
                  AND ctr IN (9,3)
                  AND eta = 'VA'
                  AND eve = e.eve
                  AND CDR_DATE(dva) < CDR_DATE('$DateArr')
            ) THEN 0
            ELSE (
                CASE 
                    WHEN e.res = 0 AND e.num < 2 AND e.ctr NOT IN (3,8) THEN d.mon
                    WHEN e.res = 0 AND $NbrJrsImp > 0 THEN ROUND(e.amo_imp + e.tini + e.pen)
                    WHEN e.res = 0 AND e.ctr != 3 AND (
                        SELECT SUM(res) FROM C##DBPROD.bkechprt 
                        WHERE eve = d.eve AND CDR_DATE(dva) < CDR_DATE('$DateArr') 
                          AND ave = (SELECT MAX(ave) FROM C##DBPROD.bkechprt WHERE eve = e.eve)
                    ) = 0 THEN d.mon
                    WHEN e.res = 0 AND e.ctr != 3 AND (
                        SELECT SUM(res) FROM C##DBPROD.bkechprt 
                        WHERE eve = d.eve AND CDR_DATE(dva) < CDR_DATE('$DateArr') 
                          AND ave = (SELECT MAX(ave) FROM C##DBPROD.bkechprt WHERE eve = e.eve)
                    ) != 0 AND e.num != (
                        SELECT MAX(num) FROM C##DBPROD.bkechprt 
                        WHERE eve = d.eve AND ave = (SELECT MAX(ave) FROM C##DBPROD.bkechprt WHERE eve = e.eve)
                    ) THEN (
                        SELECT MIN(res) FROM C##DBPROD.bkechprt 
                        WHERE eve = d.eve AND ave = (SELECT MAX(ave) FROM C##DBPROD.bkechprt WHERE eve = e.eve) AND res != 0
                    )
                    ELSE e.res
                END
            )
        END) AS MNTCRD,

        '0' AS ESTSENSIBLE,
        d.mon AS MNTTOTUTIL,

        -- Nouvelles colonnes intégrées
        $NbrEchPay AS nbrEchPay,
        $NbrEchImp AS nbrEchImp,
        $NbrEchRes AS nbrEchRes,

        (CASE
            WHEN $NbrJrsImp = 0 THEN 0 
            ELSE ROUND((
                CASE
                    WHEN $NbrJrsImp != 0 AND e.amo_imp = 0 THEN e.tot_ech
                    ELSE ROUND(e.tot_ech)
                END
            ) + e.inte)
        END) * (
            CASE
                WHEN $NbrEchImp >= 2 AND NVL($doutx, 0) = 0 THEN 2 
                ELSE 1 
            END
        ) AS MNTCRESOUF,

        (CASE
            WHEN $NbrJrsImp != 0 AND e.amo_imp = 0 THEN e.tot_ech
            ELSE ROUND(e.amo_imp)
        END) AS MNTCAPSOUF,

        (CASE
            WHEN $NbrJrsImp != 0 AND e.inte = 0 THEN (SELECT MIN(inte) FROM C##DBPROD.bkechprt WHERE eve = e.eve AND ave = (SELECT MAX(ave) FROM C##DBPROD.bkechprt WHERE eve = e.eve))
            WHEN $NbrJrsImp = 0 THEN 0
            ELSE e.inte
        END) AS MNTINTSOUF,

        e.inte interet,
        e.amo_imp capital,
        e.tot_ech,

        (CASE
            WHEN $NbrEchImp >= 2 AND NVL($doutx, 0) = 0 THEN 2 
            ELSE 1 
        END) echimpaye,

        (CASE
            WHEN e.amo_imp = 0 THEN 0
            ELSE e.tin
        END) AS MNTTAXSOUF,

        '0' AS MNTAGIOSSOUF,
        e.inte \"MNTERAT\",
        '' AS MNTPRO,

        -- Nouveau calcul pour nbrJrsImp
        $NbrJrsImp AS nbrJrsImp,

        (CASE
            WHEN $NbrJrsImp > 0 AND NVL($doutx, 0) < 1 THEN '04'
            WHEN $NbrJrsImp > 0 AND $NbrJrsImp < 90 THEN '04'
            WHEN $NbrJrsImp >= 90 AND $NbrJrsImp < 110 AND NVL($doutx, 0) > 0 THEN '06'
            WHEN $NbrJrsImp >= 110 AND $NbrJrsImp < 365 AND NVL($doutx, 0) > 0 THEN '07'
            WHEN $NbrJrsImp >= 365 AND $NbrJrsImp < 730 AND NVL($doutx, 0) > 0 THEN '08'
            WHEN $NbrJrsImp >= 730 AND $NbrJrsImp < 1095 AND NVL($doutx, 0) > 0 THEN '09'
            ELSE '01'
        END) AS ClaDeprec,

        $NbrEchImp AS testeeee

        FROM C##DBPROD.bkdosprt d,
             C##DBPROD.bkechprt e,
             C##DBPROD.bkcom co
        WHERE e.eve = d.eve
          AND d.eta IN ('VA', 'DE')
          AND d.cli = co.cli
          AND (e.dva BETWEEN CDR_DATE('01$DateMonthYear') AND CDR_DATE('01-'||TO_CHAR(ADD_MONTHS(CDR_DATE('$DateArr'), 1), 'MM-YYYY')))
          AND CDR_DATE(e.dva) <= CDR_DATE('$DateArr')
          AND d.ave = (SELECT MAX(ave) FROM C##DBPROD.bkdosprt WHERE eve = d.eve)
          AND e.ave = (SELECT MAX(ave) FROM C##DBPROD.bkechprt WHERE eve = e.eve)
          AND d.tau_int != 0
          AND d.eve NOT IN (002259)";

    try {
        $stid = oci_parse($connection, $MyRequest);
        oci_execute($stid);

        $results = [];

        while ($row = oci_fetch_assoc($stid)) {
            $results[] = array_change_key_case($row, CASE_UPPER);
        }

        // Convertir en UTF-8 après la récupération
        $results = mb_convert_encoding($results, 'UTF-8', 'ISO-8859-1');

        // Libérer les ressources
        oci_free_statement($stid);
        oci_close($connection);

        // Retourner les résultats
        return response()->json($results);
    } catch (\Throwable $e) {
        if (isset($stid)) {
            oci_free_statement($stid);
        }

        return response()->json([
            [
                'Erreur' => [
                    'type' => 'Query',
                    'Description' => $e->getMessage(),
                ],
            ],
        ], 500);
    }
}
public function GetEncoursAjust($MyDateArr)
    {
        $connection = $this->dbConnection->getConnection();
        $GetPosition = explode('-', $MyDateArr);

        //teste de conformite de la date (format mm-yyyy)
        if (
            count($GetPosition) !== 2 ||
            strlen($GetPosition[0]) !== 2 ||
            (int) $GetPosition[0] < 1 ||
            (int) $GetPosition[0] > 12 ||
            strlen($GetPosition[1]) !== 4
        ) {
            echo '[{"Erreur": {
        "type": "Date",
        "Description": "Format date erroné, format attendu MM-AAAA"
    }}]';
            return false;
        }

        //variables
        $dateArret = Carbon::create((int) $GetPosition[1], (int) $GetPosition[0], 1)->endOfMonth();
        $DateArr = $dateArret->format('d/m/y');
        $DateArrYear = $dateArret->year;
        $DateArrMonth = $dateArret->format('m');
        $DateArrDay = $dateArret->day;
        $DateMonthYear = '/' . $DateArrMonth . '/' . $DateArrYear;
        $notFound = '[{"Erreur": {
    "type": "Date",
    "Description": "Format date erroné, format attendu 01-05-1995"
    }}]';
        $myData = $notFound;
        $MyRequest = "WITH Max_Ave AS (
            SELECT eve, MAX(ave) AS max_ave
            FROM C##DBPROD.bkechprt
            GROUP BY eve
        ),
        Ech_Calculations AS (
            SELECT 
                eve,
                (select count(ech.dva) from C##DBPROD.bkechprt ech where ech.ctr in (9,3) and ech.eve=bkechprt.eve and cdr_date(ech.dva)<=cdr_date('$DateArr') and ech.ave=(SELECT MAX(kk.ave) FROM C##DBPROD.bkechprt kk WHERE kk.eve = bkechprt.eve))AS soldepaye9_3,
                SUM(0) AS impayesCTR8,
                --  max(res) AS min_res
                 (select min(tt.res) from C##DBPROD.bkechprt tt where tt.eve=bkechprt.eve and tt.res!=0 
                 and tt.ave=(SELECT MAX(k.ave) FROM C##DBPROD.bkechprt k WHERE k.eve = bkechprt.eve) 
                 and cdr_date(tt.dva)<cdr_date('$DateArr')) as min_res

            FROM C##DBPROD.bkechprt
            WHERE cdr_date(dva) < cdr_date('$DateArr')
            
              
            GROUP BY eve
        ),
        Sld_Calculations AS (
            SELECT 
                cli,
                SUM(CASE WHEN cha LIKE '341%' THEN mon ELSE 0 END) AS sld_341,
                SUM(CASE WHEN cha LIKE '3441%' OR cha LIKE '3451%' THEN mon ELSE 0 END) AS sld_3441_3451,
                SUM(CASE WHEN cha LIKE '3442%' OR cha LIKE '3452%' THEN mon ELSE 0 END) AS sld_3442_3452,
                SUM(CASE WHEN cha LIKE '3443%' OR cha LIKE '3453%' THEN mon ELSE 0 END) AS sld_3443_3453,
                SUM(CASE WHEN cha LIKE '344%' OR cha LIKE '345%' THEN mon ELSE 0 END) AS sld_344_345,
                SUM(CASE WHEN cha LIKE '301%' OR cha LIKE '311%' OR cha LIKE '321%' THEN mon ELSE 0 END) AS sld_301_311_321
            FROM C##DBPROD.bksld
            WHERE CDR_DATE(dco) < cdr_date('$DateArr')
            GROUP BY cli
        )
        SELECT DISTINCT 
            d.eve,
            d.ave,
            d.cli,
            (15 || '/$DateArrMonth' || '/$DateArrYear') AS DVA,
            (SELECT cdr_parce_ncp(p.ncp)
     ||(
    CASE
    WHEN cdr_date(d.dmep)>cdr_date('30/11/2023') THEN (SELECT clc from C##DBPROD.bkcom where ncp=p.ncp)
    END)
     FROM C##DBPROD.bkcptprt p
     WHERE p.eve=d.eve
     AND p.nat  ='004'
     AND p.ave  =
       (SELECT MAX(ave) FROM C##DBPROD.bkcptprt WHERE eve=p.eve
       )
     ) RefContCmpt,
     d.typ,
            NVL(
                CASE
                    WHEN ec.min_res = 0 THEN d.mon
                    ELSE ec.min_res
                END,
                d.mon
            ) AS MNTCRD,
            d.dmep,
            (15 || '/$DateArrMonth' || '/'||SUBSTR($DateArrYear, -2)) AS DATECH,
            (15 || '/$DateArrMonth' || '/'||SUBSTR($DateArrYear, -2)) AS DATPAI,
            0 AS MNTPAY,
            0 AS MNTAGI,
            0 AS ESTSENSIBLE,
            nvl(ec.min_res,0),
            (
            NVL(ec.soldepaye9_3,0)-(CASE WHEN d.typ=105 THEN 0 ELSE 1 END)-NVL(ec.ec.impayesCTR8,0)
            )AS nbrEchPay,
            NVL(ec.ec.impayesCTR8,0) AS NBRECHIMP,
            (CASE 
            WHEN d.typ!=105  AND NVL(ec.min_res,0)=0 THEN d.tech
            WHEN d.typ!=105  AND nvl(ec.min_res,0)>0 and d.tech>1 THEN d.tech-(NVL(ec.soldepaye9_3,0)-1+NVL(ec.impayesCTR8,0))
            ELSE
            (d.tech)-(NVL(ec.soldepaye9_3,0))
            END)AS nbrEchRes,
            '0' AS MNTCRESOUF,
            '0' AS MNTCAPSOUF,
            '0' AS MNTINTSOUF,
            0 AS MNTTAXSOUF,
            0 AS MNTAGIOSSOUF,
            0 AS MNTERAT,
            0 AS MNTPRO,
            0 AS NBRJRSIMP,
            d.mon AS MNTTOTUTIL,
            (
                CASE
                    WHEN ec.impayesCTR8 > 0 THEN '04'
                    WHEN sc.sld_341 > 0 THEN '04'
                    WHEN sc.sld_3441_3451 > 0 THEN '07'
                    WHEN sc.sld_3442_3452 > 0 THEN '08'
                    WHEN sc.sld_3443_3453 > 0 THEN '09'
                    WHEN sc.sld_344_345 > 0 THEN '06'
                    WHEN sc.sld_301_311_321 > 0 THEN '02'
                    ELSE '01'
                END
            ) AS CLADEPREC
        FROM C##DBPROD.bkdosprt d
        LEFT JOIN Max_Ave ma ON ma.eve = d.eve
        LEFT JOIN Ech_Calculations ec ON ec.eve = d.eve
        LEFT JOIN Sld_Calculations sc ON sc.cli = d.cli
        WHERE 
            -- trunc(MONTHS_BETWEEN('$DateArr', d.dmep)) >= 1
            -- AND d.ave = ma.max_ave
            -- AND NOT EXISTS (
            --     SELECT 1 
            --     FROM C##DBPROD.bkechprt
            --     WHERE EXTRACT(MONTH FROM dva) = '$DateArrMonth'
            --       AND EXTRACT(YEAR FROM cdr_date(dva)) = '$DateArrYear'
            --       AND eve = d.eve
            --       AND ave = ma.max_ave
            -- )
            -- AND d.eta IN ('VA', 'DE')
            -- AND d.ddec > '$DateArr'
            -- AND d.tau_int!=0
            -- and d.eve not in(002259)
            d.eta IN ('VA', 'DE')
            AND CDR_DATE(d.ddec) > CDR_DATE('$DateArr')
            AND d.tau_int!=0
            and d.eve not in(002259)
            AND
                (
        (d.dmep between CDR_DATE('01$DateMonthYear') and CDR_DATE('$DateArr') and NOT EXISTS (
                SELECT 1 
                FROM C##DBPROD.bkechprt
                WHERE EXTRACT(MONTH FROM CDR_DATE(dva)) = '$DateArrMonth'
                  AND EXTRACT(YEAR FROM cdr_date(dva)) = '$DateArrYear'
                  AND eve = d.eve
                  AND ave = ma.max_ave
            )
            ) or
           trunc(MONTHS_BETWEEN(CDR_DATE('$DateArr'), CDR_DATE(d.dmep))) >= 1 
            AND d.ave = ma.max_ave
            AND NOT EXISTS (
                SELECT 1 
                FROM C##DBPROD.bkechprt
                WHERE EXTRACT(MONTH FROM dva) = '$DateArrMonth'
                  AND EXTRACT(YEAR FROM cdr_date(dva)) = '$DateArrYear'
                  AND eve = d.eve
                  AND ave = ma.max_ave
            )
    )
    
            -- AND d.per_cap=4
        ORDER BY d.eve DESC
        ";
        // dd($MyRequest);
        $stid = oci_parse($connection, $MyRequest);
        // oci_bind_by_name($stid, ":id", $id);
        oci_execute($stid);

        $results = [];

        while ($row = oci_fetch_assoc($stid)) {
            $results[] = array_change_key_case((array) $row, CASE_UPPER);
        }

        $results = mb_convert_encoding($results, 'UTF-8', 'ISO-8859-1');

        oci_free_statement($stid);
        oci_close($connection);

        if (empty($results)) {
            return response()->json(json_decode($notFound, true));
        }

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
    public function show(cdrEncours $cdrEncours)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, cdrEncours $cdrEncours)
    {
        //
    }

    /**
     * Remove the specified resource FROM storage.
     */
    public function destroy(cdrEncours $cdrEncours)
    {
        //
    }
}
