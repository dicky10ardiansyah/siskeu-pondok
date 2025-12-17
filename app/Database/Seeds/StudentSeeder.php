<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Faker\Factory;

class StudentSeeder extends Seeder
{
    public function run()
    {
        // Ambil class dari database (sumber kebenaran)
        $classes = $this->db->table('classes')->get()->getResultArray();

        if (empty($classes)) {
            throw new \RuntimeException('Table classes masih kosong!');
        }

        $names = [
            'Ahmad Fauzi',
            'Andi Saputra',
            'Budi Santoso',
            'Dedi Pratama',
            'Eko Prasetyo',
            'Fajar Nugroho',
            'Hadi Setiawan',
            'Irfan Maulana',
            'Joko Susilo',
            'Rizki Ramadhan',
            'Agus Salim',
            'Bayu Wicaksono',
            'Dimas Arya',
            'Farhan Hidayat',
            'Gilang Saputro',
            'Ilham Akbar',
            'Kurniawan Putra',
            'Muhammad Rizal',
            'Nanda Prakoso',
            'Rafi Kurnia',
            'Satria Adi',
            'Yoga Pratama',
            'Zaki Alfarizi',

            'Aisyah Putri',
            'Anisa Rahma',
            'Dewi Anggraini',
            'Fitri Handayani',
            'Intan Permata',
            'Kartika Sari',
            'Lestari Ayu',
            'Nabila Zahra',
            'Putri Maharani',
            'Rina Oktaviani',
            'Siti Aminah',
            'Tiara Safira',
            'Ulfah Khairunnisa',
            'Wulan Pertiwi',
            'Yuni Astuti',

            'Bagas Firmansyah',
            'Rizal Fikri',
            'Hafiz Ramadhan',
            'Alif Nugraha',
            'Reno Maulana',
            'Vina Oktavia',
            'Diah Puspitasari',
            'Melati Kusuma',
            'Novi Lestari'
        ];

        $data = [];
        $nis  = 8001;

        for ($i = 0; $i < 50; $i++) {
            $class = $classes[$i % count($classes)];

            $data[] = [
                'name' => $names[$i % count($names)],
                'nis'         => (string) $nis++,
                'class'       => $class['id'], // SINKRON
                'status'      => null,
                'school_year' => null,
                'overpaid'    => null,
                'user_id'     => $class['user_id'],
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ];
        }

        $this->db->table('students')->insertBatch($data);
    }
}
