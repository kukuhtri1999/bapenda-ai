<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
  /**
   * Run the database seeder.
   */
  public function run(): void
  {
    // Reset cached roles and permissions
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Create permissions
    $permissions = [
      // User Management
      'view users',
      'create users',
      'edit users',
      'delete users',

      // Dashboard
      'view dashboard',

      // Chat/AI Service
      'view chat logs',
      'manage chat settings',

      // Reports
      'view reports',
      'generate reports',

      // System Settings
      'view settings',
      'edit settings',

      // Photo Editing (Secret Feature)
      'access photo editing',
    ];

    foreach ($permissions as $permission) {
      Permission::create(['name' => $permission]);
    }

    // Create roles and assign permissions

    // Super Admin - All permissions
    $superAdmin = Role::create(['name' => 'Super Admin']);
    $superAdmin->givePermissionTo(Permission::all());

    // Admin - Most permissions except some sensitive ones
    $admin = Role::create(['name' => 'Admin']);
    $admin->givePermissionTo([
      'view dashboard',
      'view users',
      'create users',
      'edit users',
      'view chat logs',
      'manage chat settings',
      'view reports',
      'generate reports',
      'view settings',
    ]);

    // Manager - Limited management permissions
    $manager = Role::create(['name' => 'Manager']);
    $manager->givePermissionTo([
      'view dashboard',
      'view users',
      'view chat logs',
      'view reports',
      'generate reports',
    ]);

    // Operator - Basic permissions
    $operator = Role::create(['name' => 'Operator']);
    $operator->givePermissionTo([
      'view dashboard',
      'view chat logs',
      'view reports',
    ]);

    // Create default Super Admin user if doesn't exist
    $superAdminUser = User::firstOrCreate(
      ['email' => 'admin@bapenda.go.id'],
      [
        'name' => 'Super Admin',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
        'is_active' => true,
      ]
    );

    $superAdminUser->assignRole('Super Admin');

    // Create sample users for testing
    $testAdmin = User::firstOrCreate(
      ['email' => 'admin.test@bapenda.go.id'],
      [
        'name' => 'Admin Test',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
        'is_active' => true,
      ]
    );
    $testAdmin->assignRole('Admin');

    $testManager = User::firstOrCreate(
      ['email' => 'manager.test@bapenda.go.id'],
      [
        'name' => 'Manager Test',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
        'is_active' => true,
      ]
    );
    $testManager->assignRole('Manager');
  }
}
