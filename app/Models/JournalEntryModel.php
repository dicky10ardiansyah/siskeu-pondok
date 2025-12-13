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
        'credit',
        'user_id'
    ];

    protected $beforeFind   = ['filterByUser'];
    protected $beforeInsert = ['addUserId'];

    // ------------------------------------------------------
    // AUTO FILTER PER USER (untuk semua query find/get)
    // ------------------------------------------------------
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

    // ------------------------------------------------------
    // AUTO TAMBAHKAN user_id ketika INSERT
    // ------------------------------------------------------
    protected function addUserId(array $data)
    {
        if (!isset($data['data']['user_id'])) {
            $data['data']['user_id'] = session()->get('user_id');
        }

        return $data;
    }

    public function getSaldoByAccount($accountId, $bulan = null, $tahun = null)
    {
        $builder = $this->db->table($this->table)
            ->select('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->where('account_id', $accountId);

        if ($bulan) $builder->where('MONTH(created_at)', $bulan);
        if ($tahun) $builder->where('YEAR(created_at)', $tahun);

        $row = $builder->get()->getRowArray();

        return ($row['total_debit'] ?? 0) - ($row['total_credit'] ?? 0);
    }
}
