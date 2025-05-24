<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $userRole = Role::firstOrCreate(['name' => 'User']);

        // Create a manager
        $manager = User::firstOrCreate([
            'email' => 'manager@example.com',
        ], [
            'name' => 'Manager',
            'password' => Hash::make('password'),
            'id' => 1,
        ]);
        $manager->assignRole($managerRole);

        // Create a regular user
        $user = User::firstOrCreate([
            'email' => 'user@example.com',
        ], [
            'name' => 'User',
            'password' => Hash::make('password'),
            'id' => 2,
        ]);
        $user->assignRole($userRole);
        // create a second user
        $user2 = User::firstOrCreate([
            'email' => 'user2@example.com',
        ], [
            'name' => 'User2',
            'password' => Hash::make('password'),
            'id' => 3,
        ]);
        $user2->assignRole($userRole);

    }
}
