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
e.dva DatEch,
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
    WHEN e.res=0 and e.num=0 THEN d.mon
    ELSE e.res
    END
    )
    END
    ) MntCrd, --Encours
'0' estSensible,
--d.mdb MntTotUtil,
d.mon MntTotUtil,
(
CASE 
WHEN (e.res=0 and e.ctr!=8 and e.num>1) THEN d.tech -- gestion des paiements anticipee
ELSE
    (
    SELECT COUNT(dva)
    FROM bkechprt
    WHERE 
    ave=(select max(ave) from bkechprt where eve=e.eve)
    AND ctr                   IN (9,3)
    AND eta                      ='VA'
    AND eve                      =e.eve
    AND CDR_DATE(dva) <= CDR_DATE('$DateArr')
    )-(
       CASE
            WHEN d.tech<(SELECT COUNT(dva) from bkechprt where eve=e.eve and ave=(select max(ave) from bkechprt where eve=e.eve)) THEN 1
            ELSE 0
        END )
END
)
    nbrEchPay,

(SELECT COUNT(dva)
FROM bkechprt
WHERE 
ave=(select max(ave) from bkechprt where eve=e.eve)
AND ctr                    ='8'
AND eta                      ='VA'
AND eve                      =e.eve
AND CDR_DATE(dva) <= CDR_DATE('$DateArr')
) nbrEchImp,-- a revoir ici c'est le nombre d'echéances impayes
(
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
    AND    ctr                   IN (9,3)
    AND eta                      ='VA'
    AND eve                      =e.eve
    AND CDR_DATE(dva) <= CDR_DATE('$DateArr')
    )-(
        (SELECT COUNT(dva)
    FROM bkechprt
    WHERE 
    ave=(select max(ave) from bkechprt where eve=e.eve)
    AND ctr                    ='8'
    AND eta                      ='VA'
    AND eve                      =e.eve
    AND CDR_DATE(dva) <= CDR_DATE('$DateArr')
    )  
    )
) 
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
        $DateArrMonth = $dateArret->month;
        $DateArrDay = $dateArret->day;
        $DateMonthYear = '/' . $DateArrMonth . '/' . $DateArrYear;
        $notFound = '[{"Erreur": {
    "type": "Date",
    "Description": "Format date erroné, format attendu 01-05-1995"
    }}]';
        $myData = $notFound;
        $MyRequest = "select distinct d.eve,d.cli,d.ave,
    (SELECT cdr_parce_ncp(p.ncp)
    FROM bkcptprt p
    WHERE p.eve=d.eve
    AND p.nat  ='004'
    AND p.ave  =
      (SELECT MAX(ave) FROM bkcptprt WHERE eve=p.eve
      )
    ) RefContCmpt,
    NVL(
    (
        CASE 
    WHEN ( select max(res) from bkechprt where (dva between (select dmep from bkdosprt where eve=d.eve and ave=0) and '30/08/23') and eve=d.eve  )=0 THEN d.mon
    ELSE ( select max(res) from bkechprt where (dva between (select dmep from bkdosprt where eve=d.eve and ave=0) and '30/08/23') and eve=d.eve  )
        END
        ),d.mon)  MNTCRD ,
        d.dmep,
        (15||'$DateArrMonth'||'$DateArrYear') DATECH,
        (15||'$DateArrMonth'||'$DateArrYear') DATPAI,
        0 MNTPAY,
        0 MNTAGI,
        0 ESTSENSIBLE,
    (
    select count(dva) from bkechprt where eve=d.eve and ave=(select max(ave) from bkechprt where eve=d.eve) and dva<'$DateArr'
    and ctr in (9,3) and eta='VA'
    )-(CASE
                WHEN (SELECT COUNT(e.dva) from bkechprt e where eve=e.eve and e.ave=(select MAX(ave) from bkdosprt where eve=e.eve))>d.tech and (
                select count(dva) from bkechprt where eve=d.eve and ave=(select max(ave) from bkechprt where eve=d.eve) and dva<'$DateArr'
    and ctr in (9,3) and eta='VA')>=1 THEN 1
                ELSE 0
                END) nbrEchPay,
    (
    select count(dva) from bkechprt where eve=d.eve and ave=(select max(ave) from bkechprt where eve=d.eve) and dva<'$DateArr'
    and ctr in (8) and eta='VA'
    )NBRECHIMP,
    (d.tech+(
                CASE
                WHEN (SELECT COUNT(e.dva) from bkechprt e where eve=e.eve and e.ave=(select MAX(ave) from bkdosprt where eve=e.eve))>d.tech THEN 1
                ELSE 0
                END )

                -(
    select count(dva) from bkechprt where eve=d.eve and ave=(select max(ave) from bkechprt where eve=d.eve) and dva<'$DateArr'
    and ctr  in(8,9,3) and eta='VA'
    ))
    nbrEchRes,
    '0' MNTCRESOUF,
    '0' MNTCAPSOUF,
    '0' MNTINTSOUF,
    0 MNTTAXSOUF,
    0 MNTAGIOSSOUF,
    0 MNTCRERAT,
    0 MNTPRO,
    0 NBRJRSIMP,
    
        d.mon MNTTOTUTIL,
        (
        CASE
    WHEN (SELECT count(dva) from bkechprt where ctr=8 and eve=d.eve and cdr_date(dva)<cdr_date('$DateArr'))>0 THEN '04'
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
    END
    ) CLADEPREC
        
        from bkdosprt d,

    
    bkechprt e 
    WHERE 

    --d.eve in ('001492','002094','001907','002101') and
    --not EXISTS (SELECT dva from bkechprt where MONTHS_BETWEEN('30/08/2023',d.dmep )>=1)
    trunc(MONTHS_BETWEEN('30/08/2023',d.dmep ))>=1
    and d.ave=(select MAX(ave) from bkdosprt where eve=d.eve
    )
    
    AND NOT EXISTS( SELECT DVA FROM bkechprt where (
    EXTRACT(MONTH FROM dva)='08' 
    and EXTRACT(YEAR FROM cdr_date(dva))='2023'
    ) and eve=d.eve
    and ave=(SELECT MAX(AVE) FROM BKECHPRT WHERE eve=d.eve) )
    AND cdr_date(e.dva)<cdr_date('30/08/2023')
    AND d.eta IN ('VA','DE')
    and d.ddec>'$DateArr'
    order by 1 DESC";
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
