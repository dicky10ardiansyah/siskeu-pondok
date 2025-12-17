<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ClassSeeder extends Seeder
{
    public function run()
    {
        $classes = [
            ['name' => 'Kelas 8',  'user_id' => 1],
            ['name' => 'Kelas 9',  'user_id' => 1],
            ['name' => 'Kelas 10', 'user_id' => 1],
            ['name' => 'Kelas 11', 'user_id' => 1],
            ['name' => 'Kelas 12', 'user_id' => 1],
        ];

        $this->db->table('classes')->insertBatch($classes);
    }
}
