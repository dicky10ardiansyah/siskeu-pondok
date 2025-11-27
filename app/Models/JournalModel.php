<?php

namespace App\Models;

use CodeIgniter\Model;

class JournalModel extends Model
{
    protected $table      = 'journals';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'date',
        'description',
        'user_id'
    ];

    public function getWithEntries($id)
    {
        $builder = $this->db->table($this->table);
        $builder->select('journals.*, journal_entries.account_id, journal_entries.debit, journal_entries.credit');
        $builder->join('journal_entries', 'journal_entries.journal_id = journals.id', 'left');
        $builder->where('journals.id', $id);
        return $builder->get()->getResultArray();
    }
}
