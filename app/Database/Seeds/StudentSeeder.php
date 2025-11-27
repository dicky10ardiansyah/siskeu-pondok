<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Faker\Factory;

class StudentSeeder extends Seeder
{
    public function run()
    {
        $faker = Factory::create('id_ID');

        $data = [];
        for ($i = 1; $i <= 50; $i++) {

            // 70% siswa punya nis, 30% kosong
            $nis = (rand(1, 100) <= 70) ? $faker->unique()->numerify('2025####') : null;

            $data[] = [
                'name'       => $faker->name(),
                'nis'        => $nis,
                'class'      => 'Kelas ' . rand(1, 12),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }

        $this->db->table('students')->insertBatch($data);
    }
}
