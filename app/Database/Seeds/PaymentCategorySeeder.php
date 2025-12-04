<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PaymentCategorySeeder extends Seeder
{
    public function run()
    {
        $data = [];

        $names = [
            'SPP Bulanan',
            'Uang Kegiatan',
            'Seragam',
            'Donasi Umum',
            'Buku Paket'
        ];

        for ($i = 0; $i < 5; $i++) {
            $data[] = [
                'name'           => $names[$i],
                'default_amount' => rand(0, 1) ? rand(10000, 300000) : null, // random amount / nullable
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s')
            ];
        }

        $this->db->table('payment_categories')->insertBatch($data);
    }
}
