<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountModel extends Model
{
    protected $table      = 'accounts';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'code',
        'name',
        'type',
        'user_id'
    ];

    protected $beforeFind   = ['filterByUser'];
    protected $beforeInsert = ['addUserId'];
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    // -------------------------------------------------------------
    // 1. FILTER DATA BERDASARKAN USER (OTOMATIS)
    // -------------------------------------------------------------
    protected function filterByUser(array $data)
    {
        // Pastikan builder ada
        if (!isset($data['builder'])) return $data;

        $builder = $data['builder'];
        $session = session();
        $role    = $session->get('user_role');
        $userId  = $session->get('user_id');

        if ($role === 'admin') {
            // admin bisa filter user via GET?user_id=XX
            $reqUser = service('request')->getGet('user_id');
            if ($reqUser) $builder->where($this->table . '.user_id', $reqUser);
            return $data;
        }

        // user biasa → hanya data miliknya
        $builder->where($this->table . '.user_id', $userId);

        return $data;
    }

    protected function addUserId(array $data)
    {
        if (!isset($data['data']['user_id'])) {
            $data['data']['user_id'] = session()->get('user_id');
        }
        return $data;
    }

    /**
     * Hitung saldo akun berdasarkan tipe akun
     *
     * @param int $accountId
     * @return float
     */
    public function getSaldo($accountId, $bulan = null, $tahun = null)
    {
        $builder = $this->db->table('journal_entries')
            ->selectSum('debit', 'total_debit')
            ->selectSum('credit', 'total_credit')
            ->where('account_id', $accountId);

        if ($bulan) {
            $builder->where('MONTH(created_at)', $bulan);
        }
        if ($tahun) {
            $builder->where('YEAR(created_at)', $tahun);
        }

        $result = $builder->get()->getRowArray();

        $account = $this->find($accountId);

        $totalDebit  = (float)($result['total_debit'] ?? 0);
        $totalCredit = (float)($result['total_credit'] ?? 0);

        switch ($account['type']) {
            case 'asset':
            case 'expense':
                return $totalDebit - $totalCredit;
            case 'income':
            case 'equity':
            case 'liability':
                return $totalCredit - $totalDebit;
            default:
                return 0;
        }
    }

    /**
     * Ambil semua akun beserta saldo, dengan filter bulan & tahun opsional
     */
    public function getAllWithSaldo($bulan = null, $tahun = null)
    {
        $accounts = $this->findAll();
        foreach ($accounts as &$acc) {
            $acc['saldo'] = $this->getSaldo($acc['id'], $bulan, $tahun);
        }
        return $accounts;
    }
}
