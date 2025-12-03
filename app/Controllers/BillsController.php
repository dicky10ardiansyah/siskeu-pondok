<?php

namespace App\Controllers;

use Dompdf\Dompdf;
use App\Models\BillModel;
use App\Models\StudentModel;
use App\Controllers\BaseController;
use App\Models\PaymentCategoryModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Exceptions\PageNotFoundException;
use Dompdf\Options;

class BillsController extends BaseController
{
    protected $billModel;
    protected $studentModel;
    protected $categoryModel;
    protected $paymentModel;

    public function __construct()
    {
        $this->billModel     = new BillModel();
        $this->studentModel  = new StudentModel();
        $this->categoryModel = new PaymentCategoryModel();
        $this->paymentModel  = new \App\Models\PaymentModel();
    }

    public function index()
    {
        $keyword       = $this->request->getVar('keyword');
        $filter_month  = $this->request->getVar('filter_month');
        $filter_year   = $this->request->getVar('filter_year');
        $filter_status = $this->request->getVar('filter_status');

        $perPage = 10; // jumlah data per halaman

        // Query dasar
        $model = $this->billModel
            ->select("bills.student_id, students.name AS student, MAX(bills.month) AS month, MAX(bills.year) AS year")
            ->join("students", "students.id = bills.student_id")
            ->groupBy("bills.student_id");

        if ($keyword) {
            $model = $model->like("students.name", $keyword);
        }
        if ($filter_month) {
            $model = $model->where("bills.month", $filter_month);
        }
        if ($filter_year) {
            $model = $model->where("bills.year", $filter_year);
        }

        // Ambil data dengan paginate agar $pager tidak null
        $bills = $model->orderBy("students.name", "ASC")->paginate($perPage, 'bills');
        $pager = $model->pager;

        // Hitung status tiap siswa
        foreach ($bills as &$b) {
            $studentBills = $this->billModel->where('student_id', $b['student_id'])->findAll();
            $totalAmount  = array_sum(array_column($studentBills, 'amount'));
            $totalPaid    = array_sum(array_column($studentBills, 'paid_amount'));
            $b['status_tagihan'] = ($totalPaid >= $totalAmount) ? 'Lunas' : 'Tunggakan';
        }
        unset($b);

        // Filter status setelah hitung (array_filter)
        if ($filter_status) {
            $bills = array_filter($bills, function ($b) use ($filter_status) {
                return $b['status_tagihan'] == $filter_status;
            });
        }

        // Reset index agar pagination tetap benar setelah filter status
        $bills = array_values($bills);

        // Kirim data ke view
        $data = [
            'bills'        => $bills,
            'pager'        => $pager,
            'keyword'      => $keyword,
            'filter_month' => $filter_month,
            'filter_year'  => $filter_year,
            'filter_status' => $filter_status,
        ];

        return view("billing/index", $data);
    }

    public function generate()
    {
        $month = (int) $this->request->getVar("month");
        $year  = (int) $this->request->getVar("year");

        if (!$month || !$year) {
            return redirect()->back()->with("error", "Pilih bulan dan tahun!");
        }

        // Hanya siswa aktif (belum lulus)
        $students   = $this->studentModel->where('status', 0)->findAll();
        $categories = $this->categoryModel->findAll();

        foreach ($students as $s) {
            foreach ($categories as $c) {

                $duration = (int) ($c['duration_months'] ?? 0);

                if ($c['billing_type'] == 'monthly') {

                    if ($duration > 0) {
                        $firstBill = $this->billModel
                            ->where('student_id', $s['id'])
                            ->where('category_id', $c['id'])
                            ->orderBy('year ASC, month ASC')
                            ->first();

                        if ($firstBill) {
                            $firstMonth = (int) $firstBill['month'];
                            $firstYear  = (int) $firstBill['year'];

                            $monthsPassed = (($year - $firstYear) * 12)
                                + ($month - $firstMonth)
                                + 1;

                            if ($monthsPassed > $duration) {
                                continue;
                            }
                        }
                    }

                    $exists = $this->billModel
                        ->where('student_id', $s['id'])
                        ->where('category_id', $c['id'])
                        ->where('month', $month)
                        ->where('year', $year)
                        ->first();

                    if ($exists) continue;

                    $this->billModel->insert([
                        'student_id'  => $s['id'],
                        'category_id' => $c['id'],
                        'month'       => $month,
                        'year'        => $year,
                        'amount'      => $c['default_amount'] ?? 0,
                        'paid_amount' => 0,
                        'status'      => 'unpaid',
                    ]);
                }

                if ($c['billing_type'] == 'one-time') {
                    $exists = $this->billModel
                        ->where('student_id', $s['id'])
                        ->where('category_id', $c['id'])
                        ->first();

                    if ($exists) continue;

                    $this->billModel->insert([
                        'student_id'  => $s['id'],
                        'category_id' => $c['id'],
                        'month'       => null,
                        'year'        => $year,
                        'amount'      => $c['default_amount'] ?? 0,
                        'paid_amount' => 0,
                        'status'      => 'unpaid',
                    ]);
                }
            }
        }

        return redirect()->to("/billing")->with("success", "Generate tagihan berhasil!");
    }

    // ===============================
    // DETAIL SISWA
    // ===============================
    public function detail($studentId)
    {
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Siswa tidak ditemukan');
        }

        // Ambil semua tagihan
        $bills = $this->billModel
            ->select("bills.*, payment_categories.name AS category")
            ->join("payment_categories", "payment_categories.id = bills.category_id", "left")
            ->where("bills.student_id", $studentId)
            ->orderBy("year ASC, month ASC")
            ->findAll();

