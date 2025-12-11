<?php

namespace App\Controllers;

use App\Models\ClassModel;
use App\Models\StudentModel;
use App\Controllers\BaseController;
use App\Models\StudentPaymentRuleModel;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PaymentCategoryClassRuleModel;

class StudentController extends BaseController
{
    protected $studentModel;
    protected $classModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->classModel   = new ClassModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $perPage = 10;

        $builder = $this->studentModel
            ->select('students.*, classes.name as class_name')
            ->join('classes', 'classes.id = students.class', 'left')
            ->where('students.status', 0) // ⚠ hanya tampil yang BELUM LULUS
            ->orderBy('students.id', 'DESC');

        // FILTER KEYWORD
        if ($keyword) {
            $builder->groupStart()
                ->like('students.name', $keyword)
                ->orLike('students.nis', $keyword)
                ->groupEnd();
        }

        $data['students'] = $builder->paginate($perPage, 'students');
        $data['pager']    = $this->studentModel->pager;
        $data['keyword']  = $keyword;

        // Data kelas untuk filter jika ingin tetap dipakai
        $data['classes'] = $this->classModel->orderBy('name', 'ASC')->findAll();

        return view('students/index', $data);
    }

    // FORM CREATE
    public function create()
    {
        $data['classes'] = $this->classModel->orderBy('name', 'ASC')->findAll();
        return view('students/create', $data);
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
        $student = $this->studentModel->find($id);

        if (!$student) {
            return redirect()->to('/students')->with('error', 'Data siswa tidak ditemukan');
        }

        $data['student'] = $student;
        $data['classes'] = $this->classModel->orderBy('name', 'ASC')->findAll();

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

    // FORM BULK EDIT
    public function bulkEdit()
    {
        // Ambil hanya siswa yang BELUM LULUS
        $students = $this->studentModel
            ->select('students.*, classes.name as class_name')
            ->join('classes', 'classes.id = students.class', 'left')
            ->where('students.status', 0) // ⬅ hanya siswa belum lulus
            ->orderBy('students.name', 'ASC')
            ->findAll();

        // Ambil semua kelas
        $classes = $this->classModel->orderBy('name', 'ASC')->findAll();

        return view('students/bulk_edit', [
            'students' => $students,
            'classes'  => $classes
        ]);
    }

    // PROSES BULK UPDATE
    public function bulkUpdate()
    {
        $studentIds   = $this->request->getVar('student_id');
        $classes      = $this->request->getVar('class');
        $statuses     = $this->request->getVar('status');
        $schoolYears  = $this->request->getVar('school_year');

        $studentPaymentRuleModel = new StudentPaymentRuleModel();
        $paymentCategoryClassRuleModel = new PaymentCategoryClassRuleModel();

        if ($studentIds && is_array($studentIds)) {
            foreach ($studentIds as $id) {
                $data = [];

                $oldStudent = $this->studentModel->find($id);

                if (isset($classes[$id]) && $classes[$id] !== '') {
                    $data['class'] = $classes[$id];
                }

                if (isset($statuses[$id])) {
                    $data['status'] = $statuses[$id];
                }

                if (isset($schoolYears[$id]) && $schoolYears[$id] !== '') {
                    $data['school_year'] = $schoolYears[$id];
                }

                if (!empty($data)) {
                    $this->studentModel->update($id, $data);

                    // --- Sinkronisasi payment rules jika class berubah ---
                    if (isset($data['class']) && $oldStudent['class'] != $data['class']) {
                        // Ambil semua kategori yang terkait dengan kelas baru
                        $categoryRules = $paymentCategoryClassRuleModel
                            ->where('class_id', $data['class'])
                            ->findAll();

                        foreach ($categoryRules as $rule) {
                            $existingRule = $studentPaymentRuleModel
                                ->where('student_id', $id)
                                ->where('category_id', $rule['category_id'])
                                ->first();

                            if ($existingRule) {
                                if (!isset($existingRule['is_paid']) || $existingRule['is_paid'] == 0) {
                                    $studentPaymentRuleModel->update($existingRule['id'], [
                                        'amount'     => $rule['amount'],
                                        'updated_at' => date('Y-m-d H:i:s')
                                    ]);
                                }
                            } else {
                                $studentPaymentRuleModel->insert([
                                    'student_id'  => $id,
                                    'category_id' => $rule['category_id'],
                                    'amount'      => $rule['amount'],
                                    'is_mandatory' => 1,
                                    'is_paid'     => 0,
                                    'created_at'  => date('Y-m-d H:i:s'),
                                    'updated_at'  => date('Y-m-d H:i:s')
                                ]);
                            }
                        }
                    }
                }
            }
        }

        return redirect()->to('/students')->with('success', 'Data siswa berhasil diupdate secara massal');
    }
}
