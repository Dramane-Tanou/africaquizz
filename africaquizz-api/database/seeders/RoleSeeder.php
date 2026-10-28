<?php
namespace Database\Seeders;
use App\Models\Role;
use Illuminate\Database\Seeder;
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'redacteur', 'label_fr' => 'Rédacteur', 'label_en' => 'Editor'],
            ['name' => 'relecteur', 'label_fr' => 'Relecteur', 'label_en' => 'Reviewer'],
            ['name' => 'editeur', 'label_fr' => 'Éditeur', 'label_en' => 'Publisher'],
            ['name' => 'admin', 'label_fr' => 'Administrateur', 'label_en' => 'Administrator'],
            ['name' => 'super_admin', 'label_fr' => 'Super-Administrateur', 'label_en' => 'Super-Administrator'],
        ];
        foreach ($roles as $r) {
            Role::updateOrCreate(['name' => $r['name']], $r);
        }
        $this->command->info('RoleSeeder : 5 rôles créés.');
    }
}