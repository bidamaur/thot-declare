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
        trim(c.cli) AS IdIntCli,
        trim(c.nidf) AS nif_niu,
        trim(c.rso) AS RaiSoc,
        TO_CHAR(c.datc, 'DDMMYYYY') AS datcre,
        trim(c.sig) AS sigle,
        '01' AS Resident,
        'CM' AS PaysSiege,
        ad.ville,
        trim(c.nrc) AS RCCM,
        CASE
            WHEN c.fju='01' THEN '00'
            WHEN c.fju='16' THEN '15'
            WHEN c.fju='03' THEN '04'
            WHEN c.fju='03' THEN '02'
            WHEN c.fju='20' THEN '19'
            WHEN c.fju='31' THEN '30'
            WHEN c.fju='04' THEN '03'
            ELSE c.fju
        END AS ForJurid,
        CASE
            WHEN c.sec IN (SELECT sec FROM cdr_naema) THEN 
                (SELECT val FROM cdr_naema WHERE sec = c.sec)
            ELSE c.sec
        END AS SecAct,
(
    CASE
      WHEN c.catn in(1302,1201) and tcli in(2,3)
      THEN 1210
      WHEN c.catn= (2202) and tcli in(2,3)
      THEN 1060
      WHEN c.catn=1401 and tcli in(2,3)
      THEN 1130
       WHEN c.catn=2203 and tcli in(2,3)
      THEN 1061
      ELSE to_number(c.catn)
    END ) AS AgeEco,
        '01' AS staleg, -- en activite a controller avant déclaration
        TO_CHAR(c.dou, 'DDMMYYYY') AS DatEntRelPar,
        '' AS ChiAffaire,
        '' AS TotBilan,
        '' AS Effectif,
        trim(em.email) as email,
        TRIM(REPLACE('00237' || 
            CASE 
                WHEN tcli = 1 THEN 
                    t.num
                ELSE 
                    (SELECT MAX(trim(t2.tel)) FROM bkcntcli t2 WHERE t2.cli = c.cli)
            END, ' ', '')) AS tel,
        0 AS sitjud,
        TO_CHAR('', 'DDMMYYYY') AS DatDebInt,
        TO_CHAR('', 'DDMMYYYY') AS DatFinInt,
        TO_CHAR(c.dou, 'DDMMYYYY') AS DatEve,
        '03' AS typadr,
        trim(ad.adr1) AS adresse,
        'CM' AS pays,
        CASE
            WHEN ad.ville IN (SELECT dbprod.cdr_parseutf8(nom_ville) FROM cdr_ville_region) THEN
                (SELECT code_region FROM cdr_ville_region WHERE dbprod.cdr_parseutf8(nom_ville) = dbprod.cdr_parseutf8(ad.ville))
            ELSE 0
        END AS Region,
        CASE
            WHEN ad.ville IN (SELECT dbprod.cdr_parseutf8(nom_ville) FROM cdr_ville_region) THEN
                (SELECT code_ville FROM cdr_ville_region WHERE dbprod.cdr_parseutf8(nom_ville) = dbprod.cdr_parseutf8(ad.ville))
            ELSE 0
        END AS ville,
        '' AS CodPost,
        '' AS IdIntMand,
        '' AS typMand,
        '' AS DatDebMand,
        '' AS DatFinMand,
        '' AS IdIntAct,
        '' AS NomAct,
        '' AS pctAct,
        '' AS DatDebAct,
        '' AS DateFinAct,
        '' AS DatMajAct,
        '' AS TelAct
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
    ORDER BY 1");
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
