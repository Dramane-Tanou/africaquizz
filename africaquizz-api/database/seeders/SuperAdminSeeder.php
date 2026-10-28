<?php
namespace Database\Seeders;
use App\Models\AdminUser;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('AFRICAQUIZZ_SUPERADMIN_PASSWORD');
        if (!is_string($password) || $password === '') {
            throw new \RuntimeException('Définissez AFRICAQUIZZ_SUPERADMIN_PASSWORD dans le fichier .env local.');
        }

        $role = Role::where('name', 'super_admin')->first();
        if (!$role) {
            $this->command->error('Rôle super_admin manquant !');
            return;
        }

        AdminUser::updateOrCreate(
            ['email' => 'superadmin@africaquizz.com'],
            [
                'role_id' => $role->id,
                'name' => 'Super Admin',
                'password' => Hash::make($password),
            ]
        );

        $this->command->info('SuperAdmin OK : superadmin@africaquizz.com');
    }
}