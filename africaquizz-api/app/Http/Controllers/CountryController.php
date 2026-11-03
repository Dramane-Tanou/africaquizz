<?php

namespace App\Http\Controllers;

use App\Models\Country;

class CountryController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Country::query()
                ->leftJoin('regions', 'regions.id', '=', 'countries.region_id')
                ->orderBy('countries.iso2')
                ->get(['countries.id', 'countries.iso2', 'countries.iso3', 'regions.slug as region'])
                ->map(fn (Country $country) => [
                    'id' => $country->id,
                    'iso2' => $country->iso2,
                    'iso3' => $country->iso3,
                    'region' => $country->region,
                    'is_african' => $country->region !== null && $country->region !== 'reste-du-monde',
                ]),
        ]);
    }
}
