<?php

namespace App\Controllers;

use App\Models\ClassModel;
use App\Models\StudentModel;
use App\Controllers\BaseController;
use App\Models\PaymentCategoryModel;
use App\Models\StudentPaymentRuleModel;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PaymentCategoryClassRuleModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Exceptions\PageForbiddenException;

class PaymentCategoriesController extends BaseController
{
    protected $paymentCategoryModel;
    protected $paymentCategoryClassRuleModel;
    protected $classModel;
    protected $studentModel;
    protected $helpers = ['form'];

    public function __construct()
    {
        $this->paymentCategoryModel = new PaymentCategoryModel();
        $this->paymentCategoryClassRuleModel = new PaymentCategoryClassRuleModel();
        $this->classModel = new ClassModel();
        $this->studentModel = new StudentModel();
    }

    // ==================================================
    // AUTHORIZATION (USER OWNERSHIP CHECK)
    // ==================================================
    private function authorize(array $category)
    {
        $session = session();

        if ($session->get('user_role') === 'admin') {
            return true;
        }

        if ($category['user_id'] != $session->get('user_id')) {
            throw new PageForbiddenException('Anda tidak memiliki akses ke data ini.');
        }
    }

    // ==================================================
    // INDEX + SEARCH + PAGINATION
    // ==================================================
    public function index()
    {
        $search     = $this->request->getGet('q');
        $filterUser = $this->request->getGet('user_id'); // admin filter
        $perPage    = 10;

        $session = session();
        $role    = $session->get('user_role');
        $userId  = $session->get('user_id');

        $builder = $this->paymentCategoryModel;

        // ----------------------------------------
        // FILTER USER
        // ----------------------------------------
        if ($role === 'admin' && $filterUser) {
            $builder = $builder->where('user_id', $filterUser);
        } elseif ($role !== 'admin') {
            // user biasa → hanya data miliknya
            $builder = $builder->where('user_id', $userId);
        }

        // ----------------------------------------
        // SEARCH
        // ----------------------------------------
        if ($search) {
            $builder = $builder->groupStart()
                ->like('name', $search)
                ->orLike('billing_type', $search)
                ->groupEnd();
        }

        // ----------------------------------------
        // PAGINATION
        // ----------------------------------------
        $data['paymentCategories'] = $builder->paginate($perPage);
        $data['pager']             = $this->paymentCategoryModel->pager;
        $data['search']            = $search;
        $data['filterUser']        = $filterUser;

        // ----------------------------------------
        // ADMIN: DROPDOWN USER
        // ----------------------------------------
        if ($role === 'admin') {
            $db = \Config\Database::connect();
            $data['users'] = $db->table('users')
                ->select('id, name')
                ->orderBy('name')
                ->get()
                ->getResultArray();
        }

        return view('payment_categories/index', $data);
    }

    // ==================================================
    // CREATE FORM
    // ==================================================
    public function create()
    {
        return view('payment_categories/create');
    }

