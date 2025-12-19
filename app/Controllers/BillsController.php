<?php

namespace App\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\BillModel;
use App\Models\UserModel;
use App\Models\PaymentModel;
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
    protected $paymentModel;

    protected $helpers = ['form', 'url'];

    public function __construct()
    {
        $this->billModel = new BillModel();
        $this->studentModel = new StudentModel();
        $this->categoryModel = new PaymentCategoryModel();
        $this->billPaymentModel = new BillPaymentModel();
        $this->studentPaymentRuleModel = new StudentPaymentRuleModel();
        $this->userModel = new UserModel();
        $this->paymentModel = new PaymentModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $filter_user_id = $this->request->getGet('user_id');
        $filter_status = $this->request->getGet('filter_status');
        $filter_month = $this->request->getGet('filter_month');
        $filter_year = $this->request->getGet('filter_year');
        $filter_class = $this->request->getGet('filter_class');

        $session = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');

        // Query siswa + join kelas, hanya siswa belum lulus
        $builder = $this->billModel
            ->select('students.id as student_id, students.name as student, classes.name as kelas, students.user_id')
            ->join('students', 'students.id = bills.student_id')
            ->join('classes', 'classes.id = students.class', 'left')
            ->where('students.status', false) // Hanya siswa belum lulus
            ->distinct()
            ->orderBy('students.name', 'ASC');

        if ($userRole !== 'admin') {
            $builder->where('students.user_id', $userId);
        } elseif ($filter_user_id) {
            $builder->where('students.user_id', $filter_user_id);
        }

        if ($filter_class) {
            $builder->where('classes.id', $filter_class);
        }

        if ($keyword) {
            $builder->like('students.name', $keyword);
        }

        if ($filter_month) {
            $builder->where('bills.month', $filter_month);
        }
        if ($filter_year) {
            $builder->where('bills.year', $filter_year);
        }

        $students = $builder->paginate(10, 'bills');
        $pager = $this->billModel->pager;

        $filteredStudents = [];
        foreach ($students as $s) {
            $unpaidCount = $this->billModel
                ->where('student_id', $s['student_id'])
                ->where('status !=', 'paid')
                ->countAllResults();

            $s['status_tagihan'] = $unpaidCount === 0 ? 'Lunas' : 'Tunggakan';
            $s['kelas'] = $s['kelas'] ?? '-';

            if (!$filter_status || $filter_status === $s['status_tagihan']) {
                $filteredStudents[] = $s;
            }
        }

        // Ambil daftar user & kelas untuk dropdown filter
        $users = [];
        if ($userRole === 'admin') {
            $users = $this->userModel->findAll();
        }

        $db = \Config\Database::connect();
        $classes = $db->table('classes')->orderBy('name', 'ASC')->get()->getResultArray();

        return view('billing/index', [
            'bills' => $filteredStudents,
            'pager' => $pager,
            'keyword' => $keyword,
            'filter_user_id' => $filter_user_id,
            'filter_status' => $filter_status,
            'filter_month' => $filter_month,
            'filter_year' => $filter_year,
            'filter_class' => $filter_class,
            'users' => $users,
            'classes' => $classes
        ]);
    }

    public function generateBills()
    {
        $selectedMonth = (int) $this->request->getPost('month');
        $selectedYear  = (int) $this->request->getPost('year');
        $userIdFilter  = $this->request->getPost('user_id');

        if (!$selectedMonth || !$selectedYear) {
            return redirect()->back()->with('error', 'Bulan dan Tahun harus dipilih!');
        }

        $session  = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');

        // Ambil siswa sesuai role & filter user
        if ($userRole === 'admin') {
            $students = $userIdFilter
                ? $this->studentModel->where('user_id', $userIdFilter)->where('status', false)->findAll()
                : $this->studentModel->where('status', false)->findAll();
        } else {
            $students = $this->studentModel->where('user_id', $userId)->where('status', false)->findAll();
        }

        if (!$students) {
            return redirect()->back()->with('error', 'Tidak ada siswa.');
        }

        // Ambil kategori pembayaran sesuai user siswa
        $categories = $userRole === 'admin'
            ? $this->categoryModel->findAll()
            : $this->categoryModel->where('user_id', $userId)->findAll();

        if (!$categories) {
            return redirect()->back()->with('error', 'Belum ada kategori pembayaran.');
        }

        $generated = false;

        foreach ($students as $student) {
            if ($student['status']) continue; // skip siswa sudah lulus
            if ($userRole !== 'admin' && $student['user_id'] != $userId) continue;

            $rules = $this->studentPaymentRuleModel
                ->where('student_id', $student['id'])
                ->findAll();

            if (!$rules) {
                // jika belum ada aturan, buat semua kategori
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

                $amount = $rule['amount'] ?? $category['default_amount'] ?? 0;

                if ($category['billing_type'] === 'monthly') {
                    $duration = (int) ($category['duration_months'] ?? 0);

                    // Hitung jumlah tagihan yang sudah ada untuk kategori ini
                    $existingCount = $this->billModel
                        ->where('student_id', $student['id'])
                        ->where('category_id', $category['id'])
                        ->countAllResults();

                    // Jika durasi ada dan sudah penuh → skip
                    if ($duration > 0 && $existingCount >= $duration) continue;

                    // Cek apakah bulan ini sudah ada
                    $existing = $this->billModel
                        ->where('student_id', $student['id'])
                        ->where('category_id', $category['id'])
                        ->where('month', $selectedMonth)
                        ->where('year', $selectedYear)
                        ->first();

                    if ($existing) continue;

                    // Insert tagihan monthly
                    $this->billModel->insert([
                        'student_id'  => $student['id'],
                        'class_id'    => $student['class'],
                        'category_id' => $category['id'],
                        'month'       => $selectedMonth,
                        'year'        => $selectedYear,
                        'amount'      => $amount,
                        'paid_amount' => 0,
                        'status'      => 'unpaid',
                        'user_id'     => $student['user_id'],
                        'created_at'  => date('Y-m-d H:i:s')
                    ]);

                    $generated = true;
                } else {
                    // One-time billing
                    $existing = $this->billModel
                        ->where('student_id', $student['id'])
                        ->where('category_id', $category['id'])
                        ->first();

                    if ($existing) continue;

                    $this->billModel->insert([
                        'student_id'  => $student['id'],
                        'class_id'    => $student['class'],
                        'category_id' => $category['id'],
                        'month'       => $selectedMonth,
                        'year'        => $selectedYear,
                        'amount'      => $amount,
                        'paid_amount' => 0,
                        'status'      => 'unpaid',
                        'user_id'     => $student['user_id'],
                        'created_at'  => date('Y-m-d H:i:s')
                    ]);

                    $generated = true;
                }
            }
        }

        if (!$generated) {
            return redirect()->back()->with('error', 'Tidak ada tagihan baru yang bisa digenerate atau sudah ada tagihan.');
        }

        return redirect()->to('/billing')->with('success', 'Billing berhasil digenerate!');
    }

    public function detail($student_id)
    {
        $session  = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');

        $student = $this->studentModel->find($student_id);
        if (!$student) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Siswa tidak ditemukan');
        if ($userRole !== 'admin' && $student['user_id'] != $userId) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Siswa tidak ditemukan');

        $bills = $this->billModel
            ->select('bills.*, payment_categories.name as category_name, payment_categories.billing_type, classes.name as kelas')
            ->join('payment_categories', 'payment_categories.id = bills.category_id')
            ->join('classes', 'classes.id = bills.class_id', 'left')
            ->where('student_id', $student_id)
            ->orderBy('year', 'ASC')
            ->orderBy('month', 'ASC')
            ->findAll();

        $totalBills = 0;
        $totalPayments = 0;
        $monthly = [];
        $one_time = [];

        foreach ($bills as $bill) {
            $paidAmount = (float) ($bill['paid_amount'] ?? 0);
            $billData = [
                'id' => $bill['id'],
                'category' => $bill['category_name'] ?? '-',
                'kelas' => $bill['kelas'] ?? '-', // kelas historis
                'amount' => $bill['amount'],
                'paid_amount' => $paidAmount,
                'status' => $paidAmount >= $bill['amount'] ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid'),
                'is_partial_payment' => $paidAmount > 0 && $paidAmount < $bill['amount'],
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
        $overpaid     = max($totalPaymentsWithOverpaid - $totalBills, 0);

        $token = bin2hex(random_bytes(32));
        \Config\Database::connect()->table('billing_tokens')->insert([
            'student_id'  => $student['id'],
            'token'       => $token,
            'expired_at'  => date('Y-m-d H:i:s', strtotime('+1 day')),
            'access_count' => 0,
            'ip_address'  => null
        ]);
        $pdfSecureUrl = base_url('billing/pdf-secure/' . $token);

        return view('billing/detail', [
            'student' => $student,
            'monthly' => $monthly,
            'one_time' => $one_time,
            'totalBills' => $totalBills,
            'totalPayments' => $totalPaymentsWithOverpaid,
            'amountDueNow' => $amountDueNow,
            'overpaid' => $overpaid,
            'pdfSecureUrl' => $pdfSecureUrl,
        ]);
    }

    public function deleteDetail($id)
    {
        // Pastikan request AJAX
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'error' => 'Request tidak valid'
            ]);
        }

        $session  = session();
        $userRole = $session->get('user_role');
        $userId   = $session->get('user_id');

        // Cari bill
        $bill = $this->billModel->find($id);
        if (!$bill) {
            return $this->response->setJSON([
                'error' => 'Data tagihan tidak ditemukan'
            ]);
        }

        // Ambil data siswa
        $student = $this->studentModel->find($bill['student_id']);
        if (!$student) {
            return $this->response->setJSON([
                'error' => 'Data siswa tidak ditemukan'
            ]);
        }

        // Validasi hak akses
        if ($userRole !== 'admin' && $student['user_id'] != $userId) {
            return $this->response->setJSON([
                'error' => 'Anda tidak punya akses menghapus data ini'
            ]);
        }

        // ❗ Optional: cegah hapus jika sudah ada pembayaran
        if ((float)$bill['paid_amount'] > 0) {
            return $this->response->setJSON([
                'error' => 'Tagihan sudah memiliki pembayaran, tidak bisa dihapus'
            ]);
        }

        // Hapus data
        $this->billModel->delete($id);

        return $this->response->setJSON([
            'success' => 'Tagihan berhasil dihapus'
        ]);
    }

    public function pdf($student_id)
    {
        $student = $this->studentModel->find($student_id);
        if (!$student) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();

        $bills = $this->billModel
            ->select('bills.*, payment_categories.name as category_name, payment_categories.billing_type, classes.name as kelas')
            ->join('payment_categories', 'payment_categories.id = bills.category_id')
            ->join('classes', 'classes.id = bills.class_id', 'left')
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
                'kelas' => $bill['kelas'] ?? '-',
                'amount' => $bill['amount'],
                'paid_amount' => $paidAmount,
                'status' => $status,
                'is_partial_payment' => $paidAmount > 0 && $paidAmount < $bill['amount'],
                'month' => $bill['month'],
                'year' => $bill['year'],
            ];

            if ($bill['billing_type'] === 'monthly') $monthly[] = $billData;
            else $one_time[] = $billData;

            $totalBills += $bill['amount'];
            $totalPayments += $paidAmount;
        }

        $totalPaymentsWithOverpaid = $totalPayments + ($student['overpaid'] ?? 0);
        $amountDueNow = max($totalBills - $totalPaymentsWithOverpaid, 0);
        $overpaid = max($totalPaymentsWithOverpaid - $totalBills, 0);

        $payments = (new \App\Models\PaymentModel())
            ->where('student_id', $student_id)
            ->orderBy('date', 'ASC')
            ->findAll();

        $html = view('billing/pdf', [
            'student' => $student,
            'monthly' => $monthly,
            'one_time' => $one_time,
            'payments' => $payments,
            'totalBills' => $totalBills,
            'totalPayments' => $totalPaymentsWithOverpaid,
            'amountDueNow' => $amountDueNow,
            'overpaid' => $overpaid,
            'datePrint' => date('d-m-Y'),
        ]);

        while (ob_get_level() > 0) ob_end_clean();

        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdf = $dompdf->output();

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="billing_' . $student['name'] . '.pdf"')
            ->setHeader('Content-Length', strlen($pdf))
            ->setBody($pdf);
    }

    public function pdfSecure($token)
    {
        $db = \Config\Database::connect();
        $ip = $this->request->getIPAddress();

        $row = $db->table('billing_tokens')
            ->where('token', $token)
            ->where('expired_at >=', date('Y-m-d H:i:s'))
            ->get()
            ->getRowArray();

        if (!$row) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Link tidak valid atau sudah kadaluarsa'
            );
        }

        // =========================
        // 🔐 IP BINDING
        // =========================
        if (empty($row['ip_address'])) {
            // akses pertama → simpan IP
            $db->table('billing_tokens')
                ->where('id', $row['id'])
                ->update(['ip_address' => $ip]);
        } elseif ($row['ip_address'] !== $ip) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Akses ditolak'
            );
        }

        // =========================
        // 🔢 BATASI 5x AKSES
        // =========================
        if ($row['access_count'] >= 5) {
            $db->table('billing_tokens')->where('id', $row['id'])->delete();

            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Link sudah tidak bisa digunakan'
            );
        }

        // tambah counter
        $db->table('billing_tokens')
            ->where('id', $row['id'])
            ->set('access_count', 'access_count + 1', false)
            ->update();

        // =========================
        // 🔥 TOKEN SEKALI PAKAI (OPTIONAL)
        // =========================
        // kalau mau SEKALI BUKA, uncomment baris ini:
        // $db->table('billing_tokens')->where('id', $row['id'])->delete();

        return $this->pdf($row['student_id']);
    }
}
