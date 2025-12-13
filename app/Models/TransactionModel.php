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
        'credit_account_id',
        'proof'
    ];

    // Tambahkan model events
    protected $beforeFind   = ['filterByUser'];
    protected $beforeInsert = ['addUserId'];

    // -------------------------------------------------------------
    // 1. FILTER DATA SECARA OTOMATIS BERDASARKAN USER
    // -------------------------------------------------------------
    protected function filterByUser(array $data)
    {
        // Jika builder tidak ada (misal query manual), skip
        if (!isset($data['builder'])) {
            return $data;
        }

        $builder = $data['builder'];

        $session = session();
        $role    = $session->get('user_role');
        $userId  = $session->get('user_id');

        // ADMIN → bisa melihat semua atau filter manual via GET ?user_id=
        if ($role === 'admin') {
            $reqUser = service('request')->getGet('user_id');
            if ($reqUser) {
                $builder->where('students.user_id', $reqUser);
            }
            return $data;
        }

        // USER BIASA → hanya lihat data miliknya
        $builder->where('students.user_id', $userId);

        return $data;
    }

    // -------------------------------------------------------------
    // 2. OTOMATIS MENAMBAHKAN user_id SAAT INSERT
    // -------------------------------------------------------------
    protected function addUserId(array $data)
    {
        $userId = session()->get('user_id');

        if (!isset($data['data']['user_id'])) {
            $data['data']['user_id'] = $userId;
        }

        return $data;
    }

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
