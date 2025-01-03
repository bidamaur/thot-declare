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
        $connection = $this->dbConnection->getConnection();
        $GetPosition = explode('-', $MyDateArr);

        //teste de conformite de la date
        if (
            count($GetPosition) !== 3 ||
            strlen($GetPosition[0]) !== 2 ||
            $GetPosition[0] > 31 ||
            $GetPosition[1] > 12 ||
            strlen($GetPosition[1]) !== 2 ||
            strlen($GetPosition[2]) !== 4
        ) {
            echo '[{"Erreur": {
        "type": "Date",
        "Description": "Format date erroné, format attendu 01-05-1995"
    }}]';
            return false;
        }

        //variables
        $dateArret = Carbon::parse($MyDateArr);
        $DateArr = $dateArret->format('d/m/y');
        $DateArrYear = $dateArret->year;
        $DateArrMonth = $dateArret->month;
        $DateArrDay = $dateArret->day;
        $DateMonthYear = '/' . $DateArrMonth . '/' . $DateArrYear;
        $notFound = '[{"Erreur": {
    "type": "Date",
    "Description": "Format date erroné, format attendu 01-05-1995"
}}]';
        $myData = $notFound;
        $MyRequest = "SELECT DISTINCT d.eve,
    e.dva,
    d.cli, 
    (SELECT cdr_parce_ncp(p.ncp)
    ||(
    CASE
    WHEN cdr_date(d.dmep)>cdr_date('30/11/2023') THEN (SELECT max(clc) from bkcom where ncp=p.ncp)
    END)
    FROM bkcptprt p
    WHERE p.eve=d.eve
    AND p.nat  ='004'
    AND p.ave  =
    (SELECT MAX(ave) FROM bkcptprt WHERE eve=p.eve
    )
    ) RefContCmpt ,
    (SELECT MAX(aa.dco)
    FROM bkauxprt aa
    WHERE aa.sen                      ='C'
    AND aa.eve                        =d.eve
    AND CDR_DATE(aa.dco) <= CDR_DATE('$DateArr')
    )datPai,
    --max(co.ddc) datPai, --date de dernier paiement (ncp like '371%' or ncp like '372%') and cli=d.cli
    cdr_date(e.dva) DatEch,
    (SELECT MAX(mon)
    FROM bkauxprt
    WHERE sen                      = 'C'
    AND eve                        = d.eve
    AND TO_DATE(dco, 'DD/MM/YYYY') < TO_DATE('$DateArr', 'DD/MM/YYYY')
    AND TO_DATE(dco, 'DD/MM/YYYY') =
    (SELECT MAX(TO_DATE(dco, 'DD/MM/YYYY'))
    FROM bkauxprt
    WHERE sen                      = 'C'
    AND eve                        = d.eve
    AND TO_DATE(dco, 'DD/MM/YYYY') < TO_DATE('$DateArr', 'DD/MM/YYYY')
    )
    ) AS MntPay,  --montant dernier paiement,
    '0' MntAgi,   -- pour les découverts
    (
    CASE
    --verification si c'est une tombee
    when (select ctr from bkechprt where num=e.num+1 and eve=e.eve and
    ave=(select max(ave) from bkechprt where eve=e.eve) )=3 THEN 0
    -- en cas d'encours a la fin d'echeance
    WHEN e.res!=0 AND  (d.tech+1)=(    (SELECT COUNT(dva)
    FROM bkechprt
    WHERE 
    ave=(select max(ave) from bkechprt where eve=e.eve)
    AND ctr                   IN (9,3)
    AND eta                      ='VA'
    AND eve                      =e.eve
    AND CDR_DATE(dva) < CDR_DATE('$DateArr')
    )) THEN 0

    ELSE  (
    CASE 
    -- WHEN e.res=0 and e.num=0  THEN d.mon
    WHEN e.res=0 and e.ctr!=3 and (SELECT SUM(res) from bkechprt 
    where eve=d.eve and dva<'$DateArr' 
    AND ave=(SELECT MAX(ave) FROM bkechprt WHERE eve=e.eve))=0
    THEN d.mon
    
    WHEN e.res=0 and e.ctr!=3 and (SELECT SUM(res) from bkechprt 
    where eve=d.eve and dva<'$DateArr' 
    AND ave=(SELECT MAX(ave) FROM bkechprt WHERE eve=e.eve))!=0 
    AND e.num!=(SELECT MAX(num) from bkechprt where 
    eve=d.eve AND ave=(SELECT MAX(ave) FROM bkechprt WHERE eve=e.eve)
    ) THEN (SELECT MIN(res) from bkechprt where 
    eve=d.eve AND ave=(SELECT MAX(ave) FROM bkechprt WHERE eve=e.eve) and res!=0)

    ELSE 
    e.res
    END
    )
    END
    ) MntCrd, --Encours
    '0' estSensible,
    --d.mdb MntTotUtil,
    d.mon MntTotUtil,
    (
    CASE
    when (select ctr from bkechprt where num=e.num+1 and eve=e.eve and
    ave=(select max(ave) from bkechprt where eve=e.eve) )=3 THEN d.tech
    ELSE (
    (
    CASE 
    WHEN (e.res=0 and e.ctr!=8 and e.num>1) THEN d.tech -- gestion des paiements anticipee
    ELSE
    (
    SELECT COUNT(dva)
    FROM bkechprt
    WHERE 
    ave=(select max(ave) from bkechprt where eve=e.eve)
    AND ctr       IN (9,3)
    AND eta  ='VA'
    AND eve  =e.eve
    AND CDR_DATE(dva) <= CDR_DATE('$DateArr')
    )-(
    CASE
    WHEN d.tech<(SELECT COUNT(dva) from bkechprt where eve=e.eve and 
    ave=(select max(ave) from bkechprt where eve=e.eve)) THEN 1
    ELSE 0
    END )
    END
    )
    )
    END
    )
    nbrEchPay,

    (
    SELECT COUNT(dva)
    FROM bkechprt
    WHERE 
    ave=(select max(ave) from bkechprt where eve=e.eve)
    AND ctr='8'
    AND eta  ='VA'
    AND eve  =e.eve
    AND CDR_DATE(dva) <= CDR_DATE('$DateArr')
    ) nbrEchImp,-- a revoir ici c'est le nombre d'echéances impayes
    
    (
    CASE
    when (select ctr from bkechprt where num=e.num+1 and eve=e.eve and
    ave=(select max(ave) from bkechprt where eve=e.eve) )=3 THEN 0
    ELSE
    ((
    CASE 
    WHEN (e.res=0 and e.ctr!=8 and e.num>1) THEN 0
    ELSE 
    (
    (
    d.tech+(
    CASE
    WHEN d.tech<(SELECT COUNT(dva) from bkechprt where eve=e.eve and ave=(select max(ave) from bkechprt where eve=e.eve)) THEN 1
    ELSE 0
    END
    )
    )-(
    SELECT COUNT(dva)
    FROM bkechprt
    WHERE 
    ave=(select max(ave) from bkechprt where eve=e.eve)
    AND ctr IN (9,3)
    AND eta ='VA'
    AND eve =e.eve
    AND CDR_DATE(dva) <= CDR_DATE('$DateArr')
    )-(
    (SELECT COUNT(dva)
    FROM bkechprt
    WHERE 
    ave=(select max(ave) from bkechprt where eve=e.eve)
    AND ctr  ='8'
    AND eta ='VA'
    AND eve =e.eve
    AND CDR_DATE(dva) <= CDR_DATE('$DateArr')
    )  
    )
    ) 
    END
    ))
    END
    )
    nbrEchRes,

    -------------- a ajouter ---------
    ROUND(e.amo_imp) MntCreSouf ,
    e.amo_imp MntCapSouf ,
    (CASE
    WHEN e.amo_imp=0 THEN 0
    ELSE e.inte
    END) MntIntSouf,
    (CASE
    WHEN e.amo_imp=0 THEN 0
    ELSE e.tin
    END) MntTaxSouf ,
    -------------- fin
    '0' MntAgiosSouf,
    e.inte MntCreRat,
    '' MntPro,  
    (
    CASE
    WHEN  e.amo_imp=0 THEN 0 
    ELSE CDR_DATE('$DateArr')-CDR_DATE((SELECT MIN(DVA) from bkechprt where eta='VA' AND ctr=8 and eve=e.eve 
    and ave=(select max(ave) from bkechprt where eve=e.eve)))
    END
    )nbrJrsImp,

    (
    CASE

    WHEN (SELECT count(dva) from bkechprt where ctr=8 and eve=e.eve and cdr_date(dva)<cdr_date('$DateArr'))>0 THEN '04'

    WHEN (select sum(mon) from bksld where
    (cha like '341%' and cli=d.cli) and dco<cdr_date('$DateArr'))>0 THEN '04'

    WHEN (select sum(mon) from bksld where
    ((cha like '3441%'  or cha like '3451%') and cli=d.cli) and dco<cdr_date('$DateArr'))>0 THEN '07'

    WHEN (select sum(mon) from bksld where
    ((cha like '3442%'  or cha like '3452%') and cli=d.cli) and dco<cdr_date('$DateArr'))>0 THEN '08'

    WHEN (select sum(mon) from bksld where
    ((cha like '3443%'  or cha like '3453%') and cli=d.cli) and dco<cdr_date('$DateArr'))>0 THEN '09'
    WHEN (select sum(mon) from bksld where
    ((cha like '344%'  or cha like '345%') and cli=d.cli) and dco<cdr_date('$DateArr'))>0 THEN '06'
    WHEN (select sum(mon) from bksld where
    (cha like '301%' or cha like '311%' or cha like '321%' and cli=d.cli) and dco<cdr_date('$DateArr'))>0 THEN '02'
    ELSE '01'
    END)
    ClaDeprec
    FROM bkdosprt d,
    bkechprt e,
    bkcom co
    WHERE e.eve=d.eve
    AND d.eta  ='VA'
    and e.ctr not in(3)
    AND d.cli=co.cli
    AND (e.dva BETWEEN '01$DateMonthYear' AND ('01-'||TO_CHAR(ADD_MONTHS(CDR_DATE('$DateArr'), 1), 'MM-YYYY')))
    AND e.dva<=CDR_DATE('$DateArr')
    AND d.ave=
    (SELECT MAX(ave) FROM bkdosprt WHERE eve=d.eve
    )
    AND e.ave=
    (SELECT MAX(ave) FROM bkechprt WHERE eve=e.eve
    )
