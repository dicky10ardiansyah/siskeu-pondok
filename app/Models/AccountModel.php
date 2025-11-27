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
    public function getSaldo($accountId)
    {
        // Ambil total debit & kredit dari journal_entries
        $builder = $this->db->table('journal_entries')
            ->selectSum('debit', 'total_debit')
            ->selectSum('credit', 'total_credit')
            ->where('account_id', $accountId)
            ->get()
            ->getRow();

        // Ambil tipe akun
        $account = $this->find($accountId);

        $totalDebit  = (float)($builder->total_debit ?? 0);
        $totalCredit = (float)($builder->total_credit ?? 0);

        // Hitung saldo berdasarkan tipe akun
        if ($account->type == 'asset' || $account->type == 'expense') {
            return $totalDebit - $totalCredit;
        } elseif ($account->type == 'income' || $account->type == 'equity') {
            return $totalCredit - $totalDebit;
        } else {
            return 0;
        }
    }

    /**
     * Ambil semua akun beserta saldo
     */
    public function getAllWithSaldo()
    {
        $accounts = $this->findAll();
        foreach ($accounts as $acc) {
            $acc->saldo = $this->getSaldo($acc->id);
        }
        return $accounts;
    }
}
