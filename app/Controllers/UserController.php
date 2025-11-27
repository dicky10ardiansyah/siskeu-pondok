<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class UserController extends BaseController
{
    protected $userModel;
    protected $helpers = ['form'];

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $search = $this->request->getGet('search');

        $userModel = new UserModel();

        if ($search) {
            $userModel->like('name', $search)->orLike('email', $search);
        }

        $data = [
            'users' => $userModel->paginate(10, 'users'), // group 'users'
            'pager' => $userModel->pager, // ✅ INI WAJIB untuk pagination
            'search' => $search,
            'title' => 'Data Pengguna',
        ];

        return view('users/index', $data);
    }

    public function create()
    {
        $data['title'] = 'Tambah Data Pengguna';
        return view('users/create', $data);
    }

    public function store()
    {
        if (!$this->validate([
            'name' => 'required',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'role' => 'required',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->userModel->save([
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => $this->request->getPost('role'),
        ]);

        return redirect()->to('/user')->with('message', 'User created successfully');
    }

    public function edit($id)
    {
        $data['user'] = $this->userModel->find($id);
        $data['title'] = 'Edit Data Pengguna';
        return view('users/edit', $data);
    }

    public function update($id)
    {
        $data = $this->userModel->find($id);

        $rules = [
            'name' => 'required',
            'role' => 'required',
        ];

        if ($this->request->getPost('email') !== $data['email']) {
            $rules['email'] = 'required|valid_email|is_unique[users.email]';
        }

        if ($this->request->getPost('password')) {
            $rules['password'] = 'min_length[6]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $updateData = [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'role' => $this->request->getPost('role'),
        ];

        if ($this->request->getPost('password')) {
            $updateData['password'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
        }

        $this->userModel->update($id, $updateData);

        return redirect()->to('/user')->with('message', 'User updated successfully');
    }

    public function delete($id)
    {
        $this->userModel->delete($id);
        return redirect()->to('/user')->with('message', 'User deleted successfully');
    }
}
