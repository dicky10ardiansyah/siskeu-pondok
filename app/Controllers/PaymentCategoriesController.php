<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PaymentCategoryModel;
use CodeIgniter\HTTP\ResponseInterface;

class PaymentCategoriesController extends BaseController
{
    protected $paymentCategoryModel;
    protected $helpers = ['form'];

    public function __construct()
    {
        $this->paymentCategoryModel = new PaymentCategoryModel();
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
            'default_amount' => $this->request->getPost('default_amount') ?: null,
            'billing_type' => $this->request->getPost('billing_type'),
            'duration_months' => $this->request->getPost('duration_months') ?: null
        ]);

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
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Kategori pembayaran tidak ditemukan');
        }

        $validationRules = [
            'name' => 'required|min_length[3]',
            'default_amount' => 'permit_empty|decimal',
            'billing_type' => 'required|in_list[monthly,one-time]',
            'duration_months' => 'permit_empty|integer'
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->paymentCategoryModel->update($id, [
            'name' => $this->request->getPost('name'),
            'default_amount' => $this->request->getPost('default_amount') ?: null,
            'billing_type' => $this->request->getPost('billing_type'),
            'duration_months' => $this->request->getPost('duration_months') ?: null
        ]);

        return redirect()->to('/payment-categories')->with('success', 'Kategori pembayaran berhasil diupdate!');
    }

    // --------------------------------------------------
    // DELETE
    // --------------------------------------------------
    public function delete($id)
    {
        $this->paymentCategoryModel->delete($id);
        return redirect()->to('/payment-categories')->with('success', 'Kategori pembayaran berhasil dihapus!');
    }
}
