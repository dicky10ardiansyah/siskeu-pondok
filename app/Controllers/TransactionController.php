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

    public function create()
    {
        $session = session();
        $role    = $session->get('user_role');
        $loginUserId = $session->get('user_id');

        $db = \Config\Database::connect();

        // Ambil user (khusus admin)
        $users = [];
        if ($role === 'admin') {
            $users = $db->table('users')
                ->select('id, name')
                ->orderBy('name')
                ->get()
                ->getResultArray();
        }

        // Tentukan user yang dipilih
        $selectedUserId = old('user_id', $loginUserId);

        // Ambil semua akun
        $allAccounts = $this->transactions->getAccounts();

        // 🔴 ADMIN: tampilkan SEMUA akun
        // 🟢 USER: hanya akun miliknya
        $accounts = ($role === 'admin')
            ? $allAccounts
            : array_filter($allAccounts, fn($a) => $a['user_id'] == $loginUserId);

        $debitAccounts  = array_filter($accounts, fn($a) => in_array($a['type'], ['asset', 'expense']));
        $creditAccounts = array_filter($accounts, fn($a) => in_array($a['type'], ['asset', 'liability', 'equity', 'income']));

        return view('transactions/create', [
            'users'           => $users,
            'selectedUserId'  => $selectedUserId,
            'debitAccounts'   => $debitAccounts,
            'creditAccounts'  => $creditAccounts,
            'role'            => $role,
        ]);
    }

    public function store()
    {
        $post = $this->request->getPost();
        $file = $this->request->getFile('proof');

        $session = session();
        $role    = $session->get('user_role');

        // Tentukan user_id
        $userId = ($role === 'admin')
            ? (int) $post['user_id']
            : (int) $session->get('user_id');

        // =======================
        // VALIDASI
        // =======================
        $rules = [
            'date'              => 'required',
            'description'       => 'required',
            'type'              => 'required|in_list[income,expense]',
            'amount'            => 'required|decimal',
            'debit_account_id'  => 'required|integer',
            'credit_account_id' => 'required|integer',
            'proof'             => 'permit_empty|max_size[proof,2048]|ext_in[proof,jpg,jpeg,png,pdf]',
        ];

        if ($role === 'admin') {
            $rules['user_id'] = 'required|integer';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // =======================
        // VALIDASI USER (ADMIN)
        // =======================
        if ($role === 'admin') {
            $db = \Config\Database::connect();
            $userExists = $db->table('users')->where('id', $userId)->countAllResults();
            if (!$userExists) {
                return redirect()->back()->withInput()->with('error', 'User tidak valid.');
            }
        }

        // =======================
        // VALIDASI KEPEMILIKAN AKUN
        // =======================
        if (
            !$this->transactions->accountBelongsToUser($post['debit_account_id'], $userId) ||
            !$this->transactions->accountBelongsToUser($post['credit_account_id'], $userId)
        ) {
            return redirect()->back()->withInput()->with('error', 'Akun tidak valid untuk user ini.');
        }

        // =======================
        // UPLOAD FILE
        // =======================
        $proofPath = null;
        $uploadDir = ROOTPATH . 'public/uploads/proof/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        if ($file && $file->isValid() && !$file->hasMoved()) {
            $fileName  = $file->getRandomName();
            $file->move($uploadDir, $fileName);
            $proofPath = 'uploads/proof/' . $fileName;
        }

        // =======================
        // TRANSAKSI DB
        // =======================
        $db = \Config\Database::connect();
        $db->transStart();

        // Simpan transaksi
        $this->transactions->insert([
            'type'              => $post['type'],
            'description'       => $post['description'],
            'amount'            => $post['amount'],
            'date'              => $post['date'],
            'user_id'           => $userId,
            'debit_account_id'  => $post['debit_account_id'],
            'credit_account_id' => $post['credit_account_id'],
            'proof'             => $proofPath,
        ]);

        $transactionId = $this->transactions->getInsertID();

        // Simpan jurnal
        $this->journals->insert([
            'date'           => $post['date'],
            'description'    => $post['description'],
            'user_id'        => $userId,
            'transaction_id' => $transactionId,
        ]);

        $journalId = $this->journals->getInsertID();

        // Simpan journal entries
        $this->journalEntries->insertBatch([
            [
                'journal_id' => $journalId,
                'account_id' => $post['debit_account_id'],
                'debit'      => $post['amount'],
                'credit'     => 0,
                'user_id'    => $userId,
            ],
            [
                'journal_id' => $journalId,
                'account_id' => $post['credit_account_id'],
                'debit'      => 0,
                'credit'     => $post['amount'],
                'user_id'    => $userId,
            ],
        ]);

        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan transaksi.');
        }

        return redirect()->to('/transactions')->with('success', 'Transaksi berhasil disimpan.');
    }

    public function edit($id)
    {
        $session = session();
        $role    = $session->get('user_role');
        $loginUserId = $session->get('user_id');

        $transaction = $this->transactions->find($id);

        if (
            !$transaction ||
            ($role !== 'admin' && $transaction['user_id'] != $loginUserId)
        ) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Transaksi tidak ditemukan');
        }

        $db = \Config\Database::connect();

        // Ambil user (khusus admin)
        $users = [];
        if ($role === 'admin') {
            $users = $db->table('users')
                ->select('id, name')
                ->orderBy('name')
                ->get()
                ->getResultArray();
        }

        // Ambil semua akun
        $allAccounts = $this->transactions->getAccounts();

        // 🔴 ADMIN: semua akun
        // 🟢 USER: hanya akun miliknya
        $accounts = ($role === 'admin')
            ? $allAccounts
            : array_filter($allAccounts, fn($a) => $a['user_id'] == $loginUserId);

        $debitAccounts  = array_filter($accounts, fn($a) => in_array($a['type'], ['asset', 'expense']));
        $creditAccounts = array_filter($accounts, fn($a) => in_array($a['type'], ['asset', 'liability', 'equity', 'income']));

        return view('transactions/edit', [
            'transaction'    => $transaction,
            'users'          => $users,
            'role'           => $role,
            'debitAccounts'  => $debitAccounts,
            'creditAccounts' => $creditAccounts,
        ]);
    }

    public function update($id)
    {
        $transaction = $this->transactions->find($id);
        $session     = session();
        $role        = $session->get('user_role');

        if (
            !$transaction ||
            ($role !== 'admin' && $transaction['user_id'] != $session->get('user_id'))
        ) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Transaksi tidak ditemukan');
        }

        $post = $this->request->getPost();
        $file = $this->request->getFile('proof');

        // Tentukan user_id
        $userId = ($role === 'admin')
            ? (int) $post['user_id']
            : (int) $transaction['user_id'];

        // =======================
        // VALIDASI
        // =======================
        $rules = [
            'date'              => 'required',
            'description'       => 'required',
            'type'              => 'required|in_list[income,expense]',
            'amount'            => 'required|decimal',
            'debit_account_id'  => 'required|integer',
            'credit_account_id' => 'required|integer',
            'proof'             => 'permit_empty|max_size[proof,2048]|ext_in[proof,jpg,jpeg,png,pdf]',
        ];

        if ($role === 'admin') {
            $rules['user_id'] = 'required|integer';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // =======================
        // VALIDASI USER (ADMIN)
        // =======================
        if ($role === 'admin') {
            $db = \Config\Database::connect();
            $userExists = $db->table('users')->where('id', $userId)->countAllResults();
            if (!$userExists) {
                return redirect()->back()->withInput()->with('error', 'User tidak valid.');
            }
        }

        // =======================
        // VALIDASI KEPEMILIKAN AKUN
        // =======================
        if (
            !$this->transactions->accountBelongsToUser($post['debit_account_id'], $userId) ||
            !$this->transactions->accountBelongsToUser($post['credit_account_id'], $userId)
        ) {
            return redirect()->back()->withInput()->with('error', 'Akun tidak valid untuk user ini.');
        }

        // =======================
        // UPLOAD FILE
        // =======================
        $proofPath = $transaction['proof'] ?? null;
        $uploadDir = ROOTPATH . 'public/uploads/proof/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        if ($file && $file->isValid() && !$file->hasMoved()) {
            if ($proofPath && file_exists(ROOTPATH . 'public/' . $proofPath)) {
                unlink(ROOTPATH . 'public/' . $proofPath);
            }
            $fileName  = $file->getRandomName();
            $file->move($uploadDir, $fileName);
            $proofPath = 'uploads/proof/' . $fileName;
        }

        // =======================
        // TRANSAKSI DB
        // =======================
        $db = \Config\Database::connect();
        $db->transStart();

        // Update transaksi
        $this->transactions->update($id, [
            'type'              => $post['type'],
            'description'       => $post['description'],
            'amount'            => $post['amount'],
            'date'              => $post['date'],
            'user_id'           => $userId,
            'debit_account_id'  => $post['debit_account_id'],
            'credit_account_id' => $post['credit_account_id'],
            'proof'             => $proofPath,
        ]);

        // Hapus jurnal lama
        $journal = $this->journals
            ->where('transaction_id', $id)
            ->first();

        if ($journal) {
            $this->journalEntries->where('journal_id', $journal['id'])->delete();
            $this->journals->delete($journal['id']);
        }

        // Buat jurnal baru
        $this->journals->insert([
            'date'           => $post['date'],
            'description'    => $post['description'],
            'user_id'        => $userId,
            'transaction_id' => $id,
        ]);

        $journalId = $this->journals->getInsertID();

        // Buat journal entries baru
        $this->journalEntries->insertBatch([
            [
                'journal_id' => $journalId,
                'account_id' => $post['debit_account_id'],
                'debit'      => $post['amount'],
                'credit'     => 0,
                'user_id'    => $userId,
            ],
            [
                'journal_id' => $journalId,
                'account_id' => $post['credit_account_id'],
                'debit'      => 0,
                'credit'     => $post['amount'],
                'user_id'    => $userId,
            ],
        ]);

        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Gagal mengupdate transaksi.');
        }

        return redirect()->to('/transactions')->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function delete($id)
    {
        $transaction = $this->transactions->find($id);
        $userId = session()->get('user_id');

        if (!$transaction || (session()->get('user_role') !== 'admin' && $transaction['user_id'] != $userId)) {
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

        // Hapus jurnal & entries berdasarkan transaction_id + user_id
        $journal = $this->journals->where('transaction_id', $id)->where('user_id', $userId)->first();
        if ($journal) {
            $this->journalEntries->where('journal_id', $journal['id'])->delete();
            $this->journals->delete($journal['id']);
        }

        $db->transComplete();

        return redirect()->to('/transactions')->with('success', 'Transaksi berhasil dihapus.');
    }
}