AND d.tau_int!=0";
        // dd($MyRequest);
        $stid = oci_parse($connection, $MyRequest);
        // oci_bind_by_name($stid, ":id", $id);
        oci_execute($stid);

        while ($row = oci_fetch_assoc($stid)) {
            // var_dump($row);  // Traitez vos résultats ici
            $results[] = $row;
            if (!$row) {
                return false;
            }
            $results = array_map(function ($row) {
                return array_change_key_case((array) $row, CASE_UPPER);
            }, $results);
            $myData = response()->json($results);

        }

        if ($myData) {
            return $myData;
        }

        oci_free_statement($stid);
        oci_close($connection);

    }


    // encours de reajustement
    public function GetEncoursAjust($MyDateArr)
    {
        $connection = $this->dbConnection->getConnection();
        $GetPosition = explode('-', $MyDateArr);

        //teste de conformite de la date
        if (
            count($GetPosition) !== 3 ||
            strlen($GetPosition[0]) !== 2 ||
            $GetPosition[0] > 31 ||
            $GetPosition[1] > 12 ||
            strlen($GetPosition[1]) !== 2 ||
            strlen($GetPosition[2]) !== 4
        ) {
            echo '[{"Erreur": {
        "type": "Date",
        "Description": "Format date erroné, format attendu 01-05-1995"
    }}]';
            return false;
        }

        //variables
        $dateArret = Carbon::parse($MyDateArr);
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
            FROM bkechprt
            GROUP BY eve
        ),
        Ech_Calculations AS (
            SELECT 
                eve,
                SUM(CASE WHEN ctr IN (9, 3) AND eta = 'VA' 
                and ave=(SELECT MAX(ave) FROM bkechprt WHERE eve = bkechprt.eve) THEN 1 ELSE 0 END) AS ctr_93_va_count,
                SUM(CASE WHEN ctr = 8 AND eta = 'VA' and ave=(SELECT MAX(u.ave) FROM bkechprt u WHERE u.eve = bkechprt.eve) THEN 1 ELSE 0 END) AS ctr_8_va_count,
                --  max(res) AS min_res
                 (select min(tt.res) from bkechprt tt where tt.eve=bkechprt.eve and tt.res!=0 
                 and tt.ave=(SELECT MAX(k.ave) FROM bkechprt k WHERE k.eve = bkechprt.eve) 
                 and cdr_date(tt.dva)<cdr_date('$DateArr')) as min_res

            FROM bkechprt
            WHERE dva < '$DateArr'
            
              
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
            FROM bksld
            WHERE dco < cdr_date('$DateArr')
            GROUP BY cli
        )
        SELECT DISTINCT 
            d.eve,
            d.cli,
            (15 || '/$DateArrMonth' || '/$DateArrYear') AS DVA,
            (SELECT cdr_parce_ncp(p.ncp)
     ||(
    CASE
    WHEN cdr_date(d.dmep)>cdr_date('30/11/2023') THEN (SELECT clc from bkcom where ncp=p.ncp)
    END)
     FROM dbprod.bkcptprt p
     WHERE p.eve=d.eve
     AND p.nat  ='004'
     AND p.ave  =
       (SELECT MAX(ave) FROM dbprod.bkcptprt WHERE eve=p.eve
       )
     ) RefContCmpt,
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
            (
                ec.ctr_93_va_count - 
                CASE
                    WHEN ec.ctr_93_va_count >= 1 THEN 1
                    ELSE 0
                END
            ) AS nbrEchPay,
            ec.ctr_8_va_count AS NBRECHIMP,
            (
                d.tech + 
                CASE
                    WHEN ec.ctr_93_va_count > d.tech THEN 1
                    ELSE 0
                END
                - ec.ctr_93_va_count
            ) AS nbrEchRes,
            '0' AS MNTCRESOUF,
            '0' AS MNTCAPSOUF,
            '0' AS MNTINTSOUF,
            0 AS MNTTAXSOUF,
            0 AS MNTAGIOSSOUF,
            0 AS MNTCRERAT,
            0 AS MNTPRO,
            0 AS NBRJRSIMP,
            d.mon AS MNTTOTUTIL,
            (
                CASE
                    WHEN ec.ctr_8_va_count > 0 THEN '04'
                    WHEN sc.sld_341 > 0 THEN '04'
                    WHEN sc.sld_3441_3451 > 0 THEN '07'
                    WHEN sc.sld_3442_3452 > 0 THEN '08'
                    WHEN sc.sld_3443_3453 > 0 THEN '09'
                    WHEN sc.sld_344_345 > 0 THEN '06'
                    WHEN sc.sld_301_311_321 > 0 THEN '02'
                    ELSE '01'
                END
            ) AS CLADEPREC
        FROM bkdosprt d
        LEFT JOIN Max_Ave ma ON ma.eve = d.eve
        LEFT JOIN Ech_Calculations ec ON ec.eve = d.eve
        LEFT JOIN Sld_Calculations sc ON sc.cli = d.cli
        WHERE 
            trunc(MONTHS_BETWEEN('$DateArr', d.dmep)) >= 1
            AND d.ave = ma.max_ave
            AND NOT EXISTS (
                SELECT 1 
                FROM bkechprt
                WHERE EXTRACT(MONTH FROM dva) = '$DateArrMonth'
                  AND EXTRACT(YEAR FROM cdr_date(dva)) = '$DateArrYear'
                  AND eve = d.eve
                  AND ave = ma.max_ave
            )
            AND d.eta IN ('VA', 'DE')
            AND d.ddec > '$DateArr'
            AND d.tau_int!=0
            -- AND d.per_cap=4
        ORDER BY d.eve DESC
        ";
        // dd($MyRequest);
        $stid = oci_parse($connection, $MyRequest);
        // oci_bind_by_name($stid, ":id", $id);
        oci_execute($stid);

        while ($row = oci_fetch_assoc($stid)) {
            // var_dump($row);  // Traitez vos résultats ici
            $results[] = $row;
            if (!$row) {
                return false;
            }
            $results = array_map(function ($row) {
                return array_change_key_case((array) $row, CASE_UPPER);
            }, $results);
            $myData = response()->json($results);

        }

        if ($myData) {
            return $myData;
        }

        oci_free_statement($stid);
        oci_close($connection);

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
