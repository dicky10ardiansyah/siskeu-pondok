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
        'type'
    ];

    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
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
