<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class StudentPaymentRules extends Migration
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
            'student_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'category_id' => [ // payment category
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'is_mandatory' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,  // default opsional
                'after'      => 'amount',
                'comment'    => '1 = wajib, 0 = opsional'
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
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'payment_categories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('student_payment_rules');
    }

    public function down()
    {
        $this->forge->dropTable('student_payment_rules');
    }
}
