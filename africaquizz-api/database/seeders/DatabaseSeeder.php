<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Référentiels (idempotents, toujours)
        $this->call(RoleSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(CountrySeeder::class);   // 250 pays (ISO-2/ISO-3)
        $this->call(BadgeSeeder::class);     // 8 badges

        // 2. Contenu de jeu
        //    QuestionBankSeeder : 1769 questions depuis database/data/questions.json
        //    (production-ready, import unique — voir database/data/)
        $this->call(QuestionBankSeeder::class);

        // 3. Contenu de démo (local + testing uniquement)
        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoQuestionsSeeder::class);   // 5 questions minimalistes pour tests
            $this->call(DailyChallengeSeeder::class);  // défi du jour avec 10 questions
        }

        // 4. Comptes admin — volontairement NON chaînés ici
        //    Ils exigent AFRICAQUIZZ_*_PASSWORD dans .env et crashent sinon.
        //    À lancer manuellement :
        //      php artisan db:seed --class=GuideAccountsSeeder --force
    }
}