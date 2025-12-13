<?php

namespace App\Controllers;

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

    public function __construct()
    {
        $this->studentModel   = new StudentModel();
        $this->categoryModel  = new PaymentCategoryModel();
        $this->ruleModel      = new StudentPaymentRuleModel();
        $this->classRuleModel = new PaymentCategoryClassRuleModel();
    }

    // --------------------------------------------------
    // Sync kategori sesuai hak akses user
    // --------------------------------------------------
    protected function syncCategoriesToStudents()
    {
        $userId = session()->get('user_id');

        $categories = $this->isAdmin()
            ? $this->categoryModel->findAll()
            : $this->categoryModel->where('user_id', $userId)->findAll();

        $students = $this->studentModel->findAll();

        foreach ($students as $student) {
            foreach ($categories as $category) {

                if (empty($category['is_mandatory'])) {
                    continue;
                }

                $existing = $this->ruleModel
                    ->where('student_id', $student['id'])
                    ->where('category_id', $category['id'])
                    ->first();

                if ($existing) {
                    continue;
                }

                $classRule = $this->classRuleModel
                    ->where('class_id', $student['class'])
                    ->where('category_id', $category['id'])
                    ->first();

                $amount = $classRule['amount']
                    ?? $category['default_amount']
                    ?? 0;

                $this->ruleModel->insert([
                    'student_id'   => $student['id'],
                    'category_id'  => $category['id'],
                    'amount'       => $amount,
                    'is_mandatory' => 1,
                    'user_id'      => $category['user_id'],
                ]);
            }
        }
    }

    // --------------------------------------------------
    // Edit tarif siswa
    // --------------------------------------------------
    public function editByStudent($student_id)
    {
        $this->syncCategoriesToStudents();

        $student = $this->studentModel->find($student_id);
        if (!$student) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Siswa tidak ditemukan');
        }

        $rulesQuery = $this->ruleModel
            ->select('student_payment_rules.*, payment_categories.name as category_name')
            ->join('payment_categories', 'payment_categories.id = student_payment_rules.category_id')
            ->where('student_payment_rules.student_id', $student_id);

        if (!$this->isAdmin()) {
            $rulesQuery->where('student_payment_rules.user_id', session()->get('user_id'));
        }

        $rules = $rulesQuery->findAll();

        $categories = $this->isAdmin()
            ? $this->categoryModel->findAll()
            : $this->categoryModel->where('user_id', session()->get('user_id'))->findAll();

        $classRules = $this->classRuleModel
            ->where('class_id', $student['class'])
            ->findAll();

        return view('student_rules/edit_multiple', compact(
            'student',
            'rules',
            'categories',
            'classRules'
        ));
    }

    // --------------------------------------------------
    // Update multiple rule siswa
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

                if (!$this->isAdmin()) {
                    $category = $this->categoryModel
                        ->where('id', $categoryId)
                        ->where('user_id', session()->get('user_id'))
                        ->first();

                    if (!$category) {
                        continue;
                    }
                }

                $this->ruleModel->insert([
                    'student_id'   => $student_id,
                    'category_id'  => $categoryId,
                    'amount'       => $amount,
                    'is_mandatory' => 1,
                    'user_id'      => session()->get('user_id'),
                ]);
            } else {
                // UPDATE
                $rule = $this->ruleModel->find($key);

                if (!$rule) {
                    continue;
                }

                if (!$this->isAdmin() && $rule['user_id'] != session()->get('user_id')) {
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

        if (
            !$rule ||
            $rule['student_id'] != $student_id ||
            (!$this->isAdmin() && $rule['user_id'] != session()->get('user_id'))
        ) {
            return redirect()->back()->with('error', 'Akses ditolak');
        }

        $this->ruleModel->update($rule_id, ['is_mandatory' => 0]);

        return redirect()->back()->with('success', 'Rule dinonaktifkan');
    }

    // --------------------------------------------------
    // Enable rule
    // --------------------------------------------------
    public function enableRule($student_id, $rule_id)
    {
        $rule = $this->ruleModel->find($rule_id);

        if (
            !$rule ||
            $rule['student_id'] != $student_id ||
            (!$this->isAdmin() && $rule['user_id'] != session()->get('user_id'))
        ) {
            return redirect()->back()->with('error', 'Akses ditolak');
        }

        $this->ruleModel->update($rule_id, ['is_mandatory' => 1]);

        return redirect()->back()->with('success', 'Rule diaktifkan');
    }

    // --------------------------------------------------
    // Add rule baru
    // --------------------------------------------------
    public function addRule($student_id)
    {
        $categoryId = $this->request->getPost('category_id');
        $amount     = $this->request->getPost('amount');

        if (!$this->isAdmin()) {
            $category = $this->categoryModel
                ->where('id', $categoryId)
                ->where('user_id', session()->get('user_id'))
                ->first();

            if (!$category) {
                return redirect()->back()->with('error', 'Akses ditolak');
            }
        }

        $this->ruleModel->insert([
            'student_id'   => $student_id,
            'category_id'  => $categoryId,
            'amount'       => $amount ?: 0,
            'is_mandatory' => 0,
            'user_id'      => session()->get('user_id'),
        ]);

        return redirect()->back()->with('success', 'Rule berhasil ditambahkan');
    }
}
