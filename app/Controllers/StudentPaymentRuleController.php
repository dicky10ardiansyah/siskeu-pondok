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
        $this->studentModel  = new StudentModel();
        $this->categoryModel = new PaymentCategoryModel();
        $this->ruleModel     = new StudentPaymentRuleModel();
        $this->classRuleModel = new PaymentCategoryClassRuleModel();
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

                // Skip jika sudah ada rule
                $existing = $this->ruleModel
                    ->where('student_id', $student['id'])
                    ->where('category_id', $category['id'])
                    ->first();

                if ($existing) {
                    continue;
                }

                // Wajib? Default = mandatory (1)
                $isMandatory = isset($category['is_mandatory'])
                    ? $category['is_mandatory']
                    : 1;

                if (!$isMandatory) {
                    continue;
                }

                // Ambil tarif kelas
                $classRule = $this->classRuleModel
                    ->where('class_id', $student['class'])
                    ->where('category_id', $category['id'])
                    ->first();

                // Jika rule kelas kosong → pakai default_amount kategori
                if ($classRule && $classRule['amount'] !== null) {
                    $amount = $classRule['amount'];
                } else {
                    $amount = $category['default_amount'] ?? 0;
                }

                // Insert
                $this->ruleModel->insert([
                    'student_id'  => $student['id'],
                    'category_id' => $category['id'],
                    'amount'      => $amount,
                    'is_mandatory' => 1,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    // --------------------------------------------------
    // Edit Tarif Siswa
    // --------------------------------------------------
    public function editByStudent($student_id)
    {
        // Selalu sinkron kategori dan rule siswa
        $this->syncCategoriesToStudents();

        $student = $this->studentModel->find($student_id);
        if (!$student) {
            throw \CodeIgniter\Exceptions\PageNotFoundException('Siswa tidak ditemukan');
        }

        // RESET QUERY UNTUK MENGAMBIL DATA TERBARU
        $rules = $this->ruleModel
            ->resetQuery()
            ->select('student_payment_rules.*, payment_categories.name as category_name')
            ->join('payment_categories', 'payment_categories.id = student_payment_rules.category_id')
            ->where('student_payment_rules.student_id', $student_id)
            ->findAll();

        $categories = $this->categoryModel->findAll();

        $classRules = $this->classRuleModel
            ->resetQuery()
            ->where('class_id', $student['class'])
            ->findAll();

        return view('student_rules/edit_multiple', compact('student', 'rules', 'categories', 'classRules'));
    }

    // --------------------------------------------------
    // Update Multiple Rule per Siswa
    // --------------------------------------------------
    public function updateByStudent($student_id)
    {
        $student = $this->studentModel->find($student_id);
        if (!$student) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Siswa tidak ditemukan');
        }

        $amounts = $this->request->getPost('amount');
        if ($amounts) {
            foreach ($amounts as $key => $amount) {

                // Hilangkan titik format
                $amount = str_replace('.', '', $amount);

                if (strpos($key, 'new_') === 0) {
                    // INI RULE BARU -> INSERT
                    $categoryId = str_replace('new_', '', $key);

                    $this->ruleModel->insert([
                        'student_id'  => $student_id,
                        'category_id' => $categoryId,
                        'amount'      => $amount,
                        'is_mandatory' => 1,
                        'created_at'  => date('Y-m-d H:i:s'),
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]);
                } else {
                    // RULE SUDAH ADA -> UPDATE
                    $this->ruleModel->update($key, [
                        'amount' => $amount,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
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
        $amount      = $this->request->getPost('amount');

        $existing = $this->ruleModel
            ->where('student_id', $student_id)
            ->where('category_id', $category_id)
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Rule sudah ada.');
        }

        // Jika amount kosong, ambil default dari kelas atau kategori
        if (!$amount) {
            $classRule = $this->classRuleModel
                ->where('class_id', $student['class_id'])
                ->where('category_id', $category_id)
                ->first();

            $amount = $classRule['amount'] ?? ($this->categoryModel->find($category_id)['default_amount'] ?? 0);
        }

        $this->ruleModel->insert([
            'student_id'  => $student_id,
            'category_id' => $category_id,
            'amount'      => $amount,
            'is_mandatory' => 0, // opsional
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Rule baru berhasil ditambahkan.');
    }
}
