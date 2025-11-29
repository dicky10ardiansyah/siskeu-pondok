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

    public function index()
    {
        $request = $this->request;

        $search = $request->getGet('q');
        $selectedClass = $request->getGet('class');
        $selectedStatus = $request->getGet('status');
        $selectedMonth = $request->getGet('month') ?? date('m');
        $selectedYear = $request->getGet('year') ?? date('Y');

        $perPage = 10;

        // Pagination siswa (filter search & class)
        $studentBuilder = $this->studentModel;
        if ($search) $studentBuilder = $studentBuilder->like('name', $search);
        if ($selectedClass) $studentBuilder = $studentBuilder->where('class', $selectedClass);

        $students = $studentBuilder->paginate($perPage, 'students');
        $pager = $studentBuilder->pager;
        $pager->setPath('bills');

        // Ambil tagihan siswa di halaman ini
        $studentIds = array_column($students, 'id');
        $bills = [];
        if (!empty($studentIds)) {
            $billBuilder = $this->billModel
                ->select('bills.*, payment_categories.name as category_name, students.name as student_name, students.class')
                ->join('payment_categories', 'payment_categories.id = bills.category_id')
                ->join('students', 'students.id = bills.student_id')
                ->where('month', (int)$selectedMonth)
                ->where('year', (int)$selectedYear)
                ->whereIn('student_id', $studentIds)
                ->orderBy('students.name', 'ASC');

            if ($selectedStatus) {
                $billBuilder->where('bills.status', $selectedStatus);
            }

            $bills = $billBuilder->findAll();
        }

        // Ambil daftar kelas untuk dropdown
        $classes = $this->studentModel->select('class')->distinct()->findColumn('class');

        return view('bills/index', [
            'students' => $students,
            'pager' => $pager,
            'bills' => $bills,
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

    public function generate()
    {
        $month = $this->request->getPost('month') ?? date('m');
        $year = $this->request->getPost('year') ?? date('Y');

        $students = $this->studentModel->findAll();
        $categories = $this->categoryModel->findAll();

        if (empty($students)) {
            return redirect()->to('/bills')->with('error', 'Tidak ada siswa untuk digenerate.');
        }

        if (empty($categories)) {
            return redirect()->to('/bills')->with('error', 'Tidak ada kategori pembayaran untuk digenerate.');
        }

        $insertedCount = 0;
        $skippedCount = 0;

        foreach ($students as $student) {
            foreach ($categories as $cat) {
                $exists = $this->billModel->where('student_id', $student['id'])
                    ->where('category_id', $cat['id'])
                    ->where('month', (int)$month)
                    ->where('year', (int)$year)
                    ->first();

                if (!$exists) {
                    $this->billModel->insert([
                        'student_id' => $student['id'],
                        'category_id' => $cat['id'],
                        'month' => (int)$month,
                        'year' => (int)$year,
                        'amount' => $cat['default_amount'],
                        'status' => 'unpaid'
                    ]);
                    $insertedCount++;
                } else {
                    $skippedCount++;
                }
            }
        }

        $message = "Generate tagihan selesai. $insertedCount tagihan dibuat, $skippedCount tagihan sudah ada.";
        return redirect()->to('/bills')->with('success', $message);
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
