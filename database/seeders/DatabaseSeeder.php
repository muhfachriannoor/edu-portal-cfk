<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@mail.com', 'name' => 'Superadmin'],
            ['password' => Hash::make('password'), 'is_active' => true]
        );
        
        $this->call([
            RoleSeeder::class,
            // StudentSeeder::class,
        ]);
    }
}
