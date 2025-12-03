<?php

namespace App\Controllers;

use App\Models\BillModel;
use App\Models\StudentModel;
use App\Models\BillPaymentModel;


class Home extends BaseController
{
    protected $billModel;
    protected $paymentModel;
    protected $studentModel;

    public function __construct()
    {
        $this->billModel = new BillModel();
        $this->paymentModel = new BillPaymentModel();
        $this->studentModel = new StudentModel();
    }

    public function index()
    {
        $year = $this->request->getGet('year') ?? date('Y');

        // Ringkasan
        $totalBill = $this->billModel->where('year', $year)->selectSum('amount')->first()['amount'];
        $totalPayment = $this->billModel->where('year', $year)->selectSum('paid_amount')->first()['paid_amount'];
        $totalDue = $totalBill - $totalPayment;
        $realization = $totalBill > 0 ? round(($totalPayment / $totalBill) * 100, 2) : 0;

        // Jumlah siswa menunggak (unique per siswa)
        $studentsInDebt = $this->billModel
            ->where('status', 'unpaid')
            ->where('year', $year)
            ->select('student_id')
            ->groupBy('student_id')
            ->countAllResults();

        // Chart bulanan
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $billsPerMonth = [];
        $paymentsPerMonth = [];
        foreach (range(1, 12) as $m) {
            $billsPerMonth[] = (int) $this->billModel
                ->where('month', $m)
                ->where('year', $year)
                ->selectSum('amount')
                ->first()['amount'];
            $paymentsPerMonth[] = (int) $this->billModel
                ->where('month', $m)
                ->where('year', $year)
                ->selectSum('paid_amount')
                ->first()['paid_amount'];
        }

        $data = [
            'title' => 'Dashboard Keuangan',
            'year' => $year,
            'summary' => [
                'total_bill' => $totalBill,
                'total_payment' => $totalPayment,
                'total_due' => $totalDue,
                'realization_percentage' => $realization,
                'students_in_debt' => $studentsInDebt
            ],
            'monthly_chart' => [
                'months' => $months,
                'bills' => $billsPerMonth,
                'payments' => $paymentsPerMonth
            ]
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
