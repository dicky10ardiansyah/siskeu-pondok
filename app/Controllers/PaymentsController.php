<?php

namespace App\Controllers;

use App\Models\BillModel;
use App\Models\AccountModel;
use App\Models\JournalModel;
use App\Models\PaymentModel;
use App\Models\StudentModel;
use App\Models\BillPaymentModel;
use App\Controllers\BaseController;
use App\Models\StudentPaymentRuleModel;
use CodeIgniter\HTTP\ResponseInterface;

class PaymentsController extends BaseController
{
    protected $paymentModel;
    protected $studentModel;
    protected $accountModel;
    protected $journalModel;
    protected $billModel;
    protected $billPaymentModel;
    protected $studentPaymentRuleModel;
    protected $db;
    protected $helpers = ['form'];

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
        $this->studentModel = new StudentModel();
        $this->accountModel = new AccountModel();
        $this->journalModel = new JournalModel();
        $this->billModel    = new BillModel();
        $this->billPaymentModel = new BillPaymentModel();
        $this->studentPaymentRuleModel = new StudentPaymentRuleModel();
        $this->db = \Config\Database::connect();
    }

    // --------------------------------------------------
    // INDEX + SEARCH + PAGINATION
    // --------------------------------------------------
    public function index()
    {
        $search = $this->request->getGet('q');
        $perPage = 10;

        $session = session();
        $role = $session->get('user_role');
        $userId = $session->get('user_id');

        $builder = $this->paymentModel
            ->select('payments.*, students.name as student_name, 
                      debit_accounts.name as debit_account_name, debit_accounts.code as debit_account_code,
                      credit_accounts.name as credit_account_name, credit_accounts.code as credit_account_code')
            ->join('students', 'students.id = payments.student_id', 'left')
            ->join('accounts as debit_accounts', 'debit_accounts.id = payments.debit_account_id', 'left')
            ->join('accounts as credit_accounts', 'credit_accounts.id = payments.credit_account_id', 'left')
            ->orderBy('payments.id', 'DESC');

        // Filter berdasarkan user role
        if ($role !== 'admin') {
            $builder->where('students.user_id', $userId);
        } else {
            $reqUser = $this->request->getGet('user_id');
            if ($reqUser) {
                $builder->where('students.user_id', $reqUser);
            }
        }

        if ($search) {
            $builder->like('students.name', $search)
                ->orLike('debit_accounts.name', $search)
                ->orLike('credit_accounts.name', $search)
                ->orLike('payments.reference', $search)
                ->orLike('payments.method', $search);
        }

        $data['payments'] = $builder->paginate($perPage, 'payments');
        $data['pager'] = $builder->pager;
        $data['search'] = $search;

        return view('payments/index', $data);
    }

    // --------------------------------------------------
    // FORM CREATE
    // --------------------------------------------------
    public function create()
    {
        $session = session();
        $role = $session->get('user_role');
        $userId = $session->get('user_id');

        $selectedStudentId = $this->request->getGet('student_id');

        // Ambil student sesuai role
        $data['students'] = $role === 'admin'
            ? $this->studentModel->findAll()
            : $this->studentModel->where('user_id', $userId)->findAll();

        $data['selectedStudentId'] = $selectedStudentId;

        // Ambil account sesuai role
        $allAccounts = $role === 'admin'
            ? $this->accountModel->findAll()
            : $this->accountModel->where('user_id', $userId)->findAll();

        $data['debitAccounts']  = array_filter($allAccounts, fn($acc) => in_array($acc['type'], ['asset', 'expense']));
        $data['creditAccounts'] = array_filter($allAccounts, fn($acc) => in_array($acc['type'], ['liability', 'equity', 'income']));

        $data['journals'] = $this->journalModel->findAll();

        return view('payments/create', $data);
    }

    // --------------------------------------------------
    // STORE
    // --------------------------------------------------
    public function store()
    {
        $session = session();
        $role = $session->get('user_role');
        $userId = $session->get('user_id');

        $validationRules = [
            'student_id'        => 'required|integer',
            'debit_account_id'  => 'required|integer',
            'credit_account_id' => 'required|integer',
            'total_amount'      => 'required|decimal',
            'date'              => 'required',
            'method'            => 'permit_empty|string',
            'reference'         => 'permit_empty|string',
            'reference_file'    => 'permit_empty|uploaded[reference_file]|max_size[reference_file,2048]|ext_in[reference_file,jpg,jpeg,png,pdf]'
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Validasi student
        $studentId = $this->request->getPost('student_id');
        $student = $this->studentModel->find($studentId);
        if (!$student || ($role !== 'admin' && $student['user_id'] != $userId)) {
            return redirect()->back()->withInput()->with('error', 'Student tidak valid atau bukan milik Anda');
        }

        // Validasi akun
        $debitAccount  = $this->accountModel->find($this->request->getPost('debit_account_id'));
        $creditAccount = $this->accountModel->find($this->request->getPost('credit_account_id'));

        if ($role !== 'admin') {
            if (($debitAccount && $debitAccount['user_id'] != $userId) ||
                ($creditAccount && $creditAccount['user_id'] != $userId)
            ) {
                return redirect()->back()->withInput()->with('error', 'Akun tidak valid atau bukan milik Anda');
            }
        }

        $paymentData = [
            'student_id'        => $studentId,
            'debit_account_id'  => $this->request->getPost('debit_account_id'),
            'credit_account_id' => $this->request->getPost('credit_account_id'),
            'total_amount'      => $this->request->getPost('total_amount'),
            'date'              => $this->request->getPost('date'),
            'method'            => $this->request->getPost('method'),
            'reference'         => $this->request->getPost('reference'),
            'user_id'           => $userId
        ];

        $file = $this->request->getFile('reference_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(ROOTPATH . 'public/uploads', $newName);
            $paymentData['reference_file'] = $newName;
        }

        $this->paymentModel->save($paymentData);
        $paymentId = $this->paymentModel->getInsertID();

        // Buat jurnal
        $refText = $paymentData['reference'] ? " - Ref: {$paymentData['reference']}" : "";
        $description = "Payment dari {$student['name']} (NIS: {$student['nis']}){$refText}";
        $journalId = $this->journalModel->insert([
            'date' => $paymentData['date'],
            'description' => $description,
            'user_id' => $userId,
        ]);
        $this->paymentModel->update($paymentId, ['journal_id' => $journalId]);

        $journalEntryModel = new \App\Models\JournalEntryModel();
        $journalEntryModel->insert([
            'journal_id' => $journalId,
            'account_id' => $paymentData['debit_account_id'],
            'debit' => $paymentData['total_amount'],
            'credit' => 0,
        ]);
        $journalEntryModel->insert([
            'journal_id' => $journalId,
            'account_id' => $paymentData['credit_account_id'],
            'debit' => 0,
            'credit' => $paymentData['total_amount'],
        ]);

        $this->updateBills($studentId);

        return redirect()->to('/payments')->with('success', 'Payment berhasil disimpan!');
    }

    // --------------------------------------------------
    // FORM EDIT
    // --------------------------------------------------
    public function edit($id)
    {
        $payment = $this->paymentModel->find($id);
        if (!$payment) throw new \CodeIgniter\Exceptions\PageNotFoundException('Pembayaran tidak ditemukan');

        $session = session();
        $role = $session->get('user_role');
        $userId = $session->get('user_id');

        if ($role !== 'admin' && $payment['user_id'] != $userId) {
            throw new \CodeIgniter\Exceptions\PageForbiddenException('Tidak boleh mengakses pembayaran orang lain');
        }

        // Ambil accounts sesuai user
        $allAccounts = $role === 'admin'
            ? $this->accountModel->findAll()
            : $this->accountModel->where('user_id', $userId)->findAll();

        $data = [
            'payment'        => $payment,
            'students'       => $role === 'admin'
                ? $this->studentModel->findAll()
                : $this->studentModel->where('user_id', $userId)->findAll(),
            'debitAccounts'  => array_filter($allAccounts, fn($acc) => in_array($acc['type'], ['asset', 'expense'])),
            'creditAccounts' => array_filter($allAccounts, fn($acc) => in_array($acc['type'], ['liability', 'equity', 'income'])),
            'journals'       => $this->journalModel->findAll(),
        ];

        return view('payments/edit', $data);
    }

    // --------------------------------------------------
    // UPDATE
    // --------------------------------------------------
    public function update($id)
    {
        $payment = $this->paymentModel->find($id);
        if (!$payment) throw new \CodeIgniter\Exceptions\PageNotFoundException('Payment tidak ditemukan');

        $session = session();
        $role = $session->get('user_role');
        $userId = $session->get('user_id');

        if ($role !== 'admin' && $payment['user_id'] != $userId) {
            throw new \CodeIgniter\Exceptions\PageForbiddenException('Tidak boleh mengubah pembayaran orang lain');
        }

        $validationRules = [
            'student_id'        => 'required|integer',
            'debit_account_id'  => 'required|integer',
            'credit_account_id' => 'required|integer',
            'total_amount'      => 'required|decimal',
            'date'              => 'required',
            'method'            => 'permit_empty|string',
            'reference'         => 'permit_empty|string',
            'reference_file'    => 'permit_empty|max_size[reference_file,2048]|ext_in[reference_file,jpg,jpeg,png,pdf]'
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Validasi student
        $studentId = $this->request->getPost('student_id');
        $student = $this->studentModel->find($studentId);
        if (!$student || ($role !== 'admin' && $student['user_id'] != $userId)) {
            return redirect()->back()->withInput()->with('error', 'Student tidak valid atau bukan milik Anda');
        }

        // Validasi akun
        $debitAccount  = $this->accountModel->find($this->request->getPost('debit_account_id'));
        $creditAccount = $this->accountModel->find($this->request->getPost('credit_account_id'));
        if ($role !== 'admin') {
            if (($debitAccount && $debitAccount['user_id'] != $userId) ||
                ($creditAccount && $creditAccount['user_id'] != $userId)
            ) {
                return redirect()->back()->withInput()->with('error', 'Akun tidak valid atau bukan milik Anda');
            }
        }

        $data = [
            'student_id'        => $studentId,
            'debit_account_id'  => $this->request->getPost('debit_account_id'),
            'credit_account_id' => $this->request->getPost('credit_account_id'),
            'total_amount'      => $this->request->getPost('total_amount'),
            'date'              => $this->request->getPost('date'),
            'method'            => $this->request->getPost('method'),
            'reference'         => $this->request->getPost('reference'),
            'user_id'           => $userId,
        ];

        $file = $this->request->getFile('reference_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            if (!empty($payment['reference_file']) && file_exists(ROOTPATH . 'public/uploads/' . $payment['reference_file'])) {
                unlink(ROOTPATH . 'public/uploads/' . $payment['reference_file']);
            }
            $newName = $file->getRandomName();
            $file->move(ROOTPATH . 'public/uploads', $newName);
            $data['reference_file'] = $newName;
        }

        $this->paymentModel->update($id, $data);

        if ($payment['journal_id']) {
            $refText = $data['reference'] ? " - Ref: {$data['reference']}" : "";
            $description = "Payment dari {$student['name']} (NIS: {$student['nis']}){$refText}";
            $this->journalModel->update($payment['journal_id'], [
                'date' => $data['date'],
                'description' => $description,
                'user_id' => $userId,
            ]);

            $journalEntryModel = new \App\Models\JournalEntryModel();
            $journalEntryModel->where('journal_id', $payment['journal_id'])->delete();
            $journalEntryModel->insert([
                'journal_id' => $payment['journal_id'],
                'account_id' => $data['debit_account_id'],
                'debit' => $data['total_amount'],
                'credit' => 0,
            ]);
            $journalEntryModel->insert([
                'journal_id' => $payment['journal_id'],
                'account_id' => $data['credit_account_id'],
                'debit' => 0,
                'credit' => $data['total_amount'],
            ]);
        }

        $this->updateBills($studentId);

        return redirect()->to('/payments')->with('success', 'Payment berhasil diperbarui!');
    }

    // --------------------------------------------------
    // DELETE
    // --------------------------------------------------
    public function delete($id)
    {
        $payment = $this->paymentModel->find($id);
        if (!$payment) throw new \CodeIgniter\Exceptions\PageNotFoundException('Payment tidak ditemukan');

        $session = session();
        $role = $session->get('user_role');
        $userId = $session->get('user_id');

        if ($role !== 'admin' && $payment['user_id'] != $userId) {
            throw new \CodeIgniter\Exceptions\PageForbiddenException('Tidak boleh menghapus pembayaran orang lain');
        }

        $studentId = $payment['student_id'];

        if (!empty($payment['reference_file'])) {
            $filePath = ROOTPATH . 'public/uploads/' . $payment['reference_file'];
            if (file_exists($filePath)) unlink($filePath);
        }

        if ($payment['journal_id']) {
            $journalEntryModel = new \App\Models\JournalEntryModel();
            $journalEntryModel->where('journal_id', $payment['journal_id'])->delete();
            $this->journalModel->delete($payment['journal_id']);
        }

        $this->paymentModel->delete($id);

        $this->updateBills($studentId);

        return redirect()->to('/payments')->with('success', 'Payment berhasil dihapus beserta file referensi dan tagihan diperbarui!');
    }

    protected function updateBills($studentId)
    {
        // Ambil semua tagihan siswa urut berdasarkan tanggal/ID
        $bills = $this->billModel
            ->where('student_id', $studentId)
            ->orderBy('year', 'ASC')
            ->orderBy('month', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        if (!$bills) return;

        // Ambil semua pembayaran baru siswa
        $payments = (new \App\Models\PaymentModel())
            ->where('student_id', $studentId)
            ->orderBy('date', 'ASC')
            ->findAll();

        // Hitung total pembayaran baru
        $totalPayment = 0;
        foreach ($payments as $p) {
            $totalPayment += (float)$p['total_amount'];
        }

        $remainingPayment = $totalPayment;

        // Reset semua tagihan sebelum distribusi
        foreach ($bills as $bill) {
            $this->billModel->update($bill['id'], [
                'paid_amount' => 0,
                'status' => 'unpaid'
            ]);
        }

        // Distribusikan pembayaran baru ke bill satu per satu
        foreach ($bills as $bill) {
            if ($remainingPayment <= 0) break;

            $billAmount = (float)$bill['amount'];

            if ($remainingPayment >= $billAmount) {
                // Bayar penuh
                $this->billModel->update($bill['id'], [
                    'paid_amount' => $billAmount,
                    'status' => 'paid'
                ]);
                $remainingPayment -= $billAmount;
            } else {
                // Bayar sebagian
                $this->billModel->update($bill['id'], [
                    'paid_amount' => $remainingPayment,
                    'status' => 'partial'
                ]);
                $remainingPayment = 0;
            }
        }

        // Hitung sisa tagihan & overpaid
        $totalBills = 0;
        foreach ($bills as $bill) $totalBills += (float)$bill['amount'];

        $amountDueNow = max($totalBills - $totalPayment, 0);
        $overpaid = max($totalPayment - $totalBills, 0);

        // Simpan overpaid siswa hanya jika ada kelebihan
        $this->studentModel->update($studentId, [
            'overpaid' => $overpaid
        ]);

        // Return info ringkas (opsional, bisa untuk debug atau view)
        return [
            'totalBills' => $totalBills,
            'totalPayment' => $totalPayment,
            'amountDueNow' => $amountDueNow,
            'overpaid' => $overpaid
        ];
    }

    // --------------------------------------------------
    // PRINT RECEIPT
    // --------------------------------------------------
    public function receipt($payment_id)
    {
        $payment = $this->paymentModel
            ->select('payments.*, students.name as student_name, students.nis')
            ->join('students', 'students.id = payments.student_id', 'left')
            ->where('payments.id', $payment_id)
            ->first();

        if (!$payment) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Payment tidak ditemukan');

        $session = session();
        $role = $session->get('user_role');
        $userId = $session->get('user_id');

        if ($role !== 'admin' && $payment['user_id'] != $userId) {
            throw new \CodeIgniter\Exceptions\PageForbiddenException('Tidak boleh mengakses pembayaran orang lain');
        }

        $rules = $this->studentPaymentRuleModel
            ->select('student_payment_rules.amount, payment_categories.name as category_name')
            ->join('payment_categories', 'payment_categories.id = student_payment_rules.category_id')
            ->where('student_payment_rules.student_id', $payment['student_id'])
            ->where('student_payment_rules.is_mandatory', 1)
            ->orderBy('student_payment_rules.id', 'ASC')
            ->findAll();

        $dompdf = new \Dompdf\Dompdf();
        $html = view('payments/receipt', compact('payment', 'rules'));
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("Kwitansi-{$payment['student_name']}-{$payment['id']}.pdf", ['Attachment' => false]);
    }
}
