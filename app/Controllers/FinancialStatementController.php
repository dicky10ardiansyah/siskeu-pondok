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

    public function index()
    {
        $userId   = session()->get('user_id');
        $userRole = session()->get('user_role'); // admin | user
        $year     = $this->request->getGet('year'); // filter tahun
        $selectedUser = $this->request->getGet('user_id'); // filter user untuk admin

        // Tentukan $userId sesuai filter
        if ($userRole === 'admin') {
            if ($selectedUser) {
                $userId = $selectedUser; // tampilkan user tertentu
            } else {
                $userId = null; // null = semua user
            }
        }

        // Ambil akun sesuai user
        $accounts = ($userRole === 'admin' && $userId === null)
            ? $this->accountsModel->findAll() // semua akun untuk admin
            : $this->accountsModel->where('user_id', $userId)->findAll();

        // Ambil jurnal
        $journalBuilder = $this->journalEntriesModel
            ->select('journal_entries.account_id, SUM(journal_entries.debit) AS debit, SUM(journal_entries.credit) AS credit')
            ->join('journals', 'journals.id = journal_entries.journal_id', 'left');

        if ($userRole !== 'admin' || $userId !== null) {
            $journalBuilder->where('journals.user_id', $userId);
        }

        if (!empty($year)) {
            $journalBuilder->where('YEAR(journals.date)', (int)$year);
        }

        $allJournal = $journalBuilder->groupBy('journal_entries.account_id')->findAll();

        // Map jurnal
        $journalMap = [];
        foreach ($allJournal as $row) {
            $journalMap[$row['account_id']] = [
                'debit'  => (float) $row['debit'],
                'credit' => (float) $row['credit'],
            ];
        }

        // Proses laporan
        $groupedData = [];
        foreach ($accounts as $acc) {
            $debit  = $journalMap[$acc['id']]['debit'] ?? 0;
            $credit = $journalMap[$acc['id']]['credit'] ?? 0;

            $saldo = match ($acc['type']) {
                'asset' => $debit - $credit,
                'liability', 'equity' => $credit - $debit,
                'income' => $credit,
                'expense' => $debit,
                default => 0,
            };

            $groupedData[$acc['type']][] = [
                'code'   => $acc['code'],
                'name'   => $acc['name'],
                'debit'  => $debit,
                'credit' => $credit,
                'saldo'  => $saldo,
            ];
        }

        // Ambil list semua user untuk dropdown admin
        $users = [];
        if ($userRole === 'admin') {
            $userModel = new \App\Models\UserModel();
            $users = $userModel->findAll(); // semua user
        }

        return view('financial_statement/index', [
            'data'          => $groupedData,
            'role'          => $userRole,
            'year'          => $year,
            'users'         => $users,
            'selected_user' => $selectedUser
        ]);
    }

    public function neraca()
    {
        $userRole = session()->get('user_role');
        $userId   = session()->get('user_id');
        $request  = service('request');

        // ====== FILTER ======
        $startDate    = $request->getGet('start_date') ?? null;
        $endDate      = $request->getGet('end_date') ?? null;
        $selectedUser = $request->getGet('user_id') ?? null;

        // ====== USER LIST (ADMIN) ======
        $userModel = new \App\Models\UserModel();
        $users = ($userRole === 'admin') ? $userModel->findAll() : [];

        if ($userRole === 'admin' && $selectedUser) {
            $userId = $selectedUser;
        }

        // ====== AMBIL AKUN ======
        $accounts = ($userRole === 'admin' && !$selectedUser)
            ? $this->accountsModel->findAll()
            : $this->accountsModel->where('user_id', $userId)->findAll();

        // ====== INIT ======
        $detail = [
            'asset'     => [],
            'liability' => [],
            'equity'    => [],
            'income'    => [],
            'expense'   => [],
        ];

        $totals = [
            'asset'     => 0,
            'liability' => 0,
            'equity'    => 0,
            'income'    => 0,
            'expense'   => 0,
        ];

        // ====== HITUNG SALDO AKUN DARI JURNAL ======
        foreach ($accounts as $account) {
            $builder = $this->accountsModel->db->table('journal_entries')
                ->selectSum('journal_entries.debit', 'debit')
                ->selectSum('journal_entries.credit', 'credit')
                ->join('journals', 'journals.id = journal_entries.journal_id', 'left')
                ->where('journal_entries.account_id', $account['id']);

            if ($userRole !== 'admin' || $selectedUser) {
                $builder->where('journals.user_id', $userId);
            }

            if ($startDate) $builder->where('journals.date >=', $startDate);
            if ($endDate)   $builder->where('journals.date <=', $endDate);

            $row = $builder->get()->getRowArray();

            $debit  = (float) ($row['debit'] ?? 0);
            $credit = (float) ($row['credit'] ?? 0);

            $saldo = match ($account['type']) {
                'asset', 'expense' => $debit - $credit,
                'liability', 'equity', 'income' => $credit - $debit,
                default => 0
            };

            $detail[$account['type']][] = [
                'name'  => $account['name'],
                'saldo' => $saldo
            ];

            $totals[$account['type']] += $saldo;
        }

        // ====== HITUNG TOTAL TUNGGAKAN (PIUTANG) ======
        $billModel = new \App\Models\BillModel();

        $billBuilder = $billModel
            ->selectSum('(amount - paid_amount)', 'total_tunggakan')
            ->where('status !=', 'paid');

        if ($userRole !== 'admin' || $selectedUser) {
            $billBuilder->where('user_id', $userId);
        }

        $row = $billBuilder->get()->getRowArray();
        $totalTunggakan = (float) ($row['total_tunggakan'] ?? 0);

        // ====== PIUTANG → ASET ======
        if ($totalTunggakan > 0) {
            $detail['asset'][] = [
                'name'  => 'Piutang',
                'saldo' => $totalTunggakan
            ];
            $totals['asset'] += $totalTunggakan;
        }

        // ====== PENDAPATAN BELUM DITERIMA → LIABILITY ======
        if ($totalTunggakan > 0) {
            $detail['liability'][] = [
                'name'  => 'Pendapatan Belum Diterima',
                'saldo' => $totalTunggakan
            ];
            $totals['liability'] += $totalTunggakan;
        }

        // ====== PENDAPATAN BELUM DITERIMA → INCOME (INFO SAJA) ======
        if ($totalTunggakan > 0) {
            $detail['income'][] = [
                'name'  => 'Pendapatan Belum Diterima',
                'saldo' => $totalTunggakan
            ];

            // Total income termasuk piutang
            $totals['income'] += $totalTunggakan;
        }

        // ====== HITUNG LABA / DEFISIT ======
        $incomeReal = $totals['income'] - $totalTunggakan; // income nyata tanpa piutang
        $netProfit = $incomeReal - $totals['expense'];

        $detail['equity'][] = [
            'name'  => $netProfit >= 0 ? 'Laba Berjalan' : 'Defisit Berjalan',
            'saldo' => $netProfit
        ];
        $totals['equity'] += $netProfit;

        // ====== CEK BALANCE ======
        $balance_check = ($totals['asset'] == ($totals['liability'] + $totals['equity']));

        return view('financial_statement/neraca', [
            'detail'        => $detail,
            'totals'        => $totals,
            'net_profit'    => $netProfit,
            'balance_check' => $balance_check,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'role'          => $userRole,
            'users'         => $users,
            'selected_user' => $selectedUser
        ]);
    }

    public function labaRugi()
    {
        $userId   = session()->get('user_id');
        $userRole = session()->get('user_role'); // admin | user
        $request  = service('request');

        // Ambil filter user jika admin (opsional)
        $selectedUser = $request->getGet('user_id');

        // Admin bisa lihat semua atau pilih user tertentu
        if ($userRole === 'admin') {
            if ($selectedUser) {
                $userId = $selectedUser; // lihat user tertentu
            } else {
                $userId = null; // null = semua user
            }
        }

        // Ambil akun
        $accounts = ($userRole === 'admin' && $userId === null)
            ? $this->accountsModel->findAll() // semua akun untuk admin
            : $this->accountsModel->where('user_id', $userId)->findAll();

        // Ambil jurnal
        $journalBuilder = $this->journalEntriesModel
            ->select('account_id, SUM(debit) AS debit, SUM(credit) AS credit')
            ->join('journals', 'journals.id = journal_entries.journal_id', 'left');

        if ($userRole !== 'admin' || $userId !== null) {
            $journalBuilder->where('journals.user_id', $userId);
        }

        $journalBuilder->groupBy('account_id');
        $allJournal = $journalBuilder->findAll();

        // Map jurnal
        $journalMap = [];
        foreach ($allJournal as $row) {
            $journalMap[$row['account_id']] = [
                'debit'  => (float)$row['debit'],
                'credit' => (float)$row['credit']
            ];
        }

        // Hitung pendapatan & beban
        $income = $expense = [];
        $total_income = $total_expense = 0;

        foreach ($accounts as $acc) {
            $debit  = $journalMap[$acc['id']]['debit'] ?? 0;
            $credit = $journalMap[$acc['id']]['credit'] ?? 0;

            if ($acc['type'] === 'income') {
                $saldo = $credit;
                $income[] = ['account_name' => $acc['name'], 'saldo' => $saldo];
                $total_income += $saldo;
            } elseif ($acc['type'] === 'expense') {
                $saldo = $debit;
                $expense[] = ['account_name' => $acc['name'], 'saldo' => $saldo];
                $total_expense += $saldo;
            }
        }

        $net_profit = $total_income - $total_expense;

        // Ambil list semua user untuk admin (opsional dropdown)
        $users = [];
        if ($userRole === 'admin') {
            $userModel = new \App\Models\UserModel();
            $users = $userModel->findAll(); // semua user, termasuk admin
        }

        return view('financial_statement/laba_rugi', [
            'income'        => $income,
            'total_income'  => $total_income,
            'expense'       => $expense,
            'total_expense' => $total_expense,
            'net_profit'    => $net_profit,
            'role'          => $userRole,
            'users'         => $users,
            'selected_user' => $selectedUser
        ]);
    }
}
