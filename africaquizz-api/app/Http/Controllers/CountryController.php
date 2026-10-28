<?php

namespace App\Http\Controllers;

use App\Models\Country;

class CountryController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Country::query()
                ->orderBy('iso2')
                ->get(['id', 'iso2', 'iso3']),
        ]);
    }
}
