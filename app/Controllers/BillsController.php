<?php

namespace App\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\BillModel;
use App\Models\UserModel;
use App\Models\StudentModel;
use App\Models\BillPaymentModel;
use App\Controllers\BaseController;
use App\Models\PaymentCategoryModel;
use App\Models\StudentPaymentRuleModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class BillsController extends BaseController
{
    protected $billModel;
    protected $studentModel;
    protected $categoryModel;
    protected $billPaymentModel;
    protected $studentPaymentRuleModel;
    protected $userModel;

    protected $helpers = ['form', 'url'];

    public function __construct()
    {
        $this->billModel = new BillModel();
        $this->studentModel = new StudentModel();
        $this->categoryModel = new PaymentCategoryModel();
        $this->billPaymentModel = new BillPaymentModel();
        $this->studentPaymentRuleModel = new StudentPaymentRuleModel();
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $filter_user_id = $this->request->getGet('user_id');
        $filter_status = $this->request->getGet('filter_status');
        $filter_month = $this->request->getGet('filter_month');
        $filter_year = $this->request->getGet('filter_year');

        $session = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');

        $builder = $this->billModel
            ->select('students.id as student_id, students.name as student, students.user_id')
            ->join('students', 'students.id = bills.student_id')
            ->distinct()
            ->orderBy('students.name', 'ASC');

        if ($userRole !== 'admin') {
            $builder->where('students.user_id', $userId);
        } elseif ($filter_user_id) {
            $builder->where('students.user_id', $filter_user_id);
        }

        if ($keyword) {
            $builder->like('students.name', $keyword);
        }

        // Filter bulan & tahun (opsional)
        if ($filter_month) {
            $builder->where('bills.month', $filter_month);
        }
        if ($filter_year) {
            $builder->where('bills.year', $filter_year);
        }

        $students = $builder->paginate(10, 'bills');
        $pager = $this->billModel->pager;

        // Hitung status tagihan dan filter status di PHP
        $filteredStudents = [];
        foreach ($students as $s) {
            $unpaidCount = $this->billModel
                ->where('student_id', $s['student_id'])
                ->where('status !=', 'paid')
                ->countAllResults();

            $s['status_tagihan'] = $unpaidCount === 0 ? 'Lunas' : 'Tunggakan';

            if (!$filter_status || $filter_status === $s['status_tagihan']) {
                $filteredStudents[] = $s;
            }
        }

        $users = [];
        if ($userRole === 'admin') {
            $users = $this->userModel->findAll();
        }

        return view('billing/index', [
            'bills' => $filteredStudents,
            'pager' => $pager,
            'keyword' => $keyword,
            'filter_user_id' => $filter_user_id,
            'filter_status' => $filter_status,
            'filter_month' => $filter_month,
            'filter_year' => $filter_year,
            'users' => $users
        ]);
    }

    public function generateBills()
    {
        $month = $this->request->getPost('month');
        $year  = $this->request->getPost('year');

        if (!$month || !$year) {
            return redirect()->back()->with('error', 'Bulan dan Tahun harus dipilih!');
        }

        $session  = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');

        // Ambil siswa sesuai role
        $students = $userRole === 'admin'
            ? $this->studentModel->findAll()
            : $this->studentModel->where('user_id', $userId)->findAll();

        if (!$students) {
            return redirect()->back()->with('error', 'Tidak ada siswa.');
        }

        // Ambil kategori pembayaran sesuai user
        $categories = $userRole === 'admin'
            ? $this->categoryModel->findAll()
            : $this->categoryModel->where('user_id', $userId)->findAll();

        if (!$categories) {
            return redirect()->back()->with('error', 'Belum ada kategori pembayaran.');
        }

        $generated = false;

        foreach ($students as $student) {
            if ($userRole !== 'admin' && $student['user_id'] != $userId) continue;

            // Ambil rule siswa
            $rules = $this->studentPaymentRuleModel
                ->where('student_id', $student['id'])
                ->findAll();

            // Jika belum ada rule → buat dari kategori
            if (!$rules) {
                foreach ($categories as $category) {
                    if ($userRole !== 'admin' && $category['user_id'] != $student['user_id']) continue;

                    $this->studentPaymentRuleModel->insert([
                        'student_id'   => $student['id'],
                        'category_id'  => $category['id'],
                        'amount'       => $category['default_amount'] ?? 0,
                        'is_mandatory' => 1,
                        'created_at'   => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);
                }

                $rules = $this->studentPaymentRuleModel
                    ->where('student_id', $student['id'])
                    ->findAll();
            }

            foreach ($rules as $rule) {
                if ($rule['is_mandatory'] == 0) continue;

                $category = $this->categoryModel->find($rule['category_id']);
                if (!$category) continue;
                if ($userRole !== 'admin' && $category['user_id'] != $student['user_id']) continue;

                $billMonth = $category['billing_type'] === 'monthly' ? $month : null;

                // Cek tagihan sudah ada
                $existingBillQuery = $this->billModel
                    ->where('student_id', $student['id'])
                    ->where('category_id', $rule['category_id']);

                if ($category['billing_type'] === 'monthly') {
                    $existingBillQuery->where('month', $month)->where('year', $year);
                }

                if ($existingBillQuery->first()) continue;

                $amount = $rule['amount'] ?? $category['default_amount'] ?? 0;

                // Insert tagihan baru
                $this->billModel->insert([
                    'student_id'  => $student['id'],
                    'category_id' => $rule['category_id'],
                    'month'       => $billMonth,
                    'year'        => $year,
                    'amount'      => $amount,
                    'paid_amount' => 0,
                    'status'      => 'unpaid',
                    'user_id'     => $student['user_id'],
                    'created_at'  => date('Y-m-d H:i:s')
                ]);

                $generated = true;
            }
        }

        if (!$generated) {
            return redirect()->back()->with('error', 'Tidak ada tagihan baru yang bisa digenerate.');
        }

        return redirect()->to('/billing')->with('success', 'Billing berhasil digenerate!');
    }

    public function detail($student_id)
    {
        $student = $this->studentModel->find($student_id);
        if (!$student) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Siswa tidak ditemukan');

        $session = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');
        if ($userRole !== 'admin' && $student['user_id'] != $userId) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Siswa tidak ditemukan');
        }

        $bills = $this->billModel
            ->select('bills.*, payment_categories.name as category_name, payment_categories.billing_type')
            ->join('payment_categories', 'payment_categories.id = bills.category_id')
            ->where('student_id', $student_id)
            ->orderBy('year', 'ASC')
            ->orderBy('month', 'ASC')
            ->findAll();

        $totalBills = 0;
        $totalPayments = 0;
        $monthly = [];
        $one_time = [];

        foreach ($bills as $bill) {
            $paidAmount = (float)($bill['paid_amount'] ?? 0);
            $status = $paidAmount >= $bill['amount'] ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            $billData = [
                'id' => $bill['id'],
                'category' => $bill['category_name'] ?? '-',
                'amount' => $bill['amount'],
                'paid_amount' => $paidAmount,
                'status' => $status,
                'is_partial_payment' => $paidAmount > 0 && $paidAmount < $bill['amount'], // <== tambahkan ini
                'month' => $bill['month'],
                'year' => $bill['year']
            ];

            if ($bill['billing_type'] === 'monthly') $monthly[] = $billData;
            else $one_time[] = $billData;

            $totalBills += $bill['amount'];
            $totalPayments += $paidAmount;
        }

        $totalPaymentsWithOverpaid = $totalPayments + ($student['overpaid'] ?? 0);
        $amountDueNow = max($totalBills - $totalPaymentsWithOverpaid, 0);
        $overpaid = max($totalPaymentsWithOverpaid - $totalBills, 0);

        return view('billing/detail', [
            'student' => $student,
            'monthly' => $monthly,
            'one_time' => $one_time,
            'totalBills' => $totalBills,
            'totalPayments' => $totalPaymentsWithOverpaid,
            'amountDueNow' => $amountDueNow,
            'overpaid' => $overpaid
        ]);
    }

    public function pdf($student_id)
    {
        $student = $this->studentModel->find($student_id);
        if (!$student) throw \CodeIgniter\Exceptions\PageNotFoundException('Siswa tidak ditemukan');

        $session = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');
        if ($userRole !== 'admin' && $student['user_id'] != $userId) {
            throw \CodeIgniter\Exceptions\PageNotFoundException('Siswa tidak ditemukan');
        }

        $bills = $this->billModel
            ->select('bills.*, payment_categories.name as category_name, payment_categories.billing_type')
            ->join('payment_categories', 'payment_categories.id = bills.category_id')
            ->where('student_id', $student_id)
            ->orderBy('year', 'ASC')
            ->orderBy('month', 'ASC')
            ->findAll();

        $totalBills = 0;
        $totalPayments = 0;
        $monthly = [];
        $one_time = [];

        foreach ($bills as $bill) {
            $paidAmount = (float)($bill['paid_amount'] ?? 0);
            $status = $paidAmount >= $bill['amount'] ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            $billData = [
                'id' => $bill['id'],
                'category_name' => $bill['category_name'] ?? '-',
                'amount' => $bill['amount'],
                'paid_amount' => $paidAmount,
                'status' => $status,
                'month' => $bill['month'],
                'year' => $bill['year']
            ];

            if ($bill['billing_type'] === 'monthly') $monthly[] = $billData;
            else $one_time[] = $billData;

            $totalBills += $bill['amount'];
            $totalPayments += $paidAmount;
        }

        $totalPaymentsWithOverpaid = $totalPayments + ($student['overpaid'] ?? 0);
        $amountDueNow = max($totalBills - $totalPaymentsWithOverpaid, 0);
        $overpaid = max($totalPaymentsWithOverpaid - $totalBills, 0);

        $datePrint = date('d-m-Y');

        $html = view('billing/pdf', [
            'student' => $student,
            'monthly' => $monthly,
            'one_time' => $one_time,
            'totalBills' => $totalBills,
            'totalPayments' => $totalPaymentsWithOverpaid,
            'amountDueNow' => $amountDueNow,
            'overpaid' => $overpaid,
            'datePrint' => $datePrint
        ]);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("Tagihan-{$student['name']}.pdf", ['Attachment' => false]);
    }
}
