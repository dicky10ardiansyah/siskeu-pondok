<?php

namespace App\Controllers;

use App\Models\ClassModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class ClassController extends BaseController
{
    protected $classModel;
    protected $helpers = ['form'];

    public function __construct()
    {
        $this->classModel = new ClassModel();
    }

    // --------------------------------------------------
    // INDEX + SEARCH + PAGINATION
    // --------------------------------------------------
    public function index()
    {
        $search  = $this->request->getGet('q');
        $perPage = 10;

        $model = $this->classModel;

        // BATASI: User biasa hanya lihat kelas miliknya
        if (session()->get('user_role') !== 'admin') {
            $model = $model->where('user_id', session()->get('user_id'));
        } else {
            // Admin bisa filter per user
            $userFilter = $this->request->getGet('user_id');
            if ($userFilter) {
                $model = $model->where('user_id', $userFilter);
            }
        }

        // Search nama kelas
        if ($search) {
            $model = $model->like('name', $search);
        }

        $classes = $model->orderBy('created_at', 'DESC')
            ->paginate($perPage, 'classes');
        $pager   = $model->pager;

        return view('classes/index', [
            'classes' => $classes,
            'pager'   => $pager,
            'search'  => $search,
        ]);
    }

    // --------------------------------------------------
    // FORM CREATE
    // --------------------------------------------------
    public function create()
    {
        return view('classes/create');
    }

    // --------------------------------------------------
    // STORE
    // --------------------------------------------------
    public function store()
    {
        $validationRules = [
            'name' => 'required|min_length[3]|max_length[255]',
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->classModel->save([
            'name'    => $this->request->getPost('name'),
            'user_id' => session()->get('user_id'),
        ]);

        return redirect()->to('/classes')->with('success', 'Kelas baru berhasil ditambahkan!');
    }

    // --------------------------------------------------
    // FORM EDIT
    // --------------------------------------------------
    public function edit($id)
    {
        $class = $this->classModel->find($id);

        if (!$class) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Kelas tidak ditemukan');
        }

        // BATASI: User biasa hanya bisa edit miliknya
        if (session()->get('user_role') !== 'admin' && $class['user_id'] != session()->get('user_id')) {
            return redirect()->to('/classes')->with('error', 'Akses ditolak.');
        }

        return view('classes/edit', ['class' => $class]);
    }

    // --------------------------------------------------
    // UPDATE
    // --------------------------------------------------
    public function update($id)
    {
        $class = $this->classModel->find($id);

        if (!$class) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Kelas tidak ditemukan');
        }

        if (session()->get('user_role') !== 'admin' && $class['user_id'] != session()->get('user_id')) {
            return redirect()->to('/classes')->with('error', 'Akses ditolak.');
        }

        $validationRules = [
            'name' => 'required|min_length[3]|max_length[255]',
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->classModel->update($id, [
            'name' => $this->request->getPost('name'),
        ]);

        return redirect()->to('/classes')->with('success', 'Data kelas berhasil diupdate!');
    }

    // --------------------------------------------------
    // DELETE
    // --------------------------------------------------
    public function delete($id)
    {
        $class = $this->classModel->find($id);

        if (!$class) {
            return redirect()->to('/classes')->with('error', 'Kelas tidak ditemukan.');
        }

        if (session()->get('user_role') !== 'admin' && $class['user_id'] != session()->get('user_id')) {
            return redirect()->to('/classes')->with('error', 'Akses ditolak.');
        }

        $this->classModel->delete($id);
        return redirect()->to('/classes')->with('success', 'Data kelas berhasil dihapus!');
    }
}
