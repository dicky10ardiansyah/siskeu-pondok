<?php

namespace App\Controllers;

use App\Models\BillModel;
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

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->billModel = new BillModel();
        $this->billPaymentModel = new BillPaymentModel();
        $this->paymentModel = new PaymentModel();
    }

    public function index()
    {
        $search       = $this->request->getGet('q');
        $filterClass  = $this->request->getGet('class');
        $filterYear   = $this->request->getGet('school_year');
        $filterStatus = $this->request->getGet('status_payment');
        $perPage      = 10;

        // Ambil siswa yang sudah lulus
        $graduateModel = $this->studentModel->where('status', true);

        if ($search) {
            $graduateModel = $graduateModel->groupStart()
                ->like('name', $search)
                ->orLike('nis', $search)
                ->groupEnd();
        }

        if ($filterClass) {
            $graduateModel = $graduateModel->where('class', $filterClass);
        }

        if ($filterYear) {
            $graduateModel = $graduateModel->where('school_year', $filterYear);
        }

        // Pagination
        $graduatesPaginate = $graduateModel->orderBy('name', 'ASC')->paginate($perPage, 'graduates');
        $pager = $graduateModel->pager;

        $dataGraduates = [];

        foreach ($graduatesPaginate as $student) {
            $tahunLulus = $student['school_year'] ?? '-';

            // Total Tagihan
            $bills = $this->billModel->where('student_id', $student['id'])->findAll();
            $totalBill = array_sum(array_column($bills, 'amount'));

            // Total Bayar
            $payments = $this->paymentModel->where('student_id', $student['id'])->findAll();
            $totalPaid = array_sum(array_column($payments, 'total_amount'));

            $statusPayment = ($totalPaid >= $totalBill) ? 'Lunas' : 'Tunggakan';

            // Filter status
            if ($filterStatus && $filterStatus != $statusPayment) {
                continue;
            }

            $dataGraduates[] = [
                'id' => $student['id'],
                'name' => $student['name'],
                'nis' => $student['nis'],
                'class' => $student['class'] ?? '-',
                'status_lulus' => 'Lulus',
                'school_year' => $tahunLulus,
                'total_bill' => $totalBill,
                'total_paid' => $totalPaid,
                'status_payment' => $statusPayment
            ];
        }

        // Ambil list kelas & tahun otomatis dari database
        $classes = $this->studentModel->select('class')->where('status', true)->groupBy('class')->orderBy('class')->findAll();
        $years   = $this->studentModel->select('school_year')->where('status', true)->groupBy('school_year')->orderBy('school_year')->findAll();

        return view('graduates/index', [
            'students'      => $dataGraduates,
            'pager'         => $pager,
            'search'        => $search,
            'filterClass'   => $filterClass,
            'filterYear'    => $filterYear,
            'filterStatus'  => $filterStatus,
            'classes'       => $classes,
            'years'         => $years
        ]);
    }
}
