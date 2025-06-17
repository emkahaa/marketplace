<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\CategorySeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // CategorySeeder akan mengelola disable/enable foreign key constraints
        // dan truncate tabelnya sendiri.
        $this->call([
            CategorySeeder::class,
            // ... panggil seeder lainnya di sini
        ]);
    }
}
