<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\OptionTranslation;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionTranslation;
use Illuminate\Database\Seeder;

class DemoQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(['slug' => 'demo-local']);

        $questions = [
            [
                'external_id' => 'demo-local-001',
                'text' => [
                    'fr' => 'Quelle ville est la capitale du Sénégal ?',
                    'en' => 'Which city is the capital of Senegal?',
                ],
                'options' => [
                    'A' => ['fr' => 'Dakar', 'en' => 'Dakar', 'correct' => true],
                    'B' => ['fr' => 'Bamako', 'en' => 'Bamako', 'correct' => false],
                    'C' => ['fr' => 'Conakry', 'en' => 'Conakry', 'correct' => false],
                    'D' => ['fr' => 'Accra', 'en' => 'Accra', 'correct' => false],
                ],
            ],
            [
                'external_id' => 'demo-local-002',
                'text' => [
                    'fr' => 'Dans quel pays se trouve le mont Kilimandjaro ?',
                    'en' => 'In which country is Mount Kilimanjaro?',
                ],
                'options' => [
                    'A' => ['fr' => 'Kenya', 'en' => 'Kenya', 'correct' => false],
                    'B' => ['fr' => 'Tanzanie', 'en' => 'Tanzania', 'correct' => true],
                    'C' => ['fr' => 'Ouganda', 'en' => 'Uganda', 'correct' => false],
                    'D' => ['fr' => 'Rwanda', 'en' => 'Rwanda', 'correct' => false],
                ],
            ],
            [
                'external_id' => 'demo-local-003',
                'text' => [
                    'fr' => 'En quelle année le Ghana a-t-il obtenu son indépendance ?',
                    'en' => 'In which year did Ghana gain independence?',
                ],
                'options' => [
                    'A' => ['fr' => '1945', 'en' => '1945', 'correct' => false],
                    'B' => ['fr' => '1957', 'en' => '1957', 'correct' => true],
                    'C' => ['fr' => '1960', 'en' => '1960', 'correct' => false],
                    'D' => ['fr' => '1963', 'en' => '1963', 'correct' => false],
                ],
            ],
            [
                'external_id' => 'demo-local-004',
                'text' => [
                    'fr' => 'Quel grand lac est partagé par le Kenya, la Tanzanie et l’Ouganda ?',
                    'en' => 'Which large lake is shared by Kenya, Tanzania, and Uganda?',
                ],
                'options' => [
                    'A' => ['fr' => 'Le lac Tchad', 'en' => 'Lake Chad', 'correct' => false],
                    'B' => ['fr' => 'Le lac Tanganyika', 'en' => 'Lake Tanganyika', 'correct' => false],
                    'C' => ['fr' => 'Le lac Victoria', 'en' => 'Lake Victoria', 'correct' => true],
                    'D' => ['fr' => 'Le lac Malawi', 'en' => 'Lake Malawi', 'correct' => false],
                ],
            ],
            [
                'external_id' => 'demo-local-005',
                'text' => [
                    'fr' => 'Quelle langue est une lingua franca largement parlée en Afrique de l’Est ?',
                    'en' => 'Which language is a widely spoken lingua franca in East Africa?',
                ],
                'options' => [
                    'A' => ['fr' => 'L’amharique', 'en' => 'Amharic', 'correct' => false],
                    'B' => ['fr' => 'Le swahili', 'en' => 'Swahili', 'correct' => true],
                    'C' => ['fr' => 'Le wolof', 'en' => 'Wolof', 'correct' => false],
                    'D' => ['fr' => 'Le berbère', 'en' => 'Berber', 'correct' => false],
                ],
            ],
        ];

        foreach ($questions as $questionData) {
            $question = Question::updateOrCreate(
                ['external_id' => $questionData['external_id']],
                [
                    'category_id' => $category->id,
                    'type' => 'mcq',
                    'difficulty' => 'decouverte',
                    'time_limit_s' => 20,
                    'status' => 'PUBLIEE',
                ],
            );

            foreach ($questionData['text'] as $locale => $text) {
                QuestionTranslation::updateOrCreate(
                    ['question_id' => $question->id, 'locale' => $locale],
                    ['text' => $text],
                );
            }

            foreach ($questionData['options'] as $key => $optionData) {
                $option = QuestionOption::updateOrCreate(
                    ['question_id' => $question->id, 'option_key' => $key],
                    ['is_correct' => $optionData['correct']],
                );

                foreach (['fr', 'en'] as $locale) {
                    OptionTranslation::updateOrCreate(
                        ['option_id' => $option->id, 'locale' => $locale],
                        ['text' => $optionData[$locale]],
                    );
                }
            }
        }
    }
}
