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
        $results = DB::select("select * from dbprod.cdr_pp");
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
