<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\cdr_pp;
use Illuminate\Http\Request;

class CdrPpController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $results = DB::select("WITH 
        NOM_PAYS AS (
            SELECT 
                n.ctab, n.cacc, TRIM(n.lib2) AS lib2
            FROM bknom n
            WHERE n.ctab = '040'
        ),
        ADRESSE_INFO AS (
            SELECT 
                ad.cli,
                MAX(ad.reg) AS reg,
                MAX(ad.ville) AS ville,
                MAX(ad.adr1) AS adr1
            FROM bkadcli ad
            GROUP BY ad.cli
        ),
        TELEPHONE AS (
            SELECT 
                t.cli,
                t.num AS phone
            FROM bktelcli t
            WHERE t.typ = (SELECT MAX(t1.typ) FROM bktelcli t1 WHERE t1.cli = t.cli)
        ),
        VILLE_REGION AS (
            SELECT 
                cdr_parseutf8(nom_ville) AS ville,
                code_region AS region,
                code_ville AS ville_code
            FROM cdr_ville_region
        )
    SELECT 
        TRIM(c.cli) AS IDINTCLI,
        TRIM(c.nidf) AS NIF_NIU,
        c.sext AS SEXE,
        TRIM(c.nom) AS NOM,
        '' AS NOMMAR,
        TRIM(c.pre) AS PRENOM,
        '' AS NOMCOMPLET,
        '01' AS PRENAI,
        REPLACE(TO_CHAR(c.dna, 'DD/MM/YYYY'), '/', '') AS DATNAI,
        TRIM(c.viln) AS VILLENAI,
        np1.lib2 AS PAYSNAI,
        '00' AS STATUT,
        '01' AS RESIDENT,
        'CM' AS PAYRES,
        np2.lib2 AS NATCLI,
        'PND' AS NOMPERE,
        'PND' AS PREPERE,
        CASE 
            WHEN TRIM(c.nmer) IS NULL THEN 'PND'
            ELSE c.nmer
        END AS NOMMERE,
        'PND' AS PREMERE,
        CASE 
            WHEN c.sit = 'C' THEN '01'
            WHEN c.sit = 'M' THEN '02'
            WHEN c.sit = 'D' THEN '03'
            WHEN c.sit = 'V' THEN '04'
            ELSE '01'
        END AS SITMAT,
        CASE 
            WHEN c.catn = 2401 THEN 1100
            WHEN c.catn = 2212 THEN 1080
            WHEN c.catn = 2203 THEN 1090
            WHEN c.catn = 2301 THEN 1110
            WHEN c.catn = 2213 THEN 1100
            WHEN c.catn IN (1601, 1602, 1603) THEN 1210
            WHEN c.catn = 1321 THEN 1210
            WHEN c.catn = 2101 THEN 1050
            WHEN c.catn IN (2101, 2102, 2103) THEN 1050
            ELSE TO_NUMBER(c.catn)
        END AS AGEECO,
        CASE 
            WHEN c.catn IN (2212, 2220) THEN TRIM(c.nrc)
            ELSE ''
        END AS RCCM,
        '' AS SECTACT,
        '' AS CA,
        '' AS NOMBEMP,
        0 AS SITJUD,
        '' AS DATDEBINT,
        '' AS DATEFININT,
        TRIM(TO_CHAR(c.dou, 'DDMMYYYY')) AS DATENTRELPAR,
        (SELECT MAX(TRIM(em.email)) FROM bkemacli em WHERE c.cli = em.cli) AS EMAIL,
        REPLACE('00237' || COALESCE(t.phone, ''), ' ', '') AS MOBILE,
        TO_CHAR(SYSDATE, 'DDMMYYYY') AS DATEVE,
        CASE 
            WHEN c.tid = '00001' AND LENGTH(TRIM(c.nid)) > 9 THEN '06'
            WHEN c.tid = '00001' AND LENGTH(TRIM(c.nid)) < 10 THEN '01'
            WHEN c.tid = '00003' THEN '02'
            WHEN c.tid = '00004' THEN '03'
            WHEN c.tid = '00002' THEN '05'
            ELSE '06'
        END AS TYPPIECE,
        TRIM(c.nid) AS NUMPIECE,
        TO_CHAR(c.did, 'DDMMYYYY') AS DATEMPIECE,
        TRIM(c.lid) AS LIEU,
        'CM' AS PAYS_,
        TO_CHAR(c.vid, 'DDMMYYYY') AS DATFINPIECE,
        '01' AS TYPADR,
        trim(ai.adr1) AS ADRESSE,
        'CM' AS PAYS,
        vr.region AS REGION,
        vr.ville_code AS VILLE,
        '' AS CODPOST,
        '' AS IDINTREL,
        '' AS NOMREL,
        '' AS PRENOMREL,
        '' AS TYPREL,
        '' AS NBRPERCH,
        '' AS TYPLOG,
        '' AS REVMENNET,
        '' AS CODDEV
    FROM 
        bkcli c
        LEFT JOIN NOM_PAYS np1 ON np1.cacc = c.payn
        LEFT JOIN NOM_PAYS np2 ON np2.cacc = c.nat
        LEFT JOIN ADRESSE_INFO ai ON ai.cli = c.cli
        LEFT JOIN TELEPHONE t ON t.cli = c.cli
        LEFT JOIN VILLE_REGION vr ON vr.ville = cdr_parseutf8(ai.ville)
    WHERE 
        c.tcli IN (1)
        AND c.cli <> 100534
        AND c.cli > 100914
    ORDER BY 
        1");
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
    public function show(cdr_pp $cdr_pp)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, cdr_pp $cdr_pp)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(cdr_pp $cdr_pp)
    {
        //
    }
}
