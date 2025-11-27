<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{
    protected $table      = 'transactions';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'type',
        'description',
        'amount',
        'date',
        'user_id',
        'debit_account_id',
        'credit_account_id'
    ];

    public function getAccounts()
    {
        return $this->db->table('accounts')->get()->getResultArray();
    }

    public function getByAccount($accountId, $start = null, $end = null)
    {
        $builder = $this->where('debit_account_id', $accountId)
            ->orWhere('credit_account_id', $accountId);

        if ($start) $builder->where('date >=', $start);
        if ($end) $builder->where('date <=', $end);

        return $builder->orderBy('date', 'ASC')->findAll();
    }

    public function getTotal($type = null)
    {
        $builder = $this->select('SUM(amount) as total');
        if ($type) $builder->where('type', $type);
        $row = $builder->first();
        return $row['total'] ?? 0;
    }
}
