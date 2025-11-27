<?php

namespace App\Controllers;

use App\Models\AccountModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class AccountController extends BaseController
{
    protected $accountModel;
    protected $helpers = ['form'];

    public function __construct()
    {
        $this->accountModel = new AccountModel();
    }

    // --------------------------------------------------
    // INDEX + SEARCH + PAGINATION
    // --------------------------------------------------
    public function index()
    {
        $search = $this->request->getGet('q');
        $perPage = 10;

        $builder = $this->accountModel;

        if ($search) {
            $builder = $builder->like('code', $search)
                ->orLike('name', $search);
        }

        $data['accounts'] = $builder->paginate($perPage);
        $data['pager'] = $this->accountModel->pager;
        $data['search'] = $search;

        return view('accounts/index', $data);
    }

    // --------------------------------------------------
    // FORM CREATE
    // --------------------------------------------------
    public function create()
    {
        return view('accounts/create');
    }

    // --------------------------------------------------
    // GENERATE KODE OTOMATIS BERDASARKAN TYPE
    // --------------------------------------------------
    private function generateCode($type)
    {
        $prefix = [
            'asset' => 'ACCA',
            'income' => 'ACCI',
            'expense' => 'ACCE'
        ];

        $codePrefix = $prefix[$type] ?? 'ACCX';

        // Ambil kode terakhir sesuai tipe
        $lastAccount = $this->accountModel
            ->like('code', $codePrefix, 'after')
            ->orderBy('id', 'DESC')
            ->first();

        if ($lastAccount) {
            $lastNumber = (int)substr($lastAccount['code'], 4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $codePrefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    // --------------------------------------------------
    // STORE
    // --------------------------------------------------
    public function store()
    {
        $validationRules = [
            'name' => 'required|min_length[3]',
            'type' => 'required|in_list[asset, liability, equity, income, expense]',
        ];

        if (!$this->validate($validationRules)) {
            // Gunakan session untuk error agar bisa tampil di view
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $type = $this->request->getPost('type');
        $code = $this->generateCode($type);

        $this->accountModel->save([
            'code' => $code,
            'name' => $this->request->getPost('name'),
            'type' => $type,
        ]);

        return redirect()->to('/accounts')->with('success', 'Akun baru berhasil ditambahkan!');
    }

    // --------------------------------------------------
    // FORM EDIT
    // --------------------------------------------------
    public function edit($id)
    {
        $account = $this->accountModel->find($id);

        if (!$account) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Akun tidak ditemukan');
        }

        return view('accounts/edit', ['account' => $account]);
    }

    // --------------------------------------------------
    // UPDATE
    // --------------------------------------------------
    public function update($id)
    {
        $account = $this->accountModel->find($id);
        if (!$account) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Akun tidak ditemukan');
        }

        $validationRules = [
            'name' => 'required|min_length[3]',
            'type' => 'required|in_list[asset, liability, equity, income, expense]',
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $newType = $this->request->getPost('type');
        $data = [
            'name' => $this->request->getPost('name'),
        ];

        // Jika tipe berubah, generate kode baru
        if ($newType !== $account['type']) {
            $data['type'] = $newType;
            $data['code'] = $this->generateCode($newType);
        }

        $this->accountModel->update($id, $data);

        return redirect()->to('/accounts')->with('success', 'Data akun berhasil diupdate!');
    }
    // --------------------------------------------------
    // DELETE
    // --------------------------------------------------
    public function delete($id)
    {
        $this->accountModel->delete($id);
        return redirect()->to('/accounts')->with('success', 'Data akun berhasil dihapus!');
    }
}
