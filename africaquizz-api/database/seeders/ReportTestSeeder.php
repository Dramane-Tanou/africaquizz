<?php
namespace Database\Seeders;

use App\Models\Question;
use App\Models\Category;
use App\Models\Country;
use Illuminate\Database\Seeder;

class ReportTestSeeder extends Seeder
{
    public function run(): void
    {
        $cat = Category::where('slug', 'histoire')->first() ?? Category::first();
        if (!$cat) {
            $this->command->error('Aucune catégorie. Lance CategorySeeder.');
            return;
        }

        $externalId = 'REPORT-TEST-001';

        // Si déjà existante, on reset à PUBLIEE
        $existing = Question::where('external_id', $externalId)->first();
        if ($existing) {
            $existing->update([
                'status' => 'PUBLIEE',
                'pending_reports_count' => 0,
                'last_reported_at' => null,
            ]);
            // Supprimer les anciens reports
            \DB::table('question_reports')->where('question_id', $existing->id)->delete();
            $this->command->info("Question existante reset à PUBLIEE : {$existing->id}");
            return;
        }

        // Créer la question complète
        $q = Question::create([
            'category_id' => $cat->id,
            'type' => 'mcq',
            'difficulty' => 'decouverte',
            'time_limit_s' => 20,
            'is_sensitive' => false,
            'status' => 'PUBLIEE',
            'external_id' => $externalId,
        ]);

        // Traductions
        $q->translations()->createMany([
            ['locale' => 'fr', 'text' => 'Quelle est la capitale de la Côte d\'Ivoire ?', 'explanation' => 'Yamoussoukro est la capitale politique.', 'key_takeaway' => 'Yamoussoukro, pas Abidjan.'],
            ['locale' => 'en', 'text' => 'What is the capital of Ivory Coast?', 'explanation' => 'Yamoussoukro is the political capital.', 'key_takeaway' => 'Yamoussoukro, not Abidjan.'],
        ]);

        // Options
        $opts = [
            ['key' => 'A', 'fr' => 'Abidjan', 'en' => 'Abidjan', 'correct' => false],
            ['key' => 'B', 'fr' => 'Yamoussoukro', 'en' => 'Yamoussoukro', 'correct' => true],
            ['key' => 'C', 'fr' => 'Bouaké', 'en' => 'Bouaké', 'correct' => false],
            ['key' => 'D', 'fr' => 'Daloa', 'en' => 'Daloa', 'correct' => false],
        ];
        foreach ($opts as $o) {
            $opt = $q->options()->create(['option_key' => $o['key'], 'is_correct' => $o['correct']]);
            $opt->translations()->createMany([
                ['locale' => 'fr', 'text' => $o['fr']],
                ['locale' => 'en', 'text' => $o['en']],
            ]);
        }

        // Source
        $q->sources()->create(['source_type' => 'level_1', 'title' => 'Source test', 'url' => 'https://example.org']);

        // Pays
        $ci = Country::where('iso2', 'CI')->first();
        if ($ci) $q->countries()->attach($ci->id);

        $this->command->info("Question REPORT-TEST-001 créée : {$q->id}");
    }
}