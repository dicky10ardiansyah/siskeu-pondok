<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Payments extends Migration
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
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'debit_account_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'credit_account_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'journal_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'total_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'date' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'method' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'reference' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
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
        $this->forge->addForeignKey('debit_account_id', 'accounts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('credit_account_id', 'accounts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('journal_id', 'journals', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('payments');
    }

    public function down()
    {
        $this->forge->dropTable('payments');
    }
}
