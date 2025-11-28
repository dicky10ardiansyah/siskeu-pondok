<?php

namespace App\Controllers;

use App\Models\BillModel;
use App\Models\PaymentModel;
use App\Models\StudentModel;
use App\Models\BillPaymentModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class PaymentsController extends BaseController
{
    protected $paymentModel;
    protected $billModel;
    protected $billPaymentModel;
    protected $studentModel;
    protected $accountModel;

    public function __construct()
    {
        helper(['form', 'url']);
        $this->paymentModel = new PaymentModel();
        $this->billModel = new BillModel();
        $this->billPaymentModel = new BillPaymentModel();
        $this->studentModel = new StudentModel();
        $this->accountModel = new \App\Models\AccountModel();
    }

    public function index()
    {
        $search = $this->request->getGet('q');
        $perPage = 10;

        $builder = $this->paymentModel
            ->select('payments.*, students.name as student_name, accounts.name as account_name')
            ->join('students', 'students.id = payments.student_id')
            ->join('accounts', 'accounts.id = payments.account_id')
            ->orderBy('payments.id', 'DESC');

        if ($search) {
            $builder->groupStart()
                ->like('students.name', $search)
                ->orLike('accounts.name', $search)
                ->orLike('payments.reference', $search)
                ->orLike('payments.method', $search)
                ->groupEnd();
        }

        $data['payments'] = $builder->paginate($perPage);
        $data['pager'] = $this->paymentModel->pager;
        $data['search'] = $search;

        return view('payments/index', $data);
    }

    // Form pembayaran
    public function create()
    {
        $accounts = $this->accountModel->findAll();
        $students = $this->studentModel->findAll();

        // Cek query string student_id dari bills
        $preselectedStudent = $this->request->getGet('student_id');

        return view('payments/create', [
            'validation' => \Config\Services::validation(),
            'accounts' => $accounts,
            'students' => $students,
            'preselectedStudent' => $preselectedStudent
        ]);
    }

    // Simpan pembayaran & alokasikan ke bills otomatis
    public function store()
    {
        if (!$this->validate([
            'student_id' => 'required',
            'total_amount' => 'required|decimal',
            'date' => 'required',
            'account_id' => 'required|is_not_unique[accounts.id]'
        ])) {
            return redirect()->back()->withInput();
        }

        $studentId = $this->request->getPost('student_id');
        $amount = $this->request->getPost('total_amount');

        // simpan payment
        $this->paymentModel->insert([
            'student_id' => $studentId,
            'account_id' => $this->request->getPost('account_id'),
            'total_amount' => $amount,
            'date' => $this->request->getPost('date'),
            'method' => $this->request->getPost('method'),
            'reference' => $this->request->getPost('reference')
        ]);

        $paymentId = $this->paymentModel->getInsertID();

        $this->allocateToBills($studentId, $paymentId, $amount);

        session()->setFlashdata('success', 'Payment processed successfully.');
        return redirect()->to('/payments');
    }

    // Form edit pembayaran
    public function edit($id)
    {
        $payment = $this->paymentModel->find($id);
        if (!$payment) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Payment not found');
        }

        $students = $this->studentModel->findAll();
        $accounts = $this->accountModel->findAll(); // <-- penting

        return view('payments/edit', [
            'payment' => $payment,
            'students' => $students,
            'accounts' => $accounts,
            'validation' => \Config\Services::validation()
        ]);
    }

    // Update pembayaran & alokasi ulang
    public function update($id)
    {
        $payment = $this->paymentModel->find($id);
        if (!$payment) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Payment not found');
        }

        if (!$this->validate([
            'student_id' => 'required',
            'total_amount' => 'required|decimal',
            'date' => 'required',
            'account_id' => 'required|is_not_unique[accounts.id]'
        ])) {
            return redirect()->back()->withInput();
        }

        $studentId = $this->request->getPost('student_id');
        $amount = $this->request->getPost('total_amount');

        // revert alokasi lama
        $this->revertAllocation($id);

        // update payment
        $this->paymentModel->update($id, [
            'student_id' => $studentId,
            'account_id' => $this->request->getPost('account_id'),
            'total_amount' => $amount,
            'date' => $this->request->getPost('date'),
            'method' => $this->request->getPost('method'),
            'reference' => $this->request->getPost('reference')
        ]);

        // alokasi baru
        $this->allocateToBills($studentId, $id, $amount);

        session()->setFlashdata('success', 'Payment updated successfully.');
        return redirect()->to('/payments');
    }

    // Delete payment & revert alokasi
    public function delete($id)
    {
        $payment = $this->paymentModel->find($id);
        if (!$payment) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Payment not found');
        }

        // revert allocation
        $this->revertAllocation($id);

        // delete payment
        $this->paymentModel->delete($id);

        session()->setFlashdata('success', 'Payment deleted successfully.');
        return redirect()->to('/payments');
    }

    // =====================================================
    // Private helper functions
    // =====================================================

    // Alokasikan pembayaran ke bills secara otomatis
    private function allocateToBills($studentId, $paymentId, $amount)
    {
        $bills = $this->billModel->where('student_id', $studentId)
            ->where('status !=', 'paid')
            ->orderBy('id', 'ASC')
            ->findAll();

        $remaining = $amount;

        foreach ($bills as $bill) {
            if ($remaining <= 0) break;

            $payAmount = min($remaining, $bill['amount'] - ($bill['paid_amount'] ?? 0));

            // simpan BillPayment
            $this->billPaymentModel->insert([
                'bill_id' => $bill['id'],
                'payment_id' => $paymentId,
                'amount' => $payAmount
            ]);

            // update bill
            $newPaid = ($bill['paid_amount'] ?? 0) + $payAmount;
            $status = 'unpaid';
            if ($newPaid >= $bill['amount']) $status = 'paid';
            elseif ($newPaid > 0) $status = 'partial';

            $this->billModel->update($bill['id'], [
                'paid_amount' => $newPaid,
                'status' => $status
            ]);

            $remaining -= $payAmount;
        }
    }

    // Revert alokasi pembayaran lama
    private function revertAllocation($paymentId)
    {
        $allocations = $this->billPaymentModel->where('payment_id', $paymentId)->findAll();

        foreach ($allocations as $alloc) {
            $bill = $this->billModel->find($alloc['bill_id']);
            $newPaid = ($bill['paid_amount'] ?? 0) - $alloc['amount'];

            $status = 'unpaid';
            if ($newPaid >= $bill['amount']) $status = 'paid';
            elseif ($newPaid > 0) $status = 'partial';

            $this->billModel->update($bill['id'], [
                'paid_amount' => max(0, $newPaid),
                'status' => $status
            ]);
        }

        // hapus alokasi lama
        $this->billPaymentModel->where('payment_id', $paymentId)->delete();
    }
}
