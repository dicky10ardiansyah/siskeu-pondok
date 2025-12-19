<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\StudentModel;
use App\Controllers\BaseController;
use App\Models\PaymentCategoryModel;
use App\Models\StudentPaymentRuleModel;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PaymentCategoryClassRuleModel;

class StudentPaymentRuleController extends BaseController
{
    protected $studentModel;
    protected $categoryModel;
    protected $ruleModel;
    protected $classRuleModel;
    protected $userModel;

    public function __construct()
    {
        $this->studentModel   = new StudentModel();
        $this->categoryModel  = new PaymentCategoryModel();
        $this->ruleModel      = new StudentPaymentRuleModel();
        $this->classRuleModel = new PaymentCategoryClassRuleModel();
        $this->userModel      = new UserModel();
    }

    private function syncStudentPaymentRules($studentId, $classId, $userId)
    {
        $classRules = $this->classRuleModel
            ->where('class_id', $classId)
            ->findAll();

        $now = date('Y-m-d H:i:s');

        foreach ($classRules as $rule) {
            $existing = $this->ruleModel
                ->where('student_id', $studentId)
                ->where('category_id', $rule['category_id'])
                ->first();

            $data = [
                'student_id'   => $studentId,
                'category_id'  => $rule['category_id'],
                'amount'       => $rule['amount'],
                'is_mandatory' => $rule['is_mandatory'],
                'is_paid'      => 0,
                'user_id'      => $userId,
                'updated_at'   => $now
            ];

            if ($existing) {
                $this->ruleModel->update($existing['id'], $data);
            } else {
                $data['created_at'] = $now;
                $this->ruleModel->insert($data);
            }
        }
    }

    public function editByStudent($student_id)
    {
        $student = $this->studentModel->find($student_id);
        if (!$student) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Siswa tidak ditemukan');
        }

        $session    = session();
        $role       = $session->get('user_role');
        $loginUser  = $session->get('user_id');

        $selectedUserId = $role === 'admin'
            ? ($this->request->getGet('user_id') ?? $student['user_id'])
            : $loginUser;

        // Sinkronisasi otomatis sebelum menampilkan
        $this->syncStudentPaymentRules($student['id'], $student['class'], $selectedUserId);

        // Ambil semua student rules
        $rules = $this->ruleModel
            ->select('student_payment_rules.*, payment_categories.name as category_name')
            ->join('payment_categories', 'payment_categories.id = student_payment_rules.category_id')
            ->where('student_payment_rules.student_id', $student_id)
            ->findAll();

        // Ambil kategori sesuai user
        $categoryQuery = $this->categoryModel;
        if ($role !== 'admin') {
            $categoryQuery->where('user_id', $loginUser);
        } else {
            $categoryQuery->where('user_id', $selectedUserId);
        }
        $categories = $categoryQuery->findAll();

        // Ambil class rules untuk referensi
        $classRules = $this->classRuleModel
            ->where('class_id', $student['class'])
            ->findAll();

        $users = $role === 'admin' ? $this->userModel->orderBy('name', 'ASC')->findAll() : [];

        return view('student_rules/edit_multiple', [
            'student'        => $student,
            'rules'          => $rules,
            'categories'     => $categories,
            'classRules'     => $classRules,
            'users'          => $users,
            'selectedUserId' => $selectedUserId,
            'role'           => $role,
        ]);
    }

    // --------------------------------------------------
    // Update multiple rule
    // --------------------------------------------------
    public function updateByStudent($student_id)
    {
        $amounts = $this->request->getPost('amount');
        if (!$amounts) {
            return redirect()->back();
        }

        foreach ($amounts as $key => $amount) {
            $amount = str_replace('.', '', $amount);

            // INSERT BARU
            if (strpos($key, 'new_') === 0) {
                $categoryId = str_replace('new_', '', $key);

                $this->ruleModel->insert([
                    'student_id'   => $student_id,
                    'category_id'  => $categoryId,
                    'amount'       => $amount,
                    'is_mandatory' => 1,
                ]);
            } else {
                // UPDATE
                $rule = $this->ruleModel->find($key);
                if (!$rule) {
                    continue;
                }

                $this->ruleModel->update($key, [
                    'amount' => $amount,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Tarif siswa berhasil diupdate');
    }

    // --------------------------------------------------
    // Disable rule
    // --------------------------------------------------
    public function disableRule($student_id, $rule_id)
    {
        $rule = $this->ruleModel->find($rule_id);

        if (!$rule || $rule['student_id'] != $student_id) {
            return redirect()->back()->with('error', 'Data tidak valid');
        }

        $this->ruleModel->update($rule_id, ['is_mandatory' => 0]);

        return redirect()->back()->with('success', 'Rule berhasil dinonaktifkan');
    }

    // --------------------------------------------------
    // Enable rule
    // --------------------------------------------------
    public function enableRule($student_id, $rule_id)
    {
        $rule = $this->ruleModel->find($rule_id);

        if (!$rule || $rule['student_id'] != $student_id) {
            return redirect()->back()->with('error', 'Data tidak valid');
        }

        $this->ruleModel->update($rule_id, ['is_mandatory' => 1]);

        return redirect()->back()->with('success', 'Rule berhasil diaktifkan');
    }

    public function addRule($student_id)
    {
        $categoryId = $this->request->getPost('category_id');
        $amount     = $this->request->getPost('amount') ?: 0;

        $student = $this->studentModel->find($student_id);
        if (!$student) {
            return redirect()->back()->with('error', 'Siswa tidak ditemukan');
        }

        $session = session();
        $role    = $session->get('user_role');

        // ADMIN boleh tentukan user_id
        $userId = ($role === 'admin')
            ? $this->request->getPost('user_id')
            : $session->get('user_id');

        $this->ruleModel->insert([
            'student_id'   => $student_id,
            'category_id'  => $categoryId,
            'amount'       => str_replace('.', '', $amount),
            'is_mandatory' => 0,
            'user_id'      => $userId,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Rule berhasil ditambahkan');
    }
}
