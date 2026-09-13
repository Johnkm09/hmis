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
            // Room Types
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

            // Reservations
            'view reservations',
            'create reservations',
            'update reservations',
            'delete reservations',

            // Operations
            'view operations',
            'create operations',
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
            // Room Types
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

            // Reservations
            'view reservations',
            'create reservations',
            'update reservations',
            'delete reservations',

            // Operations
            'view operations',
            'create operations',
        ]);

        $receptionist->givePermissionTo([
            // Room Types
            'view room types',

            // Rooms
            'view rooms',

            // Guests
            'view guests',

            // Reservations
            'view reservations',
            'create reservations',
            'update reservations',

            // Operations
            'view operations',
            'create operations',
        ]);

        $user->givePermissionTo([
            // Room Types
            'view room types',

            // Rooms
            'view rooms',

            // Guests
            'view guests',

            // Reservations
            'view reservations',
        ]);
    }
}
