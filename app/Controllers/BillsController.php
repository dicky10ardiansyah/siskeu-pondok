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
    protected $ruleModel;
    protected $studentModel;
    protected $categoryModel;
    protected $billPaymentModel;
    protected $paymentCategoryModel;
    protected $studentPaymentRuleModel;
    protected $userModel;

    protected $helpers = ['form', 'url'];

    public function __construct()
    {
        $this->billModel               = new BillModel();
        $this->ruleModel               = new StudentPaymentRuleModel();
        $this->studentPaymentRuleModel = new StudentPaymentRuleModel();
        $this->studentModel            = new StudentModel();
        $this->categoryModel           = new PaymentCategoryModel();
        $this->billPaymentModel        = new BillPaymentModel();
        $this->paymentCategoryModel    = new PaymentCategoryModel();
        $this->userModel               = new UserModel();
        helper(['form', 'url']);
    }

    // --------------------------------------------------
    // INDEX: daftar siswa
    // --------------------------------------------------
    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $filter_status = $this->request->getGet('filter_status');
        $filter_user_id = $this->request->getGet('user_id');

        $session = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');

        $builder = $this->billModel
            ->select('students.id as student_id, students.name as student, students.user_id')
            ->join('students', 'students.id = bills.student_id')
            ->distinct()
            ->orderBy('students.name', 'ASC');

        // USER biasa → filter siswa miliknya
        if ($userRole !== 'admin') {
            $builder->where('students.user_id', $userId);
        } elseif ($filter_user_id) {
            // ADMIN → filter jika pilih user tertentu
            $builder->where('students.user_id', $filter_user_id);
        }

        if ($keyword) {
            $builder->like('students.name', $keyword);
        }

        $students = $builder->paginate(10, 'bills');
        $pager = $this->billModel->pager;

        // Ambil list user untuk dropdown (admin saja)
        $users = [];
        if ($userRole === 'admin') {
            $users = $this->userModel->findAll(); // ← pakai properti, bukan new UserModel()
        }

        // Hitung status tagihan
        foreach ($students as &$s) {
            $unpaidCount = $this->billModel
                ->where('student_id', $s['student_id'])
                ->where('status !=', 'paid')
                ->countAllResults();
            $s['status_tagihan'] = $unpaidCount === 0 ? 'Lunas' : 'Tunggakan';
        }

        return view('billing/index', [
            'bills' => $students,
            'pager' => $pager,
            'keyword' => $keyword,
            'filter_status' => $filter_status,
            'filter_user_id' => $filter_user_id,
            'users' => $users
        ]);
    }

    // --------------------------------------------------
    // GENERATE TAGIHAN
    // --------------------------------------------------
    public function generateBills()
    {
        $month = $this->request->getPost('month');
        $year  = $this->request->getPost('year');

        if (!$month || !$year) {
            return redirect()->back()->with('error', 'Bulan dan Tahun harus dipilih!');
        }

        $session = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');

        // Ambil siswa sesuai role
        if ($userRole === 'admin') {
            $students = $this->studentModel->findAll();
        } else {
            $students = $this->studentModel->where('user_id', $userId)->findAll();
        }

        $categories = $this->categoryModel->findAll();
        if (!$categories) {
            return redirect()->back()->with('error', 'Belum ada kategori pembayaran.');
        }

        $generated = false;

        foreach ($students as $student) {
            $rules = $this->studentPaymentRuleModel
                ->where('student_id', $student['id'])
                ->findAll();

            if (!$rules) {
                foreach ($categories as $category) {
                    $this->studentPaymentRuleModel->insert([
                        'student_id'  => $student['id'],
                        'category_id' => $category['id'],
                        'amount'      => $category['default_amount'] ?? 0,
                        'is_mandatory' => 1,
                        'created_at'  => date('Y-m-d H:i:s'),
                        'updated_at'  => date('Y-m-d H:i:s'),
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

                $billMonth = null;

                if ($category['billing_type'] == 'one-time') {
                    $existingBill = $this->billModel
                        ->where('student_id', $student['id'])
                        ->where('category_id', $rule['category_id'])
                        ->first();
                    if ($existingBill) continue;
                } else {
                    $billMonth = $month;
                    $existingBill = $this->billModel
                        ->where('student_id', $student['id'])
                        ->where('category_id', $rule['category_id'])
                        ->where('month', $month)
                        ->where('year', $year)
                        ->first();
                    if ($existingBill) continue;

                    if ($category['duration_months']) {
                        $firstBill = $this->billModel
                            ->where('student_id', $student['id'])
                            ->where('category_id', $rule['category_id'])
                            ->orderBy('year', 'ASC')
                            ->orderBy('month', 'ASC')
                            ->first();

                        $startYear  = $firstBill['year'] ?? $year;
                        $startMonth = $firstBill['month'] ?? $month;
                        $monthsPassed = ($year - $startYear) * 12 + ($month - $startMonth);
                        if ($monthsPassed >= $category['duration_months']) continue;
                    }
                }

                $amount = $rule['amount'] ?? $category['default_amount'] ?? 0;

                $this->billModel->insert([
                    'student_id'  => $student['id'],
                    'category_id' => $rule['category_id'],
                    'month'       => $billMonth,
                    'year'        => $year,
                    'amount'      => $amount,
                    'paid_amount' => 0,
                    'status'      => 'unpaid',
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

    // --------------------------------------------------
    // DETAIL TAGIHAN SISWA
    // --------------------------------------------------
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

        $allBills = $this->billModel->where('student_id', $student_id)
            ->orderBy('year', 'ASC')
            ->orderBy('month', 'ASC')
            ->findAll();

        $monthly  = [];
        $one_time = [];
        $totalBills = 0;
        $totalPayments = 0;

        foreach ($allBills as $bill) {
            $billPayments = $this->billPaymentModel
                ->select('amount, created_at')
                ->where('bill_id', $bill['id'])
                ->findAll();

            $category = $this->paymentCategoryModel->find($bill['category_id']);
            $status = 'unpaid';
            if (($bill['paid_amount'] ?? 0) >= $bill['amount']) $status = 'paid';
            elseif (($bill['paid_amount'] ?? 0) > 0) $status = 'partial';

            $billData = [
                'id' => $bill['id'],
                'category' => $category['name'] ?? '-',
                'amount' => $bill['amount'],
                'paid_amount' => $bill['paid_amount'] ?? 0,
                'status' => $status,
                'is_partial_payment' => ($bill['paid_amount'] ?? 0) > 0 && ($bill['paid_amount'] ?? 0) < $bill['amount'],
                'payment_breakdown' => $billPayments,
                'month' => $bill['month'],
                'year' => $bill['year'],
                'partial_reason' => $bill['partial_reason'] ?? null
            ];

            if ($category['billing_type'] === 'monthly') $monthly[] = $billData;
            else $one_time[] = $billData;

            $totalBills += $bill['amount'];
            $totalPayments += $bill['paid_amount'] ?? 0;
        }

        $totalPaymentsWithOverpaid = $totalPayments + ($student['overpaid'] ?? 0);
        $amountDueNow = $totalBills - $totalPaymentsWithOverpaid;
        $overpaid = 0;
        if ($amountDueNow < 0) {
            $overpaid = abs($amountDueNow);
            $amountDueNow = 0;
        }

        return view('billing/detail', [
            'student'       => $student,
            'monthly'       => $monthly,
            'one_time'      => $one_time,
            'totalBills'    => $totalBills,
            'totalPayments' => $totalPaymentsWithOverpaid,
            'amountDueNow'  => $amountDueNow,
            'overpaid'      => $overpaid
        ]);
    }

    // --------------------------------------------------
    // DELETE DETAIL TAGIHAN
    // --------------------------------------------------
    public function deleteDetail($bill_id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['error' => 'Invalid request']);
        }

        $bill = $this->billModel->find($bill_id);
        if (!$bill) return $this->response->setJSON(['error' => 'Tagihan tidak ditemukan']);

        $student = $this->studentModel->find($bill['student_id']);
        $session = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');
        if ($userRole !== 'admin' && $student['user_id'] != $userId) {
            return $this->response->setJSON(['error' => 'Tidak punya akses']);
        }

        $this->billPaymentModel->where('bill_id', $bill_id)->delete();
        $this->billModel->delete($bill_id);

        return $this->response->setJSON(['success' => 'Tagihan berhasil dihapus']);
    }

    // --------------------------------------------------
    // PDF
    // --------------------------------------------------
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

        $monthly = [];
        $one_time = [];
        $totalBills = 0;
        $totalPayments = 0;

        foreach ($bills as $b) {
            $paymentBreakdown = $this->billPaymentModel
                ->select('amount, created_at')
                ->where('bill_id', $b['id'])
                ->findAll();

            $status = 'unpaid';
            if (($b['paid_amount'] ?? 0) >= $b['amount']) $status = 'paid';
            elseif (($b['paid_amount'] ?? 0) > 0) $status = 'partial';

            $data = [
                'id' => $b['id'],
                'category_name' => $b['category_name'],
                'amount' => $b['amount'],
                'paid_amount' => $b['paid_amount'] ?? 0,
                'status' => $status,
                'month' => $b['month'],
                'year' => $b['year'],
                'payment_breakdown' => $paymentBreakdown,
                'partial_reason' => $b['partial_reason'] ?? null
            ];

            if ($b['billing_type'] === 'monthly') $monthly[] = $data;
            else $one_time[] = $data;

            $totalBills += $b['amount'];
            $totalPayments += $b['paid_amount'] ?? 0;
        }

        $totalPaymentsWithOverpaid = $totalPayments + ($student['overpaid'] ?? 0);
        $amountDueNow = $totalBills - $totalPaymentsWithOverpaid;
        $overpaid = 0;
        if ($amountDueNow < 0) {
            $overpaid = abs($amountDueNow);
            $amountDueNow = 0;
        }

        $datePrint = date('d-m-Y');

        $html = view('billing/pdf', [
            'student'       => $student,
            'monthly'       => $monthly,
            'one_time'      => $one_time,
            'totalBills'    => $totalBills,
            'totalPayments' => $totalPaymentsWithOverpaid,
            'amountDueNow'  => $amountDueNow,
            'overpaid'      => $overpaid,
            'datePrint'     => $datePrint
        ]);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("Tagihan-{$student['name']}.pdf", ['Attachment' => false]);
    }
}
