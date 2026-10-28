<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Category;
use App\Models\Country;
use App\Models\Region;
use Illuminate\Http\Request;

class TaxonomyManagementController extends Controller
{
    public function __construct()
    {
        if (!request()->user('admin')?->hasRole('super_admin') && !request()->user('admin')?->hasRole('admin')) {
            abort(403);
        }
    }

    // === BADGES ===
    public function listBadges() { return response()->json(['data' => Badge::all()]); }

    public function createBadge(Request $request) {
        $v = $request->validate([
            'code' => 'required|string|unique:badges',
            'name_fr' => 'required|string',
            'name_en' => 'required|string',
            'tier' => 'required|in:bronze,argent,or',
            'rule_params' => 'required|array',
        ]);
        return response()->json(['data' => Badge::create($v)], 201);
    }

    public function updateBadge(Request $request, $id) {
        $badge = Badge::findOrFail($id);
        $v = $request->validate([
            'name_fr' => 'nullable|string',
            'name_en' => 'nullable|string',
            'tier' => 'nullable|in:bronze,argent,or',
            'rule_params' => 'nullable|array',
        ]);
        $badge->update(array_filter($v, fn($v) => !is_null($v)));
        return response()->json(['data' => $badge->fresh()]);
    }

    public function deleteBadge($id) {
        Badge::findOrFail($id)->delete();
        return response()->json(['message' => 'Badge supprimé.']);
    }

    // === CATEGORIES ===
    public function listCategories() {
        return response()->json(['data' => Category::withCount('questions')->get()]);
    }

    public function createCategory(Request $request) {
        $v = $request->validate(['slug' => 'required|string|unique:categories|max:50']);
        return response()->json(['data' => Category::create($v)], 201);
    }

    public function updateCategory(Request $request, $id) {
        $cat = Category::findOrFail($id);
        $v = $request->validate(['slug' => 'required|string|unique:categories,slug,' . $id]);
        $cat->update($v);
        return response()->json(['data' => $cat->fresh()]);
    }

    public function deleteCategory($id) {
        $cat = Category::findOrFail($id);
        if ($cat->questions()->count() > 0) {
            return response()->json(['message' => 'Impossible : questions associées.'], 422);
        }
        $cat->delete();
        return response()->json(['message' => 'Catégorie supprimée.']);
    }

    // === COUNTRIES ===
    public function listCountries() {
        return response()->json(['data' => Country::with('region')->orderBy('iso2')->get()]);
    }

    public function updateCountry(Request $request, $id) {
        $country = Country::findOrFail($id);
        $v = $request->validate([
            'capital' => 'nullable|string',
            'status' => 'nullable|in:member,territory,disputed',
        ]);
        $country->update(array_filter($v, fn($v) => !is_null($v)));
        return response()->json(['data' => $country->fresh()]);
    }

    public function listRegions() {
        return response()->json(['data' => Region::withCount('countries')->get()]);
    }
}