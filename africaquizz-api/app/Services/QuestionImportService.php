<?php

namespace App\Services;

use App\Models\Import;
use App\Models\ImportRow;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionTranslation;
use App\Models\OptionTranslation;
use App\Models\QuestionSource;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuestionImportService
{
    const MAX_ROWS = 5000;
    const BATCH_SIZE = 500;
    const SIMILARITY_THRESHOLD = 0.75;

    public function parseFile(string $path, string $extension): array
    {
        return match(strtolower($extension)) {
            'csv', 'txt' => $this->parseCsv($path),
            'json' => $this->parseJson($path),
            default => throw new \Exception("Format non supportÃƒÂ© : $extension. Formats acceptÃƒÂ©s : CSV, JSON (XLSX en v1.1)"),
        };
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        if (!$handle) throw new \Exception("Impossible d'ouvrir le fichier CSV");

        // DÃƒÂ©tecte BOM
        $bom = fread($handle, 3);
        $hasBom = ($bom === "\xEF\xBB\xBF");
        if (!$hasBom) rewind($handle);

        // DÃƒÂ©tecte sÃƒÂ©parateur
        $firstLine = fgets($handle);
        rewind($handle);
        if ($hasBom) fread($handle, 3);
        $separator = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        $headers = fgetcsv($handle, 0, $separator);
        if (!$headers) throw new \Exception("CSV vide ou invalide");
        $headers = array_map('trim', $headers);

        $rowNumber = 1;
        while (($data = fgetcsv($handle, 0, $separator)) !== false) {
            $rowNumber++;
            if (count($data) !== count($headers)) continue;
            $row = [];
            foreach ($headers as $i => $h) {
                $row[$h] = $data[$i] ?? null;
            }
            $rows[] = ['row_number' => $rowNumber, 'data' => $row];
            if (count($rows) > self::MAX_ROWS) {
                fclose($handle);
                throw new \Exception("Le fichier dÃƒÂ©passe " . self::MAX_ROWS . " lignes");
            }
        }
        fclose($handle);
        return $rows;
    }

    private function parseJson(string $path): array
    {
        $content = file_get_contents($path);
        $data = json_decode($content, true);
        if (!is_array($data)) throw new \Exception("JSON invalide");

        if (isset($data['data']) && is_array($data['data'])) $data = $data['data'];
        if (count($data) > self::MAX_ROWS) throw new \Exception("Le fichier dÃƒÂ©passe " . self::MAX_ROWS . " lignes");

        $rows = [];
        foreach ($data as $i => $row) {
            $rows[] = ['row_number' => $i + 1, 'data' => $row];
        }
        return $rows;
    }

    
    private function parseXlsx(string $path): array
    {
        $spreadsheet = SpreadsheetIO::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, false);

        if (empty($data)) throw new \Exception("XLSX vide");
        $headers = array_map('trim', array_shift($data));

        $rows = [];
        $rowNumber = 1;
        foreach ($data as $row) {
            $rowNumber++;
            if (empty(array_filter($row))) continue;
            if (count($row) !== count($headers)) continue;
            $assoc = [];
            foreach ($headers as $i => $h) $assoc[$h] = $row[$i] ?? null;
            $rows[] = ['row_number' => $rowNumber, 'data' => $assoc];
            if (count($rows) > self::MAX_ROWS) throw new \Exception("Le fichier dépasse " . self::MAX_ROWS . " lignes");
        }
        return $rows;
    }
    public function sanitize(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^[=+\-@]/', $value)) {
            $value = "'" . $value;
        }
        $value = strip_tags($value);
        if (class_exists('Normalizer')) {
            $value = \Normalizer::normalize($value, \Normalizer::FORM_C) ?: $value;
        }
        return preg_replace('/\s+/', ' ', $value);
    }

    public function validateRow(int $rowNumber, array $row): array
    {
        $errors = [];
        $data = [];

        foreach ($row as $k => $v) {
            $data[$k] = is_string($v) ? $this->sanitize($v) : $v;
        }

        $required = ['external_id', 'type', 'category', 'difficulty', 'question_fr', 'question_en', 'correct', 'explanation_fr', 'explanation_en', 'source_1_title', 'source_1_url'];
        foreach ($required as $field) {
            if (empty($data[$field] ?? null)) {
                $errors[] = "Champ obligatoire manquant : $field";
            }
        }

        $validTypes = ['mcq', 'true_false', 'image', 'matching', 'ordering', 'rapid_fire'];
        if (!empty($data['type']) && !in_array($data['type'], $validTypes)) {
            $errors[] = "Type invalide : {$data['type']}";
        }

        $validDiff = ['decouverte', 'connaisseur', 'expert', 'legende'];
        if (!empty($data['difficulty']) && !in_array($data['difficulty'], $validDiff)) {
            $errors[] = "DifficultÃƒÂ© invalide : {$data['difficulty']}";
        }

        if (!empty($data['category'])) {
            $cat = Category::where('slug', $data['category'])->first();
            if (!$cat) {
                $errors[] = "CatÃƒÂ©gorie inconnue : {$data['category']}";
            } else {
                $data['category_id'] = $cat->id;
            }
        }

        if (($data['type'] ?? '') === 'mcq') {
            $optionKeys = ['A', 'B', 'C', 'D'];
            $correct = strtoupper($data['correct'] ?? '');
            if (!in_array($correct, $optionKeys)) {
                $errors[] = "correct doit ÃƒÂªtre A, B, C ou D";
            }
            foreach ($optionKeys as $k) {
                foreach (['fr', 'en'] as $lang) {
                    $field = "option_" . strtolower($k) . "_$lang";
                    if (empty($data[$field] ?? null)) {
                        $errors[] = "Champ obligatoire manquant : $field";
                    }
                }
            }
            $uniqueFr = array_unique([$data['option_a_fr'] ?? '', $data['option_b_fr'] ?? '', $data['option_c_fr'] ?? '', $data['option_d_fr'] ?? '']);
            if (count($uniqueFr) < 4) {
                $errors[] = "Les 4 options FR doivent ÃƒÂªtre distinctes";
            }
        }

        if (!empty($data['source_1_url']) && !filter_var($data['source_1_url'], FILTER_VALIDATE_URL)) {
            $errors[] = "URL source_1 invalide";
        }
        if (!empty($data['source_2_url']) && !filter_var($data['source_2_url'], FILTER_VALIDATE_URL)) {
            $errors[] = "URL source_2 invalide";
        }

        if (in_array($data['difficulty'] ?? '', ['expert', 'legende']) && empty($data['source_2_url'])) {
            $errors[] = "Deux sources requises pour la difficultÃƒÂ© {$data['difficulty']}";
        }

        foreach (['explanation_fr', 'explanation_en'] as $f) {
            if (!empty($data[$f]) && mb_strlen($data[$f]) > 300) {
                $errors[] = "$f dÃƒÂ©passe 300 caractÃƒÂ¨res (" . mb_strlen($data[$f]) . ")";
            }
        }

        $warnings = [];
        if (!empty($data['external_id'])) {
            $existing = Question::where('external_id', $data['external_id'])->first();
            if ($existing) {
                $warnings[] = "external_id dÃƒÂ©jÃƒÂ  existant : une nouvelle version sera crÃƒÂ©ÃƒÂ©e";
            }
        }

        $duplicate = QuestionTranslation::where('locale', 'fr')
            ->where('text', $data['question_fr'])
            ->first();
        if ($duplicate && empty($warnings)) {
            $warnings[] = "Question identique dÃƒÂ©jÃƒÂ  existante";
        }

        if (!empty($data['question_fr'])) {
            try {
                $similar = DB::table('question_translations')
                    ->where('locale', 'fr')
                    ->whereRaw("similarity(text, ?) >= ?", [$data['question_fr'], self::SIMILARITY_THRESHOLD])
                    ->count();
                if ($similar > 0 && empty($warnings)) {
                    $warnings[] = "Question similaire ÃƒÂ  $similar question(s) existante(s)";
                }
            } catch (\Exception $e) {
                // pg_trgm peut ne pas ÃƒÂªtre prÃƒÂªt, on ignore
            }
        }

        $status = 'VALIDE';
        if (!empty($errors)) $status = 'ERREUR';
        elseif (!empty($warnings)) $status = 'AVERTISSEMENT';

        return [
            'status' => $status,
            'errors' => array_merge($errors, $warnings),
            'normalized' => $data,
        ];
    }

    public function processImport(Import $import): array
    {
        $extension = pathinfo($import->original_filename, PATHINFO_EXTENSION);
        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($import->stored_path);
        $rows = $this->parseFile($fullPath, $extension);

        $totals = ['total' => 0, 'valides' => 0, 'avertissements' => 0, 'erreurs' => 0];
        foreach ($rows as $r) {
            $result = $this->validateRow($r['row_number'], $r['data']);
            ImportRow::create([
                'import_id' => $import->id,
                'row_number' => $r['row_number'],
                'raw_data' => $r['data'],
                'normalized_data' => $result['normalized'],
                'errors' => $result['errors'],
                'status' => $result['status'],
            ]);
            $totals['total']++;
            if ($result['status'] === 'VALIDE') $totals['valides']++;
            elseif ($result['status'] === 'AVERTISSEMENT') $totals['avertissements']++;
            else $totals['erreurs']++;
        }

        $import->update([
            'status' => 'VALIDATED',
            'totals' => $totals,
        ]);
        return $totals;
    }

    public function importValidRows(Import $import): array
    {
        $query = ImportRow::where('import_id', $import->id)
            ->whereIn('status', ['VALIDE', 'AVERTISSEMENT'])
            ->whereNull('question_id');

        $created = 0;
        $updated = 0;

        $query->chunkById(self::BATCH_SIZE, function ($rows) use (&$created, &$updated) {
            DB::transaction(function () use ($rows, &$created, &$updated) {
                foreach ($rows as $row) {
                    $result = $this->createQuestionFromRow($row);
                    if ($result['action'] === 'created') $created++;
                    else $updated++;
                    $row->update(['question_id' => $result['question_id']]);
                }
            });
        });

        $import->update([
            'status' => 'IMPORTED',
            'completed_at' => now(),
        ]);
        return ['created' => $created, 'updated' => $updated];
    }

    private function createQuestionFromRow(ImportRow $row): array
    {
        $data = $row->normalized_data;

        $question = Question::where('external_id', $data['external_id'])->first();
        $action = 'created';

        if ($question) {
            $question->update(['status' => 'ARCHIVEE']);
            $question = null;
            $action = 'updated';
        }

        $question = Question::create([
            'category_id' => $data['category_id'],
            'type' => $data['type'],
            'difficulty' => $data['difficulty'],
            'time_limit_s' => match($data['difficulty']) {
                'decouverte' => 20, 'connaisseur' => 20, 'expert' => 25, 'legende' => 30, default => 20
            },
            'is_sensitive' => filter_var($data['is_sensitive'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'status' => 'BROUILLON',
            'external_id' => $data['external_id'],
        ]);

        foreach (['fr', 'en'] as $lang) {
            QuestionTranslation::create([
                'question_id' => $question->id,
                'locale' => $lang,
                'text' => $data["question_$lang"],
                'explanation' => $data["explanation_$lang"] ?? null,
                'key_takeaway' => $data["key_takeaway_$lang"] ?? null,
            ]);
        }

        if ($data['type'] === 'mcq') {
            $correct = strtoupper($data['correct']);
            foreach (['A', 'B', 'C', 'D'] as $key) {
                $option = QuestionOption::create([
                    'question_id' => $question->id,
                    'option_key' => $key,
                    'is_correct' => ($key === $correct),
                ]);
                foreach (['fr', 'en'] as $lang) {
                    $field = 'option_' . strtolower($key) . "_$lang";
                    OptionTranslation::create([
                        'option_id' => $option->id,
                        'locale' => $lang,
                        'text' => $data[$field],
                    ]);
                }
            }
        }

        if (!empty($data['source_1_url'])) {
            QuestionSource::create([
                'question_id' => $question->id,
                'source_type' => 'level_1',
                'title' => $data['source_1_title'] ?? '',
                'url' => $data['source_1_url'],
            ]);
        }
        if (!empty($data['source_2_url'])) {
            QuestionSource::create([
                'question_id' => $question->id,
                'source_type' => 'level_2',
                'title' => $data['source_2_title'] ?? '',
                'url' => $data['source_2_url'],
            ]);
        }

        if (!empty($data['countries'])) {
            $isoCodes = array_filter(array_map('trim', explode('/', $data['countries'])));
            $countryIds = Country::whereIn('iso2', $isoCodes)->pluck('id')->toArray();
            if (!empty($countryIds)) {
                $question->countries()->attach($countryIds);
            }
        }

        return ['action' => $action, 'question_id' => $question->id];
    }

    public function cancelImport(Import $import): int
    {
        $questionIds = ImportRow::where('import_id', $import->id)
            ->whereNotNull('question_id')
            ->pluck('question_id')
            ->toArray();

        if (!empty($questionIds)) {
            Question::whereIn('id', $questionIds)
                ->where('status', 'BROUILLON')
                ->update(['status' => 'ARCHIVEE']);
        }

        $import->update(['status' => 'CANCELLED']);
        return count($questionIds);
    }
}