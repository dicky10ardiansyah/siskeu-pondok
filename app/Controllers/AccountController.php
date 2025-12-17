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

    public function index()
    {
        $search      = $this->request->getGet('q');
        $filterUser  = $this->request->getGet('user_id'); // filter untuk admin
        $filterType  = $this->request->getGet('type');    // filter tipe akun
        $perPage     = 10;
        $session     = session();
        $role        = $session->get('user_role');
        $userId      = $session->get('user_id');
        $sort        = $this->request->getGet('sort') ?? 'code'; // default urut berdasarkan kode akun
        $order       = $this->request->getGet('order') ?? 'ASC';  // default ascending

        $builder = $this->accountModel;

        // ----------------------------------------
        // FILTER USER
        // ----------------------------------------
        if ($role === 'admin' && $filterUser) {
            $builder = $builder->where('user_id', $filterUser);
        } elseif ($role !== 'admin') {
            // user biasa → hanya lihat akun miliknya
            $builder = $builder->where('user_id', $userId);
        }

        // ----------------------------------------
        // FILTER TIPE AKUN
        // ----------------------------------------
        if ($filterType) {
            $builder = $builder->where('type', $filterType);
        }

        // ----------------------------------------
        // SEARCH
        // ----------------------------------------
        if ($search) {
            $builder = $builder->groupStart()
                ->like('code', $search)
                ->orLike('name', $search)
                ->groupEnd();
        }

        // ----------------------------------------
        // ORDER / SORTING
        // ----------------------------------------
        $builder = $builder->orderBy($sort, $order);

        // ----------------------------------------
        // PAGINATION
        // ----------------------------------------
        $data['accounts']   = $builder->paginate($perPage);
        $data['pager']      = $this->accountModel->pager;
        $data['search']     = $search;
        $data['filterUser'] = $filterUser;
        $data['filterType'] = $filterType;
        $data['sort']       = $sort;
        $data['order']      = $order;

        // ----------------------------------------
        // UNTUK ADMIN: AMBIL DAFTAR USER UNTUK DROPDOWN
        // ----------------------------------------
        if ($role === 'admin') {
            $db = \Config\Database::connect();
            $users = $db->table('users')
                ->select('id, name')
                ->orderBy('name', 'ASC')
                ->get()
                ->getResultArray();
            $data['users'] = $users;
        }

        return view('accounts/index', $data);
    }

    // --------------------------------------------------
    // GENERATE KODE OTOMATIS BERDASARKAN TYPE
    // --------------------------------------------------
    private function generateCode($type)
    {
        $prefix = [
            'asset'     => 'ACCA',
            'liability' => 'ACCL',
            'equity'    => 'ACCEQ',
            'income'    => 'ACCI',
            'expense'   => 'ACCE'
        ];

        $codePrefix = $prefix[$type] ?? 'ACCX';

        $lastAccount = $this->accountModel
            ->like('code', $codePrefix, 'after')
            ->orderBy('id', 'DESC')
            ->first();

        if ($lastAccount) {
            $lastNumber = (int)substr($lastAccount['code'], strlen($codePrefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $codePrefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function create()
    {
        $data = [];

        if (session()->get('user_role') === 'admin') {
            $db = \Config\Database::connect();
            $data['users'] = $db->table('users')
                ->select('id, name')
                ->orderBy('name')
                ->get()
                ->getResultArray();
        }

        return view('accounts/create', $data);
    }

    public function store()
    {
        $validationRules = [
            'name' => 'required|min_length[3]',
            'type' => 'required|in_list[asset, liability, equity, income, expense]',
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $type = $this->request->getPost('type');
        $code = $this->generateCode($type);

        // tentukan user_id
        $userId = session()->get('user_id');
        if (session()->get('user_role') === 'admin') {
            $userId = $this->request->getPost('user_id');
        }

        $this->accountModel->save([
            'code'    => $code,
            'name'    => $this->request->getPost('name'),
            'type'    => $type,
            'user_id' => $userId
        ]);

        return redirect()->to('/accounts')->with('success', 'Akun baru berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $account = $this->accountModel->find($id);

        if (!$account || (session()->get('user_role') !== 'admin' && $account['user_id'] != session()->get('user_id'))) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Akun tidak ditemukan');
        }

        $data['account'] = $account;

        if (session()->get('user_role') === 'admin') {
            $db = \Config\Database::connect();
            $data['users'] = $db->table('users')
                ->select('id, name')
                ->orderBy('name')
                ->get()
                ->getResultArray();
        }

        return view('accounts/edit', $data);
    }

    public function update($id)
    {
        $account = $this->accountModel->find($id);

        if (!$account || (session()->get('user_role') !== 'admin' && $account['user_id'] != session()->get('user_id'))) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Akun tidak ditemukan');
        }

        $validationRules = [
            'name' => 'required|min_length[3]',
            'type' => 'required|in_list[asset, liability, equity, income, expense]',
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name' => $this->request->getPost('name'),
        ];

        // admin bisa ubah user
        if (session()->get('user_role') === 'admin') {
            $data['user_id'] = $this->request->getPost('user_id');
        }

        $newType = $this->request->getPost('type');
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
        $account = $this->accountModel->where('id', $id)->first();

        if (!$account || (session()->get('user_role') !== 'admin' && $account['user_id'] != session()->get('user_id'))) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Akun tidak ditemukan');
        }

        $this->accountModel->delete($id);

        return redirect()->to('/accounts')->with('success', 'Data akun berhasil dihapus!');
    }
}
