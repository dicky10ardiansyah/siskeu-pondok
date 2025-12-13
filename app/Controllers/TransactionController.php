<?php

namespace App\Controllers;

use App\Models\AccountModel;
use App\Models\JournalModel;
use App\Models\TransactionModel;
use App\Models\JournalEntryModel;
use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

class TransactionController extends BaseController
{
    protected $transactions;
    protected $journals;
    protected $journalEntries;

    public function __construct()
    {
        $this->transactions = new TransactionModel();
        $this->journals = new JournalModel();
        $this->journalEntries = new JournalEntryModel();
        helper(['form', 'url']);
    }

    // --------------------------------------------------
    // INDEX + SEARCH + FILTER USER + PAGINATION
    // --------------------------------------------------
    public function index()
    {
        $keyword    = $this->request->getGet('search');
        $filterUser = $this->request->getGet('user_id');
        $perPage    = 10;
        $session    = session();
        $role       = $session->get('user_role');
        $userId     = $session->get('user_id');

        $builder = $this->transactions;

        // Filter user
        if ($role === 'admin' && $filterUser) {
            $builder = $builder->where('transactions.user_id', $filterUser);
        } elseif ($role !== 'admin') {
            $builder = $builder->where('transactions.user_id', $userId);
        }

        // Search
        if ($keyword) {
            $builder = $builder->groupStart()
                ->like('description', $keyword)
                ->orLike('type', $keyword)
                ->groupEnd();
        }

        // Pagination
        $transactions = $builder->orderBy('date', 'DESC')->paginate($perPage, 'transactions');
        $pager        = $builder->pager;

        // Ambil semua akun untuk mapping debit/credit
        $accounts = $this->transactions->getAccounts();

        // Ambil daftar user untuk admin
        $users = [];
        if ($role === 'admin') {
            $db    = \Config\Database::connect();
            $users = $db->table('users')->select('id, name')->orderBy('name')->get()->getResultArray();
        }

        $data = [
            'transactions' => $transactions,
            'accounts'     => $accounts,
            'pager'        => $pager,
            'keyword'      => $keyword,
            'filterUser'   => $filterUser,
            'users'        => $users,
        ];

        return view('transactions/index', $data);
    }

    // --------------------------------------------------
    // FORM CREATE
    // --------------------------------------------------
    public function create()
    {
        $allAccounts = $this->transactions->getAccounts();

        $debitAccounts = array_filter($allAccounts, fn($a) => in_array($a['type'], ['asset', 'expense']));
        $creditAccounts = array_filter($allAccounts, fn($a) => in_array($a['type'], ['asset', 'liability', 'equity', 'income']));

        return view('transactions/create', [
            'debitAccounts'  => $debitAccounts,
            'creditAccounts' => $creditAccounts,
        ]);
    }

    // --------------------------------------------------
    // STORE TRANSAKSI + UPLOAD BUKTI + JURNAL OTOMATIS
    // --------------------------------------------------
    public function store()
    {
        $post = $this->request->getPost();
        $file = $this->request->getFile('proof');

        // Validasi
        $validationRules = [
            'date' => 'required',
            'description' => 'required',
            'type' => 'required|in_list[income,expense]',
            'amount' => 'required|decimal',
            'debit_account_id' => 'required|integer',
            'credit_account_id' => 'required|integer',
            'proof' => 'permit_empty|max_size[proof,2048]|ext_in[proof,jpg,jpeg,png,pdf]',
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Upload file
        $proofPath = null;
        $uploadDir = ROOTPATH . 'public/uploads/proof/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $fileName = $file->getRandomName();
            $file->move($uploadDir, $fileName);
            $proofPath = 'uploads/proof/' . $fileName;
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // Simpan transaksi
        $transactionData = [
            'type'              => $post['type'],
            'description'       => $post['description'],
            'amount'            => $post['amount'],
            'date'              => $post['date'],
            'user_id'           => session()->get('user_id'),
            'debit_account_id'  => $post['debit_account_id'],
            'credit_account_id' => $post['credit_account_id'],
            'proof'             => $proofPath,
        ];

        $this->transactions->insert($transactionData);
        $transactionId = $this->transactions->getInsertID();

        // Buat jurnal
        $journalData = [
            'date'        => $post['date'],
            'description' => $post['description'],
            'user_id'     => session()->get('user_id'),
        ];
        $this->journals->insert($journalData);
        $journalId = $this->journals->getInsertID();

        // Buat journal entries
        $journalEntries = [
            [
                'journal_id' => $journalId,
                'account_id' => $post['debit_account_id'],
                'debit'      => $post['amount'],
                'credit'     => 0,
            ],
            [
                'journal_id' => $journalId,
                'account_id' => $post['credit_account_id'],
                'debit'      => 0,
                'credit'     => $post['amount'],
            ]
        ];
        $this->journalEntries->insertBatch($journalEntries);

        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan transaksi.');
        }

        return redirect()->to('/transactions')->with('success', 'Transaksi berhasil disimpan dan jurnal dibuat.');
    }

