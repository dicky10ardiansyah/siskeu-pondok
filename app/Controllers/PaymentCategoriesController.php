<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PaymentCategoryModel;
use CodeIgniter\HTTP\ResponseInterface;

class PaymentCategoriesController extends BaseController
{
    protected $paymentCategoryModel;

    public function __construct()
    {
        helper(['form', 'url']);
        $this->paymentCategoryModel = new PaymentCategoryModel();
    }

    // ================= INDEX =================
    public function index()
    {
        $search = $this->request->getGet('q');
        $perPage = 10;

        $builder = $this->paymentCategoryModel;

        if ($search) {
            $builder = $builder->like('name', $search);
        }

        $categories = $builder->paginate($perPage, 'categories');

        return view('payment_categories/index', [
            'categories' => $categories,
            'pager' => $this->paymentCategoryModel->pager,
            'search' => $search
        ]);
    }

    // ================= CREATE =================
    public function create()
    {
        return view('payment_categories/create', [
            'validation' => \Config\Services::validation()
        ]);
    }

    // ================= STORE =================
    public function store()
    {
        $validation = $this->validate([
            'name' => 'required|is_unique[payment_categories.name]',
            'default_amount' => 'required|numeric|greater_than_equal_to[0]'
        ]);

        if (!$validation) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $this->paymentCategoryModel->save([
            'name' => $this->request->getPost('name'),
            'default_amount' => $this->request->getPost('default_amount')
        ]);

        return redirect()->to('/payment-categories')->with('success', 'Kategori pembayaran berhasil ditambahkan.');
    }

    // ================= EDIT =================
    public function edit($id)
    {
        $category = $this->paymentCategoryModel->find($id);
        if (!$category) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Kategori tidak ditemukan");
        }

        return view('payment_categories/edit', [
            'category' => $category,
            'validation' => \Config\Services::validation()
        ]);
    }

    // ================= UPDATE =================
    public function update($id)
    {
        $category = $this->paymentCategoryModel->find($id);
        if (!$category) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Kategori tidak ditemukan");
        }

        $validation = $this->validate([
            'name' => "required|is_unique[payment_categories.name,id,{$id}]",
            'default_amount' => 'required|numeric|greater_than_equal_to[0]'
        ]);

        if (!$validation) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $this->paymentCategoryModel->update($id, [
            'name' => $this->request->getPost('name'),
            'default_amount' => $this->request->getPost('default_amount')
        ]);

        return redirect()->to('/payment-categories')->with('success', 'Kategori pembayaran berhasil diperbarui.');
    }

    // ================= DELETE =================
    public function delete($id)
    {
        $category = $this->paymentCategoryModel->find($id);
        if (!$category) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Kategori tidak ditemukan");
        }

        // Optional: cek apakah kategori sudah ada tagihan
        $this->paymentCategoryModel->delete($id);

        return redirect()->to('/payment-categories')->with('success', 'Kategori pembayaran berhasil dihapus.');
    }
}
