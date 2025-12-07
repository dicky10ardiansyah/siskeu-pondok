<?php

namespace App\Controllers;

use App\Models\AccountModel;
use App\Models\JournalModel;
use App\Models\TransactionModel;
use App\Models\JournalEntryModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        $request = service('request');
        $startDate = $request->getGet('start_date'); // format YYYY-MM-DD
        $endDate   = $request->getGet('end_date');   // format YYYY-MM-DD

        $accounts = $this->accountsModel->findAll();

        // Inisialisasi total
        $totals = [
            'asset'      => 0,
            'liability'  => 0,
            'equity'     => 0,
            'income'     => 0,
            'expense'    => 0,
        ];

        // Menyimpan detail akun untuk view
        $detail = [
            'asset'     => [],
            'liability' => [],
            'equity'    => [],
            'income'    => [],
            'expense'   => [],
        ];

        foreach ($accounts as $account) {
            // Query sum debit & credit per akun dengan join journals
            $builder = $this->accountsModel->db->table('journal_entries')
                ->selectSum('journal_entries.debit', 'total_debit')
                ->selectSum('journal_entries.credit', 'total_credit')
                ->join('journals', 'journals.id = journal_entries.journal_id', 'left')
                ->where('journal_entries.account_id', $account['id']);

            if ($startDate) {
                $builder->where('journals.date >=', $startDate);
            }
            if ($endDate) {
                $builder->where('journals.date <=', $endDate);
            }

            $result = $builder->get()->getRowArray();

            $totalDebit  = (float)($result['total_debit'] ?? 0);
            $totalCredit = (float)($result['total_credit'] ?? 0);

            // Hitung saldo sesuai tipe akun
            $saldo = 0;
            switch ($account['type']) {
                case 'asset':
                case 'expense':
                    $saldo = $totalDebit - $totalCredit;
                    break;
                case 'income':
                case 'liability':
                case 'equity':
                    $saldo = $totalCredit - $totalDebit;
                    break;
            }

            $totals[$account['type']] += $saldo;
            $detail[$account['type']][] = [
                'name' => $account['name'],
                'saldo' => $saldo
            ];
        }

        // Hitung laba/rugi bersih dan masukkan ke equity
        $netProfit = $totals['income'] - $totals['expense'];
        $totals['equity'] += $netProfit;
        $detail['equity'][] = [
            'name' => ($netProfit >= 0 ? 'Laba Berjalan' : 'Defisit Berjalan'),
            'saldo' => $netProfit
        ];

        // Cek keseimbangan neraca
        $balance_check = ($totals['asset'] == $totals['liability'] + $totals['equity']);

        return view('financial_statement/neraca', [
            'detail'        => $detail,
            'totals'        => $totals,
            'net_profit'    => $netProfit,
            'balance_check' => $balance_check,
            'start_date'    => $startDate,
            'end_date'      => $endDate
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
