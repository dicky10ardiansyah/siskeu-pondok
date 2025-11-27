<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class JournalEntries extends Migration
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
            'journal_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'account_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'debit' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
            ],
            'credit' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
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
        $this->forge->addForeignKey('journal_id', 'journals', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('journal_entries');
    }

    public function down()
    {
        $this->forge->dropTable('journal_entries');
    }
}
