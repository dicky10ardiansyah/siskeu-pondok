<?php

namespace App\Models;

use CodeIgniter\Model;

class JournalEntryModel extends Model
{
    protected $table      = 'journal_entries';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'journal_id',
        'account_id',
        'debit',
        'credit'
    ];

    public function getSaldoByAccount($accountId)
    {
        $builder = $this->db->table($this->table);
        $builder->select('SUM(debit) as total_debit, SUM(credit) as total_credit');
        $builder->where('account_id', $accountId);
        $row = $builder->get()->getRowArray();

        return ($row['total_debit'] ?? 0) - ($row['total_credit'] ?? 0);
    }
}
