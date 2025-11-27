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
    protected $journalsModel;
    protected $transactionsModel;

    public function __construct()
    {
        $this->accountsModel = new AccountModel();
        $this->journalEntriesModel = new JournalEntryModel();
        $this->journalsModel = new JournalModel();
        $this->transactionsModel = new TransactionModel();
    }

    /**
     * Laporan keuangan lengkap
     */
    public function index()
    {
        $accounts = $this->accountsModel->findAll();

        $data = [];
        foreach ($accounts as $account) {
            $debit = $this->journalEntriesModel->selectSum('debit')->where('account_id', $account['id'])->first()['debit'];
            $credit = $this->journalEntriesModel->selectSum('credit')->where('account_id', $account['id'])->first()['credit'];

            $data[] = [
                'account_name' => $account['name'],
                'account_code' => $account['code'],
                'type' => $account['type'],
                'saldo' => ($debit ?? 0) - ($credit ?? 0)
            ];
        }

        return view('financial_statement/index', ['data' => $data]);
    }

    /**
     * Laporan Neraca (Balance Sheet)
     */
    public function neraca()
    {
        // Aset
        $assets = $this->accountsModel->where('type', 'asset')->findAll();
        $dataAssets = [];
        $totalAssets = 0;
        foreach ($assets as $asset) {
            $debit = $this->journalEntriesModel->selectSum('debit')->where('account_id', $asset['id'])->first()['debit'];
            $credit = $this->journalEntriesModel->selectSum('credit')->where('account_id', $asset['id'])->first()['credit'];
            $saldo = ($debit ?? 0) - ($credit ?? 0);
            $totalAssets += $saldo;

            $dataAssets[] = [
                'account_name' => $asset['name'],
                'saldo' => $saldo
            ];
        }

        // Kewajiban
        $liabilities = $this->accountsModel->where('type', 'liability')->findAll();
        $dataLiabilities = [];
        $totalLiabilities = 0;
        foreach ($liabilities as $liability) {
            $debit = $this->journalEntriesModel->selectSum('debit')->where('account_id', $liability['id'])->first()['debit'];
            $credit = $this->journalEntriesModel->selectSum('credit')->where('account_id', $liability['id'])->first()['credit'];
            $saldo = ($credit ?? 0) - ($debit ?? 0); // kewajiban biasanya kredit bertambah
            $totalLiabilities += $saldo;

            $dataLiabilities[] = [
                'account_name' => $liability['name'],
                'saldo' => $saldo
            ];
        }

        // Ekuitas
        $equities = $this->accountsModel->where('type', 'equity')->findAll();
        $dataEquities = [];
        $totalEquities = 0;
        foreach ($equities as $equity) {
            $debit = $this->journalEntriesModel->selectSum('debit')->where('account_id', $equity['id'])->first()['debit'];
            $credit = $this->journalEntriesModel->selectSum('credit')->where('account_id', $equity['id'])->first()['credit'];
            $saldo = ($credit ?? 0) - ($debit ?? 0);
            $totalEquities += $saldo;

            $dataEquities[] = [
                'account_name' => $equity['name'],
                'saldo' => $saldo
            ];
        }

        return view('financial_statement/neraca', [
            'assets' => $dataAssets,
            'total_assets' => $totalAssets,
            'liabilities' => $dataLiabilities,
            'total_liabilities' => $totalLiabilities,
            'equities' => $dataEquities,
            'total_equities' => $totalEquities,
            'total_liabilities_equities' => $totalLiabilities + $totalEquities
        ]);
    }

    /**
     * Laporan Laba Rugi (Income Statement)
     */
    public function labaRugi()
    {
        $incomeAccounts = $this->accountsModel->where('type', 'income')->findAll();
        $expenseAccounts = $this->accountsModel->where('type', 'expense')->findAll();

        $totalIncome = 0;
        $totalExpense = 0;

        $incomeData = [];
        foreach ($incomeAccounts as $acc) {
            $debit = $this->journalEntriesModel->selectSum('debit')->where('account_id', $acc['id'])->first()['debit'];
            $credit = $this->journalEntriesModel->selectSum('credit')->where('account_id', $acc['id'])->first()['credit'];
            $saldo = ($credit ?? 0) - ($debit ?? 0); // income biasanya kredit bertambah
            $totalIncome += $saldo;

            $incomeData[] = [
                'account_name' => $acc['name'],
                'saldo' => $saldo
            ];
        }

        $expenseData = [];
        foreach ($expenseAccounts as $acc) {
            $debit = $this->journalEntriesModel->selectSum('debit')->where('account_id', $acc['id'])->first()['debit'];
            $credit = $this->journalEntriesModel->selectSum('credit')->where('account_id', $acc['id'])->first()['credit'];
            $saldo = ($debit ?? 0) - ($credit ?? 0); // expense biasanya debit bertambah
            $totalExpense += $saldo;

            $expenseData[] = [
                'account_name' => $acc['name'],
                'saldo' => $saldo
            ];
        }

        $netProfit = $totalIncome - $totalExpense;

        return view('financial_statement/laba_rugi', [
            'income' => $incomeData,
            'total_income' => $totalIncome,
            'expense' => $expenseData,
            'total_expense' => $totalExpense,
            'net_profit' => $netProfit
        ]);
    }
}
