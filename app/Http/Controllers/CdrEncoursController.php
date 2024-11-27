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
$MyRequest = "WITH MaxData AS (
    SELECT 
        eve,
        MAX(ave) AS max_ave,
        MAX(CDR_DATE(dco)) AS max_dco,
        MAX(mon) AS max_mon
    FROM 
        dbprod.bkauxprt
    WHERE 
        CDR_DATE(dco) < CDR_DATE('$DateArr')
    GROUP BY eve
),
SumMon AS (
    SELECT 
        cli,
        CASE WHEN SUM(mon) < 0 THEN '01' ELSE NULL END AS cla_01,
        CASE WHEN SUM(CASE WHEN cha LIKE '341%' THEN mon ELSE 0 END) < 0 THEN '04' ELSE NULL END AS cla_04,
        CASE WHEN SUM(CASE WHEN cha LIKE '3441%' OR cha LIKE '3451%' THEN mon ELSE 0 END) < 0 THEN '07' ELSE NULL END AS cla_07,
        CASE WHEN SUM(CASE WHEN cha LIKE '3442%' OR cha LIKE '3452%' THEN mon ELSE 0 END) < 0 THEN '08' ELSE NULL END AS cla_08,
        CASE WHEN SUM(CASE WHEN cha LIKE '3443%' OR cha LIKE '3453%' THEN mon ELSE 0 END) < 0 THEN '09' ELSE NULL END AS cla_09,
        CASE WHEN SUM(CASE WHEN cha LIKE '344%' OR cha LIKE '345%' THEN mon ELSE 0 END) < 0 THEN '06' ELSE NULL END AS cla_06
    FROM 
        dbprod.bksld
    WHERE 
        dco < CDR_DATE('$DateArr')
    GROUP BY cli
),
NbrEch AS (
    SELECT 
        eve, 
        COUNT(DISTINCT CASE WHEN ctr IN (9, 3) AND eta = 'VA' AND CDR_DATE(dva) < CDR_DATE('$DateArr') THEN dva END) AS nbrEchPay,
        COUNT(DISTINCT CASE WHEN ctr = '8' AND eta = 'VA' AND CDR_DATE(dva) < CDR_DATE('$DateArr') THEN dva END) AS nbrEchImp
    FROM 
        dbprod.bkechprt
    GROUP BY eve
),
NbrJrs AS (
    SELECT 
        eve,
        CASE 
            WHEN e.amo_imp = 0 THEN 0
            ELSE CDR_DATE(MIN(DVA)) - CDR_DATE('$DateArr')
        END AS nbrJrsImp
    FROM 
        dbprod.bkechprt e
    WHERE 
        eta = 'VA' AND ctr = 8
    GROUP BY eve, e.amo_imp
)
SELECT DISTINCT 
    trim(d.eve) as eve,
    trim(d.cli) as cli,
    trim(e.dva) as dva ,
    cdr_parce_ncp(p.ncp) AS RefContCmpt,
    md.max_dco AS datPai,
    md.max_dco AS DatEch,
    md.max_mon AS MntPay,
    '0' AS MntAgi,
    e.res AS MntCrd,
    '0' AS estSensible,
    d.mon AS MntTotUtil,
    COALESCE(ne.nbrEchPay, 0) AS nbrEchPay,
    COALESCE(ne.nbrEchImp, 0) AS nbrEchImp,
    (d.tech - COALESCE(ne.nbrEchPay, 0)) AS nbrEchRes,
    ROUND(e.amo_imp) AS MntCreSouf,
    e.amo_imp AS MntCapSouf,
    CASE WHEN e.amo_imp = 0 THEN 0 ELSE e.inte END AS MntIntSouf,
    CASE WHEN e.amo_imp = 0 THEN 0 ELSE e.tin END AS MntTaxSouf,
    '0' AS MntAgiosSouf,
    e.inte AS MntCreRat,
    '' AS MntPro,
    nj.nbrJrsImp,
    NVL(SumMon.cla_01, '01') AS ClaDeprec
FROM 
    dbprod.bkdosprt d
JOIN 
    dbprod.bkechprt e ON e.eve = d.eve
JOIN 
    dbprod.bkcom co ON d.cli = co.cli
LEFT JOIN 
    MaxData md ON md.eve = d.eve
LEFT JOIN 
    SumMon ON SumMon.cli = d.cli
LEFT JOIN 
    dbprod.bkcptprt p ON p.eve = d.eve AND p.nat = '004'
LEFT JOIN 
    NbrEch ne ON ne.eve = d.eve
LEFT JOIN 
    NbrJrs nj ON nj.eve = d.eve
WHERE 
    d.eta = 'VA'
    AND d.ave = md.max_ave
    AND e.ave = md.max_ave
    AND e.dva BETWEEN '01/$DateArrMonth/$DateArrYear' AND CDR_DATE('$DateArr')
    AND d.ave = (SELECT MAX(ave) FROM dbprod.bkdosprt WHERE eve = d.eve)
ORDER BY 
    1";
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