    public function store()
    {
        $rules = [
            'name'            => 'required|min_length[3]',
            'default_amount'  => 'permit_empty|decimal',
            'billing_type'    => 'required|in_list[monthly,one-time]',
            'duration_months' => 'permit_empty|integer'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $session = session();
        $userId = $session->get('user_id');

        // -------------------------------
        // Insert kategori baru
        // -------------------------------
        $this->paymentCategoryModel->insert([
            'name'            => $this->request->getPost('name'),
            'default_amount'  => $this->request->getPost('default_amount') ?: 0,
            'billing_type'    => $this->request->getPost('billing_type'),
            'duration_months' => $this->request->getPost('duration_months') ?: null,
            'user_id'         => $userId
        ]);

        $categoryId = $this->paymentCategoryModel->getInsertID();
        $defaultAmount = $this->request->getPost('default_amount') ?: 0;

        // -------------------------------
        // Auto-create class rules
        // -------------------------------
        $classes = $this->classModel
            ->where('user_id', $userId)
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($classes as $class) {
            $existing = $this->paymentCategoryClassRuleModel
                ->where('category_id', $categoryId)
                ->where('class_id', $class['id'])
                ->first();

            if (!$existing) {
                $this->paymentCategoryClassRuleModel->insert([
                    'category_id' => $categoryId,
                    'class_id'    => $class['id'],
                    'amount'      => $defaultAmount,
                    'user_id'     => $userId
                ]);
            }

            // -------------------------------
            // Auto-create student rules
            // -------------------------------
            $students = $this->studentModel
                ->where('class', $class['id'])
                ->findAll();

            foreach ($students as $student) {
                $existingRule = (new \App\Models\StudentPaymentRuleModel())
                    ->where('student_id', $student['id'])
                    ->where('category_id', $categoryId)
                    ->first();

                if (!$existingRule) {
                    (new \App\Models\StudentPaymentRuleModel())->insert([
                        'student_id'   => $student['id'],
                        'category_id'  => $categoryId,
                        'amount'       => $defaultAmount,
                        'is_mandatory' => 1,
                        'user_id'      => $student['user_id'] ?? $userId,
                        'created_at'   => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }

        return redirect()->to('/payment-categories')
            ->with('success', 'Kategori pembayaran berhasil ditambahkan, termasuk tarif siswa otomatis.');
    }

    // ==================================================
    // EDIT FORM
    // ==================================================
    public function edit($id)
    {
        $category = $this->paymentCategoryModel->find($id);

        if (!$category) {
            throw new PageNotFoundException();
        }

        $this->authorize($category);

        return view('payment_categories/edit', [
            'category' => $category
        ]);
    }

    public function update($id)
    {
        $category = $this->paymentCategoryModel->find($id);
        if (!$category) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Kategori tidak ditemukan');
        }

        $session = session();
        if (
            $session->get('user_role') !== 'admin' &&
            $category['user_id'] != $session->get('user_id')
        ) {
            return redirect()->to('/payment-categories')->with('error', 'Akses ditolak');
        }

        $rules = [
            'name'            => 'required|min_length[3]',
            'default_amount'  => 'required|decimal',
            'billing_type'    => 'required|in_list[monthly,one-time]',
            'duration_months' => 'permit_empty|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $defaultAmount = (float) $this->request->getPost('default_amount');

        $db = \Config\Database::connect();
        $db->transStart();

        // =====================================
        // UPDATE CATEGORY
        // =====================================
        $this->paymentCategoryModel->update($id, [
            'name'            => $this->request->getPost('name'),
            'default_amount'  => $defaultAmount,
            'billing_type'    => $this->request->getPost('billing_type'),
            'duration_months' => $this->request->getPost('duration_months') ?: null,
        ]);

        $studentRuleModel = new \App\Models\StudentPaymentRuleModel();

        // =====================================
        // SYNC CLASS + STUDENT RULE
        // =====================================
        $classes = $this->classModel
            ->where('user_id', $category['user_id'])
            ->findAll();

        foreach ($classes as $class) {

            // ---------- CLASS RULE ----------
            $classRule = $this->paymentCategoryClassRuleModel
                ->where('category_id', $id)
                ->where('class_id', $class['id'])
                ->first();

            if ($classRule) {
                $this->paymentCategoryClassRuleModel->update($classRule['id'], [
                    'amount' => $defaultAmount
                ]);
            } else {
                $this->paymentCategoryClassRuleModel->insert([
                    'category_id' => $id,
                    'class_id'    => $class['id'],
                    'amount'      => $defaultAmount,
                    'user_id'     => $category['user_id']
                ]);
            }

            // ---------- STUDENT RULE ----------
            $students = $this->studentModel
                ->where('class', $class['id'])
                ->findAll();

            foreach ($students as $student) {

                $studentRule = $studentRuleModel
                    ->where('student_id', $student['id'])
                    ->where('category_id', $id)
                    ->first();

                if ($studentRule) {
                    // UPDATE hanya rule otomatis
                    if ($studentRule['is_mandatory'] == 1) {
                        $studentRuleModel->update($studentRule['id'], [
                            'amount'     => $defaultAmount,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    }
                } else {
                    // INSERT baru
                    $studentRuleModel->insert([
                        'student_id'   => $student['id'],
                        'category_id'  => $id,
                        'amount'       => $defaultAmount,
                        'is_mandatory' => 1,
                        'user_id'      => $student['user_id'] ?? $category['user_id'],
                        'created_at'   => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal memperbarui data');
        }

        return redirect()->to('/payment-categories')
            ->with('success', 'Kategori & tarif siswa berhasil disinkronkan');
    }

    // ==================================================
    // DELETE
    // ==================================================
    public function delete($id)
    {
        $category = $this->paymentCategoryModel->find($id);

        if (!$category) {
            throw new PageNotFoundException();
        }

        $this->authorize($category);

        $this->paymentCategoryModel->delete($id);
        $this->paymentCategoryClassRuleModel
            ->where('category_id', $id)
            ->delete();

        return redirect()->to('/payment-categories')
            ->with('success', 'Kategori pembayaran berhasil dihapus.');
    }

    public function editClassRules($categoryId)
    {
        $session = session();
        $role    = $session->get('user_role');
        $userId  = $session->get('user_id');

        $category = $this->paymentCategoryModel->find($categoryId);
        if (!$category) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Kategori tidak ditemukan');
        }

        if ($role !== 'admin' && $category['user_id'] != $userId) {
            throw new \CodeIgniter\Exceptions\PageForbiddenException('Anda tidak memiliki akses ke data ini');
        }

        $classes = $this->classModel
            ->where('user_id', $category['user_id'])
            ->orderBy('id', 'ASC')
            ->findAll();

        $rulesRaw = $this->paymentCategoryClassRuleModel
            ->where('category_id', $categoryId)
            ->where('user_id', $category['user_id'])
            ->findAll();

        $classRules = [];
        foreach ($rulesRaw as $rule) {
            $classRules[$rule['class_id']] = [
                'amount'       => $rule['amount'],
                'is_mandatory' => $rule['is_mandatory'] ?? 1,
                'user_id'      => $rule['user_id']
            ];
        }

        // --- Tambahkan daftar user untuk admin ---
        $users = [];
        if ($role === 'admin') {
            $db = \Config\Database::connect();
            $users = $db->table('users')
                ->select('id, name')
                ->orderBy('name')
                ->get()
                ->getResultArray();
        }

        return view('payment_categories/class_rules', [
            'category'   => $category,
            'classes'    => $classes,
            'classRules' => $classRules,
            'users'      => $users
        ]);
    }

    public function updateClassRules($categoryId)
    {
        $session = session();
        $role    = $session->get('user_role');
        $userId  = $session->get('user_id');

        // Ambil kategori
        $category = $this->paymentCategoryModel
            ->where('id', $categoryId)
            ->where($role === 'admin' ? 'id > 0' : ['user_id' => $userId])
            ->first();

        if (!$category) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(
                'Kategori tidak ditemukan atau akses ditolak'
            );
        }

        $amounts   = $this->request->getPost('amounts');
        $mandatory = $this->request->getPost('mandatory') ?? [];
        $userDefault = $this->request->getPost('user_ids_default') ?? $category['user_id'];

        if (!$amounts || !is_array($amounts)) {
            return redirect()->back()->with('error', 'Tidak ada perubahan');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $studentRuleModel = new \App\Models\StudentPaymentRuleModel();

        foreach ($amounts as $classId => $rawAmount) {

            $amount      = (float) str_replace('.', '', $rawAmount);
            $isMandatory = isset($mandatory[$classId]) ? 1 : 0;
            $userIdRule  = $userDefault;

            // ---------- UPDATE / INSERT CLASS RULE ----------
            $classRule = $this->paymentCategoryClassRuleModel
                ->where('category_id', $categoryId)
                ->where('class_id', $classId)
                ->first();

            if ($classRule) {
                $this->paymentCategoryClassRuleModel->update($classRule['id'], [
                    'amount'       => $amount,
                    'is_mandatory' => $isMandatory,
                    'user_id'      => $userIdRule
                ]);
            } else {
                $this->paymentCategoryClassRuleModel->insert([
                    'category_id'  => $categoryId,
                    'class_id'     => $classId,
                    'amount'       => $amount,
                    'is_mandatory' => $isMandatory,
                    'user_id'      => $userIdRule
                ]);
            }

            // ---------- UPDATE STUDENT RULES ----------
            $students = $this->studentModel->where('class', $classId)->findAll();

            foreach ($students as $student) {
                $studentRule = $studentRuleModel
                    ->where('student_id', $student['id'])
                    ->where('category_id', $categoryId)
                    ->first();

                $updateData = [
                    'is_mandatory' => $isMandatory,
                    'updated_at'   => date('Y-m-d H:i:s'),
                    'user_id'      => $userIdRule
                ];

                if ($isMandatory == 1) {
                    $updateData['amount'] = $amount;
                }

                if ($studentRule) {
                    $studentRuleModel->update($studentRule['id'], $updateData);
                } else {
                    $studentRuleModel->insert(array_merge($updateData, [
                        'student_id'   => $student['id'],
                        'category_id'  => $categoryId,
                        'created_at'   => date('Y-m-d H:i:s')
                    ]));
                }
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menyimpan perubahan');
        }

        return redirect()->back()->with('success', 'Tarif kelas terpilih berhasil diperbarui');
    }
}
