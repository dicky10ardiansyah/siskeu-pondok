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

    // ==================================================
    // STORE
    // ==================================================
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

        // user_id otomatis dari model (beforeInsert)
        $this->paymentCategoryModel->insert([
            'name'            => $this->request->getPost('name'),
            'default_amount'  => $this->request->getPost('default_amount') ?: 0,
            'billing_type'    => $this->request->getPost('billing_type'),
            'duration_months' => $this->request->getPost('duration_months') ?: null,
        ]);

        $categoryId = $this->paymentCategoryModel->getInsertID();

        // Buat default rule untuk semua kelas
        $classes = $this->classModel->findAll();
        foreach ($classes as $class) {
            $this->paymentCategoryClassRuleModel->insert([
                'category_id' => $categoryId,
                'class_id'    => $class['id'],
                'amount'      => $this->request->getPost('default_amount') ?: 0
            ]);
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

    // ==================================================
    // UPDATE
    // ==================================================
    public function update($id)
    {
        $category = $this->paymentCategoryModel->find($id);

        if (!$category) {
            throw new PageNotFoundException();
        }

        $this->authorize($category);

        $rules = [
            'name'            => 'required|min_length[3]',
            'default_amount'  => 'permit_empty|decimal',
            'billing_type'    => 'required|in_list[monthly,one-time]',
            'duration_months' => 'permit_empty|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $this->paymentCategoryModel->update($id, [
            'name'            => $this->request->getPost('name'),
            'default_amount'  => $this->request->getPost('default_amount') ?: 0,
            'billing_type'    => $this->request->getPost('billing_type'),
            'duration_months' => $this->request->getPost('duration_months') ?: null,
        ]);

        return redirect()->to('/payment-categories')
            ->with('success', 'Kategori pembayaran berhasil diperbarui.');
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

    // --------------------------------------------------
    // CRUD Tarif per Kelas (Opsional jika butuh view terpisah)
    // --------------------------------------------------
    public function editClassRules($categoryId)
    {
        $filterUser = $this->request->getGet('user_id'); // untuk admin
        $session    = session();
        $role       = $session->get('user_role');
        $userId     = $session->get('user_id');

        // Ambil kategori + filter user
        $catBuilder = $this->paymentCategoryModel;

        if ($role === 'admin' && $filterUser) {
            $catBuilder = $catBuilder->where('user_id', $filterUser);
        } elseif ($role !== 'admin') {
            $catBuilder = $catBuilder->where('user_id', $userId);
        }

        $category = $catBuilder->where('id', $categoryId)->first();

        if (!$category) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(
                'Kategori tidak ditemukan atau Anda tidak memiliki akses'
            );
        }

        // Ambil class rules → ikut owner category
        $rulesRaw = $this->paymentCategoryClassRuleModel
            ->where('category_id', $categoryId)
            ->where('user_id', $category['user_id'])
            ->findAll();

        $classRules = [];
        foreach ($rulesRaw as $r) {
            $classRules[$r['class_id']] = $r['amount'];
        }

        $classes = $this->classModel->findAll();

        return view('payment_categories/class_rules', [
            'category'   => $category,
            'classes'    => $classes,
            'classRules' => $classRules,
            'filterUser' => $filterUser
        ]);
    }

    public function updateClassRules($categoryId)
    {
        $session = session();
        $role    = $session->get('user_role');
        $userId  = $session->get('user_id');

        $category = $this->paymentCategoryModel
            ->where('id', $categoryId)
            ->where($role === 'admin' ? 'id > 0' : ['user_id' => $userId])
            ->first();

        if (!$category) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(
                'Kategori tidak ditemukan atau Anda tidak memiliki akses'
            );
        }

        $amounts = $this->request->getPost('amounts');
        if (!$amounts || !is_array($amounts)) {
            return redirect()->back()->with('error', 'Tidak ada data yang disimpan');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($amounts as $classId => $amount) {
            $amount = (float) str_replace('.', '', $amount);

            $existing = $this->paymentCategoryClassRuleModel
                ->where('category_id', $categoryId)
                ->where('class_id', $classId)
                ->where('user_id', $category['user_id'])
                ->first();

            if ($existing) {
                $this->paymentCategoryClassRuleModel->update($existing['id'], [
                    'amount' => $amount
                ]);
            } else {
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
