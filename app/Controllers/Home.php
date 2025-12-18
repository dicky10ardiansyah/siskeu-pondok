<?php

namespace App\Controllers;

use App\Models\BillModel;
use App\Models\UserModel;
use App\Models\AccountModel;
use App\Models\PaymentModel;
use App\Models\StudentModel;
use App\Models\TransactionModel;
use App\Models\JournalEntryModel;


class Home extends BaseController
{
    protected $accountModel;
    protected $billModel;
    protected $userModel;
    protected $paymentModel;

    public function __construct()
    {
        $this->accountModel = new AccountModel();
        $this->billModel = new BillModel();
        $this->userModel = new UserModel();
        $this->paymentModel = new PaymentModel();
    }

    public function index()
    {
        $session = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');

        $db = \Config\Database::connect();

        // =========================
        // Hitung Total Saldo Akun
        // =========================
        $accountTypes = ['asset', 'liability', 'equity', 'income', 'expense'];
        $totals = [];

        foreach ($accountTypes as $type) {
            $builder = $db->table('accounts a')
                ->select("SUM(CASE 
                    WHEN a.type IN ('asset','expense') THEN IFNULL(j.debit,0) - IFNULL(j.credit,0)
                    ELSE IFNULL(j.credit,0) - IFNULL(j.debit,0)
                  END) AS total")
                ->join('journal_entries j', 'j.account_id = a.id', 'left')
                ->where('a.type', $type);

            if ($userRole !== 'admin') {
                $builder->where('a.user_id', $userId);
            }

            $totals[$type] = (float) ($builder->get()->getRow()->total ?? 0);
        }

        // =========================
        // Hitung Tagihan & Pembayaran (informasi saja)
        // =========================
        $billBuilder = $db->table('bills');
        if ($userRole !== 'admin') $billBuilder->where('user_id', $userId);
        $allBills = $billBuilder->get()->getResultArray();

        $paymentBuilder = $db->table('payments');
        if ($userRole !== 'admin') $paymentBuilder->where('user_id', $userId);
        $allPayments = $paymentBuilder->get()->getResultArray();

        $total_tagihan = 0;
        $total_dibayar = 0;
        $siswaMenunggak = [];

        $siswaTagihan = [];
        foreach ($allBills as $bill) {
            $siswaTagihan[$bill['student_id']]['total_tagihan'] = ($siswaTagihan[$bill['student_id']]['total_tagihan'] ?? 0) + $bill['amount'];
        }
        foreach ($allPayments as $pay) {
            $siswaTagihan[$pay['student_id']]['total_dibayar'] = ($siswaTagihan[$pay['student_id']]['total_dibayar'] ?? 0) + $pay['total_amount'];
        }

        foreach ($siswaTagihan as $studentId => $vals) {
            $tagihan = $vals['total_tagihan'] ?? 0;
            $dibayar = $vals['total_dibayar'] ?? 0;
            $total_tagihan += $tagihan;
            $total_dibayar += $dibayar;

            if ($dibayar < $tagihan) $siswaMenunggak[] = $studentId;
        }

        // =========================
        // Hitung Total Tunggakan Akurat (sama dengan FSController)
        // =========================
        $billModel = new \App\Models\BillModel();
        $billBuilder = $billModel
            ->selectSum('(amount - paid_amount)', 'total_tunggakan')
            ->where('status !=', 'paid');

        if ($userRole !== 'admin') {
            $billBuilder->where('user_id', $userId);
        }

        $row = $billBuilder->get()->getRowArray();
        $total_tunggakan = (float) ($row['total_tunggakan'] ?? 0);

        // =========================
        // Ambil semua user (admin)
        // =========================
        $allUsers = [];
        if ($userRole === 'admin') {
            $allUsers = $db->table('users')->select('id, name')->get()->getResultArray();
        }

        // =========================
        // Kirim data ke view
        // =========================
        $data = [
            'title' => 'Home',
            'totals' => $totals,
            'total_tagihan' => $total_tagihan,
            'total_dibayar' => $total_dibayar,
            'total_tunggakan' => $total_tunggakan,   // sudah akurat
            'jumlah_siswa_menunggak' => count($siswaMenunggak),
            'allUsers' => $allUsers,
            'user_role' => $userRole,
            'selected_user_id' => $this->request->getGet('user_id') ?? '',
            'start_date' => $this->request->getGet('start_date'),
            'end_date' => $this->request->getGet('end_date'),
        ];

        return view('home/index', $data);
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
