<?php

namespace App\Controllers;

use App\Models\BillModel;
use App\Models\StudentModel;
use App\Controllers\BaseController;
use App\Models\PaymentCategoryModel;
use CodeIgniter\HTTP\ResponseInterface;
use Dompdf\Dompdf;

class BillsController extends BaseController
{
    protected $billModel;
    protected $studentModel;
    protected $categoryModel;

    public function __construct()
    {
        helper(['form', 'url']);
        $this->billModel = new BillModel();
        $this->studentModel = new StudentModel();
        $this->categoryModel = new PaymentCategoryModel();
    }

    // ================= INDEX =================
    public function index()
    {
        $request = $this->request;

        // -------------------------
        // Ambil filter
        // -------------------------
        $search = $request->getGet('q');
        $selectedClass = $request->getGet('class');
        $selectedStatus = $request->getGet('status');
        $selectedMonth = $request->getGet('month');
        $selectedYear = $request->getGet('year');

        $perPage = 10;

        // -------------------------
        // Ambil siswa sesuai filter
        // -------------------------
        $studentBuilder = $this->studentModel;

        if ($search) $studentBuilder = $studentBuilder->like('name', $search);
        if ($selectedClass) $studentBuilder = $studentBuilder->where('class', $selectedClass);

        $students = $studentBuilder->paginate($perPage);
        $pager = $this->studentModel->pager;

        // Ambil semua bills sesuai filter
        $billsQuery = $this->billModel->select('bills.*, payment_categories.name as category_name')
            ->join('payment_categories', 'payment_categories.id = bills.category_id');

        if ($selectedStatus) $billsQuery = $billsQuery->where('status', $selectedStatus);
        if ($selectedMonth) $billsQuery = $billsQuery->where('month', $selectedMonth);
        if ($selectedYear) $billsQuery = $billsQuery->where('year', $selectedYear);

        $allBills = $billsQuery->findAll();

        // -------------------------
        // Group bills per student
        // -------------------------
        $billsGrouped = [];
        foreach ($allBills as $bill) {
            $billsGrouped[$bill['student_id']][] = $bill;
        }

        // Ambil list kelas untuk filter dropdown
        $classes = $this->studentModel->select('class')->distinct()->findColumn('class');

        // Kirim data ke view
        return view('bills/index', [
            'students' => $students,
            'pager' => $pager,
            'billsGrouped' => $billsGrouped,
            'classes' => $classes,
            'search' => $search,
            'selectedClass' => $selectedClass,
            'selectedStatus' => $selectedStatus,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear
        ]);
    }

    public function detail($studentId)
    {
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Siswa tidak ditemukan');
        }

        // Ambil semua tagihan siswa + join kategori
        $bills = $this->billModel
            ->select('bills.*, payment_categories.name as category_name')
            ->join('payment_categories', 'payment_categories.id = bills.category_id')
            ->where('student_id', $studentId)
            ->findAll();

        return view('bills/detail', [
            'student' => $student,
            'bills' => $bills
        ]);
    }

    // ================= GENERATE =================
    public function generate()
    {
        // Ambil semua siswa
        $students = $this->studentModel->findAll();

        // Ambil kategori pembayaran
        $categories = $this->categoryModel->findAll();

        // Loop siswa & generate tagihan sesuai kategori default
        foreach ($students as $student) {
            foreach ($categories as $cat) {
                $exists = $this->billModel->where('student_id', $student['id'])
                    ->where('category_id', $cat['id'])
                    ->where('month', date('m'))
                    ->where('year', date('Y'))
                    ->first();

                if (!$exists) {
                    $this->billModel->insert([
                        'student_id' => $student['id'],
                        'category_id' => $cat['id'],
                        'month' => date('m'),
                        'year' => date('Y'),
                        'amount' => $cat['default_amount'],
                        'status' => 'unpaid',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }

        return redirect()->to('/bills')->with('success', 'Tagihan berhasil digenerate otomatis.');
    }

    public function print($studentId)
    {
        $student = $this->studentModel->find($studentId);
        if (!$student) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Siswa tidak ditemukan');
        }

        $bills = $this->billModel
            ->select('bills.*, payment_categories.name as category_name')
            ->join('payment_categories', 'payment_categories.id = bills.category_id')
            ->where('student_id', $studentId)
            ->findAll();

        // Load view sebagai HTML
        $html = view('bills/print', [
            'student' => $student,
            'bills' => $bills
        ]);

        // Inisialisasi Dompdf
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Stream PDF ke browser
        $dompdf->stream('tagihan_' . $student['name'] . '.pdf', ['Attachment' => false]);
    }
}
