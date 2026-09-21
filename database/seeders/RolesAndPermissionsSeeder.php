<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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

            // Services
            'view services',
            'create services',
            'update services',
            'delete services',

            // Folios
            'view folios',
            'create folios',
            'update folios',

            // Folio Charges
            'view folio charges',
            'create folio charges',
            'update folio charges',

            // Payments
            'view payments',
            'create payments',
            'update payments',

            // Refunds
            'view refunds',
            'create refunds',
            'update refunds',

            // Invoices
            'view invoices',
            'create invoices',
            'update invoices',
            'delete invoices',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $manager = Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => 'web',
        ]);

        $receptionist = Role::firstOrCreate([
            'name' => 'receptionist',
            'guard_name' => 'web',
        ]);

        $user = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);

        // Super Admin
        $superAdmin->givePermissionTo($permissions);

        // Manager
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

            // Services
            'view services',
            'create services',
            'update services',
            'delete services',

            // Folios
            'view folios',
            'create folios',
            'update folios',

            // Folio Charges
            'view folio charges',
            'create folio charges',
            'update folio charges',

            // Payments
            'view payments',
            'create payments',
            'update payments',

            // Refunds
            'view refunds',
            'create refunds',
            'update refunds',

            // Invoices
            'view invoices',
            'create invoices',
            'update invoices',
            'delete invoices',
        ]);

        // Receptionist
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

            // Services
            'view services',

            // Folios
            'view folios',
            'create folios',
            'update folios',

            // Folio Charges
            'view folio charges',
            'create folio charges',

            // Payments
            'view payments',
            'create payments',

            // Refunds
            'view refunds',
            'create refunds',

            // Invoices
            'view invoices',
            'create invoices',
        ]);

        // User
        $user->givePermissionTo([
            // Room Types
            'view room types',

            // Rooms
            'view rooms',

            // Guests
            'view guests',

            // Reservations
            'view reservations',

            // Services
            'view services',
        ]);
    }
}
