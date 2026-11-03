<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Country;
use App\Models\OptionTranslation;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Importe la banque de questions depuis database/data/questions.json
 * (extraite du backup PostgreSQL du 2026-10-26). Rejouable sans doublons :
 * chaque question est identifiée par son external_id.
 */
class QuestionBankSeeder extends Seeder
{
    public function run(): void
    {
        $questions = json_decode(
            file_get_contents(database_path('data/questions.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $categories = [];
        $countries = Country::pluck('id', 'iso2');
        $missingCountries = [];

        foreach ($questions as $questionData) {
            DB::transaction(function () use ($questionData, &$categories, $countries, &$missingCountries) {
                $categories[$questionData['category']] ??= Category::firstOrCreate(['slug' => $questionData['category']]);

                $question = Question::updateOrCreate(
                    ['external_id' => $questionData['external_id']],
                    [
                        'category_id' => $categories[$questionData['category']]->id,
                        'type' => $questionData['type'],
                        'difficulty' => $questionData['difficulty'],
                        'time_limit_s' => $questionData['time_limit_s'],
                        'is_sensitive' => $questionData['is_sensitive'],
                        'status' => $questionData['status'],
                    ],
                );

                foreach ($questionData['translations'] as $locale => $translation) {
                    QuestionTranslation::updateOrCreate(
                        ['question_id' => $question->id, 'locale' => $locale],
                        $translation,
                    );
                }

                foreach ($questionData['options'] as $optionData) {
                    $option = QuestionOption::updateOrCreate(
                        ['question_id' => $question->id, 'option_key' => $optionData['key']],
                        ['is_correct' => $optionData['is_correct']],
                    );

                    foreach ($optionData['text'] as $locale => $text) {
                        OptionTranslation::updateOrCreate(
                            ['option_id' => $option->id, 'locale' => $locale],
                            ['text' => $text],
                        );
                    }
                }

                $countryIds = [];
                foreach ($questionData['countries'] as $iso2) {
                    if (isset($countries[$iso2])) {
                        $countryIds[] = $countries[$iso2];
                    } else {
                        $missingCountries[$iso2] = true;
                    }
                }
                $question->countries()->sync($countryIds);

                $question->sources()->delete();
                $question->sources()->createMany($questionData['sources']);
            });
        }

        $this->command->info('QuestionBankSeeder : ' . count($questions) . ' questions importées (total base: ' . Question::count() . ').');

        if ($missingCountries) {
            $this->command->warn('Pays absents (lance CountrySeeder) : ' . implode(', ', array_keys($missingCountries)));
        }
    }
}
