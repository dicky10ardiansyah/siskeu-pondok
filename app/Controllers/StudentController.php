<?php

namespace App\Controllers;

use App\Models\UserModel;
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
    protected $userModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->classModel   = new ClassModel();
        $this->userModel    = new UserModel();
    }

    // LIST STUDENTS
    public function index()
    {
        $keyword     = $this->request->getGet('keyword');
        $classFilter = $this->request->getGet('class');
        $userFilter  = $this->request->getGet('user_id');
        $role        = session()->get('user_role');
        $userId      = session()->get('user_id');
        $perPage     = 10;

        $builder = $this->studentModel
            ->select('students.*, classes.name as class_name')
            ->join('classes', 'classes.id = students.class', 'left')
            ->orderBy('students.id', 'DESC');

        if ($role === 'admin') {
            // Admin bisa filter user
            if ($userFilter) {
                $builder->where('students.user_id', $userFilter);
            }
        } else {
            // User biasa hanya lihat miliknya sendiri & status belum lulus
            $builder->where('students.user_id', $userId)
                ->where('students.status', 0);
        }

        // Filter kelas
        if ($classFilter) {
            $builder->where('students.class', $classFilter);
        }

        // Filter keyword
        if ($keyword) {
            $builder->groupStart()
                ->like('students.name', $keyword)
                ->orLike('students.nis', $keyword)
                ->groupEnd();
        }

        $data['students']       = $builder->paginate($perPage, 'students');
        $data['pager']          = $this->studentModel->pager;
        $data['keyword']        = $keyword;
        $data['class']          = $classFilter;
        $data['selected_user']  = $userFilter;
        $data['classes']        = $this->classModel->orderBy('name', 'ASC')->findAll();

        // Daftar user hanya untuk admin
        $data['users'] = [];
        if ($role === 'admin') {
            $data['users'] = $this->userModel->orderBy('name', 'ASC')->findAll();
        }

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
            'name' => 'required',
            'nis'  => 'permit_empty|is_unique[students.nis]',
            'class' => 'permit_empty',
            'school_year' => 'permit_empty|integer|exact_length[4]',
            'status'      => 'permit_empty|in_list[0,1]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $nis = $this->request->getVar('nis') ?: null;
        $status = $this->request->getVar('status') ? 1 : 0;

        $this->studentModel->save([
            'name'        => $this->request->getVar('name'),
            'nis'         => $nis,
            'class'       => $this->request->getVar('class'),
            'status'      => $status,
            'school_year' => $this->request->getVar('school_year') ?: null,
            'user_id'     => session()->get('user_id'), // set user_id langsung
        ]);

        return redirect()->to('/students')->with('success', 'Data siswa berhasil ditambahkan');
    }

    // FORM EDIT
    public function edit($id)
    {
        $student = $this->studentModel->find($id);
        $role    = session()->get('user_role');
        $userId  = session()->get('user_id');

        if (!$student) {
            return redirect()->to('/students')->with('error', 'Data siswa tidak ditemukan');
        }

        // user biasa hanya bisa edit data miliknya
        if ($role !== 'admin' && $student['user_id'] != $userId) {
            return redirect()->to('/students')->with('error', 'Tidak punya akses untuk mengedit data ini');
        }

        $data['student'] = $student;
        $data['classes'] = $this->classModel->orderBy('name', 'ASC')->findAll();

        return view('students/edit', $data);
    }

    // UPDATE DATA STUDENT
    public function update($id)
    {
        $student = $this->studentModel->find($id);
        $role    = session()->get('user_role');
        $userId  = session()->get('user_id');

        if (!$student) {
            return redirect()->to('/students')->with('error', 'Data siswa tidak ditemukan');
        }

        if ($role !== 'admin' && $student['user_id'] != $userId) {
            return redirect()->to('/students')->with('error', 'Tidak punya akses untuk mengupdate data ini');
        }

        $validation = \Config\Services::validation();

        $rules = [
            'name' => 'required',
            'nis'  => "permit_empty|is_unique[students.nis,id,{$id}]",
            'class' => 'permit_empty',
            'school_year' => 'permit_empty|integer|exact_length[4]',
            'status'      => 'permit_empty|in_list[0,1]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $nis = $this->request->getVar('nis') ?: null;
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
        $student = $this->studentModel->find($id);
        $role    = session()->get('user_role');
        $userId  = session()->get('user_id');

        if (!$student) {
            return redirect()->to('/students')->with('error', 'Data siswa tidak ditemukan');
        }

        if ($role !== 'admin' && $student['user_id'] != $userId) {
            return redirect()->to('/students')->with('error', 'Tidak punya akses untuk menghapus data ini');
        }

        $this->studentModel->delete($id);
        return redirect()->to('/students')->with('success', 'Data berhasil dihapus');
    }

    // FORM BULK EDIT
    public function bulkEdit()
    {
        $role   = session()->get('user_role');
        $userId = session()->get('user_id');

        $builder = $this->studentModel
            ->select('students.*, classes.name as class_name')
            ->join('classes', 'classes.id = students.class', 'left')
            ->orderBy('students.name', 'ASC');

        if ($role !== 'admin') {
            $builder->where('students.user_id', $userId)
                ->where('students.status', 0);
        }

        $students = $builder->findAll();
        $classes  = $this->classModel->orderBy('name', 'ASC')->findAll();

        return view('students/bulk_edit', [
            'students' => $students,
            'classes'  => $classes
        ]);
    }

    // PROSES BULK UPDATE
    public function bulkUpdate()
    {
        $studentIds  = $this->request->getVar('student_id');
        $classes     = $this->request->getVar('class');
        $statuses    = $this->request->getVar('status');
        $schoolYears = $this->request->getVar('school_year');

        $role    = session()->get('user_role');
        $userId  = session()->get('user_id');

        $studentPaymentRuleModel = new StudentPaymentRuleModel();
        $paymentCategoryClassRuleModel = new PaymentCategoryClassRuleModel();

        if ($studentIds && is_array($studentIds)) {
            foreach ($studentIds as $id) {
                $oldStudent = $this->studentModel->find($id);
                if (!$oldStudent) continue;

                // user biasa hanya bisa update data miliknya
                if ($role !== 'admin' && $oldStudent['user_id'] != $userId) continue;

                $data = [];
                if (isset($classes[$id]) && $classes[$id] !== '') $data['class'] = $classes[$id];
                if (isset($statuses[$id])) $data['status'] = $statuses[$id];
                if (isset($schoolYears[$id]) && $schoolYears[$id] !== '') $data['school_year'] = $schoolYears[$id];

                if (!empty($data)) {
                    $this->studentModel->update($id, $data);

                    // Sinkronisasi payment rules jika class berubah
                    if (isset($data['class']) && $oldStudent['class'] != $data['class']) {
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
