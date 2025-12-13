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
    protected $helpers = ['form'];

    public function __construct()
    {
        $this->paymentCategoryModel = new PaymentCategoryModel();
        $this->paymentCategoryClassRuleModel = new PaymentCategoryClassRuleModel();
        $this->classModel = new ClassModel();
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

        // Insert kategori baru beserta user_id
        $this->paymentCategoryModel->insert([
            'name'            => $this->request->getPost('name'),
            'default_amount'  => $this->request->getPost('default_amount') ?: 0,
            'billing_type'    => $this->request->getPost('billing_type'),
            'duration_months' => $this->request->getPost('duration_months') ?: null,
            'user_id'         => $userId
        ]);

        $categoryId = $this->paymentCategoryModel->getInsertID();

        // Ambil kelas milik user kategori
        $classes = $this->classModel
            ->where('user_id', $userId)
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($classes as $class) {
            // Cek dulu jika rule sudah ada untuk kategori + kelas
            $existing = $this->paymentCategoryClassRuleModel
                ->where('category_id', $categoryId)
                ->where('class_id', $class['id'])
                ->first();

            if (!$existing) {
                $this->paymentCategoryClassRuleModel->insert([
                    'category_id' => $categoryId,
                    'class_id'    => $class['id'],
                    'amount'      => $this->request->getPost('default_amount') ?: 0,
                    'user_id'     => $userId
                ]);
            }
        }

        return redirect()->to('/payment-categories')
            ->with('success', 'Kategori pembayaran berhasil ditambahkan.');
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
        if ($session->get('user_role') !== 'admin' && $category['user_id'] != $session->get('user_id')) {
            return redirect()->to('/payment-categories')->with('error', 'Akses ditolak.');
        }

        $rules = [
            'name'            => 'required|min_length[3]',
            'default_amount'  => 'required|decimal',
            'billing_type'    => 'required|in_list[monthly,one-time]',
            'duration_months' => 'permit_empty|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $defaultAmount = $this->request->getPost('default_amount') ?: 0;

        // Update kategori
        $this->paymentCategoryModel->update($id, [
            'name'            => $this->request->getPost('name'),
            'default_amount'  => $defaultAmount,
            'billing_type'    => $this->request->getPost('billing_type'),
            'duration_months' => $this->request->getPost('duration_months') ?: null,
            'user_id'         => $category['user_id']
        ]);

        // =====================================================
        // Sinkronisasi class rules
        // =====================================================
        $classes = $this->classModel
            ->where('user_id', $category['user_id'])
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($classes as $class) {
            $existing = $this->paymentCategoryClassRuleModel
                ->where('category_id', $id)
                ->where('class_id', $class['id'])
                ->first();

            if ($existing) {
                // Update semua amount sesuai default_amount kategori
                $this->paymentCategoryClassRuleModel->update($existing['id'], [
                    'amount' => $defaultAmount
                ]);
            } else {
                // Insert jika belum ada
                $this->paymentCategoryClassRuleModel->insert([
                    'category_id' => $id,
                    'class_id'    => $class['id'],
                    'amount'      => $defaultAmount,
                    'user_id'     => $category['user_id']
                ]);
            }
        }

        return redirect()->to('/payment-categories')
            ->with('success', 'Kategori pembayaran dan tarif per kelas berhasil diperbarui.');
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

        // ======================================
        // Ambil kategori (validasi ownership)
        // ======================================
        $category = $this->paymentCategoryModel->find($categoryId);

        if (!$category) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(
                'Kategori tidak ditemukan'
            );
        }

        // User biasa hanya boleh akses kategori miliknya
        if ($role !== 'admin' && $category['user_id'] != $userId) {
            throw new \CodeIgniter\Exceptions\PageForbiddenException(
                'Anda tidak memiliki akses ke data ini'
            );
        }

        // ======================================
        // Ambil kelas MILIK OWNER KATEGORI
        // ======================================
        $classes = $this->classModel
            ->where('user_id', $category['user_id'])
            ->orderBy('id', 'ASC')
            ->findAll();

        // ======================================
        // Ambil class rules MILIK OWNER KATEGORI
        // ======================================
        $rulesRaw = $this->paymentCategoryClassRuleModel
            ->where('category_id', $categoryId)
            ->where('user_id', $category['user_id'])
            ->findAll();

        // Mapping: class_id => amount
        $classRules = [];
        foreach ($rulesRaw as $rule) {
            $classRules[$rule['class_id']] = $rule['amount'];
        }

        return view('payment_categories/class_rules', [
            'category'   => $category,
            'classes'    => $classes,
            'classRules' => $classRules,
        ]);
    }

    public function updateClassRules($categoryId)
    {
        $session = session();
        $role    = $session->get('user_role');
        $userId  = $session->get('user_id');

        // Ambil kategori sesuai user atau admin
        $category = $this->paymentCategoryModel
            ->where('id', $categoryId)
            ->where($role === 'admin' ? 'id > 0' : ['user_id' => $userId])
            ->first();

        if (!$category) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(
                'Kategori tidak ditemukan atau Anda tidak memiliki akses'
            );
        }

        // Ambil input amounts: [class_id => amount]
        $amounts = $this->request->getPost('amounts');
        if (!$amounts || !is_array($amounts)) {
            return redirect()->back()->with('error', 'Tidak ada data yang disimpan');
        }

        // Ambil kelas milik user kategori
        $classes = $this->classModel
            ->where('user_id', $category['user_id'])
            ->orderBy('id', 'ASC')
            ->findAll();

        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($classes as $class) {
            $classId = $class['id'];

            // Ambil nilai amount input, default ke 0 jika tidak ada
            $amount = isset($amounts[$classId]) ? (float) str_replace('.', '', $amounts[$classId]) : 0;

            // Cek existing rule untuk kategori + kelas
            $existing = $this->paymentCategoryClassRuleModel
                ->where('category_id', $categoryId)
                ->where('class_id', $classId)
                ->first();

            if ($existing) {
                // Update jika sudah ada
                $this->paymentCategoryClassRuleModel->update($existing['id'], [
                    'amount'  => $amount,
                    'user_id' => $category['user_id'] // pastikan konsisten
                ]);
            } else {
                // Insert baru jika belum ada
                $this->paymentCategoryClassRuleModel->insert([
                    'category_id' => $categoryId,
                    'class_id'    => $classId,
                    'amount'      => $amount,
                    'user_id'     => $category['user_id']
                ]);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menyimpan perubahan');
        }

        return redirect()->back()->with('success', 'Tarif per kelas berhasil diperbarui');
    }
}
