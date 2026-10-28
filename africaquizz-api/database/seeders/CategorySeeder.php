<?php
namespace Database\Seeders;
use App\Models\Category;
use Illuminate\Database\Seeder;
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $slugs = [
            'histoire', 'geographie', 'culture', 'personnalites', 'sport',
            'economie', 'sciences', 'tech', 'arts-musique', 'societe',
            'sante', 'afrique-monde'
        ];
        foreach ($slugs as $slug) {
            Category::firstOrCreate(['slug' => $slug]);
        }
        $this->command->info('CategorySeeder : ' . count($slugs) . ' catégories disponibles.');
    }
}