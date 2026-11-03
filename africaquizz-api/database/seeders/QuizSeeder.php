<?php
namespace Database\Seeders;

use App\Models\Category;
use App\Models\Question;
use App\Models\Country;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate au lieu de create (évite le conflit avec CategorySeeder)
        $categories = [
            'histoire' => Category::firstOrCreate(['slug' => 'histoire']),
            'geographie' => Category::firstOrCreate(['slug' => 'geographie']),
            'culture' => Category::firstOrCreate(['slug' => 'culture']),
        ];

        $questions = [
            // === HISTOIRE ===
            [
                'ext' => 'BF-HIS-0001', 'cat' => 'histoire', 'diff' => 'connaisseur', 'time' => 20, 'country' => 'BF',
                'q_fr' => 'En quelle année la Haute-Volta a-t-elle pris le nom de Burkina Faso ?',
                'q_en' => 'In which year did Upper Volta take the name Burkina Faso?',
                'opts' => [
                    ['A', '1960', '1960', false],
                    ['B', '1984', '1984', true],
                    ['C', '1991', '1991', false],
                    ['D', '2000', '2000', false],
                ],
                'expl_fr' => 'Le changement de nom date de 1984, sous Thomas Sankara.',
                'expl_en' => 'The renaming took place in 1984, under Thomas Sankara.',
            ],
            [
                'ext' => 'ZA-HIS-0001', 'cat' => 'histoire', 'diff' => 'expert', 'time' => 25, 'country' => 'ZA',
                'q_fr' => 'En quelle année Nelson Mandela a-t-il été libéré de prison ?',
                'q_en' => 'In which year was Nelson Mandela released from prison?',
                'opts' => [
                    ['A', '1985', '1985', false],
                    ['B', '1990', '1990', true],
                    ['C', '1994', '1994', false],
                    ['D', '1999', '1999', false],
                ],
                'expl_fr' => 'Libéré le 11 février 1990 après 27 ans.',
                'expl_en' => 'Released on Feb 11, 1990 after 27 years.',
            ],
            [
                'ext' => 'GH-HIS-0001', 'cat' => 'histoire', 'diff' => 'connaisseur', 'time' => 20, 'country' => 'GH',
                'q_fr' => 'Quel pays a obtenu son indépendance en premier en Afrique subsaharienne ?',
                'q_en' => 'Which country first gained independence in sub-Saharan Africa?',
                'opts' => [
                    ['A', 'Ghana', 'Ghana', true],
                    ['B', 'Sénégal', 'Senegal', false],
                    ['C', 'Nigeria', 'Nigeria', false],
                    ['D', 'Kenya', 'Kenya', false],
                ],
                'expl_fr' => 'Le Ghana est indépendant en 1957, sous Kwame Nkrumah.',
                'expl_en' => 'Ghana became independent in 1957, under Kwame Nkrumah.',
            ],
            // === GÉOGRAPHIE ===
            [
                'ext' => 'ZA-GEO-0001', 'cat' => 'geographie', 'diff' => 'decouverte', 'time' => 20, 'country' => 'ZA',
                'q_fr' => 'Quelle est la capitale administrative de l\'Afrique du Sud ?',
                'q_en' => 'What is the administrative capital of South Africa?',
                'opts' => [
                    ['A', 'Johannesburg', 'Johannesburg', false],
                    ['B', 'Le Cap', 'Cape Town', false],
                    ['C', 'Pretoria', 'Pretoria', true],
                    ['D', 'Durban', 'Durban', false],
                ],
                'expl_fr' => 'Pretoria est la capitale administrative, Le Cap la législative.',
                'expl_en' => 'Pretoria is the administrative capital, Cape Town the legislative.',
            ],
            [
                'ext' => 'SN-GEO-0001', 'cat' => 'geographie', 'diff' => 'decouverte', 'time' => 20, 'country' => 'SN',
                'q_fr' => 'Quelle est la capitale du Sénégal ?',
                'q_en' => 'What is the capital of Senegal?',
                'opts' => [
                    ['A', 'Dakar', 'Dakar', true],
                    ['B', 'Thiès', 'Thiès', false],
                    ['C', 'Saint-Louis', 'Saint-Louis', false],
                    ['D', 'Touba', 'Touba', false],
                ],
                'expl_fr' => 'Dakar est la capitale du Sénégal depuis 1960.',
                'expl_en' => 'Dakar has been the capital since 1960.',
            ],
            [
                'ext' => 'CI-GEO-0001', 'cat' => 'geographie', 'diff' => 'connaisseur', 'time' => 20, 'country' => 'CI',
                'q_fr' => 'Quelle est la capitale politique de la Côte d\'Ivoire ?',
                'q_en' => 'What is the political capital of Ivory Coast?',
                'opts' => [
                    ['A', 'Abidjan', 'Abidjan', false],
                    ['B', 'Yamoussoukro', 'Yamoussoukro', true],
                    ['C', 'Bouaké', 'Bouaké', false],
                    ['D', 'Daloa', 'Daloa', false],
                ],
                'expl_fr' => 'Yamoussoukro est la capitale politique depuis 1983.',
                'expl_en' => 'Yamoussoukro has been the political capital since 1983.',
            ],
            [
                'ext' => 'EG-GEO-0001', 'cat' => 'geographie', 'diff' => 'decouverte', 'time' => 20, 'country' => 'EG',
                'q_fr' => 'Quel fleuve traverse l\'Égypte ?',
                'q_en' => 'Which river flows through Egypt?',
                'opts' => [
                    ['A', 'Le Congo', 'The Congo', false],
                    ['B', 'Le Niger', 'The Niger', false],
                    ['C', 'Le Nil', 'The Nile', true],
                    ['D', 'Le Zambèze', 'The Zambezi', false],
                ],
                'expl_fr' => 'Le Nil traverse l\'Égypte du sud au nord.',
                'expl_en' => 'The Nile flows through Egypt from south to north.',
            ],
            // === CULTURE ===
            [
                'ext' => 'NG-CUL-0001', 'cat' => 'culture', 'diff' => 'connaisseur', 'time' => 20, 'country' => 'NG',
                'q_fr' => 'Quel est le nom du célèbre écrivain nigérian, auteur de "Things Fall Apart" ?',
                'q_en' => 'Who is the famous Nigerian writer, author of "Things Fall Apart"?',
                'opts' => [
                    ['A', 'Wole Soyinka', 'Wole Soyinka', false],
                    ['B', 'Chinua Achebe', 'Chinua Achebe', true],
                    ['C', 'Chimamanda Ngozi Adichie', 'Chimamanda Ngozi Adichie', false],
                    ['D', 'Ben Okri', 'Ben Okri', false],
                ],
                'expl_fr' => 'Chinua Achebe publie "Things Fall Apart" en 1958.',
                'expl_en' => 'Chinua Achebe published "Things Fall Apart" in 1958.',
            ],
            [
                'ext' => 'ML-CUL-0001', 'cat' => 'culture', 'diff' => 'expert', 'time' => 25, 'country' => 'ML',
                'q_fr' => 'Quel empire historique est originaire de la région du Mandé ?',
                'q_en' => 'Which historical empire originated in the Mandé region?',
                'opts' => [
                    ['A', 'Empire du Ghana', 'Ghana Empire', false],
                    ['B', 'Empire du Mali', 'Mali Empire', true],
                    ['C', 'Empire Songhaï', 'Songhai Empire', false],
                    ['D', 'Empire du Kanem', 'Kanem Empire', false],
                ],
                'expl_fr' => 'L\'Empire du Mali (XIIIe-XVIe siècle) est né au Mandé.',
                'expl_en' => 'The Mali Empire (13th-16th century) was born in Mandé.',
            ],
            [
                'ext' => 'ET-CUL-0001', 'cat' => 'culture', 'diff' => 'connaisseur', 'time' => 20, 'country' => 'ET',
                'q_fr' => 'Quelle est la langue officielle de l\'Éthiopie ?',
                'q_en' => 'What is the official language of Ethiopia?',
                'opts' => [
                    ['A', 'Arabe', 'Arabic', false],
                    ['B', 'Amharique', 'Amharic', true],
                    ['C', 'Swahili', 'Swahili', false],
                    ['D', 'Anglais', 'English', false],
                ],
                'expl_fr' => 'L\'amharique est la langue de travail officielle.',
                'expl_en' => 'Amharic is the official working language.',
            ],
        ];

        $created = 0;
        foreach ($questions as $q) {
            // Skip si existe déjà
            if (Question::where('external_id', $q['ext'])->exists()) continue;

            $question = Question::create([
                'category_id' => $categories[$q['cat']]->id,
                'type' => 'mcq',
                'difficulty' => $q['diff'],
                'time_limit_s' => $q['time'],
                'is_sensitive' => false,
                'status' => 'PUBLIEE',
                'external_id' => $q['ext'],
            ]);

            $question->translations()->createMany([
                ['locale' => 'fr', 'text' => $q['q_fr'], 'explanation' => $q['expl_fr']],
                ['locale' => 'en', 'text' => $q['q_en'], 'explanation' => $q['expl_en']],
            ]);

            foreach ($q['opts'] as $opt) {
                $option = $question->options()->create([
                    'option_key' => $opt[0],
                    'is_correct' => $opt[3],
                ]);
                $option->translations()->createMany([
                    ['locale' => 'fr', 'text' => $opt[1]],
                    ['locale' => 'en', 'text' => $opt[2]],
                ]);
            }

            // Pays
            if ($q['country']) {
                $country = Country::where('iso2', $q['country'])->first();
                if ($country) $question->countries()->attach($country->id);
            }

            // Source
            $question->sources()->create([
                'source_type' => 'level_1',
                'title' => 'Source Wikipedia',
                'url' => 'https://fr.wikipedia.org',
            ]);

            $created++;
        }

        $this->command->info("QuizSeeder : $created questions créées (total base: " . Question::count() . ")");
    }
}