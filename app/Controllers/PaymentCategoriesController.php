<?php

namespace App\Controllers;

use App\Models\ClassModel;
use App\Models\StudentModel;
use App\Controllers\BaseController;
use App\Models\PaymentCategoryModel;
use App\Models\StudentPaymentRuleModel;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PaymentCategoryClassRuleModel;

class PaymentCategoriesController extends BaseController
{
    protected $paymentCategoryModel;
    protected $paymentCategoryClassRuleModel;
    protected $classModel;
    protected $helpers = ['form'];

    public function __construct()
    {
        $this->paymentCategoryModel = new PaymentCategoryModel();
        $this->paymentCategoryClassRuleModel = new PaymentCategoryClassRuleModel();
        $this->classModel = new ClassModel();
    }

    // --------------------------------------------------
    // INDEX + SEARCH + PAGINATION
    // --------------------------------------------------
    public function index()
    {
        $search = $this->request->getGet('q');
        $perPage = 10;

        $builder = $this->paymentCategoryModel;

        if ($search) {
            $builder = $builder->like('name', $search);
        }

        $data['paymentCategories'] = $builder->paginate($perPage, 'payment_categories');
        $data['pager'] = $this->paymentCategoryModel->pager;
        $data['search'] = $search;

        return view('payment_categories/index', $data);
    }

    // --------------------------------------------------
    // FORM CREATE
    // --------------------------------------------------
    public function create()
    {
        return view('payment_categories/create');
    }

    // --------------------------------------------------
    // STORE
    // --------------------------------------------------
    public function store()
    {
        $validationRules = [
            'name' => 'required|min_length[3]',
            'default_amount' => 'permit_empty|decimal',
            'billing_type' => 'required|in_list[monthly,one-time]',
            'duration_months' => 'permit_empty|integer'
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->paymentCategoryModel->save([
            'name' => $this->request->getPost('name'),
            'default_amount' => $this->request->getPost('default_amount') ?: 0,
            'billing_type' => $this->request->getPost('billing_type'),
            'duration_months' => $this->request->getPost('duration_months') ?: null
        ]);

        $categoryId = $this->paymentCategoryModel->getInsertID();

        // Generate tarif default untuk semua kelas
        $classes = $this->classModel->findAll(); // jika tidak ada tabel kelas, bisa ambil distinct dari students
        foreach ($classes as $class) {
            $this->paymentCategoryClassRuleModel->insert([
                'category_id' => $categoryId,
                'class_id' => $class['id'],
                'amount' => $this->request->getPost('default_amount') ?: 0
            ]);
        }

        return redirect()->to('/payment-categories')->with('success', 'Kategori pembayaran berhasil ditambahkan!');
    }

    // --------------------------------------------------
    // FORM EDIT
    // --------------------------------------------------
    public function edit($id)
    {
        $category = $this->paymentCategoryModel->find($id);

        if (!$category) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Kategori pembayaran tidak ditemukan');
        }

        return view('payment_categories/edit', ['category' => $category]);
    }

    // --------------------------------------------------
    // UPDATE
    // --------------------------------------------------
    public function update($id)
    {
        $category = $this->paymentCategoryModel->find($id);
        if (!$category) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Kategori pembayaran tidak ditemukan');
        }

