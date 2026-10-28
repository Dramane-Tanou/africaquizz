<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GuideAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $accounts = [
            [
                'email' => 'superadmin@africaquizz.com',
                'name' => 'Super Admin',
                'role' => 'super_admin',
                'password_env' => 'AFRICAQUIZZ_SUPERADMIN_PASSWORD',
            ],
            [
                'email' => 'admin@africaquizz.com',
                'name' => 'Admin Principal',
                'role' => 'admin',
                'password_env' => 'AFRICAQUIZZ_ADMIN_PASSWORD',
            ],
            [
                'email' => 'relecteur@africaquizz.com',
                'name' => 'Relecteur Test',
                'role' => 'relecteur',
                'password_env' => 'AFRICAQUIZZ_REVIEWER_PASSWORD',
            ],
            [
                'email' => 'editeur@africaquizz.com',
                'name' => 'Éditeur Test',
                'role' => 'editeur',
                'password_env' => 'AFRICAQUIZZ_EDITOR_PASSWORD',
            ],
        ];

        foreach ($accounts as $account) {
            $password = env($account['password_env']);
            if (!is_string($password) || $password === '') {
                throw new \RuntimeException("Définissez {$account['password_env']} dans l'environnement local.");
            }

            $role = Role::where('name', $account['role'])->firstOrFail();
            AdminUser::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role_id' => $role->id,
                    'password' => Hash::make($password),
                ]
            );
        }

        $this->command->info('GuideAccountsSeeder : 4 comptes de test créés ou mis à jour.');
    }
}
