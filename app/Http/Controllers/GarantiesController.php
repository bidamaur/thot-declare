<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Services\DatabaseConnection;
use App\Models\garanties;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GarantiesController extends Controller
{
  protected $dbConnection;
  public function __construct(DatabaseConnection $dbConnection)
  {
    $this->dbConnection = $dbConnection;
  }
  /**
   * Display a listing of the resource.
   */
  public function getGaranties($MyDateArr)
  {


    $connection = $this->dbConnection->getConnection();
    $dateArret = Carbon::parse($MyDateArr);
    $DateArr = $dateArret->format('d/m/y');
    $DateArrYear = $dateArret->year;
    $DateArrMonth = $dateArret->month;
    $DateArrDay = $dateArret->day;
    $notFound = '[{"Erreur": {
      "type": "404",
      "Description": "Aucune donn&eacute;e trouv&eacute;e pour la p&eacute;riode du ' . $DateArr . '"
      }}]';
    $myData = $notFound;

    $MyQuery = "SELECT en.cli,
       SUBSTR(en.neng,1,6) eve,
       -- Garantie
       g.ref RefIntGar,
       (
       CASE
         WHEN g.cnat IN('001','002','003','004')
         THEN 201
         WHEN g.cnat ='012'
         THEN 205
         WHEN g.cnat ='016'
         THEN 210
         ELSE 300
       END ) NatGar,
       'XAF' CodDevGar,
       g.mont MntGar,
       (
            SELECT mon FROM C##DBPROD.bksld WHERE (ncp like '455%' or ncp like '458%' ) and eve =SUBSTR(en.neng,1,6)
            and dco=(SELECT 
 MAX(dco) from C##DBPROD.bksld where (ncp like '455%' or ncp like '458%' ) and eve =SUBSTR(en.neng,1,6)
 AND cdr_date(dco)<cdr_date('$DateArr'))
            ) as MntAffGar,
       en.mnta MntAffGar,
       en.poura,
       g.ref RefExtGar,
       (
       CASE
         WHEN g.cnat IN('001','002','003','004')
         THEN 01
         ELSE 08
       END )TypRefGar,
       (SELECT DISTINCT cli FROM C##DBPROD.bkdosprt WHERE eve=SUBSTR(en.neng,1,6)
       ) IdIntGarant,
       ( SELECT trim(nom||''||pre) FROM C##DBPROD.bkcli WHERE cli=en.cli
       )NomNaiGarant,
       '01' StatutGar
     FROM C##DBPROD.bkeng en,
       C##DBPROD.bkgar g
     WHERE g.eve=en.ngar";

    $stid = oci_parse($connection, $MyQuery);
    oci_execute($stid);

    $results = [];

    while ($row = oci_fetch_assoc($stid)) {
      $results[] = array_change_key_case($row, CASE_UPPER);
    }

    $results = mb_convert_encoding($results, 'UTF-8', 'ISO-8859-1');

    oci_free_statement($stid);
    oci_close($connection);

    if (empty($results)) {
      return response()->json(json_decode($notFound, true));
    }

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
  public function show(garanties $garanties)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, garanties $garanties)
  {
    //
  }

  /**
   * Remove the specified resource FROM storage.
   */
  public function destroy(garanties $garanties)
  {
    //
  }
}
