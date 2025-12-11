<?php

namespace App\Controllers;

use App\Models\AccountModel;
use App\Models\JournalModel;
use App\Models\JournalEntryModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class JournalsController extends BaseController
{
    protected $journalModel;
    protected $entryModel;
    protected $accountModel;
    protected $perPage = 10; // jumlah jurnal per halaman

    public function __construct()
    {
        $this->journalModel = new JournalModel();
        $this->entryModel = new JournalEntryModel();
        $this->accountModel = new AccountModel();
    }

    // List semua jurnal dengan search dan pagination
    public function index()
    {
        $journalModel = new JournalModel();

        // Ambil keyword pencarian
        $keyword = $this->request->getGet('keyword');

        // Query dasar dengan join ke users
        $builder = $journalModel
            ->select('journals.*, users.name AS user_name')
            ->join('users', 'users.id = journals.user_id', 'left');

        // Jika ada keyword, terapkan filter
        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('journals.description', $keyword)
                ->orLike('journals.date', $keyword)
                ->groupEnd();
        }

        // Pagination (FINAL)
        $data['journals'] = $builder->paginate(10, 'journals');
        $data['pager'] = $journalModel->pager;
        $data['keyword'] = $keyword;

        return view('journals/index', $data);
    }

    // Pembuatan jurnal baru
    public function create()
    {
        $data['accounts'] = $this->accountModel->findAll();
        return view('journals/create', $data);
    }

    public function store()
    {
        $post = $this->request->getPost();

        // --- VALIDASI DASAR ---
        $errors = [];
        if (empty($post['date'])) $errors[] = "Tanggal harus diisi.";
        if (empty($post['description'])) $errors[] = "Deskripsi harus diisi.";
        if (empty($post['account_id']) || !is_array($post['account_id'])) $errors[] = "Minimal satu akun harus dipilih.";

        $totalDebit  = 0;
        $totalCredit = 0;

        // --- HITUNG DEBIT & KREDIT BERDASARKAN type[] ---
        if (!empty($post['account_id']) && is_array($post['account_id'])) {
            foreach ($post['account_id'] as $i => $accountId) {
                $type   = $post['type'][$i]   ?? '';
                $amount = (float)($post['amount'][$i] ?? 0);

                if (empty($accountId)) {
                    $errors[] = "Akun ke-" . ($i + 1) . " belum dipilih.";
                }
                if (empty($type)) {
                    $errors[] = "Tipe akun ke-" . ($i + 1) . " harus diisi.";
                }
                if ($amount <= 0) {
                    $errors[] = "Nominal akun ke-" . ($i + 1) . " harus lebih dari 0.";
                }

                if ($type === 'debit') {
                    $totalDebit += $amount;
                } elseif ($type === 'credit') {
                    $totalCredit += $amount;
                }
            }
        }

        if ($totalDebit != $totalCredit) {
            $errors[] = "Total Debit dan Kredit harus sama.";
        }

        // --- KIRIM KEMBALI JIKA ERROR ---
        if (!empty($errors)) {
            return redirect()->back()->with('errors', $errors)->withInput();
        }

        // --- SIMPAN HEADER JURNAL ---
        $journalId = $this->journalModel->insert([
            'date'        => $post['date'],
            'description' => $post['description'],
            'user_id'     => session()->get('user_id'),
        ]);

        // --- SIMPAN DETAIL ENTRIES ---
        foreach ($post['account_id'] as $i => $accountId) {
            $type   = $post['type'][$i];
            $amount = (float)$post['amount'][$i];

            $this->entryModel->insert([
                'journal_id' => $journalId,
                'account_id' => $accountId,
                'debit'      => $type === 'debit' ? $amount : 0,
                'credit'     => $type === 'credit' ? $amount : 0,
            ]);
        }

        // --- SUKSES ---
        return redirect()->to('/journals')->with('success', 'Jurnal berhasil ditambahkan.');
    }

    public function show($id)
    {
        $data['journal'] = $this->journalModel->find($id);
        $data['entries'] = $this->entryModel->where('journal_id', $id)->findAll();
        return view('journals/show', $data);
    }

    public function edit($id)
    {
        $data['journal'] = $this->journalModel->find($id);
        $data['entries'] = $this->entryModel->where('journal_id', $id)->findAll();
        $data['accounts'] = $this->accountModel->findAll();
        return view('journals/edit', $data);
    }

    public function update($id)
    {
        $post = $this->request->getPost();
        $errors = [];

        // Validasi dasar
        if (empty($post['date'])) {
            $errors['date'] = 'Tanggal harus diisi';
        }
        if (empty($post['description'])) {
            $errors['description'] = 'Deskripsi harus diisi';
        }

        // Validasi detail entri
        if (empty($post['account_id']) || !is_array($post['account_id'])) {
            $errors['account_id'] = 'Minimal satu akun harus diisi';
        }

        // Cek nilai debit/kredit total
        $totalDebit = 0;
        $totalCredit = 0;

        if (!isset($errors['account_id'])) {
            foreach ($post['account_id'] as $i => $accountId) {
                $type = $post['type'][$i] ?? '';
                $amount = isset($post['amount'][$i]) ? (float)$post['amount'][$i] : 0;

                if (empty($accountId)) {
                    $errors['account_id'] = 'Akun ke-' . ($i + 1) . ' belum dipilih';
                    break;
                }
                if (empty($type)) {
                    $errors['type'] = 'Tipe akun ke-' . ($i + 1) . ' harus diisi';
                    break;
                }
                if ($amount <= 0) {
                    $errors['amount'] = 'Nominal akun ke-' . ($i + 1) . ' harus lebih dari 0';
                    break;
                }

                if ($type == 'debit') {
                    $totalDebit += $amount;
                } else {
                    $totalCredit += $amount;
                }
            }
        }

        if ($totalDebit != $totalCredit) {
            $errors['balance'] = 'Total Debit dan Kredit harus sama';
        }

        // Jika error
        if ($errors) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        // Update jurnal utama
        $this->journalModel->update($id, [
            'date' => $post['date'],
            'description' => $post['description'],
        ]);

        // Hapus entri lama
        $this->entryModel->where('journal_id', $id)->delete();

        // Simpan entri baru
        foreach ($post['account_id'] as $i => $accountId) {
            $type = $post['type'][$i];
            $amount = (float)$post['amount'][$i];

            $this->entryModel->insert([
                'journal_id' => $id,
                'account_id' => $accountId,
                'debit'  => $type == 'debit' ? $amount : 0,
                'credit' => $type == 'credit' ? $amount : 0,
            ]);
        }

        return redirect()->to('/journals')->with('success', 'Jurnal berhasil diperbarui');
    }

    public function delete($id)
    {
        $this->journalModel->delete($id);
        return redirect()->to('/journals')->with('success', 'Jurnal berhasil dihapus');
    }

    // Fungsi pagination sederhana
    private function pager($totalItems, $currentPage)
    {
        $totalPages = ceil($totalItems / $this->perPage);
        return [
            'total' => $totalItems,
            'current' => $currentPage,
            'last' => $totalPages
        ];
    }
}
