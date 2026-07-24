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
     * Comparaison des engagements de contrôle avec les engagements détaillés pour détecter les anomalies.
     * Clé de jointure : (CLI, EVE, AVE).
     * Chaque différence est signalée comme erreur, SAUF les différences sur Statut et Motif.
     */
    public function compareEngagements($MyDateArr)
    {
        $connection = $this->dbConnection->getConnection();

        $GetPosition = explode('-', $MyDateArr);
        if (
            count($GetPosition) !== 2 ||
            strlen($GetPosition[0]) !== 2 ||
            (int) $GetPosition[0] < 1 ||
            (int) $GetPosition[0] > 12 ||
            strlen($GetPosition[1]) !== 4
        ) {
            return response()->json([
                ['Erreur' => [
                    'type' => 'Date',
                    'Description' => 'Format date erroné, format attendu MM-YYYY',
                ]],
            ], 400);
        }

        $dateArret = Carbon::create((int) $GetPosition[1], (int) $GetPosition[0], 1)->endOfMonth();
        $DateArr = $dateArret->format('d/m/y');
        $DateArrYear = $dateArret->year;
        $DateArrMonth = ($dateArret->month < 10 ? '0'.$dateArret->month : $dateArret->month);
        $DateMonthYear = '/'.$DateArrMonth.'/'.$DateArrYear;

        // 1. Récupérer les engagements de contrôle (ctrEngagements)
        $ctrQuery = "SELECT trim(c.cli) as cli,
            d.eve,
            d.ave,
            d.mon as MNTENG,
            d.dmep,
            d.dpec as DATDEB,
            d.ddec as DATFIN,
            d.tech as duree,
            d.ctr
          FROM C##DBPROD.bkcli c, C##DBPROD.bkdosprt d
          WHERE c.cli = d.cli";

        $stid = oci_parse($connection, $ctrQuery);
        oci_execute($stid);
        $ctrResults = [];
        while ($row = oci_fetch_assoc($stid)) {
            $ctrResults[] = array_change_key_case($row, CASE_UPPER);
        }
        oci_free_statement($stid);

        // 2. Récupérer les engagements détaillés (avec Statut et Motif)
        $engQuery = "SELECT DISTINCT trim(c.cli) as cli,
            d.eve,
            d.ave,
            (SELECT cdr_parce_ncp(p.ncp)
            ||(CASE
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
            '' NatConso,
            '' TypConso,
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
            d.mon MNTENG,
            d.dmep DatMep,
            d.tech Duree,
            d.ctr
          FROM C##DBPROD.bkdosprt d, C##DBPROD.bkechprt e,
              C##DBPROD.bkcli c
            WHERE
            e.eve=d.eve
            and c.cli    =d.cli
            AND d.eta      in ('VA','DE')
          AND (EXTRACT(YEAR FROM d.ddec)>2022)
         AND d.ave=(SELECT MAX(bb.ave) FROM C##DBPROD.bkdosprt bb WHERE bb.eve=d.eve)
         and e.ctr not in(3)
         AND (EXTRACT(MONTH FROM d.dmep)='$DateArrMonth' and EXTRACT(YEAR FROM CDR_DATE(d.dmep))='$DateArrYear' )
         AND cdr_date(d.dmep)<cdr_date('01-'||TO_CHAR(ADD_MONTHS(CDR_DATE('01$DateMonthYear'), 1), 'MM-YYYY'))
         AND d.tau_int!=0";

        $stid2 = oci_parse($connection, $engQuery);
        oci_execute($stid2);
        $engResults = [];
        while ($row = oci_fetch_assoc($stid2)) {
            $engResults[] = array_change_key_case($row, CASE_UPPER);
        }
        oci_free_statement($stid2);
        oci_close($connection);

        // 3. Indexer les engagements de contrôle par (CLI|EVE|AVE)
        $ctrIndex = [];
        foreach ($ctrResults as $ctr) {
            $key = strtoupper(trim($ctr['cli'] ?? '')).'|'.strtoupper(trim($ctr['eve'] ?? '')).'|'.strtoupper(trim($ctr['ave'] ?? ''));
            $ctrIndex[$key] = $ctr;
        }

        // 4. Comparer chaque engagement détaillé avec son correspondant de contrôle
        $anomalies = [];
        $total = count($engResults);
        $processed = 0;

        // Champs à exclure de la comparaison (Statut et Motif)
        $excludedFields = ['STATUT', 'MOTIF'];

        foreach ($engResults as $idx => $eng) {
            $processed = $idx + 1;
            $progress = (int)round(($processed / $total) * 100);

            $cli = trim($eng['CLI'] ?? '');
            $eve = trim($eng['EVE'] ?? '');
            $ave = trim($eng['AVE'] ?? '');
            $key = strtoupper($cli).'|'.strtoupper($eve).'|'.strtoupper($ave);

            if (!isset($ctrIndex[$key])) {
                $anomalies[] = [
                    'type' => 'avertissement',
                    'cli' => $cli,
                    'eve' => $eve,
                    'ave' => $ave,
                    'field' => 'CLI/EVE/AVE',
                    'code' => 'CMP_MISSING',
                    'message' => "Aucun engagement de contrôle correspondant trouvé pour la clé (CLI=$cli, EVE=$eve, AVE=$ave).",
                    'value' => $key,
                    'progress' => $progress,
                ];
                continue;
            }

            $ctr = $ctrIndex[$key];

            // Champs à exclure de la comparaison (Statut et Motif + clés de jointure)
        $excludedFields = ['STATUT', 'MOTIF', 'CLI', 'EVE', 'AVE'];

        // Collecter toutes les clés uniques des deux jeux de données
        $allFields = array_unique(
            array_merge(
                array_keys($eng),
                array_keys($ctr)
            )
        );

        foreach ($allFields as $field) {
            if (in_array($field, $excludedFields)) {
                continue;
            }

            $engVal = isset($eng[$field]) ? trim((string) $eng[$field]) : '';
            $ctrVal = isset($ctr[$field]) ? trim((string) $ctr[$field]) : '';

            // Normaliser les valeurs pour comparaison
            $engValNorm = str_replace([' ', ',', '.'], '', $engVal);
            $ctrValNorm = str_replace([' ', ',', '.'], '', $ctrVal);

            if ($engValNorm !== $ctrValNorm && $engValNorm !== '' && $ctrValNorm !== '') {
                $label = $field;
                $anomalies[] = [
                    'type' => 'erreur',
                    'cli' => $cli,
                    'eve' => $eve,
                    'ave' => $ave,
                    'field' => $field,
                    'code' => 'CMP_DIFF',
                    'message' => "Différence sur le champ $label : engagement=$engVal, contrôle=$ctrVal.",
                    'value' => "$engVal vs $ctrVal",
                    'progress' => $progress,
                ];
            }
        }
        }

        return response()->json([
            'total' => $total,
            'anomalies' => $anomalies,
            'progress' => 100,
        ]);
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

public function GetEngagementsEchus($MyDateArr, $MyDateDeb = '12/2023')
{
    // 1. Validation de la date d'arrêt ($MyDateArr)
    $GetPositionArr = explode('-', $MyDateArr);
    if (
        count($GetPositionArr) !== 2 ||
        strlen($GetPositionArr[0]) !== 2 ||
        (int) $GetPositionArr[0] < 1 ||
        (int) $GetPositionArr[0] > 12 ||
        strlen($GetPositionArr[1]) !== 4
    ) {
        return response()->json([
            [
                'Erreur' => [
                    'type' => 'Date',
                    'Description' => "Format date d'arrêt erroné, format attendu MM-AAAA",
                ],
            ],
        ]);
    }

    // 2. Validation de la date de début ($MyDateDeb)
    $GetPositionDeb = explode('-', $MyDateDeb);
    if (
        count($GetPositionDeb) !== 2 ||
        strlen($GetPositionDeb[0]) !== 2 ||
        (int) $GetPositionDeb[0] < 1 ||
        (int) $GetPositionDeb[0] > 12 ||
        strlen($GetPositionDeb[1]) !== 4
    ) {
        return response()->json([
            [
                'Erreur' => [
                    'type' => 'Date',
                    'Description' => "Format date de début erroné, format attendu MM-AAAA",
                ],
            ],
        ]);
    }

    $connection = $this->dbConnection->getConnection();

    // Construction de la Date de Fin (Arret) -> Dernier jour du mois
    $dateArret = Carbon::create((int) $GetPositionArr[1], (int) $GetPositionArr[0], 1)->endOfMonth();
    $DateArr = $dateArret->format('d/m/Y');
    $DateArrYear = $dateArret->year;
    $DateArrMonth = ($dateArret->month < 10 ? '0' . $dateArret->month : $dateArret->month);
    $DateMonthYear = '/' . $DateArrMonth . '/' . $DateArrYear;

    // Construction de la Date de Début -> Premier jour du mois
    $dateDebut = Carbon::create((int) $GetPositionDeb[1], (int) $GetPositionDeb[0], 1)->startOfMonth();
    $DateDeb = $dateDebut->format('d/m/Y');
    $clientsDtx="SELECT distinct cli
                    FROM C##DBPROD.bksld 
                    WHERE
                       TO_CHAR(cdr_date(dco), 'MM/YYYY') = TO_CHAR(cdr_date('$DateArr'), 'MM/YYYY')
                      AND (ncp LIKE '344%' OR ncp LIKE '345%') and mon!=0";
    $notFound = '[{"Erreur": {
        "type": "404",
        "Description": "Aucune donnée trouvée pour la période du ' . $DateDeb . ' au ' . $DateArr . '"
    }}]';
$mont_dtx="ABS(NVL((
                    SELECT SUM(mon) 
                    FROM C##DBPROD.bksld 
                    WHERE cli = d.cli 
                      AND TO_CHAR(cdr_date(dco), 'MM/YYYY') = TO_CHAR(cdr_date('$DateArr'), 'MM/YYYY')
                      AND (ncp LIKE '344%' OR ncp LIKE '345%')
                      
                ), 0))";
    $DossierReglement_anticipe="SELECT eve FROM C##DBPROD.bkechprt WHERE (cdr_date(dva) BETWEEN cdr_date('$DateDeb') AND cdr_date('$DateArr')) AND ctr=3";  
    $query = "SELECT DISTINCT 
            TRIM(c.cli) AS cli,
            TRIM(c.tcli) AS tcli,
            d.eve,
            d.ave,
            (SELECT cdr_parce_ncp(p.ncp) || 
                    (CASE 
                        WHEN cdr_date(d.dmep) > cdr_date('30/11/2023') 
                        THEN (SELECT clc FROM C##DBPROD.bkcom WHERE ncp = p.ncp)
                     END)
             FROM C##DBPROD.bkcptprt p
             WHERE p.eve = d.eve
               AND p.nat = '004'
               AND p.ave = (SELECT MAX(ave) FROM C##DBPROD.bkcptprt WHERE eve = p.eve)
            ) RefContCmpt,
            (SELECT p.ncp
             FROM C##DBPROD.bkcptprt p
             WHERE p.eve = d.eve
               AND p.nat = '004'
               AND p.ave = (SELECT MAX(ave) FROM C##DBPROD.bkcptprt WHERE eve = p.eve)
            ) ncp_ori,
            '10030' CodAge,
            
            /* GESTION DES STATUTS */
            (CASE
                /* BLOC 1 : Remboursement anticipe total */
                WHEN EXISTS (
                    SELECT 1 FROM C##DBPROD.bkechprt 
                    WHERE eve = d.eve 
                      AND ctr = 3 
                      AND eta = 'VA' 
                      AND cdr_date(dva) <= cdr_date('$DateArr')
                ) THEN '02'
                
                /* BLOC 2 : Fin a terme */
                WHEN d.ddec <= cdr_date('$DateArr') THEN '02'
                
                /* BLOC 3 : Passage en perte (Solde comptes 344/345) */
                WHEN ABS(NVL((
                    SELECT SUM(mon) 
                    FROM C##DBPROD.bksld 
                    WHERE cli = d.cli 
                      AND TO_CHAR(cdr_date(dco), 'MM/YYYY') = TO_CHAR(cdr_date('$DateArr'), 'MM/YYYY')
                      AND (ncp LIKE '344%' OR ncp LIKE '345%')
                ), 0)) > 0 THEN '02'

                /* FALLBACKS PREEXISTANTS */
                WHEN e.ctr = 3 THEN '02'
                WHEN (SELECT DVA FROM C##DBPROD.bkechprt WHERE res = 0 AND eve = d.eve AND ave = (SELECT MAX(ave) FROM C##DBPROD.bkechprt WHERE eve = d.eve) AND (cdr_date(dva) BETWEEN cdr_date('01$DateMonthYear') AND add_months(cdr_date('01$DateMonthYear'), 1))) = d.ddec THEN '02'
                WHEN (SELECT MAX(ctr) FROM C##DBPROD.bkechprt WHERE eve = d.eve AND ave = (SELECT MAX(ave) FROM C##DBPROD.bkechprt WHERE eve = d.eve) AND (cdr_date(dva) BETWEEN cdr_date('$DateArr') AND add_months(cdr_date('$DateArr'), 1))) = 3 THEN '02'
                
                ELSE '00'
            END) Statut,
            
            '' NatConso,
            '' TypConso,
            
            /* GESTION DES MOTIFS */
            (CASE
                /* BLOC 1 : Remboursement anticipe total */
                WHEN EXISTS (
                    SELECT 1 FROM C##DBPROD.bkechprt 
                    WHERE eve = d.eve 
                      AND ctr = 3 
                      AND eta = 'VA' 
                      AND cdr_date(dva) <= cdr_date('$DateArr')
                      AND $mont_dtx=0 
                ) THEN '02'
                
                /* BLOC 2 : Fin a terme */
                WHEN d.ddec <= cdr_date('$DateArr') and $mont_dtx=0  and e.eve not in($DossierReglement_anticipe) THEN '01'
                
                /* BLOC 3 : Passage en perte */
                WHEN $mont_dtx != 0 THEN '05'

                /* REGLES SPECIFIQUES PREEXISTANTES */
                WHEN d.ctr = 9 AND d.ddec < cdr_date('$DateArr') THEN '01'
                WHEN d.ctr = 5 AND d.ddec < cdr_date('$DateArr') THEN '02'
                ELSE ''
            END) Motif,
            
            '01' TypEng,
            (CASE
                WHEN (d.typ IN ('200','201') AND c.tcli <> 1) THEN '01'
                WHEN d.typ IN ('200','201') AND c.tcli = '1' THEN '09'
                WHEN (d.typ IN ('200','201') AND c.tcli <> 1) THEN '03'
                WHEN d.typ IN ('099','106','107') THEN '02'
                WHEN (d.typ IN ('105','104','103') AND c.tcli = 1) THEN '05'
                WHEN (d.typ IN ('105','104','103') AND c.tcli != 1) THEN '02'
                WHEN (c.tcli = 1 AND d.typ = '100') THEN '05'
                WHEN (c.tcli != 1 AND d.typ = '100') THEN '02'
                ELSE '02'
             END) NatEng,
            'XAF' CodDev,
            d.mon MntEng,
            '0' MntCrCedee,
            '0' MntEpargne,
            '2' \"MODREMBEPARGNE\",
            '0' \"TAUXRENUM\",
            TO_CHAR(d.dmep, 'dd/mm/yyyy') DatMep,
            REPLACE(d.tau_int, ',', '.') TxInt,
            '' TxComm,
            '2' \"TXBONIFIE\",
            (CASE
                WHEN d.tau_int < 8 OR (d.tau_int > d.teg) THEN REPLACE(CEIL(d.teg), ',', '.') 
                ELSE REPLACE(d.teg, ',', '.')
             END) AS TxEffGlob,
            '00' TypTxInt,
            '' IndRef,
            '' Sprd,
            (CASE
                WHEN (SELECT COUNT(dva) FROM C##DBPROD.bkechprt WHERE eve = d.eve AND ave = (SELECT MAX(ave) FROM C##DBPROD.bkechprt WHERE eve = d.eve)) IN (1, 2) 
                THEN TO_CHAR(d.dmep, 'ddmmyyyy')
                ELSE (SELECT TO_CHAR(MAX(dva), 'ddmmyyyy') FROM C##DBPROD.bkechprt WHERE num = 01 AND eve = d.eve)
             END) DatDeb,
            TO_CHAR(d.ddec, 'ddmmyyyy') DatFin,
            (CASE
                WHEN d.per_cap = '1' THEN '03'
                WHEN d.per_cap = '3' THEN '04'
                WHEN d.per_cap = '6' THEN '06'
                WHEN d.per_cap = '12' THEN '07'
                WHEN d.per_cap = '4' THEN '05'
                ELSE '00'     
             END) Periodicite, 
            '02' UnitDur,      
            d.tech Duree,      
            (CASE
                WHEN d.typ IN (200, 201, 107) THEN '02' 
                WHEN d.typ IN (100, 099, 106, 103, 104) THEN '01' 
                WHEN d.typ = 105 AND MONTHS_BETWEEN(d.ddec, d.dpec) <= 24 THEN '01'
                WHEN d.typ = 105 AND MONTHS_BETWEEN(d.ddec, d.dpec) > 24 THEN '02'
                ELSE d.typ
             END) Maturite,
            TO_CHAR(d.dpec, 'ddmmyyyy') DatPreEchCap,
            d.tech NbrEch,                         
            '03' MoyRem,
            '01' \"TYECH\",
            (SELECT MAX(tot_ech) FROM C##DBPROD.bkechprt WHERE EXTRACT(DAY FROM dva) = EXTRACT(DAY FROM d.dpec) AND eve = d.eve AND amo_cal != 0) MntEch,  
            '03' \"TYAMO\",
            (SELECT SUM(inte) FROM C##DBPROD.bkechprt WHERE eve = d.eve) TotInt,
            ROUND((SELECT SUM(mon_fra) FROM C##DBPROD.bkdosprt WHERE eve = d.eve)) \"FRADOS\",
            (CASE 
                WHEN ROUND((SELECT (SUM(d.mon_co1) + SUM(d.mon_co2)) FROM C##DBPROD.bkdosprt WHERE eve = d.eve)) = 0 
                THEN ROUND((SELECT SUM(mnt) FROM C##DBPROD.bkcanprt WHERE eve = d.eve AND ges_teg = 'O'))
                ELSE ROUND((SELECT (SUM(d.mon_co1) + SUM(d.mon_co2)) FROM C##DBPROD.bkdosprt WHERE eve = d.eve))
             END) \"FRANNEXE\",
            '0' MntPrm, 
            '' MntTax,                 
            TO_CHAR(d.dmep, 'ddmmyyyy') DatEve,
            d.eve RefInt,
            d.cli IdInt 
        FROM C##DBPROD.bkdosprt d
        JOIN C##DBPROD.bkechprt e ON e.eve = d.eve
        JOIN C##DBPROD.bkcli c ON c.cli = d.cli
        WHERE d.eta IN ('VA', 'DE')
AND d.ave=(SELECT MAX(bb.ave) FROM C##DBPROD.bkdosprt bb WHERE bb.eve = d.eve)
          AND d.tau_int != 0
          /* Date de déclaration = DateArr, Date de début = DateDeb */
          AND cdr_date(d.dmep) <= cdr_date('$DateArr')
          AND cdr_date(d.dmep) > cdr_date('$DateDeb')
          AND (
              d.cli in($clientsDtx)
              or d.eve in ($DossierReglement_anticipe)
          )";

    try {
        $stid = oci_parse($connection, $query);
        oci_execute($stid);

        $results = [];

        while ($row = oci_fetch_assoc($stid)) {
            $results[] = array_change_key_case($row, CASE_UPPER);
        }

        oci_free_statement($stid);
        oci_close($connection);

        if (empty($results)) {
            return response()->json([
                [
                    'Erreur' => [
                        'type' => '404',
                        'Description' => 'Aucune donn&eacute;e trouv&eacute;e pour la p&eacute;riode du ' . $DateDeb . ' au ' . $DateArr,
                    ],
                ],
            ]);
        }

        return response()->json($results);
    } catch (\Throwable $e) {
        if (isset($stid)) {
            oci_free_statement($stid);
        }
        oci_close($connection);

        return response()->json([
            [
                'Erreur' => [
                    'type' => '500',
                    'Description' => 'Erreur serveur lors du chargement des engagements : ' . $e->getMessage(),
                ],
            ],
        ]);
    }
}
}