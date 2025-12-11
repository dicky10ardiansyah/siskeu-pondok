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
        $search = $this->request->getGet('q');
        $perPage = 10;

        $builder = $this->classModel;

        if ($search) {
            $builder = $builder->like('name', $search);
        }

        $data['classes'] = $builder->paginate($perPage, 'classes'); // group = 'classes'
        $data['pager']   = $this->classModel->pager;
        $data['search']  = $search;

        return view('classes/index', $data);
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
            'name' => $this->request->getPost('name'),
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
        $this->classModel->delete($id);
        return redirect()->to('/classes')->with('success', 'Data kelas berhasil dihapus!');
    }
}
