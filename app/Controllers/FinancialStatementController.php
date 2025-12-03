<?php

namespace App\Controllers;

use App\Models\AccountModel;
use App\Models\JournalModel;
use App\Models\TransactionModel;
use App\Models\JournalEntryModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class FinancialStatementController extends BaseController
{
    protected $accountsModel;
    protected $journalEntriesModel;

    public function __construct()
    {
        $this->accountsModel = new AccountModel();
        $this->journalEntriesModel = new JournalEntryModel();
    }

    /**
     * Laporan Keuangan Lengkap
     */
    public function index()
    {
        $accounts = $this->accountsModel->findAll();

        // Ambil semua jurnal entries, group by account_id
        $allJournal = $this->journalEntriesModel
            ->select('account_id, SUM(debit) AS debit, SUM(credit) AS credit')
            ->groupBy('account_id')
            ->findAll();

        $journalMap = [];
        foreach ($allJournal as $row) {
            $journalMap[$row['account_id']] = [
                'debit' => (float)$row['debit'],
                'credit' => (float)$row['credit'],
            ];
        }

        $groupedData = [];
        foreach ($accounts as $acc) {
            $debit = $journalMap[$acc['id']]['debit'] ?? 0;
            $credit = $journalMap[$acc['id']]['credit'] ?? 0;

            // Hitung saldo sesuai tipe akun
            $saldo = 0;
            switch ($acc['type']) {
                case 'asset':
                    $saldo = $debit - $credit;
                    break;
                case 'liability':
                case 'equity':
                    $saldo = $credit - $debit;
                    break;
                case 'income':
                    $saldo = $credit;
                    break;
                case 'expense':
                    $saldo = $debit;
                    break;
            }

            $groupedData[$acc['type']][] = [
                'code' => $acc['code'],
                'name' => $acc['name'],
                'debit' => $debit,
                'credit' => $credit,
                'saldo' => $saldo
            ];
        }

        return view('financial_statement/index', ['data' => $groupedData]);
    }

    /**
     * Neraca (Balance Sheet)
     */
    public function neraca()
    {
        $accounts = $this->accountsModel->findAll();

        $assets = $liabilities = $equities = [];
        $total_assets = $total_liabilities = $total_equities = 0;

        foreach ($accounts as $acc) {
            $debit = (float)($this->journalEntriesModel->selectSum('debit')->where('account_id', $acc['id'])->first()['debit'] ?? 0);
            $credit = (float)($this->journalEntriesModel->selectSum('credit')->where('account_id', $acc['id'])->first()['credit'] ?? 0);

            switch ($acc['type']) {
                case 'asset':
                    $saldo = $debit - $credit;
                    $assets[] = ['code' => $acc['code'], 'name' => $acc['name'], 'debit' => $debit, 'credit' => $credit, 'saldo' => $saldo];
                    $total_assets += $saldo;
                    break;
                case 'liability':
                    $saldo = $credit - $debit;
                    $liabilities[] = ['code' => $acc['code'], 'name' => $acc['name'], 'debit' => $debit, 'credit' => $credit, 'saldo' => $saldo];
                    $total_liabilities += $saldo;
                    break;
                case 'equity':
                    $saldo = $credit - $debit;
                    $equities[] = ['code' => $acc['code'], 'name' => $acc['name'], 'debit' => $debit, 'credit' => $credit, 'saldo' => $saldo];
                    $total_equities += $saldo;
                    break;
                case 'income':
                    $saldo = $credit;
                    $equities[] = ['code' => $acc['code'], 'name' => $acc['name'], 'debit' => $debit, 'credit' => $credit, 'saldo' => $saldo];
                    $total_equities += $saldo;
                    break;
                case 'expense':
                    $saldo = $debit;
                    $equities[] = ['code' => $acc['code'], 'name' => $acc['name'], 'debit' => $debit, 'credit' => $credit, 'saldo' => -$saldo];
                    $total_equities -= $saldo;
                    break;
            }
        }

        return view('financial_statement/neraca', [
            'assets' => $assets,
            'total_assets' => $total_assets,
            'liabilities' => $liabilities,
            'total_liabilities' => $total_liabilities,
            'equities' => $equities,
            'total_equities' => $total_equities,
            'total_liabilities_equities' => $total_liabilities + $total_equities
        ]);
    }

    /**
     * Laporan Laba Rugi
     */
    public function labaRugi()
    {
        $accounts = $this->accountsModel->findAll();

        $allJournal = $this->journalEntriesModel
            ->select('account_id, SUM(debit) AS debit, SUM(credit) AS credit')
            ->groupBy('account_id')
            ->findAll();

        $journalMap = [];
        foreach ($allJournal as $row) {
            $journalMap[$row['account_id']] = ['debit' => (float)$row['debit'], 'credit' => (float)$row['credit']];
        }

        $income = $expense = [];
        $total_income = $total_expense = 0;

        foreach ($accounts as $acc) {
            $debit = $journalMap[$acc['id']]['debit'] ?? 0;
            $credit = $journalMap[$acc['id']]['credit'] ?? 0;

            if ($acc['type'] == 'income') {
                $saldo = $credit;
                $income[] = ['account_name' => $acc['name'], 'saldo' => $saldo];
                $total_income += $saldo;
            } elseif ($acc['type'] == 'expense') {
                $saldo = $debit;
                $expense[] = ['account_name' => $acc['name'], 'saldo' => $saldo];
                $total_expense += $saldo;
            }
        }

        $net_profit = $total_income - $total_expense;

        return view('financial_statement/laba_rugi', [
            'income' => $income,
            'total_income' => $total_income,
            'expense' => $expense,
            'total_expense' => $total_expense,
            'net_profit' => $net_profit
        ]);
    }
}
