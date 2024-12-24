<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $accountantRole = Role::firstOrCreate(['name' => 'accountant']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);

        // Define permissions for different areas
        $permissions = [
            // User management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            
            // Account management
            'view_accounts',
            'create_accounts',
            'edit_accounts',
            'delete_accounts',
            
            // Transaction management
            'view_transactions',
            'create_transactions',
            'edit_transactions',
            'delete_transactions',
            
            // Report management
            'view_reports',
            'create_reports',
            'export_reports'
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to roles
        // Admin gets all permissions
        $adminRole->syncPermissions(Permission::all());

        // Accountant permissions
        $accountantRole->syncPermissions([
            'view_accounts',
            'create_accounts',
            'edit_accounts',
            'view_transactions',
            'create_transactions',
            'edit_transactions',
            'view_reports',
            'create_reports',
            'export_reports'
        ]);

        // Employee permissions
        $employeeRole->syncPermissions([
            'view_accounts',
            'view_transactions',
            'view_reports'
        ]);
    }
}
