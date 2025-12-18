<?php

namespace App\Controllers;

use App\Models\BillModel;
use App\Models\UserModel;
use App\Models\ClassModel;
use App\Models\PaymentModel;
use App\Models\StudentModel;
use App\Models\BillPaymentModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class GraduateController extends BaseController
{
    protected $studentModel;
    protected $billModel;
    protected $billPaymentModel;
    protected $paymentModel;
    protected $userModel;
    protected $classModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->billModel = new BillModel();
        $this->billPaymentModel = new BillPaymentModel();
        $this->paymentModel = new PaymentModel();
        $this->userModel = new UserModel();
        $this->classModel   = new ClassModel();
    }

    public function index()
    {
        // Get filter inputs
        $search       = $this->request->getGet('q');
        $filterClass  = $this->request->getGet('class');
        $filterYear   = $this->request->getGet('school_year');
        $filterStatus = $this->request->getGet('status_payment');
        $filterUser   = $this->request->getGet('user_id');
        $perPage      = 10;

        // Get logged-in user
        $userId  = session()->get('user_id');
        $isAdmin = session()->get('user_role') === 'admin';

        // Base query
        $graduateModel = $this->studentModel->where('status', true);

        // NON ADMIN hanya melihat data miliknya
        if (!$isAdmin) {
            $graduateModel = $graduateModel->where('user_id', $userId);
        }

        // ADMIN dapat filter berdasarkan user
        if ($isAdmin && $filterUser) {
            $graduateModel = $graduateModel->where('user_id', $filterUser);
        }

        // Filter kelas (ID)
        if ($filterClass) {
            $graduateModel = $graduateModel->where('class', $filterClass);
        }

        // Filter tahun
        if ($filterYear) {
            $graduateModel = $graduateModel->where('school_year', $filterYear);
        }

        // Search
        if ($search) {
            $graduateModel = $graduateModel->groupStart()
                ->like('name', $search)
                ->orLike('nis', $search)
                ->groupEnd();
        }

        // Pagination
        $graduatesPaginate = $graduateModel->orderBy('name', 'ASC')->paginate($perPage, 'graduates');
        $pager = $graduateModel->pager;

        // Build data
        $dataGraduates = [];
        foreach ($graduatesPaginate as $student) {

            // Ambil nama kelas dari tabel classes
            $classRow  = $this->classModel->find($student['class']);
            $className = $classRow['name'] ?? '-';

            // Ambil tagihan & pembayaran
            $bills    = $this->billModel->where('student_id', $student['id'])->findAll();
            $payments = $this->paymentModel->where('student_id', $student['id'])->findAll();

            $totalBill = array_sum(array_column($bills, 'amount'));
            $totalPaid = array_sum(array_column($payments, 'total_amount'));
            $statusPayment = ($totalPaid >= $totalBill) ? 'Lunas' : 'Tunggakan';

            // Filter status pembayaran
            if ($filterStatus && $filterStatus != $statusPayment) {
                continue;
            }

            $dataGraduates[] = [
                'id'             => $student['id'],
                'name'           => $student['name'],
                'nis'            => $student['nis'],
                'class'          => $className,
                'status_lulus'   => 'Lulus',
                'school_year'    => $student['school_year'] ?? '-',
                'total_bill'     => $totalBill,
                'total_paid'     => $totalPaid,
                'status_payment' => $statusPayment,
                'user_name'      => $student['user_id']
                    ? $this->userModel->find($student['user_id'])['name']
                    : '-'
            ];
        }

        // Dropdown classes & years
        $classesQuery = $this->studentModel->select('class')->where('status', true);
        if (!$isAdmin) {
            $classesQuery = $classesQuery->where('user_id', $userId);
        }
        $classIds = $classesQuery->groupBy('class')->orderBy('class')->findAll();

        // Ambil nama kelas
        $classes = [];
        foreach ($classIds as $c) {
            $classRow = $this->classModel->find($c['class']);
            if ($classRow) {
                $classes[] = ['id' => $c['class'], 'name' => $classRow['name']];
            }
        }

        $years = $this->studentModel
            ->select('school_year')
            ->where('status', true)
            ->groupBy('school_year')
            ->orderBy('school_year')
            ->findAll();

        // User dropdown (admin only)
        $users = $isAdmin
            ? $this->userModel->findAll()
            : [$this->userModel->find($userId)];

        return view('graduates/index', [
            'students'      => $dataGraduates,
            'pager'         => $pager,
            'search'        => $search,
            'filterClass'   => $filterClass,
            'filterYear'    => $filterYear,
            'filterStatus'  => $filterStatus,
            'filterUser'    => $filterUser,
            'classes'       => $classes,
            'years'         => $years,
            'users'         => $users
        ]);
    }
}
