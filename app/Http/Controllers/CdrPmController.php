<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\cdr_pm;
use Illuminate\Http\Request;

class CdrPmController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $results = DB::select("SELECT 
        TRIM(c.cli) AS IDINTCLI,
        TRIM(c.nidf) AS NIF_NIU,
        TRIM(c.rso) AS RAISOC,
        TO_CHAR(c.datc, 'DDMMYYYY') AS DATCRE,
        TRIM(c.sig) AS SIGLE,
        '01' AS RESIDENT,
        'CM' AS PAYSSIEGE,
        ad.ville,
        TRIM(c.nrc) AS RCCM,
        CASE
            WHEN c.fju='01' THEN '00'
            WHEN c.fju='16' THEN '15'
            WHEN c.fju='03' THEN '04'
            WHEN c.fju='03' THEN '02'
            WHEN c.fju='20' THEN '19'
            WHEN c.fju='31' THEN '30'
            WHEN c.fju='04' THEN '03'
            ELSE c.fju
        END AS FORJURID,
        CASE
            WHEN c.sec IN (SELECT sect FROM cdr_naema) THEN 
                (SELECT val FROM cdr_naema WHERE sect = c.sec)
            ELSE c.sec
        END AS SECACT,
        (
            CASE
                WHEN c.catn IN(1302,1201) AND tcli IN(2,3)
                THEN 1210
                WHEN c.catn= (2202) AND tcli IN(2,3)
                THEN 1062
                WHEN c.catn=1401 AND tcli IN(2,3)
                THEN 1130
                WHEN c.catn=2203 AND tcli IN(2,3)
                THEN 1061
                ELSE TO_NUMBER(c.catn)
            END 
        ) AS AGEECO,
        '01' AS STALEG, -- EN ACTIVITE A CONTROLLER AVANT DECLARATION
        TO_CHAR(c.dou, 'DDMMYYYY') AS DATENTRELPAR,
        '' AS CHIAFFAIRE,
        '' AS TOTBILAN,
        '' AS EFFECTIF,
        TRIM(em.email) AS EMAIL,
        TRIM(REPLACE('00237' || 
            CASE 
                WHEN tcli = 1 THEN 
                    t.num
                ELSE 
                    (SELECT MAX(TRIM(t2.tel)) FROM bkcntcli t2 WHERE t2.cli = c.cli)
            END, ' ', '')) AS TEL,
        0 AS SITJUD,
        TO_CHAR('', 'DDMMYYYY') AS DATDEBINT,
        TO_CHAR('', 'DDMMYYYY') AS DATFININT,
        TO_CHAR(c.dou, 'DDMMYYYY') AS DATEVE,
        '03' AS TYPADR,
        TRIM(ad.adr1) AS ADRESSE,
        'CM' AS PAYS,
        CASE
      WHEN cdr_parseutf8(ad.ville) IN
        (SELECT cdr_parseutf8(nom_ville) FROM cdr_ville_region
        )
      THEN
        (SELECT code_region
        FROM cdr_ville_region
        WHERE cdr_parseutf8(nom_ville) = cdr_parseutf8(ad.ville)
        )
      ELSE 0
    END AS  REGION,
    CASE
      WHEN cdr_parseutf8(ad.ville) IN
        (SELECT cdr_parseutf8(nom_ville) FROM cdr_ville_region
        )
      THEN
        (SELECT code_ville
        FROM cdr_ville_region
        WHERE cdr_parseutf8(nom_ville) = cdr_parseutf8(ad.ville)
        )
      ELSE 0
    END AS ville,
        '' AS CODPOST,
        '' AS IDINTMAND,
        '' AS TYPMAND,
        '' AS DATDEBMAND,
        '' AS DATFINMAND,
        '' AS IDINTACT,
        '' AS NOMACT,
        '' AS PCTACT,
        '' AS DATDEBACT,
        '' AS DATEFINACT,
        '' AS DATMAJACT,
        '' AS TELACT
    FROM 
        bkcli c
    LEFT JOIN 
        (SELECT cli, MAX(ville) AS ville, MAX(adr1) AS adr1 FROM bkadcli GROUP BY cli) ad 
        ON ad.cli = c.cli
    LEFT JOIN 
        (SELECT cli, MAX(email) AS email FROM bkadcli GROUP BY cli) em 
        ON em.cli = c.cli
    LEFT JOIN 
        (SELECT cli, MAX(num) AS num FROM bktelcli GROUP BY cli) t 
        ON t.cli = c.cli
    WHERE 
        c.tcli IN (2, 3)
        AND c.cli NOT IN (000020, 100500)
        --and c.cli>100924
    ORDER BY 1
    ");
            $results = array_map(function($row) {
                return array_change_key_case((array)$row, CASE_UPPER);
            }, $results);
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
    public function show(cdr_pm $cdr_pm)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, cdr_pm $cdr_pm)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(cdr_pm $cdr_pm)
    {
        //
    }
}
