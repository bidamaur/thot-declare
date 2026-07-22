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
/** fonction des encours */
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
        strlen($GetPosition[1]) !== 4 ||
        !checkdate((int) $GetPosition[0], 1, (int) $GetPosition[1])
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

    // Variables de dates calculées via Carbon
    $dateArret     = Carbon::create((int) $GetPosition[1], (int) $GetPosition[0], 1)->endOfMonth();
    $dateDebutMois = Carbon::create((int) $GetPosition[1], (int) $GetPosition[0], 1)->startOfMonth();

    $DateArr       = $dateArret->format('d/m/Y');
    $DateDebMois   = $dateDebutMois->format('d/m/Y');

    // --- BLOCS SQL DYNAMIQUES POUR LES CALCULS D'ÉCHÉANCES ---
    
    // 1. Nombre total d'échéances pour L'AVENANT COURANT
    $NbrEchTotal = "(CASE 
        WHEN EXISTS (SELECT 1 FROM C##DBPROD.bkechprt WHERE num = 0 AND eve = d.eve AND ave = d.ave) THEN 
            (SELECT COUNT(dva) FROM C##DBPROD.bkechprt WHERE eve = d.eve AND ave = d.ave) - 1
        ELSE 
            (SELECT COUNT(dva) FROM C##DBPROD.bkechprt WHERE eve = d.eve AND ave = d.ave)
    END)";

    // 2. Nombre d'échéances payées (ctr = 9)
    $NbrEchPay = "(CASE 
        WHEN EXISTS (SELECT 1 FROM C##DBPROD.bkechprt WHERE num = 0 AND eve = d.eve AND ave = d.ave AND CDR_DATE(dva) <= CDR_DATE('$DateArr')) THEN 
            (SELECT COUNT(dva) FROM C##DBPROD.bkechprt WHERE eve = d.eve AND ave = d.ave AND ctr = 9 AND eta = 'VA' AND CDR_DATE(dva) <= CDR_DATE('$DateArr')) - 1
        ELSE 
            (SELECT COUNT(dva) FROM C##DBPROD.bkechprt WHERE eve = d.eve AND ave = d.ave AND ctr = 9 AND eta = 'VA' AND CDR_DATE(dva) <= CDR_DATE('$DateArr'))
    END)";

    // 3. Nombre d'échéances impayées
    $NbrEchImp = "(CASE
        WHEN NOT EXISTS (
            SELECT 1 FROM C##DBPROD.bksld 
            WHERE cli = d.cli 
              AND (ncp LIKE '344%' OR ncp LIKE '345%') 
              AND sde != 0 
              AND CDR_DATE(dco) <= CDR_DATE('$DateArr')
        ) AND (
            SELECT COUNT(dva) FROM C##DBPROD.bkechprt 
            WHERE ctr = 8 AND eve = d.eve AND ave = d.ave AND CDR_DATE(dva) <= CDR_DATE('$DateArr')
        ) > 2 THEN 2
        ELSE (
            SELECT COUNT(dva) FROM C##DBPROD.bkechprt 
            WHERE ctr = 8 AND eve = d.eve AND ave = d.ave AND CDR_DATE(dva) <= CDR_DATE('$DateArr')
        )
    END)";

    // 4. Calculs dérivés
    $NbrEchRes = "($NbrEchTotal - ($NbrEchPay + $NbrEchImp))";
    $NbrJrsImp = "($NbrEchImp * 30)";

    // --- SUBQUERIES BKSLD SÉCURISÉES SUR CLI ---
    $doutx = "(SELECT SUM(mon) FROM C##DBPROD.bksld 
               WHERE cli = d.cli 
                 AND (ncp LIKE '344%' OR ncp LIKE '345%') 
                 AND CDR_DATE(dco) <= CDR_DATE('$DateArr'))";

    $mon_douteux = "ABS(NVL((
        SELECT mon FROM C##DBPROD.bksld 
        WHERE cli = d.cli 
          AND (ncp LIKE '344%' OR ncp LIKE '345%')
          AND CDR_DATE(dco) = (
              SELECT MAX(CDR_DATE(dco)) 
              FROM C##DBPROD.bksld 
              WHERE cli = d.cli 
                AND (ncp LIKE '344%' OR ncp LIKE '345%') 
                AND mon != 0 
                AND CDR_DATE(dco) <= CDR_DATE('$DateArr')
          ) 
          AND ROWNUM = 1
    ), 0))";

    $montant_provision_imp = "ABS(NVL((
        SELECT SUM(mon) 
        FROM C##DBPROD.bksld 
        WHERE cli = d.cli 
          AND cha = '3911000'
          AND TO_CHAR(CDR_DATE(dco), 'MM/YYYY') = TO_CHAR(CDR_DATE('$DateArr'), 'MM/YYYY') 
          AND $NbrJrsImp != 0
    ), 0))";

    $montant_provision_dtx = "ABS(NVL((
        SELECT SUM(mon) 
        FROM C##DBPROD.bksld 
        WHERE cli = d.cli 
          AND cha = '3943000'
          AND TO_CHAR(CDR_DATE(dco), 'MM/YYYY') = TO_CHAR(CDR_DATE('$DateArr'), 'MM/YYYY') 
          AND $NbrJrsImp != 0
    ), 0))";

    $mon_impaye = "ABS(NVL((
        SELECT SUM(mon) 
        FROM C##DBPROD.bksld 
        WHERE cli = d.cli 
          AND cha = '3411000'
          AND TO_CHAR(CDR_DATE(dco), 'MM/YYYY') = TO_CHAR(CDR_DATE('$DateArr'), 'MM/YYYY') 
          AND $NbrJrsImp != 0
    ), 0))";

    // Numéro de la dernière échéance du dossier pour cet avenant précis
    $last_num_echeance = "(SELECT MAX(num) FROM C##DBPROD.bkechprt WHERE eve = d.eve AND ave = d.ave)";

    // --- REQUÊTE PRINCIPALE ALIGNÉE SUR (EVE, AVE) ---
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
           AND p.ave = d.ave
           AND p.nat = '004'
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

        $mon_douteux AS MNTAGI,

        (CASE 
            WHEN e.num = $last_num_echeance THEN 0
            WHEN e.num IN (0, 1, 2, 3) AND e.res = 0 THEN d.mon
            WHEN (e.num >= 4 AND e.num <= $last_num_echeance) AND e.res = 0 THEN 
                NVL((SELECT res FROM C##DBPROD.bkechprt 
                     WHERE eve = d.eve 
                       AND ave = d.ave 
                       AND TO_CHAR(dva, 'MM/YYYY') = TO_CHAR(ADD_MONTHS(e.dva, -1), 'MM/YYYY')
                       AND ROWNUM = 1), 0)
            ELSE e.res
        END) AS MNTCRD,

        '0' AS ESTSENSIBLE,
        d.mon AS MNTTOTUTIL,

        -- Colonnes d'échéances
        $NbrEchPay AS nbrEchPay,
        $NbrEchImp AS nbrEchImp,
        $NbrEchRes AS nbrEchRes,

        (CASE 
            WHEN $mon_douteux = 0 THEN $mon_impaye 
            ELSE $mon_douteux 
        END) AS MNTCRESOUF,

        (CASE
            WHEN $NbrJrsImp != 0 AND e.amo_imp = 0 THEN e.tot_ech
            ELSE ROUND(e.amo_imp)
        END) AS MNTCAPSOUF,

        (CASE
            WHEN $NbrJrsImp != 0 AND e.inte = 0 THEN (SELECT MIN(inte) FROM C##DBPROD.bkechprt WHERE eve = e.eve AND ave = e.ave)
            WHEN $NbrJrsImp = 0 THEN 0
            ELSE (e.inte + NVL(e.ini, 0))
        END) AS MNTINTSOUF,

        e.inte interet,
        e.amo_imp capital,
        e.tot_ech,

        (CASE
            WHEN $NbrEchImp >= 2 AND $mon_douteux = 0 THEN 2 
            ELSE 1 
        END) echimpaye,

        (CASE
            WHEN e.amo_imp = 0 THEN 0
            ELSE e.tin
        END) AS MNTTAXSOUF,

        '0' AS MNTAGIOSSOUF,
        e.inte MNTERAT,
        (CASE
            WHEN $mon_douteux != 0 THEN $montant_provision_dtx
            WHEN $mon_impaye != 0 THEN $montant_provision_imp
            ELSE 0
        END) AS MNTPRO,

        $NbrJrsImp AS nbrJrsImp,

        -- Classes de dépréciation
        (CASE
            WHEN $NbrJrsImp = 0 AND $mon_douteux = 0 THEN '01'
            WHEN $NbrJrsImp BETWEEN 1 AND 90 AND $mon_douteux = 0 THEN '02'
            WHEN ($NbrJrsImp BETWEEN 91 AND 180) OR ($mon_douteux != 0 AND $NbrJrsImp <= 180) THEN '03'
            WHEN $NbrJrsImp BETWEEN 181 AND 360 THEN '04'
            WHEN $NbrJrsImp > 360 THEN '05'
            ELSE '01'
        END) AS ClaDeprec,

        $NbrEchImp AS testeeee

        FROM C##DBPROD.bkdosprt d
        INNER JOIN C##DBPROD.bkechprt e ON e.eve = d.eve AND e.ave = d.ave
        WHERE d.eta IN ('VA', 'DE')
          AND e.dva BETWEEN CDR_DATE('$DateDebMois') AND CDR_DATE('$DateArr')
          AND CDR_DATE(e.dva) <= CDR_DATE('$DateArr')
          AND d.tau_int != 0
          AND d.eve NOT IN ('002259')
          AND e.ctr != 3";

    $stid = null;
    try {
        $stid = oci_parse($connection, $MyRequest);
        oci_execute($stid);

        $results = [];

        while ($row = oci_fetch_assoc($stid)) {
            $results[] = array_change_key_case($row, CASE_UPPER);
        }

        // Convertir en UTF-8 après la récupération
        $results = mb_convert_encoding($results, 'UTF-8', 'ISO-8859-1');

        return response()->json($results);
    } catch (\Throwable $e) {
        return response()->json([
            [
                'Erreur' => [
                    'type' => 'Query',
                    'Description' => $e->getMessage(),
                ],
            ],
        ], 500);
    } finally {
        if ($stid) {
            oci_free_statement($stid);
        }
        if (isset($connection) && $connection) {
            oci_close($connection);
        }
    }
}
/** Fin de encours */
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
