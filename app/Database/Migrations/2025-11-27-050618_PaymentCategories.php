<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PaymentCategories extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'default_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'billing_type' => [
                'type' => 'ENUM',
                'constraint' => ['monthly', 'one-time'],
                'default' => 'monthly'
            ],
            'duration_months' => [
                'type' => 'TINYINT',
                'null' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
            ],
            'updated_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
            ]
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('payment_categories');
    }

    public function down()
    {
        $this->forge->dropTable('payment_categories');
    }
}
