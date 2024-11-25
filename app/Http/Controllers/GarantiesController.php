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
    public function getGaranties()
    {

      
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

       $MyQuery=DB::select("SELECT en.cli,
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
            SELECT mon FROM dbprod.bksld WHERE (ncp like '455%' or ncp like '458%' ) and eve =SUBSTR(en.neng,1,6)
            and dco=(SELECT 
 MAX(dco) from dbprod.bksld where (ncp like '455%' or ncp like '458%' ) and eve =SUBSTR(en.neng,1,6)
AND cdr_date(dco)<cdr_date('$DateArr'))
            ) as MntAffGar,
       en.mnta MntAffGar, -- a retirer
       en.poura ,--pourcentage de couverture du pret
       g.ref RefExtGar,
       (
       CASE
         WHEN g.cnat IN('001','002','003','004')
         THEN 01
         ELSE 08
       END )TypRefGar, -- ----  numero titre foncier,Référence Facture,numero bail,ref nentissement
       (SELECT DISTINCT cli FROM dbprod.bkdosprt WHERE eve=SUBSTR(en.neng,1,6)
       ) IdIntGarant,
       ( SELECT trim(nom||''||pre) FROM dbprod.bkcli WHERE cli=en.cli
       )NomNaiGarant,
       '01' StatutGar
     FROM dbprod.bkeng en,
       dbprod.bkgar g
     WHERE g.eve=en.ngar");
     $prepare=oci_parse($connection, $MyQuery);
     oci_execute($prepare);
     while($MyData=oci_fetch_assoc($prepare) ){
    $MyData[]=$MyRow;
    if(!$MyData){
      echo  response()->json($MyData);
      return false;
    }
    $data=response()->json($MyData);
  }
//integration des garanties
if($date){
  return $data;
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
     * Remove the specified resource from storage.
     */
    public function destroy(garanties $garanties)
    {
        //
    }
}
