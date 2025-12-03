<?php

namespace App\Controllers;

use App\Models\StudentModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class StudentController extends BaseController
{
    protected $studentModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
    }

    public function index()
    {
        $keyword = $this->request->getVar('keyword'); // search dari query string
        $studentsModel = $this->studentModel;

        if ($keyword) {
            $studentsModel = $studentsModel
                ->like('name', $keyword)
                ->orLike('nis', $keyword);
        }

        $data['students'] = $studentsModel
            ->orderBy('id', 'DESC')
            ->paginate(5, 'students');

        $data['pager'] = $this->studentModel->pager;
        $data['keyword'] = $keyword;

        return view('students/index', $data);
    }

    // FORM CREATE
    public function create()
    {
        return view('students/create');
    }

    // SAVE NEW STUDENT
    public function store()
    {
        $validation = \Config\Services::validation();

        $rules = [
            'name' => [
                'rules'  => 'required',
                'label'  => 'Nama',
            ],
            'nis' => [
                'rules'  => 'permit_empty|is_unique[students.nis]',
                'label'  => 'NIS',
            ],
            'class' => [
                'rules'  => 'permit_empty',
                'label'  => 'Kelas',
            ],
            'school_year' => [
                'rules'  => 'permit_empty|integer|exact_length[4]',
                'label'  => 'Tahun Lulus',
            ],
            'status' => [
                'rules'  => 'permit_empty|in_list[0,1]',
                'label'  => 'Status Lulus',
            ]
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $nis = $this->request->getVar('nis');
        $nis = $nis === '' ? null : $nis;

        $status = $this->request->getVar('status') ? 1 : 0;

        $this->studentModel->save([
            'name'        => $this->request->getVar('name'),
            'nis'         => $nis,
            'class'       => $this->request->getVar('class'),
            'status'      => $status,
            'school_year' => $this->request->getVar('school_year') ?: null,
        ]);

        return redirect()->to('/students')->with('success', 'Data siswa berhasil ditambahkan');
    }

    // FORM EDIT
    public function edit($id)
    {
        $data['student'] = $this->studentModel->find($id);

        if (!$data['student']) {
            return redirect()->to('/students')->with('error', 'Data tidak ditemukan');
        }

        return view('students/edit', $data);
    }

    // UPDATE DATA STUDENT
    public function update($id)
    {
        $validation = \Config\Services::validation();

        $rules = [
            'name' => [
                'rules'  => 'required',
                'label'  => 'Nama',
            ],
            'nis' => [
                'rules'  => "permit_empty|is_unique[students.nis,id,{$id}]",
                'label'  => 'NIS',
            ],
            'class' => [
                'rules'  => 'permit_empty',
                'label'  => 'Kelas',
            ],
            'school_year' => [
                'rules'  => 'permit_empty|integer|exact_length[4]',
                'label'  => 'Tahun Lulus',
            ],
            'status' => [
                'rules'  => 'permit_empty|in_list[0,1]',
                'label'  => 'Status Lulus',
            ]
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $nis = $this->request->getVar('nis');
        $nis = $nis === '' ? null : $nis;

        $status = $this->request->getVar('status') ? 1 : 0;

        $this->studentModel->update($id, [
            'name'        => $this->request->getVar('name'),
            'nis'         => $nis,
            'class'       => $this->request->getVar('class'),
            'status'      => $status,
            'school_year' => $this->request->getVar('school_year') ?: null,
        ]);

        return redirect()->to('/students')->with('success', 'Data siswa berhasil diupdate');
    }

    // DELETE DATA
    public function delete($id)
    {
        $this->studentModel->delete($id);
        return redirect()->to('/students')->with('success', 'Data berhasil dihapus');
    }
}
