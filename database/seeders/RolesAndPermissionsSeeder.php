<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view room types',
            'create room types',
            'update room types',
            'delete room types',

            // Rooms
            'view rooms',
            'create rooms',
            'update rooms',
            'delete rooms',

            // Guests
            'view guests',
            'create guests',
            'update guests',
            'delete guests',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin'
        ]);

        $manager = Role::firstOrCreate([
            'name' => 'manager'
        ]);

        $receptionist = Role::firstOrCreate([
            'name' => 'receptionist'
        ]);

        $user = Role::firstOrCreate([
            'name' => 'user'
        ]);

        $superAdmin->givePermissionTo($permissions);

        $manager->givePermissionTo([
            'view room types',
            'create room types',
            'update room types',
            'delete room types',

            // Rooms
            'view rooms',
            'create rooms',
            'update rooms',
            'delete rooms',

            // Guests
            'view guests',
            'create guests',
            'update guests',
            'delete guests',
        ]);

        $receptionist->givePermissionTo([
            'view room types',

            // Rooms
            'view rooms',
            // Guests
            'view guests'
        ]);

        $user->givePermissionTo([
            'view room types',

            // Rooms
            'view rooms',

            // Guests
            'view guests',
        ]);
    }
}
