<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\AccountModel;
use App\Models\JournalModel;
use App\Models\JournalEntryModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

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
        helper(['form', 'url']);
    }

    /**
     * INDEX: list jurnal dengan search, filter user, pagination
     */
    public function index()
    {
        $journalModel = $this->journalModel;

        // Ambil filter keyword & user
        $keyword = $this->request->getGet('keyword');
        $reqUser = $this->request->getGet('user_id');

        $session = session();
        $role    = $session->get('user_role');
        $userId  = $session->get('user_id');

        // Build query
        $builder = $journalModel
            ->select('journals.*, users.name AS user_name')
            ->join('users', 'users.id = journals.user_id', 'left');

        // Filter user (hanya admin bisa pilih user)
        if ($role === 'admin' && !empty($reqUser)) {
            $builder->where('journals.user_id', $reqUser);
            $filterUser = $reqUser;
        } elseif ($role !== 'admin') {
            $builder->where('journals.user_id', $userId);
            $filterUser = $userId;
        } else {
            $filterUser = null;
        }

        // Filter keyword
        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('journals.description', $keyword)
                ->orLike('journals.date', $keyword)
                ->groupEnd();
        }

        // Pagination
        $journals = $builder->orderBy('date', 'DESC')->paginate(10, 'journals');
        $pager = $journalModel->pager;

        // Ambil list user untuk dropdown (admin saja)
        $users = [];
        if ($role === 'admin') {
            $users = (new \App\Models\UserModel())->findAll();
        }

        $data = [
            'journals'   => $journals,
            'pager'      => $pager,
            'keyword'    => $keyword,
            'users'      => $users,
            'filterUser' => $filterUser,
        ];

        return view('journals/index', $data);
    }

    /**
     * FORM CREATE JURNAL
     */
    public function create()
    {
        $data['accounts'] = $this->accountModel->findAll();
        return view('journals/create', $data);
    }

    /**
     * STORE: simpan jurnal + entries
     */
    public function store()
    {
        $post = $this->request->getPost();

        $errors = [];
        if (empty($post['date'])) $errors[] = 'Tanggal harus diisi';
        if (empty($post['description'])) $errors[] = 'Deskripsi harus diisi';
        if (empty($post['account_id']) || !is_array($post['account_id'])) $errors[] = 'Minimal satu akun harus dipilih';

        $totalDebit = 0;
        $totalCredit = 0;

        if (!empty($post['account_id'])) {
            foreach ($post['account_id'] as $i => $accountId) {
                $type = $post['type'][$i] ?? '';
                $amount = (float)($post['amount'][$i] ?? 0);

                if (empty($accountId)) $errors[] = "Akun ke-" . ($i + 1) . " belum dipilih";
                if (empty($type)) $errors[] = "Tipe akun ke-" . ($i + 1) . " harus diisi";
                if ($amount <= 0) $errors[] = "Nominal akun ke-" . ($i + 1) . " harus lebih dari 0";

                if ($type === 'debit') $totalDebit += $amount;
                elseif ($type === 'credit') $totalCredit += $amount;
            }
        }

        if ($totalDebit != $totalCredit) $errors[] = "Total Debit dan Kredit harus sama";

        if ($errors) return redirect()->back()->withInput()->with('errors', $errors);

        // Simpan header jurnal
        $journalId = $this->journalModel->insert([
            'date' => $post['date'],
            'description' => $post['description'],
            'user_id' => session()->get('user_id')
        ]);

        // Simpan entries
        foreach ($post['account_id'] as $i => $accountId) {
            $type = $post['type'][$i];
            $amount = (float)$post['amount'][$i];

            $this->entryModel->insert([
                'journal_id' => $journalId,
                'account_id' => $accountId,
                'debit'  => $type === 'debit' ? $amount : 0,
                'credit' => $type === 'credit' ? $amount : 0,
                'user_id' => session()->get('user_id')
            ]);
        }

        return redirect()->to('/journals')->with('success', 'Jurnal berhasil ditambahkan');
    }

    /**
     * SHOW DETAIL JURNAL
     */
    public function show($id)
    {
        $journal = $this->journalModel->find($id);
        if (!$journal) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Jurnal tidak ditemukan');
        }

        // Cek akses user
        if (session()->get('user_role') !== 'admin' && $journal['user_id'] != session()->get('user_id')) {
            return redirect()->to('/journals')->with('error', 'Tidak bisa mengakses jurnal ini');
        }

        $entries = $this->entryModel->where('journal_id', $id)->findAll();

        return view('journals/show', [
            'journal' => $journal,
            'entries' => $entries
        ]);
    }

    /**
     * FORM EDIT JURNAL
     */
    public function edit($id)
    {
        $journal = $this->journalModel->find($id);
        if (!$journal) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Jurnal tidak ditemukan');
        }

        if (session()->get('user_role') !== 'admin' && $journal['user_id'] != session()->get('user_id')) {
            return redirect()->to('/journals')->with('error', 'Tidak bisa mengakses jurnal ini');
        }

        $entries = $this->entryModel->where('journal_id', $id)->findAll();
        $accounts = $this->accountModel->findAll();

        return view('journals/edit', [
            'journal' => $journal,
            'entries' => $entries,
            'accounts' => $accounts
        ]);
    }

    /**
     * UPDATE JURNAL
     */
    public function update($id)
    {
        $journal = $this->journalModel->find($id);
        if (!$journal) {
            return redirect()->back()->with('error', 'Jurnal tidak ditemukan');
        }

        if (session()->get('user_role') !== 'admin' && $journal['user_id'] != session()->get('user_id')) {
            return redirect()->to('/journals')->with('error', 'Tidak bisa mengakses jurnal ini');
        }

        $post = $this->request->getPost();
        $errors = [];

        if (empty($post['date'])) $errors[] = 'Tanggal harus diisi';
        if (empty($post['description'])) $errors[] = 'Deskripsi harus diisi';
        if (empty($post['account_id']) || !is_array($post['account_id'])) $errors[] = 'Minimal satu akun harus dipilih';

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($post['account_id'] as $i => $accountId) {
            $type = $post['type'][$i] ?? '';
            $amount = (float)($post['amount'][$i] ?? 0);

            if (empty($accountId)) $errors[] = "Akun ke-" . ($i + 1) . " belum dipilih";
            if (empty($type)) $errors[] = "Tipe akun ke-" . ($i + 1) . " harus diisi";
            if ($amount <= 0) $errors[] = "Nominal akun ke-" . ($i + 1) . " harus lebih dari 0";

            if ($type === 'debit') $totalDebit += $amount;
            elseif ($type === 'credit') $totalCredit += $amount;
        }

        if ($totalDebit != $totalCredit) $errors[] = "Total Debit dan Kredit harus sama";

        if ($errors) return redirect()->back()->withInput()->with('errors', $errors);

        // Update header jurnal
        $this->journalModel->update($id, [
            'date' => $post['date'],
            'description' => $post['description']
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
                'debit'  => $type === 'debit' ? $amount : 0,
                'credit' => $type === 'credit' ? $amount : 0,
                'user_id' => session()->get('user_id')
            ]);
        }

        return redirect()->to('/journals')->with('success', 'Jurnal berhasil diperbarui');
    }

    /**
     * DELETE JURNAL
     */
    public function delete($id)
    {
        $journal = $this->journalModel->find($id);
        if (!$journal) {
            return redirect()->to('/journals')->with('error', 'Jurnal tidak ditemukan');
        }

        if (session()->get('user_role') !== 'admin' && $journal['user_id'] != session()->get('user_id')) {
            return redirect()->to('/journals')->with('error', 'Tidak bisa menghapus jurnal ini');
        }

        // Hapus entries
        $this->entryModel->where('journal_id', $id)->delete();
        $this->journalModel->delete($id);

        return redirect()->to('/journals')->with('success', 'Jurnal berhasil dihapus');
    }
}
