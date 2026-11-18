<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class RolePermissionsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->seedClientRolePermissions();

        $this->seedSubUserRolePermissions();
    }

    private function seedSubUserRolePermissions()
    {
        $role = Role::whereName('Sub User')->first();

        $this->clearRolePermissions($role);

        $premissions = [
            'qrcode.list',
            'qrcode.show',
            'qrcode.update',
            'qrcode.store',
            'qrcode.showStats',
            'folder.list',
        ];

        $permissions = Permission::whereIn('slug', $premissions)->get();

        foreach ($permissions as $permission) {
            $role->permissions()->save($permission);
        }
    }

    private function seedClientRolePermissions()
    {
        $defaultRole = Role::where('is_default_role_for_new_signup', true)->first();

        $this->clearRolePermissions($defaultRole);

        $defaultPermissions = Permission::whereIn('slug', [
            'qrcode.list',
            'qrcode.show',
            'qrcode.update',
            'qrcode.store',
            'qrcode.archive',
            'qrcode.showStats',
            'qrcode.destroy',
            'subscription.store',
            'subscription.update',
            'file.show',
            'file.destroy',
            'qrcode-redirect.update',
            'domain.add',
            'domain.destroy',
            'domain.show',
            'domain.updateStatus',
            'folder.list',
            'folder.store',
            'folder.update',
            'folder.destroy',
            'user.invite'
        ])->get();

        foreach ($defaultPermissions as $permission) {
            $defaultRole->permissions()->save($permission);
        }
    }

    private function clearRolePermissions(Role $role)
    {
        $role->permissions()->detach($role->permissions->pluck('id'));
    }
}