        $validationRules = [
            'name' => 'required|min_length[3]',
            'default_amount' => 'permit_empty|decimal',
            'billing_type' => 'required|in_list[monthly,one-time]',
            'duration_months' => 'permit_empty|integer',
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Update kategori
        $this->paymentCategoryModel->update($id, [
            'name' => $this->request->getPost('name'),
            'default_amount' => $this->request->getPost('default_amount') ?: 0,
            'billing_type' => $this->request->getPost('billing_type'),
            'duration_months' => $this->request->getPost('duration_months') ?: null
        ]);

        // Update tarif per kelas jika dikirim
        $classAmounts = $this->request->getPost('class_amount'); // array[class_id => amount]
        if ($classAmounts && is_array($classAmounts)) {
            foreach ($classAmounts as $classId => $amount) {
                // Hapus format titik ribuan
                $amount = str_replace('.', '', $amount);
                $amount = floatval($amount);

                // Cek apakah sudah ada record
                $existing = $this->paymentCategoryClassRuleModel
                    ->where('category_id', $id)
                    ->where('class_id', $classId)
                    ->first();

                if ($existing) {
                    // Update
                    $this->paymentCategoryClassRuleModel->update($existing['id'], [
                        'amount' => $amount
                    ]);
                } else {
                    // Insert baru
                    $this->paymentCategoryClassRuleModel->insert([
                        'category_id' => $id,
                        'class_id' => $classId,
                        'amount' => $amount
                    ]);
                }
            }
        }

        return redirect()->to('/payment-categories')->with('success', 'Kategori pembayaran berhasil diupdate!');
    }

    // --------------------------------------------------
    // DELETE
    // --------------------------------------------------
    public function delete($id)
    {
        $this->paymentCategoryModel->delete($id);

        // Hapus semua tarif per kelas yang terkait
        $this->paymentCategoryClassRuleModel->where('category_id', $id)->delete();

        return redirect()->to('/payment-categories')->with('success', 'Kategori pembayaran berhasil dihapus!');
    }

    // --------------------------------------------------
    // CRUD Tarif per Kelas (Opsional jika butuh view terpisah)
    // --------------------------------------------------
    public function editClassRules($categoryId)
    {
        $category = $this->paymentCategoryModel->find($categoryId);
        $classRulesRaw = $this->paymentCategoryClassRuleModel->where('category_id', $categoryId)->findAll();
        $classes = $this->classModel->findAll();

        // Transformasi: [class_id => amount]
        $classRules = [];
        foreach ($classRulesRaw as $rule) {
            $classRules[$rule['class_id']] = $rule['amount'];
        }

        return view('payment_categories/class_rules', compact('category', 'classRules', 'classes'));
    }

    public function updateClassRules($categoryId)
    {
        $category = $this->paymentCategoryModel->find($categoryId);
        if (!$category) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Kategori pembayaran tidak ditemukan');
        }

        $amounts = $this->request->getPost('amounts');
        if (!$amounts || !is_array($amounts)) {
            return redirect()->back()->with('error', 'Tidak ada data yang disimpan.');
        }

        $studentModel = new StudentModel();
        $studentPaymentRuleModel = new StudentPaymentRuleModel();
        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($amounts as $classId => $amount) {

            // --- UPDATE / INSERT payment_category_class_rules ---
            $existingRule = $this->paymentCategoryClassRuleModel
                ->where('category_id', $categoryId)
                ->where('class_id', $classId)
                ->first();

            if ($existingRule) {
                $this->paymentCategoryClassRuleModel->update($existingRule['id'], [
                    'amount' => $amount
                ]);
            } else {
                $this->paymentCategoryClassRuleModel->insert([
                    'category_id' => $categoryId,
                    'class_id'    => $classId,
                    'amount'      => $amount
                ]);
            }

            // --- Update student_payment_rules ---
            $students = $studentModel->where('class', $classId)->findAll();
            foreach ($students as $stu) {

                $rule = $studentPaymentRuleModel
                    ->where('student_id', $stu['id'])
                    ->where('category_id', $categoryId)
                    ->first();

                if ($rule) {
                    if (!isset($rule['is_paid']) || $rule['is_paid'] == 0) {
                        $studentPaymentRuleModel->update($rule['id'], [
                            'amount'     => $amount,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                } else {
                    $studentPaymentRuleModel->insert([
                        'student_id'  => $stu['id'],
                        'category_id' => $categoryId,
                        'amount'      => $amount,
                        'is_mandatory' => 1,
                        'is_paid'     => 0,
                        'created_at'  => date('Y-m-d H:i:s'),
                        'updated_at'  => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menyimpan perubahan.');
        }

        return redirect()->back()->with('success', 'Tarif kategori per kelas berhasil diperbarui dengan aman.');
    }
}
