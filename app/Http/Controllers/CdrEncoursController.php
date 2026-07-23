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
/*  *
     * ceci est  la fonction des encours d'ajustement
     */
    public function GetEncoursAjust($MyDateArr)
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
    $MoisAnneeStr  = $dateArret->format('m/Y'); // Format MM/YYYY

    // --- BLOCS SQL DYNAMIQUES POUR LES CALCULS D'ÉCHÉANCES ---

    // 1. Nombre total d'échéances pour L'AVENANT COURANT
    $NbrEchTotal = "(CASE 
        WHEN EXISTS (SELECT 1 FROM C##DBPROD.bkechprt WHERE num = 0 AND eve = d.eve AND ave = d.ave) THEN 
            (SELECT COUNT(dva) FROM C##DBPROD.bkechprt WHERE eve = d.eve AND ave = d.ave) - 1
        ELSE 
            (SELECT COUNT(dva) FROM C##DBPROD.bkechprt WHERE eve = d.eve AND ave = d.ave)
    END)";

    // 2. Nombre d'échéances payées (ctr = 9) à la date de l'ajustement
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

    $last_num_echeance = "(SELECT MAX(num) FROM C##DBPROD.bkechprt WHERE eve = d.eve AND ave = d.ave)";

    // --- REQUÊTE PRINCIPALE POUR LES AJUSTEMENTS ---
    $MyRequest = "WITH Last_Ech_Reelle AS (
        -- Recherche de la dernière vraie échéance connue (Exclusion CTR = 0 et CTR = 3)
        SELECT e_sub.*,
               ROW_NUMBER() OVER (PARTITION BY e_sub.eve, e_sub.ave ORDER BY CDR_DATE(e_sub.dva) DESC) as rn
        FROM C##DBPROD.bkechprt e_sub
        WHERE CDR_DATE(e_sub.dva) <= CDR_DATE('$DateArr')
          AND e_sub.ctr NOT IN (0, 3)
    )
    SELECT DISTINCT 
        d.eve,
        d.ave,
        TO_CHAR(CDR_DATE(last_e.dva), 'DD') || '/$MoisAnneeStr' AS DVA,
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

        -- Récupération du dernier paiement réel
        (SELECT MAX(aa.dco)
         FROM C##DBPROD.bkauxprt aa
         WHERE aa.sen = 'C'
           AND aa.eve = d.eve
           AND CDR_DATE(aa.dco) <= CDR_DATE('$DateArr')
        ) datPai,

        TO_CHAR(CDR_DATE(last_e.dva), 'DD') || '/$MoisAnneeStr' AS DatEch,

        -- Récupération du dernier montant payé réel
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

        -- Encours restant
        (CASE 
            WHEN last_e.num = $last_num_echeance THEN 0
            WHEN last_e.num IN (0, 1, 2, 3) AND last_e.res = 0 THEN d.mon
            WHEN (last_e.num >= 4 AND last_e.num <= $last_num_echeance) AND last_e.res = 0 THEN 
                NVL((SELECT res FROM C##DBPROD.bkechprt 
                     WHERE eve = d.eve 
                       AND ave = d.ave 
                       AND TO_CHAR(dva, 'MM/YYYY') = TO_CHAR(ADD_MONTHS(last_e.dva, -1), 'MM/YYYY')
                       AND ROWNUM = 1), 0)
            ELSE last_e.res
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
            WHEN $NbrJrsImp != 0 AND last_e.amo_imp = 0 THEN last_e.tot_ech
            ELSE ROUND(last_e.amo_imp)
        END) AS MNTCAPSOUF,

        (CASE
            WHEN $NbrJrsImp != 0 AND last_e.inte = 0 THEN (SELECT MIN(inte) FROM C##DBPROD.bkechprt WHERE eve = last_e.eve AND ave = last_e.ave)
            WHEN $NbrJrsImp = 0 THEN 0
            ELSE (last_e.inte + NVL(last_e.ini, 0))
        END) AS MNTINTSOUF,

        last_e.inte interet,
        last_e.amo_imp capital,
        last_e.tot_ech,

        (CASE
            WHEN $NbrEchImp >= 2 AND $mon_douteux = 0 THEN 2 
            ELSE 1 
        END) echimpaye,

        (CASE
            WHEN last_e.amo_imp = 0 THEN 0
            ELSE last_e.tin
        END) AS MNTTAXSOUF,

        '0' AS MNTAGIOSSOUF,
        last_e.inte MNTERAT,
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
        INNER JOIN Last_Ech_Reelle last_e ON last_e.eve = d.eve AND last_e.ave = d.ave AND last_e.rn = 1
        WHERE d.eta IN ('VA', 'DE')
          AND d.tau_int != 0
          AND d.eve NOT IN ('002259')
          
          -- 1. Date de déchéance/fin supérieure à la date d'arrêt (dossier en cours)
          AND CDR_DATE(d.ddec) > CDR_DATE('$DateArr')
          
          -- 2. Exclusion des dossiers soldés (il doit rester du capital à amortir / échéances restantes)
          AND $NbrEchRes > 0
          
          -- 3. Validation que la mise en place (DMEP) est bien antérieure ou égale au mois d'arrêt
          AND CDR_DATE(d.dmep) <= CDR_DATE('$DateArr')

          -- 4. Aucune échéance réelle présente sur le mois d'analyse
          AND NOT EXISTS (
              SELECT 1 
              FROM C##DBPROD.bkechprt ex
              WHERE ex.eve = d.eve 
                AND ex.ave = d.ave
                AND TO_CHAR(CDR_DATE(ex.dva), 'MM/YYYY') = '$MoisAnneeStr'
          )
        ORDER BY d.eve DESC";

    $stid = null;
    try {
        $stid = oci_parse($connection, $MyRequest);
        oci_execute($stid);

        $results = [];

        while ($row = oci_fetch_assoc($stid)) {
            $results[] = array_change_key_case($row, CASE_UPPER);
        }

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