        // Ambil semua pembayaran student
        $payments = $this->paymentModel
            ->where('student_id', $studentId)
            ->orderBy('date', 'ASC')
            ->findAll();

        // Siapkan queue pembayaran (FIFO)
        $queue = [];
        $totalPayment = 0.0;
        foreach ($payments as $p) {
            $amount = (float) ($p['total_amount'] ?? $p['amount'] ?? 0);
            if ($amount <= 0) continue;

            $queue[] = [
                'amount' => $amount,
                'date'   => $p['date'] ?? ($p['created_at'] ?? null)
            ];
            $totalPayment += $amount;
        }

        $totalBills = array_sum(array_column($bills, 'amount'));

        // Distribusi pembayaran
        foreach ($bills as &$bill) {
            $billAmount = (float) $bill['amount'];
            $remaining = $billAmount;
            $bill['paid_amount'] = 0;
            $bill['payment_breakdown'] = [];
            $bill['is_partial_payment'] = false;
            $bill['partial_reason'] = '';

            foreach ($queue as &$q) {
                if ($remaining <= 0) break;
                if (!isset($q['amount']) || $q['amount'] <= 0) continue;

                $alloc = min($remaining, $q['amount']);

                $bill['payment_breakdown'][] = [
                    'amount' => $alloc,
                    'date'   => $q['date']
                ];

                $bill['paid_amount'] += $alloc;
                $remaining -= $alloc;
                $q['amount'] -= $alloc;
            }

            $bill['remaining'] = $remaining;

            // Tentukan status & partial reason
            if ($bill['paid_amount'] == 0) {
                $bill['status'] = 'Belum Bayar';
            } elseif ($bill['paid_amount'] < $billAmount) {
                $bill['status'] = 'Sebagian';
                $bill['is_partial_payment'] = true;
                $bill['partial_reason'] = 'Sisa ' . number_format($remaining, 0, ',', '.') . ' belum dibayar.';
            } else {
                $bill['status'] = 'Lunas';
            }
        }
        unset($bill);

        // Pisahkan billing bulanan & one-time
        $monthly = [];
        $one_time = [];
        foreach ($bills as $b) {
            if ($b['month']) $monthly[] = $b;
            else $one_time[] = $b;
        }

        // Hitung harus dibayar sekarang
        $amountDueNow = max(0, $totalBills - $totalPayment);

        return view('billing/detail', [
            'student'        => $student,
            'monthly'        => $monthly,
            'one_time'       => $one_time,
            'totalBills'     => $totalBills,
            'totalPayments'  => $totalPayment,
            'amountDueNow'   => $amountDueNow
        ]);
    }

    public function pdf($studentId)
    {
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Siswa tidak ditemukan');
        }

        // Ambil tagihan & pembayaran (sama seperti detail())
        $bills = $this->billModel
            ->select("bills.*, payment_categories.name AS category")
            ->join("payment_categories", "payment_categories.id = bills.category_id", "left")
            ->where("bills.student_id", $studentId)
            ->orderBy("year ASC, month ASC")
            ->findAll();

        $payments = $this->paymentModel
            ->where('student_id', $studentId)
            ->orderBy('date', 'ASC')
            ->findAll();

        $queue = [];
        $totalPayment = 0.0;
        foreach ($payments as $p) {
            $amount = (float) ($p['total_amount'] ?? $p['amount'] ?? 0);
            if ($amount <= 0) continue;

            $queue[] = [
                'amount' => $amount,
                'date'   => $p['date'] ?? ($p['created_at'] ?? null)
            ];
            $totalPayment += $amount;
        }

        $totalBills = array_sum(array_column($bills, 'amount'));

        foreach ($bills as &$bill) {
            $billAmount = (float) $bill['amount'];
            $remaining = $billAmount;
            $bill['paid_amount'] = 0;
            $bill['payment_breakdown'] = [];
            $bill['is_partial_payment'] = false;
            $bill['partial_reason'] = '';

            foreach ($queue as &$q) {
                if ($remaining <= 0) break;
                if (!isset($q['amount']) || $q['amount'] <= 0) continue;

                $alloc = min($remaining, $q['amount']);
                $bill['payment_breakdown'][] = [
                    'amount' => $alloc,
                    'date'   => $q['date']
                ];

                $bill['paid_amount'] += $alloc;
                $remaining -= $alloc;
                $q['amount'] -= $alloc;
            }

            $bill['remaining'] = $remaining;

            if ($bill['paid_amount'] == 0) {
                $bill['status'] = 'Belum Bayar';
            } elseif ($bill['paid_amount'] < $billAmount) {
                $bill['status'] = 'Sebagian';
                $bill['is_partial_payment'] = true;
                $bill['partial_reason'] = 'Sisa ' . number_format($remaining, 0, ',', '.') . ' belum dibayar.';
            } else {
                $bill['status'] = 'Lunas';
            }
        }
        unset($bill);

        // Pisahkan billing bulanan & one-time
        $monthly = [];
        $one_time = [];
        foreach ($bills as $b) {
            if ($b['month']) $monthly[] = $b;
            else $one_time[] = $b;
        }

        $amountDueNow = max(0, $totalBills - $totalPayment);

        // Load view HTML untuk PDF
        $html = view('billing/pdf', [
            'student'       => $student,
            'monthly'       => $monthly,
            'one_time'      => $one_time,
            'totalBills'    => $totalBills,
            'totalPayments' => $totalPayment,
            'amountDueNow'  => $amountDueNow,
            'datePrint'     => date('d M Y')
        ]);

        // Setup Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Output PDF
        $dompdf->stream("Billing_{$student['name']}.pdf", ["Attachment" => false]);
    }
}
