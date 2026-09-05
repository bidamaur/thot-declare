<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class CsrfController extends Controller
{
    public function __invoke()
    {
        return response()->json(['csrf_token' => csrf_token()]);
    }
}
