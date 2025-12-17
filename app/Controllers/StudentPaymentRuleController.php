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

    public function editByStudent($student_id)
    {
        $student = $this->studentModel->find($student_id);
        if (!$student) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Siswa tidak ditemukan');
        }

        // rules siswa
        $rules = $this->ruleModel
            ->select('student_payment_rules.*, payment_categories.name as category_name')
            ->join('payment_categories', 'payment_categories.id = student_payment_rules.category_id')
            ->where('student_payment_rules.student_id', $student_id)
            ->findAll();

        $session = session();
        $role    = $session->get('user_role');
        $loginUserId = $session->get('user_id');

        // =============================
        // ADMIN boleh pilih user_id
        // =============================
        $users = [];
        if ($role === 'admin') {
            $users = $this->userModel->findAll();
            $selectedUserId = $this->request->getGet('user_id') ?? $student['user_id'];
        } else {
            $selectedUserId = $loginUserId;
        }

        // kategori mengikuti user terpilih
        $categoryQuery = $this->categoryModel;
        if ($role !== 'admin') {
            $categoryQuery->where('user_id', $loginUserId);
        } else {
            $categoryQuery->where('user_id', $selectedUserId);
        }

        $categories = $categoryQuery->findAll();

        // class rule
        $classRules = $this->classRuleModel
            ->where('class_id', $student['class'])
            ->findAll();

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
