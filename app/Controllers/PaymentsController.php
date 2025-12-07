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

        $builder = $this->paymentModel
            ->select('payments.*, students.name as student_name, 
                      debit_accounts.name as debit_account_name, debit_accounts.code as debit_account_code,
                      credit_accounts.name as credit_account_name, credit_accounts.code as credit_account_code')
            ->join('students', 'students.id = payments.student_id', 'left')
            ->join('accounts as debit_accounts', 'debit_accounts.id = payments.debit_account_id', 'left')
            ->join('accounts as credit_accounts', 'credit_accounts.id = payments.credit_account_id', 'left')
            ->orderBy('payments.id', 'DESC');

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
        // Ambil student_id dari query string (misal dari tombol "Cek/Bayar")
        $selectedStudentId = $this->request->getGet('student_id');

        // Ambil semua siswa
        $data['students'] = $this->studentModel->findAll();
        $data['selectedStudentId'] = $selectedStudentId;

        // Ambil semua akun
        $allAccounts = $this->accountModel->findAll();

        // Filter debit accounts (asset, expense)
        $data['debitAccounts'] = array_filter($allAccounts, function ($acc) {
            return in_array($acc['type'], ['asset', 'expense']);
        });

        // Filter credit accounts (liability, equity, income)
        $data['creditAccounts'] = array_filter($allAccounts, function ($acc) {
            return in_array($acc['type'], ['liability', 'equity', 'income']);
        });

        // Ambil semua journals (opsional)
        $data['journals'] = $this->journalModel->findAll();

        // Load view create payment
        return view('payments/create', $data);
    }

    // --------------------------------------------------
    // STORE
    // --------------------------------------------------
    public function store()
    {
        $validationRules = [
            'student_id'       => 'required|integer',
            'debit_account_id' => 'required|integer',
            'credit_account_id' => 'required|integer',
            'total_amount'     => 'required|decimal',
            'date'             => 'required',
            'method'           => 'permit_empty|string',
            'reference'        => 'permit_empty|string',
            'reference_file'   => 'permit_empty|uploaded[reference_file]|max_size[reference_file,2048]|ext_in[reference_file,jpg,jpeg,png,pdf]'
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $paymentData = [
            'student_id'       => $this->request->getPost('student_id'),
            'debit_account_id' => $this->request->getPost('debit_account_id'),
            'credit_account_id' => $this->request->getPost('credit_account_id'),
            'total_amount'     => $this->request->getPost('total_amount'),
            'date'             => $this->request->getPost('date'),
            'method'           => $this->request->getPost('method'),
            'reference'        => $this->request->getPost('reference'),
        ];

        // Upload file referensi jika ada
        $file = $this->request->getFile('reference_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(ROOTPATH . 'public/uploads', $newName);  // simpan di public/uploads
            $paymentData['reference_file'] = $newName;
        }

        $this->paymentModel->save($paymentData);
        $paymentId = $this->paymentModel->getInsertID();

        // Buat jurnal
        $student = $this->studentModel->find($paymentData['student_id']);
        $refText = $paymentData['reference'] ? " - Ref: {$paymentData['reference']}" : "";
        $description = "Payment dari {$student['name']} (NIS: {$student['nis']}){$refText}";
        $journalId = $this->journalModel->insert([
            'date' => $paymentData['date'],
            'description' => $description,
            'user_id' => session()->get('user_id') ?? null,
        ]);
        $this->paymentModel->update($paymentId, ['journal_id' => $journalId]);

        // Journal entries
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

        $this->updateBills($paymentData['student_id']);

        return redirect()->to('/payments')->with('success', 'Payment berhasil disimpan!');
    }

    // --------------------------------------------------
    // FORM EDIT
    // --------------------------------------------------
    public function edit($id)
    {
        $payment = $this->paymentModel->find($id);

        if (!$payment) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Pembayaran tidak ditemukan');
        }

        $allAccounts = $this->accountModel->findAll();

        // Filter debit (asset, expense)
        $debitAccounts = array_filter($allAccounts, function ($acc) {
            return in_array($acc['type'], ['asset', 'expense']);
        });

        // Filter credit (liability, equity, income)
        $creditAccounts = array_filter($allAccounts, function ($acc) {
            return in_array($acc['type'], ['liability', 'equity', 'income']);
        });

        $data = [
            'payment'       => $payment,
            'students'      => $this->studentModel->findAll(),
            'debitAccounts' => $debitAccounts,
            'creditAccounts' => $creditAccounts,
            'journals'      => $this->journalModel->findAll(),
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

        $validationRules = [
            'student_id'       => 'required|integer',
            'debit_account_id' => 'required|integer',
            'credit_account_id' => 'required|integer',
            'total_amount'     => 'required|decimal',
            'date'             => 'required',
            'method'           => 'permit_empty|string',
            'reference'        => 'permit_empty|string',
            'reference_file'   => 'permit_empty|max_size[reference_file,2048]|ext_in[reference_file,jpg,jpeg,png,pdf]'
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'student_id'       => $this->request->getPost('student_id'),
            'debit_account_id' => $this->request->getPost('debit_account_id'),
            'credit_account_id' => $this->request->getPost('credit_account_id'),
            'total_amount'     => $this->request->getPost('total_amount'),
            'date'             => $this->request->getPost('date'),
            'method'           => $this->request->getPost('method'),
            'reference'        => $this->request->getPost('reference'),
        ];

        $file = $this->request->getFile('reference_file');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            // hapus file lama jika ada
            if (!empty($payment['reference_file']) && file_exists(ROOTPATH . 'public/uploads/' . $payment['reference_file'])) {
                unlink(ROOTPATH . 'public/uploads/' . $payment['reference_file']);
            }
            $newName = $file->getRandomName();
            $file->move(ROOTPATH . 'public/uploads', $newName); // simpan di public/uploads
            $data['reference_file'] = $newName;
        }

        $this->paymentModel->update($id, $data);

        if ($payment['journal_id']) {
            $student = $this->studentModel->find($data['student_id']);
            $refText = $data['reference'] ? " - Ref: {$data['reference']}" : "";
            $description = "Payment dari {$student['name']} (NIS: {$student['nis']}){$refText}";
            $this->journalModel->update($payment['journal_id'], [
                'date' => $data['date'],
                'description' => $description,
                'user_id' => session()->get('user_id') ?? null,
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

        $this->updateBills($data['student_id']);

        return redirect()->to('/payments')->with('success', 'Payment berhasil diperbarui!');
    }

    // --------------------------------------------------
    // DELETE
    // --------------------------------------------------
    public function delete($id)
    {
        $payment = $this->paymentModel->find($id);

        if (!$payment) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Payment tidak ditemukan');
        }

        $studentId = $payment['student_id'];

        // Hapus file referensi jika ada
        if (!empty($payment['reference_file'])) {
            $filePath = ROOTPATH . 'public/uploads/' . $payment['reference_file'];  // ambil dari public/uploads
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Hapus jurnal beserta journal_entries jika ada
        if ($payment['journal_id']) {
            $journalEntryModel = new \App\Models\JournalEntryModel();
            $journalEntryModel->where('journal_id', $payment['journal_id'])->delete();
            $this->journalModel->delete($payment['journal_id']);
        }

        // Hapus payment
        $this->paymentModel->delete($id);

        // Update bills
        $this->updateBills($studentId);

        return redirect()->to('/payments')->with('success', 'Payment berhasil dihapus beserta file referensi dan tagihan diperbarui!');
    }

    // --------------------------------------------------
    // FUNCTION UTILITY UPDATE BILLS
    // --------------------------------------------------
    private function updateBills($studentId)
    {
        // Reset semua bills student
        $allBills = $this->billModel->where('student_id', $studentId)->findAll();
        foreach ($allBills as $bill) {
            $this->billModel->update($bill['id'], [
                'paid_amount' => 0,
                'status'      => 'unpaid'
            ]);
        }

        // Hitung ulang semua payment student
        $payments = $this->paymentModel
            ->where('student_id', $studentId)
            ->orderBy('date', 'ASC')
            ->findAll();

        foreach ($payments as $p) {
            $totalPayment = (float)$p['total_amount'];
            $bills = $this->billModel
                ->where('student_id', $studentId)
                ->whereIn('status', ['unpaid', 'partial'])
                ->orderBy('year', 'ASC')
                ->orderBy('month', 'ASC')
                ->findAll();

            foreach ($bills as $bill) {
                $remaining = $bill['amount'] - $bill['paid_amount'];
                if ($totalPayment <= 0) break;

                if ($totalPayment >= $remaining) {
                    $bill['paid_amount'] += $remaining;
                    $bill['status'] = 'paid';
                    $totalPayment -= $remaining;
                } else {
                    $bill['paid_amount'] += $totalPayment;
                    $bill['status'] = 'partial';
                    $totalPayment = 0;
                }

                $this->billModel->update($bill['id'], [
                    'paid_amount' => $bill['paid_amount'],
                    'status'      => $bill['status']
                ]);
            }
        }
    }

    public function receipt($payment_id)
    {
        // Ambil data payment beserta nama siswa
        $payment = $this->paymentModel
            ->select('payments.*, students.name as student_name, students.nis')
            ->join('students', 'students.id = payments.student_id', 'left')
            ->where('payments.id', $payment_id)
            ->first();

        if (!$payment) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Payment tidak ditemukan');
        }

        // Ambil semua Student Payment Rule aktif untuk siswa
        $rules = $this->studentPaymentRuleModel
            ->select('student_payment_rules.amount, payment_categories.name as category_name')
            ->join('payment_categories', 'payment_categories.id = student_payment_rules.category_id')
            ->where('student_payment_rules.student_id', $payment['student_id'])
            ->where('student_payment_rules.is_mandatory', 1)
            ->orderBy('student_payment_rules.id', 'ASC')
            ->findAll();

        // Tampilkan PDF tanpa hitung total pembayaran
        $dompdf = new \Dompdf\Dompdf();
        $html = view('payments/receipt', compact('payment', 'rules'));
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("Kwitansi-{$payment['student_name']}-{$payment['id']}.pdf", ['Attachment' => false]);
    }
}
