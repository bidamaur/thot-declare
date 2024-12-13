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
if (count($GetPosition) !== 3 || 
    strlen($GetPosition[0]) !== 2 || 
    $GetPosition[0]>31            ||
    $GetPosition[1]>12            ||
    strlen($GetPosition[1]) !== 2 || 
    strlen($GetPosition[2]) !== 4) {
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
       
$notFound='[{"Erreur": {
    "type": "Date",
    "Description": "Format date erroné, format attendu 01-05-1995"
}}]';
$myData= $notFound;
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
(SELECT COUNT(dva)
FROM bkechprt
WHERE 
ave=(select max(ave) from bkechprt where eve=e.eve)
AND ctr                   IN (9,3)
AND eta                      ='VA'
AND eve                      =e.eve
AND CDR_DATE(dva) <= CDR_DATE('$DateArr')
)-1 nbrEchPay,
(SELECT COUNT(dva)
FROM bkechprt
WHERE 
ave=(select max(ave) from bkechprt where eve=e.eve)
AND ctr                    ='8'
AND eta                      ='VA'
AND eve                      =e.eve
AND CDR_DATE(dva) <= CDR_DATE('$DateArr')
) nbrEchImp,-- a revoir ici c'est le nombre d'echéances impayes


((d.tech+1)-(SELECT COUNT(dva)
FROM bkechprt
WHERE 
ave=(select max(ave) from bkechprt where eve=e.eve)
AND    ctr                   IN (9,3)
AND eta                      ='VA'
AND eve                      =e.eve
AND CDR_DATE(dva) <= CDR_DATE('$DateArr')
)) nbrEchRes,

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
ELSE CDR_DATE((SELECT MIN(DVA) from bkechprt where eta='VA' AND ctr=8 and eve=e.eve ))-CDR_DATE('$DateArr')
END
)nbrJrsImp,

(
CASE
WHEN (select sum(mon) from bksld where
(cha like '301%' or cha like '311%' or cha like '321%' and cli=d.cli) and dco<cdr_date('$DateArr'))<0 THEN '01'

WHEN (select sum(mon) from bksld where
(cha like '341%' and cli=d.cli) and dco<cdr_date('$DateArr'))<0 THEN '04'

WHEN (select sum(mon) from bksld where
((cha like '3441%'  or cha like '3451%') and cli=d.cli) and dco<cdr_date('$DateArr'))<0 THEN '07'

WHEN (select sum(mon) from bksld where
((cha like '3442%'  or cha like '3452%') and cli=d.cli) and dco<cdr_date('$DateArr'))<0 THEN '08'

WHEN (select sum(mon) from bksld where
((cha like '3443%'  or cha like '3453%') and cli=d.cli) and dco<cdr_date('$DateArr'))<0 THEN '09'
WHEN (select sum(mon) from bksld where
((cha like '344%'  or cha like '345%') and cli=d.cli) and dco<cdr_date('$DateArr'))<0 THEN '06'

END)
ClaDeprec
FROM bkdosprt d,
bkechprt e,
bkcom co
WHERE e.eve=d.eve
AND d.eta  ='VA'
--and d.ctr not in(9,2)
AND d.cli=co.cli
AND (e.dva BETWEEN '01/$DateArrMonth/$DateArrYear' AND '$DateArr')
AND d.ave=
(SELECT MAX(ave) FROM bkdosprt WHERE eve=d.eve
)
AND e.ave=
(SELECT MAX(ave) FROM bkechprt WHERE eve=e.eve
)";
    // dd($MyRequest);
$stid = oci_parse($connection, $MyRequest);
// oci_bind_by_name($stid, ":id", $id);
oci_execute($stid);

while ($row = oci_fetch_assoc($stid)) {
    // var_dump($row);  // Traitez vos résultats ici
    $results[]=$row;
    if(!$row)
    {return false;}
    $results = array_map(function($row) {
        return array_change_key_case((array)$row, CASE_UPPER);
    }, $results);
    $myData= response()->json($results);
   
}

if($myData){ return $myData;}

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
