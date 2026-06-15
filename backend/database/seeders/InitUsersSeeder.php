<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user
        User::updateOrCreate([
            'email' => 'admin@example.com'
        ], [
            'name' => 'Admin User',
            'password' => Hash::make('AdminPass123!'),
            'role' => 'admin',
        ]);

        // Inspector user
        User::updateOrCreate([
            'email' => 'inspector@example.com'
        ], [
            'name' => 'Inspector User',
            'password' => Hash::make('InspectorPass123!'),
            'role' => 'inspector',
        ]);
    }
}
