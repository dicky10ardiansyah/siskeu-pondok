<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ClassModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class ClassController extends BaseController
{
    protected $classModel;
    protected $userModel;
    protected $helpers = ['form'];

    public function __construct()
    {
        $this->classModel = new ClassModel();
        $this->userModel  = new UserModel();
    }

    private function authorize($ownerId)
    {
        return session()->get('user_role') === 'admin'
            || $ownerId == session()->get('user_id');
    }

    public function index()
    {
        $search  = $this->request->getGet('q');
        $perPage = 10;

        $model = $this->classModel->getWithUser();

        // User biasa hanya lihat data sendiri
        if (session()->get('user_role') !== 'admin') {
            $model->where('classes.user_id', session()->get('user_id'));
        }
        // Admin boleh filter user
        elseif ($userId = $this->request->getGet('user_id')) {
            $model->where('classes.user_id', $userId);
        }

        if ($search) {
            $model->like('classes.name', $search);
        }

        $data = [
            'classes' => $model->paginate($perPage, 'classes'),
            'pager'   => $model->pager,
            'search'  => $search,
        ];

        // HANYA admin dapat list user
        if (session()->get('user_role') === 'admin') {
            $data['users'] = $this->userModel->findAll();
        }

        return view('classes/index', $data);
    }

    public function create()
    {
        $data = [];

        // Hanya admin yang membutuhkan list user
        if (session()->get('user_role') === 'admin') {
            $data['users'] = $this->userModel->findAll();
        }

        return view('classes/create', $data);
    }

    public function store()
    {
        // Default user_id dari session
        $userId = session()->get('user_id');

        // Jika admin, ambil dari form dropdown
        if (session()->get('user_role') === 'admin') {
            $userId = $this->request->getPost('user_id');
        }

        $this->classModel->save([
            'name'    => $this->request->getPost('name'),
            'user_id' => $userId,
        ]);

        return redirect()->to('/classes')->with('success', 'Kelas ditambahkan');
    }

    public function edit($id)
    {
        $class = $this->classModel->find($id);

        if (!$class || !$this->authorize($class['user_id'])) {
            return redirect()->to('/classes')->with('error', 'Akses ditolak');
        }

        $data = [
            'class' => $class
        ];

        // Hanya admin dapat list user
        if (session()->get('user_role') === 'admin') {
            $data['users'] = $this->userModel->findAll();
        }

        return view('classes/edit', $data);
    }

    public function update($id)
    {
        $class = $this->classModel->find($id);

        if (!$class) {
            return redirect()->to('/classes')->with('error', 'Data tidak ditemukan');
        }

        // Cek otorisasi
        if (session()->get('user_role') !== 'admin' && $class['user_id'] != session()->get('user_id')) {
            return redirect()->to('/classes')->with('error', 'Akses ditolak');
        }

        $data = [
            'name' => $this->request->getPost('name'),
        ];

        // Hanya admin yang bisa ubah pemilik kelas
        if (session()->get('user_role') === 'admin') {
            $data['user_id'] = $this->request->getPost('user_id');
        }

        $this->classModel->update($id, $data);

        return redirect()->to('/classes')->with('success', 'Kelas diperbarui');
    }

    public function delete($id)
    {
        $class = $this->classModel->find($id);

        if (!$class || !$this->authorize($class['user_id'])) {
            return redirect()->to('/classes')->with('error', 'Akses ditolak');
        }

        $this->classModel->delete($id);
        return redirect()->to('/classes')->with('success', 'Kelas dihapus');
    }
}
