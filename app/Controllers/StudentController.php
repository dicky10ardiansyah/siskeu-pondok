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
    protected $paymentCategoryClassRuleModel;
    protected $studentPaymentRuleModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->classModel   = new ClassModel();
        $this->userModel    = new UserModel();
        $this->paymentCategoryClassRuleModel = new PaymentCategoryClassRuleModel();
        $this->studentPaymentRuleModel       = new StudentPaymentRuleModel();
    }

    private function syncStudentPaymentRules($studentId, $classId, $userId)
    {
        $classRules = $this->paymentCategoryClassRuleModel
            ->where('class_id', $classId)
            ->findAll();

        $now = date('Y-m-d H:i:s');

        foreach ($classRules as $rule) {
            $existing = $this->studentPaymentRuleModel
                ->where('student_id', $studentId)
                ->where('category_id', $rule['category_id'])
                ->first();

            if (!$existing) {
                $this->studentPaymentRuleModel->insert([
                    'student_id'   => $studentId,
                    'category_id'  => $rule['category_id'],
                    'amount'       => $rule['amount'],
                    'is_mandatory' => 1,
                    'is_paid'      => 0,
                    'user_id'      => $userId,
                    'created_at'   => $now,
                    'updated_at'   => $now
                ]);
            }
        }
    }

    public function index()
    {
        $keyword     = $this->request->getGet('keyword');
        $classFilter = $this->request->getGet('class');
        $userFilter  = $this->request->getGet('user_id');

        $role    = session()->get('user_role');
        $isAdmin = $role === 'admin';
        $userId  = session()->get('user_id');
        $perPage = 10;

        // Query siswa
        $builder = $this->studentModel
            ->select('students.*, classes.name as class_name')
            ->join('classes', 'classes.id = students.class', 'left')
            ->orderBy('students.id', 'DESC');

        if ($isAdmin) {
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

        // Filter keyword (nama / NIS)
        if ($keyword) {
            $builder->groupStart()
                ->like('students.name', $keyword)
                ->orLike('students.nis', $keyword)
                ->groupEnd();
        }

        // Ambil daftar kelas untuk dropdown
        if ($isAdmin) {
            $classes = $this->classModel->orderBy('name', 'ASC')->findAll();
        } else {
            $classes = $this->classModel
                ->where('user_id', $userId)
                ->orderBy('name', 'ASC')
                ->findAll();
        }

        $data = [
            'students'      => $builder->paginate($perPage, 'students'),
            'pager'         => $this->studentModel->pager,
            'keyword'       => $keyword,
            'class'         => $classFilter,
            'selected_user' => $userFilter,
            'classes'       => $classes,
            'users'         => $isAdmin ? $this->userModel->orderBy('name', 'ASC')->findAll() : [],
            'isAdmin'       => $isAdmin
        ];

        return view('students/index', $data);
    }

    public function create()
    {
        $role   = session()->get('user_role');
        $userId = session()->get('user_id');

        if ($role === 'admin') {
            $data['classes'] = $this->classModel
                ->orderBy('name', 'ASC')
                ->findAll();
        } else {
            $data['classes'] = $this->classModel
                ->where('user_id', $userId)
                ->orderBy('name', 'ASC')
                ->findAll();
        }

        // user list hanya admin
        $data['users'] = [];
        if ($role === 'admin') {
            $data['users'] = $this->userModel->orderBy('name', 'ASC')->findAll();
        }

        return view('students/create', $data);
    }

    public function store()
    {
        $validation = \Config\Services::validation();

        $rules = [
            'name'        => 'required',
            'nis'         => 'permit_empty|is_unique[students.nis]',
            'class'       => 'permit_empty',
            'school_year' => 'permit_empty|integer|exact_length[4]',
            'status'      => 'permit_empty|in_list[0,1]',
            'parent_name'  => 'required',
            'phone'       => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $userId = session()->get('user_role') === 'admin'
            ? ($this->request->getVar('user_id') ?: session()->get('user_id'))
            : session()->get('user_id');

        $studentData = [
            'name'        => $this->request->getVar('name'),
            'nis'         => $this->request->getVar('nis') ?: null,
            'class'       => $this->request->getVar('class'),
            'status'      => $this->request->getVar('status') ? 1 : 0,
            'school_year' => $this->request->getVar('school_year') ?: null,
            'user_id'     => $userId,
            'address'     => $this->request->getVar('address') ?: null,
            'parent_name' => $this->request->getVar('parent_name') ?: null,
            'phone'       => $this->request->getVar('phone') ?: null
        ];

        // Simpan siswa
        $newStudentId = $this->studentModel->insert($studentData);

        // Sinkronisasi student_payment_rules otomatis
        if ($newStudentId) {
            $this->syncStudentPaymentRules($newStudentId, $studentData['class'], $userId);
        }

        return redirect()->to('/students')->with('success', 'Data siswa berhasil ditambahkan');
    }

    public function edit($id)
    {
        $student = $this->studentModel->find($id);
        $role    = session()->get('user_role');
        $userId  = session()->get('user_id');

        if (!$student) {
            return redirect()->to('/students')->with('error', 'Data siswa tidak ditemukan');
        }

        if ($role !== 'admin' && $student['user_id'] != $userId) {
            return redirect()->to('/students')->with('error', 'Tidak punya akses');
        }

        $data['student'] = $student;

        if ($role === 'admin') {
            $data['classes'] = $this->classModel
                ->orderBy('name', 'ASC')
                ->findAll();
        } else {
            $data['classes'] = $this->classModel
                ->where('user_id', $userId)
                ->orderBy('name', 'ASC')
                ->findAll();
        }

        $data['users'] = [];
        if ($role === 'admin') {
            $data['users'] = $this->userModel->orderBy('name', 'ASC')->findAll();
        }

        return view('students/edit', $data);
    }

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
            'name'        => 'required',
            'nis'         => "permit_empty|is_unique[students.nis,id,{$id}]",
            'class'       => 'permit_empty',
            'school_year' => 'permit_empty|integer|exact_length[4]',
            'status'      => 'permit_empty|in_list[0,1]',
            'parent_name'  => 'required',
            'phone'       => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $updateData = [
            'name'        => $this->request->getVar('name'),
            'nis'         => $this->request->getVar('nis') ?: null,
            'class'       => $this->request->getVar('class'),
            'status'      => $this->request->getVar('status') ? 1 : 0,
            'school_year' => $this->request->getVar('school_year') ?: null,
            'address'     => $this->request->getVar('address') ?: null,
            'parent_name' => $this->request->getVar('parent_name') ?: null,
            'phone'       => $this->request->getVar('phone') ?: null
        ];

        // Admin bisa update user_id
        if ($role === 'admin') {
            $updateData['user_id'] = $this->request->getVar('user_id');
        }

        $this->studentModel->update($id, $updateData);

        // Jika class berubah, sinkronisasi payment rules
        if (isset($updateData['class']) && $student['class'] != $updateData['class']) {
            $this->syncStudentPaymentRules($id, $updateData['class'], $student['user_id']);
        }

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

    public function bulkEdit()
    {
        $role        = session()->get('user_role');
        $userId      = session()->get('user_id');
        $keyword     = $this->request->getGet('keyword');
        $classFilter = $this->request->getGet('class');

        // Query siswa
        $builder = $this->studentModel
            ->select('students.*, classes.name as class_name')
            ->join('classes', 'classes.id = students.class', 'left')
            ->orderBy('students.name', 'ASC');

        if ($role !== 'admin') {
            // User biasa hanya lihat miliknya sendiri & status belum lulus
            $builder->where('students.user_id', $userId)
                ->where('students.status', 0);

            // Filter kelas hanya jika termasuk kelas miliknya
            if ($classFilter) {
                $builder->where('students.class', $classFilter);
            }
        } else {
            // Admin bisa filter semua siswa
            if ($classFilter) {
                $builder->where('students.class', $classFilter);
            }
        }

        if ($keyword) {
            $builder->groupStart()
                ->like('students.name', $keyword)
                ->orLike('students.nis', $keyword)
                ->groupEnd();
        }

        $students = $builder->findAll();

        // Daftar kelas sesuai hak akses
        if ($role === 'admin') {
            $classes = $this->classModel->orderBy('name', 'ASC')->findAll();
            $users   = $this->userModel->orderBy('name', 'ASC')->findAll();
        } else {
            $classes = $this->classModel
                ->where('user_id', $userId)
                ->orderBy('name', 'ASC')
                ->findAll();
            $users = [];
        }

        return view('students/bulk_edit', [
            'students'    => $students,
            'classes'     => $classes,
            'users'       => $users,
            'keyword'     => $keyword,
            'classFilter' => $classFilter
        ]);
    }

    public function bulkUpdate()
    {
        $studentIds  = $this->request->getVar('student_id') ?: [];
        $classes     = $this->request->getVar('class') ?: [];
        $statuses    = $this->request->getVar('status') ?: [];
        $schoolYears = $this->request->getVar('school_year') ?: [];
        $userIds     = $this->request->getVar('user_id') ?: [];

        $role   = session()->get('user_role');
        $userId = session()->get('user_id');
        $now    = date('Y-m-d H:i:s');

        foreach ($studentIds as $id) {
            $student = $this->studentModel->find($id);
            if (!$student) continue;
            if ($role !== 'admin' && $student['user_id'] != $userId) continue;

            $updateData = [];
            if (isset($classes[$id]) && $classes[$id] !== '') $updateData['class'] = $classes[$id];
            if (isset($statuses[$id])) $updateData['status'] = $statuses[$id];
            if (isset($schoolYears[$id]) && $schoolYears[$id] !== '') $updateData['school_year'] = $schoolYears[$id];
            if ($role === 'admin' && isset($userIds[$id]) && $userIds[$id] !== '') $updateData['user_id'] = $userIds[$id];

            if (!empty($updateData)) {
                $this->studentModel->update($id, $updateData);

                // Sinkronisasi payment rules jika class berubah
                if (isset($updateData['class']) && $student['class'] != $updateData['class']) {
                    $classRules = $this->paymentCategoryClassRuleModel
                        ->where('class_id', $updateData['class'])
                        ->findAll();

                    foreach ($classRules as $rule) {
                        $existingRule = $this->studentPaymentRuleModel
                            ->where('student_id', $id)
                            ->where('category_id', $rule['category_id'])
                            ->first();

                        if ($existingRule) {
                            if (!isset($existingRule['is_paid']) || $existingRule['is_paid'] == 0) {
                                $this->studentPaymentRuleModel->update($existingRule['id'], [
                                    'amount'     => $rule['amount'],
                                    'updated_at' => $now
                                ]);
                            }
                        } else {
                            $this->studentPaymentRuleModel->insert([
                                'student_id'   => $id,
                                'category_id'  => $rule['category_id'],
                                'amount'       => $rule['amount'],
                                'is_mandatory' => 1,
                                'is_paid'      => 0,
                                'user_id'      => $student['user_id'],
                                'created_at'   => $now,
                                'updated_at'   => $now
                            ]);
                        }
                    }
                }
            }
        }

        return redirect()->to('/students')->with('success', 'Data siswa berhasil diupdate secara massal');
    }
}
