<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_admin_can_sign_in_without_two_factor_authentication(): void
    {
        $role = Role::create([
            'name' => 'super_admin',
            'label_fr' => 'Super-Administrateur',
            'label_en' => 'Super-Administrator',
        ]);
        AdminUser::create([
            'role_id' => $role->id,
            'name' => 'Local Admin',
            'email' => 'local-admin@example.test',
            'password' => 'local-admin-password',
        ]);

        config(['app.env' => 'local']);
        config(['auth.admin_2fa_bypass' => true]);
        $this->postJson('/api/v1/admin/auth/login', [
                'email' => 'local-admin@example.test',
                'password' => 'local-admin-password',
            ])
            ->assertOk()
            ->assertJsonPath('data.admin.email', 'local-admin@example.test')
            ->assertJsonPath('data.warning', '2FA bypass activé (dev uniquement)')
            ->assertJsonStructure(['data' => ['token', 'admin']])
            ->assertJsonMissingPath('data.temp_token');
    }

    public function test_two_factor_bypass_is_ignored_outside_local_environments(): void
    {
        $role = Role::create([
            'name' => 'admin',
            'label_fr' => 'Administrateur',
            'label_en' => 'Administrator',
        ]);
        AdminUser::create([
            'role_id' => $role->id,
            'name' => 'Production Admin',
            'email' => 'production-admin@example.test',
            'password' => 'local-admin-password',
        ]);

        $this->app->detectEnvironment(fn () => 'production');
        config(['auth.admin_2fa_bypass' => true]);
        $this->postJson('/api/v1/admin/auth/login', [
                'email' => 'production-admin@example.test',
                'password' => 'local-admin-password',
            ])
            ->assertOk()
            ->assertJsonPath('data.requires_2fa_setup', true)
            ->assertJsonMissingPath('data.token');
    }

    public function test_editorial_permissions_match_each_role(): void
    {
        $reviewer = $this->createAdminForRole('relecteur');
        $this->actingAs($reviewer, 'admin')
            ->getJson('/api/v1/admin/reports')
            ->assertOk();
        $this->getJson('/api/v1/admin/imports')->assertForbidden();

        $editor = $this->createAdminForRole('editeur');
        $this->actingAs($editor, 'admin')
            ->getJson('/api/v1/admin/imports')
            ->assertOk();
        $this->getJson('/api/v1/admin/reports')->assertForbidden();
        $this->assertFalse($editor->canValidate());
    }

    public function test_admin_can_manage_players_but_only_super_admin_can_manage_staff(): void
    {
        $admin = $this->createAdminForRole('admin');
        $this->actingAs($admin, 'admin')
            ->getJson('/api/v1/admin/super/users')
            ->assertOk();
        $this->getJson('/api/v1/admin/super/admins')->assertForbidden();

        $superAdmin = $this->createAdminForRole('super_admin');
        $this->actingAs($superAdmin, 'admin')
            ->getJson('/api/v1/admin/super/admins')
            ->assertOk();

        $reviewer = $this->createAdminForRole('relecteur');
        $this->actingAs($reviewer, 'admin')
            ->getJson('/api/v1/admin/super/users')
            ->assertForbidden();
    }

    private function createAdminForRole(string $roleName): AdminUser
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['label_fr' => $roleName, 'label_en' => $roleName]
        );

        return AdminUser::create([
            'role_id' => $role->id,
            'name' => ucfirst($roleName),
            'email' => $roleName . '@example.test',
            'password' => 'local-admin-password',
        ]);
    }
}
