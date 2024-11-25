<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class QueryServices
{
    public function getEncoursData($Arr,$Month,$Year)
    {
        $mrequest="WITH MaxAve AS (
            SELECT eve, MAX(ave) AS max_ave
            FROM bkdosprt
            GROUP BY eve
        ),
        MaxDco AS (
            SELECT eve, MAX(CDR_DATE(dco)) AS max_dco
            FROM bkauxprt
            WHERE sen = 'C' AND CDR_DATE(dco) < CDR_DATE('$Arr')
            GROUP BY eve
        ),
        MaxMon AS (
            SELECT eve, MAX(CDR_DATE(dco)) AS max_dco, MAX(mon) AS max_mon
            FROM bkauxprt
            WHERE sen = 'C' AND CDR_DATE(dco) < CDR_DATE('$Arr')
            GROUP BY eve
        ),
        SumMon AS (
            SELECT cli,
                   CASE
                       WHEN SUM(mon) < 0 THEN '01'
                       ELSE NULL
                   END AS cla_01,
                   CASE
                       WHEN SUM(CASE WHEN cha LIKE '341%' THEN mon ELSE 0 END) < 0 THEN '04'
                       ELSE NULL
                   END AS cla_04,
                   CASE
                       WHEN SUM(CASE WHEN cha LIKE '3441%' OR cha LIKE '3451%' THEN mon ELSE 0 END) < 0 THEN '07'
                       ELSE NULL
                   END AS cla_07,
                   CASE
                       WHEN SUM(CASE WHEN cha LIKE '3442%' OR cha LIKE '3452%' THEN mon ELSE 0 END) < 0 THEN '08'
                       ELSE NULL
                   END AS cla_08,
                   CASE
                       WHEN SUM(CASE WHEN cha LIKE '3443%' OR cha LIKE '3453%' THEN mon ELSE 0 END) < 0 THEN '09'
                       ELSE NULL
                   END AS cla_09,
                   CASE
                       WHEN SUM(CASE WHEN cha LIKE '344%' OR cha LIKE '345%' THEN mon ELSE 0 END) < 0 THEN '06'
                       ELSE NULL
                   END AS cla_06
            FROM bksld
            WHERE dco < CDR_DATE('$Arr')
            GROUP BY cli
        )
        SELECT DISTINCT 
            d.eve,
            d.cli,
            cdr_parce_ncp(p.ncp) AS RefContCmpt,
            MaxDco.max_dco AS datPai,
            d.ddec AS DatEch,
            MaxMon.max_mon AS MntPay,
            '0' AS MntAgi,
            e.res AS MntCrd,
            '0' AS estSensible,
            d.mon AS MntTotUtil,
            (SELECT COUNT(dva)
             FROM bkechprt
             WHERE ctr IN (9, 3)
               AND eta = 'VA'
               AND eve = e.eve
               AND CDR_DATE(dva) < CDR_DATE('$Arr')) AS nbrEchPay,
            (SELECT COUNT(dva)
             FROM bkechprt
             WHERE ctr = '8'
               AND eta = 'VA'
               AND eve = e.eve
               AND CDR_DATE(dva) < CDR_DATE('$Arr')) AS nbrEchImp,
            (d.nbe - e.num) AS nbrEchRes,
            ROUND(e.amo_imp) AS MntCreSouf,
            e.amo_imp AS MntCapSouf,
            CASE WHEN e.amo_imp = 0 THEN 0 ELSE e.inte END AS MntIntSouf,
            CASE WHEN e.amo_imp = 0 THEN 0 ELSE e.tin END AS MntTaxSouf,
            '0' AS MntAgiosSouf,
            e.inte AS MntCreRat,
            '' AS MntPro,
            CASE
                WHEN e.amo_imp = 0 THEN 0
                ELSE CDR_DATE((SELECT MIN(DVA)
                               FROM bkechprt
                               WHERE eta = 'VA' AND ctr = 8 AND eve = e.eve)) - CDR_DATE('$Arr')
            END AS nbrJrsImp,
            SUMMon.cla_01 AS ClaDeprec
        FROM 
            bkdosprt d
        JOIN 
            bkechprt e ON e.eve = d.eve
        JOIN 
            bkcom co ON d.cli = co.cli
        LEFT JOIN 
            MaxAve ma ON ma.eve = d.eve
        LEFT JOIN 
            MaxDco MaxDco ON MaxDco.eve = d.eve
        LEFT JOIN 
            MaxMon MaxMon ON MaxMon.eve = d.eve
        LEFT JOIN 
            SumMon ON SumMon.cli = d.cli
        LEFT JOIN 
            bkcptprt p ON p.eve = d.eve AND p.nat = '004'
        WHERE 
            d.eta = 'VA'
            AND d.ave = ma.max_ave
            AND e.ave = ma.max_ave
            AND e.dva BETWEEN '01/$Month/$Year' AND CDR_DATE('$Arr')
            AND d.ave = (SELECT MAX(ave) FROM bkdosprt WHERE eve = d.eve)
        ";
    }
}
