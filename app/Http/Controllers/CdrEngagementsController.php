<?php

namespace App\Http\Controllers;
use App\Services\DatabaseConnection;
use Illuminate\Support\Facades\DB;
use App\Models\cdrEngagements;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CdrEngagementsController extends Controller
{
  protected $dbConnection;
    public function __construct(DatabaseConnection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }
    /**
     * Display a listing of the resource.
     */
    public function GetEngagements($MyDateArr)
    {
// Vérification du format de date
$GetPosition = explode('-', $MyDateArr);
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

      $connection = $this->dbConnection->getConnection();
      $dateArret = Carbon::parse($MyDateArr);
      $DateArr = $dateArret->format('d/m/y');
      $DateArrYear = $dateArret->year;
      $DateArrMonth = $dateArret->month;
      $DateArrDay = $dateArret->day;
      
      $notFound='[{"Erreur": {
        "type": "404",
        "Description": "Aucune donn&eacute;e trouv&eacute;e pour la p&eacute;riode du '.$DateArr.'"
    }}]';
$myData= $notFound;
$query="SELECT DISTINCT trim(c.cli) as cli,
      TRIM(e.dva) as dva,
      TRIM(e.ctr) as ctr,
          trim(c.tcli) as tcli,
          d.eve,
          d.ave,
          (SELECT cdr_parce_ncp(p.ncp)
          FROM dbprod.bkcptprt p
          WHERE p.eve=d.eve
          AND p.nat  ='004'
          AND p.ave  =
            (SELECT MAX(ave) FROM dbprod.bkcptprt WHERE eve=p.eve
            )
          ) RefContCmpt,
              (SELECT p.ncp
          FROM dbprod.bkcptprt p
          WHERE p.eve=d.eve
          AND p.nat  ='004'
          AND p.ave  =
            (SELECT MAX(ave) FROM dbprod.bkcptprt WHERE eve=p.eve
            )
          ) ncp_ori,
          '10030' CodAge,
  (CASE
 WHEN e.ctr=3 THEN '02'
 WHEN (SELECT MAX(num) FROM bkechprt where eve=e.eve AND ave=(SELECT MAX(ave) FROM dbprod.bkechprt  WHERE eve=e.eve) )=e.num THEN '02'
 WHEN (SELECT max(ctr) from bkechprt where eve=e.eve AND ave=(SELECT MAX(ave) FROM dbprod.bkechprt  WHERE eve=e.eve) AND ( cdr_date(dva) 
 between cdr_date('$DateArr') and add_months(cdr_date('$DateArr'),1)   ))=3 THEN '02'
 ELSE '00'
  END
  ) Statut,
          '' NatConso,--non
          '' TypConso,--non
