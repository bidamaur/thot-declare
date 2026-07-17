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
    // Vérification du format de date (mm-yyyy)
    $GetPosition = explode('-', $MyDateArr);
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

    $connection = $this->dbConnection->getConnection();
    $dateArret = Carbon::create((int) $GetPosition[1], (int) $GetPosition[0], 1)->endOfMonth();
    $DateArr = $dateArret->format('d/m/y');
    $DateArrYear = $dateArret->year;
    $DateArrMonth = ($dateArret->month<10?'0'.$dateArret->month:$dateArret->month);
    $DateArrDay = $dateArret->day;
    $DateMonthYear = '/' . $DateArrMonth . '/' . $DateArrYear;

    $notFound = '[{"Erreur": {
        "type": "404",
        "Description": "Aucune donn&eacute;e trouv&eacute;e pour la p&eacute;riode du ' . $DateArr . '"
    }}]';
    $myData = $notFound;
    $query = "SELECT DISTINCT trim(c.cli) as cli,
    -- TRIM(e.dva) as dva,
     --TRIM(e.ctr) as ctr,
         trim(c.tcli) as tcli,
         d.eve,
         d.ave,
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
             (SELECT p.ncp
         FROM C##DBPROD.bkcptprt p
         WHERE p.eve=d.eve
         AND p.nat  ='004'
         AND p.ave  =
           (SELECT MAX(ave) FROM C##DBPROD.bkcptprt WHERE eve=p.eve
           )
         ) ncp_ori,
         '10030' CodAge,
    (CASE
    WHEN e.ctr=3 THEN '02'
    WHEN (SELECT DVA FROM C##DBPROD.bkechprt where res=0 and eve=d.eve and ave=(SELECT MAX(ave) FROM C##DBPROD.bkechprt  WHERE eve=d.eve) and 
( cdr_date(dva) 
between cdr_date('01$DateMonthYear') and add_months(cdr_date('01$DateMonthYear'),1)   ))=d.ddec THEN '02'
    WHEN (SELECT max(ctr) from C##DBPROD.bkechprt where eve=d.eve AND ave=(SELECT MAX(ave) FROM C##DBPROD.bkechprt  WHERE eve=d.eve) AND ( cdr_date(dva) 
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
        '2' \"MODREMBEPARGNE\",                 -- on ne fait pas
        '0' \"TAUXRENUM\",                      -- taux de remboursement de l'epargne
        TO_CHAR(d.dmep,'dd/mm/yyyy') DatMep,-- mise en place
        REPLACE(d.tau_int,',','.') TxInt,
        '' TxComm,-- on ne fait pas
        '2' \"TXBONIFIE\",
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
         (
          CASE
          WHEN (select count(dva) from C##DBPROD.bkechprt where eve=d.eve and ave=(select max(ave) from C##DBPROD.bkechprt where eve=d.eve)) in(2,1) THEN TO_CHAR(d.dmep,'ddmmyyyy')
          ELSE (SELECT TO_CHAR(max(dva),'ddmmyyyy') from C##DBPROD.bkechprt where num=01 and eve=d.eve)
          END
          ) DatDeb,
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
           WHEN d.typ IN(100,099,106,103,104) THEN '01' 
           WHEN d.typ=105 and MONTHS_BETWEEN(d.ddec,d.dpec)<=24 THEN '01'
           WHEN d.typ=105 and MONTHS_BETWEEN(d.ddec,d.dpec)>24 THEN '02'
           ELSE d.typ
         END ) Maturite,
         TO_CHAR(d.dpec,'ddmmyyyy') DatPreEchCap,
         d.tech NbrEch,                         
          '03' MoyRem,
          '01' \"TYECH\",
          ( SELECT MAX(tot_ech) FROM C##DBPROD.bkechprt WHERE EXTRACT(DAY FROM dva)=EXTRACT(DAY FROM d.dpec) AND eve=d.eve and amo_cal!=0
          )MntEch,  
          '03' \"TYAMO\",
         (SELECT SUM(inte)
         FROM C##DBPROD.bkechprt
         WHERE eve=d.eve
         ) TotInt,
          ROUND((SELECT sum(mon_fra) from C##DBPROD.bkdosprt where eve=d.eve) ) \"FRADOS\",
    (
    CASE 
         WHEN ROUND((SELECT (SUM(d.mon_co1)+SUM(d.mon_co2)) from C##DBPROD.bkdosprt where eve=d.eve))=0 THEN  ROUND(
         (SELECT SUM(mnt) FROM C##DBPROD.bkcanprt WHERE eve=d.eve AND ges_teg='O'
         ))
         ELSE
        ROUND((SELECT (SUM(d.mon_co1)+SUM(d.mon_co2)) from C##DBPROD.bkdosprt where eve=d.eve))
    END
     )\"FRANNEXE\",
         '0' MntPrm, 
           '' MntTax,                 
         TO_CHAR(d.dmep,'ddmmyyyy') DatEve,
     d.eve RefInt,
     d.cli IdInt 
     FROM C##DBPROD.bkdosprt d,C##DBPROD.bkechprt e ,
         C##DBPROD.bkcli c
       WHERE 
       e.eve=d.eve
       --and d.eve='002221'
       and c.cli    =d.cli
     
       AND d.eta      in ('VA','DE')
     AND (EXTRACT(YEAR FROM d.ddec)>2022)
     
    AND d.ave=(SELECT MAX(bb.ave) FROM C##DBPROD.bkdosprt bb WHERE bb.eve=d.eve)
    and e.ctr not in(3)
    --  and (cdr_date(e.dva) between cdr_date('01/07/2023') and cdr_date('31/07/2023'))
    --AND (cdr_date(d.dmep) between cdr_date('01" . $DateMonthYear . "') and cdr_date('$DateArr'))
    AND (EXTRACT(MONTH FROM d.dmep)='$DateArrMonth' and EXTRACT(YEAR FROM CDR_DATE(d.dmep))='$DateArrYear' )
    AND cdr_date(d.dmep)<cdr_date('01-'||TO_CHAR(ADD_MONTHS(CDR_DATE('01$DateMonthYear'), 1), 'MM-YYYY'))
    --AND e.ave=(SELECT max(ave) from C##DBPROD.bkechprt where eve=d.eve)
    AND d.tau_int!=0 ";
    
    $stid = oci_parse($connection, $query);
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
  }

  /**
   * Contrôle des engagements : liste des dossiers de prêt (bkdosprt) liés aux clients (bkcli).
   */
  public function ctrEngagements()
  {
    $connection = $this->dbConnection->getConnection();

    $query = "SELECT trim(c.cli) as cli,
        d.eve,
        d.ave,
        d.mon as MNTENG,
        d.dmep  ,
        d.dpec as DATDEB,
        d.ddec as DATFIN,
        d.tech as duree,
        d.ctr
      FROM C##DBPROD.bkcli c, C##DBPROD.bkdosprt d
      WHERE c.cli = d.cli";

    $stid = oci_parse($connection, $query);
    oci_execute($stid);

    $results = [];

    while ($row = oci_fetch_assoc($stid)) {
      $results[] = array_change_key_case($row, CASE_UPPER);
    }

    $results = mb_convert_encoding($results, 'UTF-8', 'ISO-8859-1');

    oci_free_statement($stid);
    oci_close($connection);

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
