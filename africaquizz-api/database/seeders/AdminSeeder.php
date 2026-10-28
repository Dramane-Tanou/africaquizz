<?php
namespace Database\Seeders;
use App\Models\AdminUser;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $passwordEnvironment = [
            'admin' => 'AFRICAQUIZZ_ADMIN_PASSWORD',
            'relecteur' => 'AFRICAQUIZZ_REVIEWER_PASSWORD',
            'editeur' => 'AFRICAQUIZZ_EDITOR_PASSWORD',
            'redacteur' => 'AFRICAQUIZZ_AUTHOR_PASSWORD',
        ];
        $passwords = array_map(fn (string $variable) => env($variable), $passwordEnvironment);

        foreach ($passwords as $role => $password) {
            if (!is_string($password) || $password === '') {
                throw new \RuntimeException("Définissez {$passwordEnvironment[$role]} dans le fichier .env local.");
            }
        }

        $adminRole = Role::where('name', 'admin')->first();
        if (!$adminRole) {
            $this->command->error('Rôle admin introuvable. Lance RoleSeeder d\'abord.');
            return;
        }

        AdminUser::updateOrCreate(
            ['email' => 'admin@africaquizz.com'],
            [
                'name' => 'Admin Principal',
                'password' => Hash::make($passwords['admin']),
                'role_id' => $adminRole->id,
            ]
        );

        // Créer aussi un relecteur et un éditeur pour tester
        $relecteurRole = Role::where('name', 'relecteur')->first();
        AdminUser::updateOrCreate(
            ['email' => 'relecteur@africaquizz.com'],
            ['name' => 'Relecteur Test', 'password' => Hash::make($passwords['relecteur']), 'role_id' => $relecteurRole->id]
        );

        $editeurRole = Role::where('name', 'editeur')->first();
        AdminUser::updateOrCreate(
            ['email' => 'editeur@africaquizz.com'],
            ['name' => 'Éditeur Test', 'password' => Hash::make($passwords['editeur']), 'role_id' => $editeurRole->id]
        );

        $redacteurRole = Role::where('name', 'redacteur')->first();
        AdminUser::updateOrCreate(
            ['email' => 'redacteur@africaquizz.com'],
            ['name' => 'Rédacteur Test', 'password' => Hash::make($passwords['redacteur']), 'role_id' => $redacteurRole->id]
        );

        $this->command->info('AdminSeeder : 4 admins créés (admin, relecteur, editeur, redacteur).');
        $this->command->info('Configurez les mots de passe dans le fichier .env local avant de vous connecter.');
    }
}