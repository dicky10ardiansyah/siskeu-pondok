<?php

namespace App\Controllers;

use App\Models\BillModel;
use App\Models\AccountModel;
use App\Models\JournalModel;
use App\Models\PaymentModel;
use App\Models\StudentModel;
use App\Models\BillPaymentModel;
use App\Models\JournalEntryModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class PaymentsController extends BaseController
{
    protected $paymentModel;
    protected $billModel;
    protected $billPaymentModel;
    protected $studentModel;
    protected $accountModel;
    protected $journalModel;
    protected $journalEntryModel;

    protected $db;

    // di atas file: gunakan namespace & use sesuai controller Anda

    public function __construct()
    {
        helper(['form', 'url']);

        $this->db = \Config\Database::connect();

        $this->paymentModel = new PaymentModel();
        $this->billModel = new BillModel();
        $this->billPaymentModel = new BillPaymentModel();
        $this->studentModel = new StudentModel();
        $this->accountModel = new AccountModel();

        $this->journalModel = new JournalModel();
        $this->journalEntryModel = new JournalEntryModel();
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

        return view('payments/index', [
            'payments' => $builder->paginate($perPage),
            'pager' => $this->paymentModel->pager,
            'search' => $search
        ]);
    }

    public function create()
    {
        return view('payments/create', [
            'validation' => \Config\Services::validation(),
            'accounts' => $this->accountModel->findAll(),
            'students' => $this->studentModel->findAll(),
            'preselectedStudent' => $this->request->getGet('student_id')
        ]);
    }

    public function store()
    {
        if (!$this->validate([
            'student_id'    => 'required',
            'total_amount'  => 'required|decimal',
            'date'          => 'required',
            'account_id'    => 'required|is_not_unique[accounts.id]'
        ])) {
            return redirect()->back()->withInput()
                ->with('error', 'Validasi gagal. Periksa form Anda.');
        }

        $studentId = $this->request->getPost('student_id');
        $amount    = (float)$this->request->getPost('total_amount');
        $accountId = $this->request->getPost('account_id');
        $date      = $this->request->getPost('date');

        $db = \Config\Database::connect();
        $db->transStart();

        try {

            // INSERT PAYMENT
            $this->paymentModel->insert([
                'student_id'    => $studentId,
                'account_id'    => $accountId,
                'total_amount'  => $amount,
                'date'          => $date,
                'method'        => $this->request->getPost('method'),
                'reference'     => $this->request->getPost('reference')
            ]);

            $paymentId = $this->paymentModel->getInsertID();

            if (!$paymentId) {
                throw new \Exception('Gagal menyimpan data pembayaran.');
            }

            // CREATE JOURNAL (wajib akun income)
            $this->createJournalForPayment($paymentId, $studentId, $accountId, $amount, $date);

            // ALOKASI KE BILL
            $this->allocateToBills($studentId, $paymentId, $amount);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Terjadi kesalahan transaksi database.');
            }

            session()->setFlashdata('success', 'Payment berhasil disimpan.');
            return redirect()->to('/payments');
        } catch (\Exception $e) {

            $db->transRollback();

            // kirim pesan error ke view
            return redirect()->back()->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $payment = $this->paymentModel->find($id);
        if (!$payment) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Payment not found');
        }

        return view('payments/edit', [
            'payment' => $payment,
            'students' => $this->studentModel->findAll(),
            'accounts' => $this->accountModel->findAll(),
            'validation' => \Config\Services::validation()
        ]);
    }

    // update() — juga dibungkus transaksi dan hapus jurnal lama sebelum membuat yang baru
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
        $amount = (float)$this->request->getPost('total_amount');
        $accountId = $this->request->getPost('account_id');
        $date = $this->request->getPost('date');

        $this->db->transStart();

        try {
            // revert allocation first
            $this->revertAllocation($id);

            // delete old journal
            $this->deleteJournalByPayment($id);

            // update payment
            $this->paymentModel->update($id, [
                'student_id' => $studentId,
                'account_id' => $accountId,
                'total_amount' => $amount,
                'date' => $date,
                'method' => $this->request->getPost('method'),
                'reference' => $this->request->getPost('reference')
            ]);

            // create journal again
            $this->createJournalForPayment($id, $studentId, $accountId, $amount, $date);

            // reallocate
            $this->allocateToBills($studentId, $id, $amount);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaksi DB gagal saat mengupdate payment/jurnal.');
            }

            session()->setFlashdata('success', 'Payment updated successfully.');
            return redirect()->to('/payments');
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Payment update error: ' . $e->getMessage());
            session()->setFlashdata('error', 'Gagal mengupdate payment: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    // delete() — hapus jurnal dulu, revert allocation, kemudian delete payment, juga dibungkus transaksi
    public function delete($id)
    {
        $payment = $this->paymentModel->find($id);
        if (!$payment) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Payment not found');
        }

        $this->db->transStart();

        try {
            $this->revertAllocation($id);
            $this->deleteJournalByPayment($id);
            $this->paymentModel->delete($id);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaksi DB gagal saat delete payment.');
            }

            session()->setFlashdata('success', 'Payment deleted successfully.');
            return redirect()->to('/payments');
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Payment delete error: ' . $e->getMessage());
            session()->setFlashdata('error', 'Gagal menghapus payment: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    // ===============================
    // PRIVATE FUNCTIONS
    // ===============================

    private function createJournalForPayment($paymentId, $studentId, $accountId, $amount, $date)
    {
        // Ambil akun debit (kas/bank)
        $debitAcc = $this->accountModel->find($accountId);
        if (!$debitAcc) {
            throw new \Exception("Akun kas/bank dengan ID {$accountId} tidak ditemukan.");
        }

        // Default income account ID (ubah sesuai kebutuhan)
        $defaultIncomeAccountId = 5;

        // Cek apakah akun pendapatan default ada
        $incomeAcc = $this->accountModel->find($defaultIncomeAccountId);

        // Jika tidak ada, cari akun dengan type = 'income'
        if (!$incomeAcc) {
            $row = $this->db->table('accounts')->where('type', 'income')->limit(1)->get()->getRowArray();
            if ($row) {
                $incomeAccountId = $row['id'];
            } else {
                throw new \Exception('Tidak ditemukan akun pendapatan (income). Silakan buat akun bertipe "income".');
            }
        } else {
            $incomeAccountId = $defaultIncomeAccountId;
        }

        // Ambil nama siswa
        $student = $this->studentModel->find($studentId);
        $studentName = $student ? $student['name'] : 'Unknown';

        // Insert jurnal utama
        $this->journalModel->insert([
            'date' => $date,
            'description' => "Pembayaran siswa: {$studentName} (Payment ID: {$paymentId})",
            'user_id' => session()->get('id') ?? 1
        ]);
        $journalId = $this->journalModel->getInsertID();

        if (!$journalId) {
            throw new \Exception('Gagal membuat jurnal utama.');
        }

        // Insert journal_entries: DEBIT (kas/bank)
        $this->journalEntryModel->insert([
            'journal_id' => $journalId,
            'account_id' => $accountId,
            'debit' => $amount,
            'credit' => 0
        ]);

        // Insert journal_entries: KREDIT (pendapatan)
        $this->journalEntryModel->insert([
            'journal_id' => $journalId,
            'account_id' => $incomeAccountId,
            'debit' => 0,
            'credit' => $amount
        ]);

        return $journalId;
    }

    private function deleteJournalByPayment($paymentId)
    {
        $desc = "Payment ID: $paymentId";

        $journal = $this->journalModel
            ->like('description', $desc)
            ->first();

        if ($journal) {
            $this->journalEntryModel->where('journal_id', $journal['id'])->delete();
            $this->journalModel->delete($journal['id']);
        }
    }

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

            $this->billPaymentModel->insert([
                'bill_id' => $bill['id'],
                'payment_id' => $paymentId,
                'amount' => $payAmount
            ]);

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

        $this->billPaymentModel->where('payment_id', $paymentId)->delete();
    }
}
