<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call('ClassSeeder');   // HARUS dulu
        $this->call('StudentSeeder');
    }
}
