<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Models\ImportRow;
use App\Services\QuestionImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function __construct(private QuestionImportService $importService) {}

    // 1. Upload + validation (preview)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240|mimes:csv,txt,json,xlsx,xls',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'xlsm') {
            return response()->json(['message' => 'Format XLSX non supportÃƒÂ© pour le MVP. Utilisez CSV ou JSON.'], 422);
        }

        // Stockage
        $storedName = Str::uuid() . '.' . $extension;
        $storedPath = $file->storeAs('imports', $storedName);

        // CrÃƒÆ’Ã‚Â©ation de l'Import
        // === CORRECTION : admin vs user ===
        $authUser = $request->user('sanctum');
        $userId = null;
        $adminId = null;
        if ($authUser instanceof \App\Models\User) {
            $userId = $authUser->id;
        } elseif ($authUser instanceof \App\Models\AdminUser) {
            $adminId = $authUser->id;
        }

        $import = Import::create([
            'user_id' => $userId,
            'uploaded_by_admin_id' => $adminId,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'status' => 'PARSING',
        ]);

        try {
            $totals = $this->importService->processImport($import);
        } catch (\Exception $e) {
            $import->update([
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Erreur lors du parsing',
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => [
                'id' => $import->id,
                'status' => $import->status,
                'totals' => $totals,
                'message' => 'Fichier parsÃƒÆ’Ã‚Â©. Utilisez POST /admin/imports/{id}/confirm pour importer les lignes valides.',
            ]
        ], 201);
    }

    // 2. Statut + preview
    public function show($id)
    {
        $import = Import::with('rows')->findOrFail($id);

        $preview = $import->rows()
            ->orderBy('row_number')
            ->limit(50)
            ->get(['row_number', 'status', 'errors', 'question_id']);

        return response()->json([
            'data' => [
                'id' => $import->id,
                'original_filename' => $import->original_filename,
                'status' => $import->status,
                'totals' => $import->totals,
                'error_message' => $import->error_message,
                'created_at' => $import->created_at->toIso8601String(),
                'completed_at' => $import->completed_at?->toIso8601String(),
                'preview' => $preview,
            ]
        ]);
    }

    // 3. Confirmer l'import (crÃƒÆ’Ã‚Â©er les questions en BROUILLON)
    public function confirm($id)
    {
        $import = Import::findOrFail($id);

        if ($import->status !== 'VALIDATED') {
            return response()->json(['message' => "L'import doit ÃƒÆ’Ã‚Âªtre en statut VALIDATED (actuel : {$import->status})"], 422);
        }

        $result = $this->importService->importValidRows($import);

        return response()->json([
            'data' => [
                'id' => $import->id,
                'status' => $import->status,
                'questions_created' => $result['created'],
                'questions_updated' => $result['updated'],
                'message' => "{$result['created']} questions crÃƒÆ’Ã‚Â©ÃƒÆ’Ã‚Â©es en BROUILLON. {$result['updated']} nouvelles versions crÃƒÆ’Ã‚Â©ÃƒÆ’Ã‚Â©es.",
            ]
        ]);
    }

    // 4. Annuler (archiver les brouillons)
    public function cancel($id)
    {
        $import = Import::findOrFail($id);
        $archived = $this->importService->cancelImport($import);

        return response()->json([
            'data' => [
                'id' => $import->id,
                'status' => 'CANCELLED',
                'questions_archived' => $archived,
            ]
        ]);
    }

    // 5. Liste des imports
    public function index(Request $request)
    {
        $imports = Import::with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'user_id', 'original_filename', 'status', 'totals', 'created_at']);

        return response()->json(['data' => $imports]);
    }

    // 6. Rapport d'erreurs tÃƒÆ’Ã‚Â©lÃƒÆ’Ã‚Â©chargeable
    public function errorsReport($id)
    {
        $import = Import::findOrFail($id);
        $errors = $import->rows()->where('status', 'ERREUR')->orderBy('row_number')->get();

        $csv = "row_number;status;errors\n";
        foreach ($errors as $e) {
            $errMsg = is_array($e->errors) ? implode(' | ', $e->errors) : '';
            $csv .= $e->row_number . ';' . $e->status . ';"' . str_replace('"', '""', $errMsg) . "\"\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"import_{$id}_errors.csv\"",
        ]);
    }
}