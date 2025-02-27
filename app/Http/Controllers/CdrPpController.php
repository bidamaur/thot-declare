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
        function parseUtf8($input_string)
        {
            // Liste des caractères accentués et leurs remplacements
            $trans = [
                'á' => 'a',
                'à' => 'a',
                'â' => 'a',
                'ä' => 'a',
                'ã' => 'a',
                'å' => 'a',
                'ç' => 'c',
                'é' => 'e',
                'è' => 'e',
                'ê' => 'e',
                'ë' => 'e',
                'í' => 'i',
                'ì' => 'i',
                'î' => 'i',
                'ï' => 'i',
                'ñ' => 'n',
                'ó' => 'o',
                'ò' => 'o',
                'ô' => 'o',
                'ö' => 'o',
                'õ' => 'o',
                'ú' => 'u',
                'ù' => 'u',
                'û' => 'u',
                'ü' => 'u',
                'ý' => 'y',
                'ÿ' => 'y',
                '!' => '',
                '@' => '',
                '#' => '',
                '$' => '',
                '%' => '',
                '^' => '',
                '&' => '',
                '*' => '',
                '(' => '',
                ')' => '',
                '_' => '',
                '+' => '',
                '{' => '',
                '}' => '',
                '[' => '',
                ']' => '',
                '|' => '',
                ';' => '',
                ':' => '',
                '"' => '',
                '-' => '',
                '<' => '',
                '>' => '',
                ',' => '',
                '.' => '',
                '?' => '',
                '/' => ''
            ];

            // Remplace les caractères accentués et autres caractères spéciaux
            $output_string = strtr($input_string, $trans);

            // Supprime les espaces et met tout en majuscules
            return strtoupper(trim(str_replace(' ', '', $output_string)));
        }

        $results = DB::select("SELECT 
        TRIM(c.cli) AS IDINTCLI,
        (CASE
            WHEN  trim(nidf) IS not  NULL  THEN replace(trim(nidf),'						','')
            WHEN  trim(nidn) IS not  NULL  THEN  replace(trim(nidn),'						','')     
            ELSE replace(trim(idext),'						','')
        END) AS NIF_NIU,
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
        'CM' AS PAYSRES,
        np2.lib2 AS NATCLI,
        NVL((select trim(vala) from BKICLI   where cli=c.cli and iden='NOMPERE'),'PND') AS NOMPERE,
       NVL((select trim(vala) from BKICLI   where cli=c.cli and iden='PREPERE'),'PND') AS PREPERE,
        CASE 
            WHEN TRIM(c.nmer) IS NULL THEN NVL((select trim(vala) from BKICLI   where cli=c.cli and iden='NOMMERE'),'PND') 
            ELSE c.nmer
        END AS NOMMERE,
        NVL((select trim(vala) from BKICLI   where cli=c.cli and iden='PREMERE'),'PND') AS PREMERE,
        CASE 
            WHEN c.sit = 'C' THEN '01'
            WHEN c.sit = 'M' THEN '02'
            WHEN c.sit = 'D' THEN '03'
            WHEN c.sit = 'V' THEN '04'
            ELSE '01'
        END AS SITMAT,
        CASE
            WHEN c.catn in(2401) AND c.tcli = 1
            THEN 1100
            WHEN c.catn in (2203,2401) AND c.tcli = 1
            THEN 1080
            ELSE TO_NUMBER(c.catn)
        END AS AGEECO,
        TRIM(c.nrc) AS RCCM,
        CASE
            WHEN c.sec IN (SELECT sect FROM cdr_naema) THEN 
                (SELECT val FROM cdr_naema WHERE sect = c.sec)
            ELSE c.sec
        END AS SECTACT,
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
            WHEN c.tid = '00001' AND LENGTH(TRIM(c.nid))=20 THEN '06'
            WHEN c.tid = '00001' AND ( LENGTH(TRIM(c.nid))=9 or LENGTH(TRIM(c.nid))=17) THEN '01'
            WHEN c.tid = '00003' AND ( LENGTH(TRIM(c.nid))=7 or LENGTH(TRIM(c.nid))=8 or LENGTH(TRIM(c.nid))=9 ) THEN '02'
            WHEN c.tid = '00004' AND  LENGTH(TRIM(c.nid))=18 THEN '03'
            WHEN c.tid = '00002' THEN '05'
            ELSE '07'
        END AS TYPPIECE,
        TRIM(c.nid) AS NUMPIECE,
        TO_CHAR(c.did, 'DDMMYYYY') AS DATEMPIECE,
        TRIM(c.lid) AS LIEU,
        'CM' AS PAYS_,
        TO_CHAR(c.vid, 'DDMMYYYY') AS DATFINPIECE,
        '01' AS TYPADR,
        TRIM(ai.adr1) AS ADRESSE,
        'CM' AS PAYS,
        vr.region AS REGION,
        TRIM(vr.ville_code) AS VILLE,
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
    LEFT JOIN 
        (SELECT n.cacc, TRIM(n.lib2) AS lib2 FROM bknom n WHERE n.ctab = '040') np1 
        ON np1.cacc = c.payn
    LEFT JOIN 
        (SELECT n.cacc, TRIM(n.lib2) AS lib2 FROM bknom n WHERE n.ctab = '040') np2 
        ON np2.cacc = c.nat
    LEFT JOIN 
        (SELECT ad.cli, MAX(ad.reg) AS reg, MAX(ad.ville) AS ville, MAX(ad.adr1) AS adr1 
         FROM bkadcli ad 
         GROUP BY ad.cli) ai 
        ON ai.cli = c.cli
    LEFT JOIN 
        (SELECT t.cli, t.num AS phone 
         FROM bktelcli t 
         WHERE t.typ = (SELECT MAX(t1.typ) FROM bktelcli t1 WHERE t1.cli = t.cli)) t 
        ON t.cli = c.cli
    LEFT JOIN 
        (SELECT dbprod.cdr_parseUtf8(nom_ville) AS ville, code_region AS region, code_ville AS ville_code 
         FROM cdr_ville_region) vr 
        ON vr.ville = dbprod.cdr_parseUtf8(ai.ville)
    WHERE 
        c.tcli IN (1)
        -- AND c.cli <> 100534
        -- AND c.cli > 100914
    ORDER BY 1");
        if (!$results) {
            echo "[{
                'type':'Erreur',
                'Description':'Personne physique non disponible'
            }]";
            return false;
        }

        $results = array_map(function ($row) {
            return array_change_key_case((array) $row, CASE_UPPER);
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