(
    CASE
      WHEN d.ctr=9 and d.ddec>=cdr_date('$DateArr') 
      THEN ''
      WHEN d.ctr=9 and d.ddec<cdr_date('$DateArr') 
      THEN '01'
      
      WHEN d.ctr=5 and d.ddec>=cdr_date('$DateArr') 
      THEN ''
      WHEN d.ctr=5 and d.ddec<cdr_date('$DateArr') 
      THEN '02'
      
      WHEN d.ctr not IN(9,5) 
      THEN ''
      
    END ) Motif,
          '01' TypEng, --type de credit avec échéancier
          (
          CASE
            WHEN (d.typ IN('200','201')
            AND c.tcli  <>1)
            THEN '01'
            WHEN d.typ IN ('200','201')
            AND c.tcli  ='1'
            THEN '09'
            WHEN (d.typ IN('200','201')
            AND c.tcli  <>1)
            THEN '03'
            WHEN d.typ IN('099','106','107')
            THEN '02'
            WHEN (d.typ IN('105','104','103','105')
            AND c.tcli   =1)
            THEN '05'
                  WHEN (d.typ IN('105','104','103','105')
            AND c.tcli   !=1)
            THEN '02'
            WHEN (c.tcli=1
            AND d.typ   ='100')
            THEN '05'
            WHEN (c.tcli !=1
            AND d.typ     ='100')
            THEN '02'
            ELSE '02'
          END ) NatEng, -----------------------------
          'XAF' CodDev,                            --devise
          d.mon MntEng ,
          '0' MntCrCedee,                      --l'import export ne nous concerne
          '0' MntEpargne,                     --on ne fait pas
          '2' ModRembEpargne,                 -- on ne fait pas
          '0' TauxRenum,                      -- taux de remboursement de l'epargne
          TO_CHAR(d.dmep,'dd/mm/yyyy') DatMep,-- mise en place
          REPLACE(d.tau_int,',','.') TxInt,
          '' TxComm,-- on ne fait pas
          '2' TxBonifie,
         ( 
          CASE
          WHEN d.tau_int<8 or  (d.tau_int >d.teg) THEN REPLACE(CEIL(d.teg),',','.') 
          ELSE REPLACE(d.teg,',','.')
          END 
          ) as TxEffGlob,
          '00' TypTxInt,  --type de taux: fixe
          '' IndRef,
          '' Sprd,
          -- TO_CHAR(d.dpec,'ddmmyyyy') DatDeb, -- date de premiere echeance du crédit à revoir avec les diferes
          (SELECT TO_CHAR(max(dva),'ddmmyyyy') from bkechprt where num=01 and eve=d.eve) DatDeb,
          TO_CHAR(d.ddec,'ddmmyyyy') DatFin, --derniere echeance
          (
          CASE
            WHEN d.per_cap='1'
            THEN '03'
             WHEN d.per_cap='3'
            THEN '04'
            WHEN d.per_cap='6'
            THEN '06'
            WHEN d.per_cap='12'
            THEN '07'
            WHEN d.per_cap='4'
            THEN '05'
            ELSE '00'     
          END ) Periodicite, 
          '02' UnitDur,      
          d.tech Duree,       
          (
          CASE
            WHEN d.typ IN(200,201,107)
            THEN '02' 
            WHEN d.typ IN(100,099,106,103,104)
            THEN '01' 
            ELSE d.typ
          END ) Maturite,
          TO_CHAR(d.dpec,'ddmmyyyy') DatPreEchCap,
          d.tech NbrEch,                         
          '03' MoyRem,
          '01' TypEch,
          ( SELECT MAX(tot_ech) FROM dbprod.bkechprt WHERE EXTRACT(DAY FROM dva)=EXTRACT(DAY FROM d.dpec) AND eve=d.eve and amo_cal!=0
          )MntEch,  
          '03' TypAmo,
          (SELECT SUM(inte)
          FROM dbprod.bkechprt
          WHERE eve=d.eve
          ) TotInt,
          ROUND((SELECT sum(mon_fra) from bkdosprt where eve=d.eve) ) fraDos,
    (
    CASE 
          WHEN ROUND((SELECT (SUM(d.mon_co1)+SUM(d.mon_co2)) from bkdosprt where eve=d.eve))=0 THEN  ROUND(
          (SELECT SUM(mnt) FROM bkcanprt WHERE eve=d.eve AND ges_teg='O'
          ))
          ELSE
         ROUND((SELECT (SUM(d.mon_co1)+SUM(d.mon_co2)) from bkdosprt where eve=d.eve))
    END
    )fraAnnexe,
          '0' MntPrm, 
            '' MntTax,                 
          TO_CHAR(d.dmep,'ddmmyyyy') DatEve,
      d.eve RefInt,
      d.cli IdInt 
      FROM dbprod.bkdosprt d,dbprod.bkechprt e ,
          dbprod.bkcli c
        WHERE 
        e.eve=d.eve
        and c.cli    =d.cli
      
        AND d.eta      in ('VA','DE')
      AND (EXTRACT(YEAR FROM d.ddec)>2022)
      
    AND d.ave=(SELECT MAX(bb.ave) FROM dbprod.bkdosprt bb WHERE bb.eve=d.eve)
    --and e.ctr not in(3)
    and (cdr_date(d.dmep) between cdr_date('01/".$DateArrMonth."/".$DateArrYear."') and cdr_date('$DateArr'))
    AND cdr_date('$DateArr')>cdr_date(e.dva)   -- on recupere uniquement les ech. avant la DateArr
    AND e.ave=(SELECT max(ave) from bkechprt where eve=d.eve)
    AND d.tau_int!=0";
     
     $stid = oci_parse($connection, $query);
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
     
     if($myData){ 

      return $myData;}
     
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
    public function show(cdrEngagements $cdrEngagements)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, cdrEngagements $cdrEngagements)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(cdrEngagements $cdrEngagements)
    {
        //
    }
}
