<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\cdr_cptdebiteur;
use Illuminate\Http\Request;

class CdrCptdebiteurController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //recuperation des comptes debiteurs
        $result= DB::select();
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
    public function show(cdr_cptdebiteur $cdr_cptdebiteur)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, cdr_cptdebiteur $cdr_cptdebiteur)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(cdr_cptdebiteur $cdr_cptdebiteur)
    {
        //
    }
}
