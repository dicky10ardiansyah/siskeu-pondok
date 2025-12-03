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

    // List semua transaksi
    public function index()
    {
        $keyword = $this->request->getGet('search');

        $builder = $this->transactions;

        if ($keyword) {
            $builder = $builder->like('description', $keyword)
                ->orLike('type', $keyword);
        }

        // Gunakan paginate, misal 10 data per halaman
        $transactions = $builder->orderBy('date', 'DESC')->paginate(10, 'transactions');
        $pager = $builder->pager;

        // Ambil akun untuk mapping
        $accounts = $this->transactions->getAccounts();

        $data = [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'pager' => $pager,
            'keyword' => $keyword
        ];

        return view('transactions/index', $data);
    }

    // Form create transaksi
    public function create()
    {
        $allAccounts = $this->transactions->getAccounts();

        $debitAccounts = array_filter($allAccounts, function ($acc) {
            return in_array($acc['type'], ['asset', 'expense']);
        });

        $creditAccounts = array_filter($allAccounts, function ($acc) {
            return in_array($acc['type'], ['asset', 'liability', 'equity', 'income']);
        });

        return view('transactions/create', [
            'debitAccounts' => $debitAccounts,
            'creditAccounts' => $creditAccounts,
        ]);
    }

    // Simpan transaksi + jurnal otomatis
    public function store()
    {
        $post = $this->request->getPost();

        $db = \Config\Database::connect();
        $db->transStart();

        // 1️⃣ Simpan transaksi
        $transactionData = [
            'type' => $post['type'],
            'description' => $post['description'],
            'amount' => $post['amount'],
            'date' => $post['date'],
            'user_id' => session()->get('user_id'),
            'debit_account_id' => $post['debit_account_id'],
            'credit_account_id' => $post['credit_account_id'],
        ];
        $this->transactions->insert($transactionData);
        $transactionId = $this->transactions->getInsertID();

        // 2️⃣ Buat jurnal
        $journalData = [
            'date' => $post['date'],
            'description' => $post['description'],
            'user_id' => session()->get('user_id'),
        ];
        $this->journals->insert($journalData);
        $journalId = $this->journals->getInsertID();

        // 3️⃣ Buat journal entries
        $journalEntries = [
            [
                'journal_id' => $journalId,
                'account_id' => $post['debit_account_id'],
                'debit' => $post['amount'],
                'credit' => 0,
            ],
            [
                'journal_id' => $journalId,
                'account_id' => $post['credit_account_id'],
                'debit' => 0,
                'credit' => $post['amount'],
            ]
        ];
        $this->journalEntries->insertBatch($journalEntries);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menyimpan transaksi.');
        }

        return redirect()->to('/transactions')->with('success', 'Transaksi berhasil disimpan.');
    }

    // Form edit transaksi
    public function edit($id)
    {
        $transaction = $this->transactions->find($id);
        $allAccounts = $this->transactions->getAccounts();

        $debitAccounts = array_filter($allAccounts, function ($acc) {
            return in_array($acc['type'], ['asset', 'expense']);
        });

        $creditAccounts = array_filter($allAccounts, function ($acc) {
            return in_array($acc['type'], ['asset', 'liability', 'equity', 'income']);
        });

        return view('transactions/edit', [
            'transaction' => $transaction,
            'debitAccounts' => $debitAccounts,
            'creditAccounts' => $creditAccounts,
        ]);
    }

    // Update transaksi + jurnal
    public function update($id)
    {
        $post = $this->request->getPost();
        $db = \Config\Database::connect();
        $db->transStart();

        // 1️⃣ Update transaksi
        $transactionData = [
            'type' => $post['type'],
            'description' => $post['description'],
            'amount' => $post['amount'],
            'date' => $post['date'],
            'debit_account_id' => $post['debit_account_id'],
            'credit_account_id' => $post['credit_account_id'],
        ];
        $this->transactions->update($id, $transactionData);

        // 2️⃣ Hapus jurnal lama & journal entries terkait
        $journal = $this->journals->where('description', $post['description'])->first();
        if ($journal) {
            $this->journalEntries->where('journal_id', $journal['id'])->delete();
            $this->journals->delete($journal['id']);
        }

        // 3️⃣ Buat jurnal baru (insert)
        $journalData = [
            'date' => $post['date'],
            'description' => $post['description'],
            'user_id' => session()->get('user_id'),
        ];
        $this->journals->insert($journalData);
        $journalId = $this->journals->getInsertID();

        $journalEntries = [
            [
                'journal_id' => $journalId,
                'account_id' => $post['debit_account_id'],
                'debit' => $post['amount'],
                'credit' => 0,
            ],
            [
                'journal_id' => $journalId,
                'account_id' => $post['credit_account_id'],
                'debit' => 0,
                'credit' => $post['amount'],
            ]
        ];
        $this->journalEntries->insertBatch($journalEntries);

        $db->transComplete();

        return redirect()->to('/transactions')->with('success', 'Transaksi berhasil diupdate.');
    }

    // Hapus transaksi + jurnal
    public function delete($id)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $transaction = $this->transactions->find($id);
        if ($transaction) {
            $this->transactions->delete($id);

            // Hapus jurnal dan journal entries
            $journal = $this->journals->where('description', $transaction['description'])->first();
            if ($journal) {
                $this->journalEntries->where('journal_id', $journal['id'])->delete();
                $this->journals->delete($journal['id']);
            }
        }

        $db->transComplete();

        return redirect()->to('/transactions')->with('success', 'Transaksi berhasil dihapus.');
    }
}
