<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'country_id' => ['present', 'nullable', 'uuid', 'exists:countries,id'],
        ]);

        $profile = $request->user()->profile;
        if (!$profile) {
            return response()->json(['message' => 'Profil introuvable.'], 409);
        }

        $profile->update(['country_id' => $validated['country_id']]);

        return response()->json(['data' => $profile->fresh()]);
    }
}