    // --------------------------------------------------
    // FORM EDIT
    // --------------------------------------------------
    public function edit($id)
    {
        $transaction = $this->transactions->find($id);
        if (!$transaction || (session()->get('user_role') !== 'admin' && $transaction['user_id'] != session()->get('user_id'))) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Transaksi tidak ditemukan');
        }

        $allAccounts = $this->transactions->getAccounts();
        $debitAccounts  = array_filter($allAccounts, fn($a) => in_array($a['type'], ['asset', 'expense']));
        $creditAccounts = array_filter($allAccounts, fn($a) => in_array($a['type'], ['asset', 'liability', 'equity', 'income']));

        return view('transactions/edit', [
            'transaction'   => $transaction,
            'debitAccounts' => $debitAccounts,
            'creditAccounts' => $creditAccounts,
        ]);
    }

    // --------------------------------------------------
    // UPDATE TRANSAKSI + UPLOAD BUKTI + JURNAL OTOMATIS
    // --------------------------------------------------
    public function update($id)
    {
        $transaction = $this->transactions->find($id);
        if (!$transaction || (session()->get('user_role') !== 'admin' && $transaction['user_id'] != session()->get('user_id'))) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Transaksi tidak ditemukan');
        }

        $post = $this->request->getPost();
        $file = $this->request->getFile('proof');

        $validationRules = [
            'date' => 'required',
            'description' => 'required',
            'type' => 'required|in_list[income,expense]',
            'amount' => 'required|decimal',
            'debit_account_id' => 'required|integer',
            'credit_account_id' => 'required|integer',
            'proof' => 'permit_empty|max_size[proof,2048]|ext_in[proof,jpg,jpeg,png,pdf]',
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $proofPath = $transaction['proof'] ?? null;
        $uploadDir = ROOTPATH . 'public/uploads/proof/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

        if ($file && $file->isValid() && !$file->hasMoved()) {
            if ($proofPath && file_exists(ROOTPATH . 'public/' . $proofPath)) unlink(ROOTPATH . 'public/' . $proofPath);
            $randomName = $file->getRandomName();
            $file->move($uploadDir, $randomName);
            $proofPath = 'uploads/proof/' . $randomName;
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // Update transaksi
        $transactionData = [
            'type'              => $post['type'],
            'description'       => $post['description'],
            'amount'            => $post['amount'],
            'date'              => $post['date'],
            'debit_account_id'  => $post['debit_account_id'],
            'credit_account_id' => $post['credit_account_id'],
            'proof'             => $proofPath,
        ];
        $this->transactions->update($id, $transactionData);

        // Hapus jurnal lama
        $journal = $this->journals->where('description', $transaction['description'])->first();
        if ($journal) {
            $this->journalEntries->where('journal_id', $journal['id'])->delete();
            $this->journals->delete($journal['id']);
        }

        // Buat jurnal baru
        $journalData = [
            'date'        => $post['date'],
            'description' => $post['description'],
            'user_id'     => session()->get('user_id'),
        ];
        $this->journals->insert($journalData);
        $journalId = $this->journals->getInsertID();

        // Buat journal entries baru
        $journalEntries = [
            [
                'journal_id' => $journalId,
                'account_id' => $post['debit_account_id'],
                'debit'      => $post['amount'],
                'credit'     => 0,
            ],
            [
                'journal_id' => $journalId,
                'account_id' => $post['credit_account_id'],
                'debit'      => 0,
                'credit'     => $post['amount'],
            ]
        ];
        $this->journalEntries->insertBatch($journalEntries);

        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Gagal mengupdate transaksi.');
        }

        return redirect()->to('/transactions')->with('success', 'Transaksi berhasil diupdate dan jurnal diperbarui.');
    }

    // --------------------------------------------------
    // DELETE TRANSAKSI + JURNAL + BUKTI
    // --------------------------------------------------
    public function delete($id)
    {
        $transaction = $this->transactions->find($id);
        if (!$transaction || (session()->get('user_role') !== 'admin' && $transaction['user_id'] != session()->get('user_id'))) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Transaksi tidak ditemukan');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // Hapus bukti
        if ($transaction['proof'] && file_exists(ROOTPATH . 'public/' . $transaction['proof'])) {
            unlink(ROOTPATH . 'public/' . $transaction['proof']);
        }

        // Hapus transaksi
        $this->transactions->delete($id);

        // Hapus jurnal & entries
        $journal = $this->journals->where('description', $transaction['description'])->first();
        if ($journal) {
            $this->journalEntries->where('journal_id', $journal['id'])->delete();
            $this->journals->delete($journal['id']);
        }

        $db->transComplete();

        return redirect()->to('/transactions')->with('success', 'Transaksi berhasil dihapus.');
    }
}
