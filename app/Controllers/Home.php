<?php

namespace App\Controllers;

use App\Models\BillModel;
use App\Models\AccountModel;
use App\Models\StudentModel;
use App\Models\TransactionModel;
use App\Models\JournalEntryModel;


class Home extends BaseController
{
    protected $accountModel;
    protected $billModel;

    public function __construct()
    {
        $this->accountModel = new AccountModel();
        $this->billModel = new BillModel();
    }

    public function index()
    {
        $request = service('request');
        $startDate = $request->getGet('start_date'); // format: YYYY-MM-DD
        $endDate   = $request->getGet('end_date');   // format: YYYY-MM-DD

        $accounts = $this->accountModel->findAll();

        // Inisialisasi total keuangan
        $totals = [
            'asset'      => 0,
            'liability'  => 0,
            'equity'     => 0,
            'income'     => 0,
            'expense'    => 0,
        ];

        foreach ($accounts as $account) {
            // Query saldo akun dengan join journals untuk filter tanggal
            $builder = $this->accountModel->db->table('journal_entries')
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

            // Simpan saldo sementara
            $totals[$account['type']] += $saldo;
        }

        // --- Hitung laba/rugi bersih ---
        // Income – Expense masuk ke Equity
        $netProfit = $totals['income'] - $totals['expense'];
        $totals['equity'] += $netProfit;

        // --- Hitung Tagihan dan Pembayaran ---
        $billBuilder = $this->billModel->db->table('bills');

        if ($startDate) {
            $billBuilder->where('DATE(CONCAT(year, "-", LPAD(month,2,"0"), "-01")) >=', $startDate);
        }
        if ($endDate) {
            $billBuilder->where('DATE(CONCAT(year, "-", LPAD(month,2,"0"), "-01")) <=', $endDate);
        }

        $bills = $billBuilder->get()->getResultArray();

        $totalTagihan = 0;
        $totalDibayar = 0;
        $tunggakan = 0;
        $siswaMenunggak = [];

        foreach ($bills as $bill) {
            $totalTagihan += (float)$bill['amount'];
            $totalDibayar += (float)$bill['paid_amount'];
            $sisa = (float)$bill['amount'] - (float)$bill['paid_amount'];
            if ($sisa > 0) {
                $tunggakan += $sisa;
                $siswaMenunggak[$bill['student_id']] = true;
            }
        }

        $jumlahSiswaMenunggak = count($siswaMenunggak);

        // Kirim semua data ke view
        return view('home/index', [
            'title' => 'Home',
            'totals' => $totals,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_tagihan' => $totalTagihan,
            'total_dibayar' => $totalDibayar,
            'total_tunggakan' => $tunggakan,
            'jumlah_siswa_menunggak' => $jumlahSiswaMenunggak
        ]);
    }

    public function develop()
    {
        $data = ['title' => 'Develop'];
        return view('home/develop', $data);
    }

    public function about()
    {
        $data = ['title' => 'About'];
        return view('home/about', $data);
    }
}
