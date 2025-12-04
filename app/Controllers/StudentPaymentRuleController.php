<?php

namespace App\Controllers;

use App\Models\StudentModel;
use App\Controllers\BaseController;
use App\Models\PaymentCategoryModel;
use App\Models\StudentPaymentRuleModel;
use CodeIgniter\HTTP\ResponseInterface;

class StudentPaymentRuleController extends BaseController
{
    protected $studentModel;
    protected $categoryModel;
    protected $ruleModel;

    public function __construct()
    {
        $this->studentModel  = new StudentModel();
        $this->categoryModel = new PaymentCategoryModel();
        $this->ruleModel     = new StudentPaymentRuleModel();
    }

    // --------------------------------------------------
    // Sync semua kategori ke semua siswa
    // --------------------------------------------------
    protected function syncCategoriesToStudents()
    {
        $categories = $this->categoryModel->findAll();
        $students   = $this->studentModel->findAll();

        foreach ($students as $student) {
            foreach ($categories as $category) {
                // Hanya tambah jika belum ada
                $existing = $this->ruleModel
                    ->where('student_id', $student['id'])
                    ->where('category_id', $category['id'])
                    ->first();

                if (!$existing && ($category['is_mandatory'] ?? 1)) {
                    $this->ruleModel->insert([
                        'student_id' => $student['id'],
                        'category_id' => $category['id'],
                        'amount' => $category['default_amount'] ?? 0,
                        'is_mandatory' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }

    // --------------------------------------------------
    // Edit Tarif Siswa
    // --------------------------------------------------
    public function editByStudent($student_id)
    {
        $this->syncCategoriesToStudents();

        $student = $this->studentModel->find($student_id);
        if (!$student) {
            throw \CodeIgniter\Exceptions\PageNotFoundException('Siswa tidak ditemukan');
        }

        $rules = $this->ruleModel
            ->select('student_payment_rules.*, payment_categories.name as category_name')
            ->join('payment_categories', 'payment_categories.id = student_payment_rules.category_id')
            ->where('student_payment_rules.student_id', $student_id)
            ->findAll();

        $categories = $this->categoryModel->findAll();

        return view('student_rules/edit_multiple', compact('student', 'rules', 'categories'));
    }

    // --------------------------------------------------
    // Update Multiple Rule per Siswa
    // --------------------------------------------------
    public function updateByStudent($student_id)
    {
        $student = $this->studentModel->find($student_id);
        if (!$student) {
            throw \CodeIgniter\Exceptions\PageNotFoundException('Siswa tidak ditemukan');
        }

        $amounts = $this->request->getPost('amount');
        if ($amounts) {
            foreach ($amounts as $rule_id => $amount) {
                $this->ruleModel->update($rule_id, ['amount' => $amount]);
            }
        }

        return redirect()->back()->with('success', 'Tarif siswa berhasil diupdate!');
    }

    public function disableRule($student_id, $rule_id)
    {
        $rule = $this->ruleModel->find($rule_id);
        if (!$rule || $rule['student_id'] != $student_id) {
            return redirect()->back()->with('error', 'Rule tidak ditemukan.');
        }

        // Disable tanpa mengubah amount
        $this->ruleModel->update($rule_id, [
            'is_mandatory' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Rule berhasil dinonaktifkan.');
    }

    public function enableRule($student_id, $rule_id)
    {
        $rule = $this->ruleModel->find($rule_id);
        if (!$rule || $rule['student_id'] != $student_id) {
            return redirect()->back()->with('error', 'Rule tidak ditemukan.');
        }

        // Enable tanpa mengubah amount
        $this->ruleModel->update($rule_id, [
            'is_mandatory' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Rule berhasil diaktifkan.');
    }

    // --------------------------------------------------
    // ADD NEW RULE
    // --------------------------------------------------
    public function addRule($student_id)
    {
        $student = $this->studentModel->find($student_id);
        if (!$student) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Siswa tidak ditemukan');
        }

        $category_id = $this->request->getPost('category_id');
        $amount = $this->request->getPost('amount');

        $existing = $this->ruleModel
            ->where('student_id', $student_id)
            ->where('category_id', $category_id)
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Rule sudah ada.');
        }

        $this->ruleModel->insert([
            'student_id' => $student_id,
            'category_id' => $category_id,
            'amount' => $amount,
            'is_mandatory' => 0, // opsional
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Rule baru berhasil ditambahkan.');
    }
}
