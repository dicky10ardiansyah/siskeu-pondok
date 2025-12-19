<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ClassModel;
use App\Models\StudentModel;
use App\Controllers\BaseController;
use App\Models\PaymentCategoryModel;
use App\Models\StudentPaymentRuleModel;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PaymentCategoryClassRuleModel;

class ClassController extends BaseController
{
    protected $classModel;
    protected $userModel;
    protected $paymentCategoryModel;
    protected $paymentCategoryClassRuleModel;
    protected $studentPaymentRuleModel;
    protected $studentModel;
    protected $helpers = ['form'];

    public function __construct()
    {
        $this->classModel = new ClassModel();
        $this->userModel  = new UserModel();
        $this->paymentCategoryModel         = new PaymentCategoryModel();
        $this->paymentCategoryClassRuleModel = new PaymentCategoryClassRuleModel();
        $this->studentPaymentRuleModel      = new StudentPaymentRuleModel();
        $this->studentModel                 = new StudentModel();
    }

    private function authorize($ownerId)
    {
        return session()->get('user_role') === 'admin'
            || $ownerId == session()->get('user_id');
    }

    public function index()
    {
        $search  = $this->request->getGet('q');
        $perPage = 10;

        $model = $this->classModel->getWithUser();

        // User biasa hanya lihat data sendiri
        if (session()->get('user_role') !== 'admin') {
            $model->where('classes.user_id', session()->get('user_id'));
        }
        // Admin boleh filter user
        elseif ($userId = $this->request->getGet('user_id')) {
            $model->where('classes.user_id', $userId);
        }

        if ($search) {
            $model->like('classes.name', $search);
        }

        $data = [
            'classes' => $model->paginate($perPage, 'classes'),
            'pager'   => $model->pager,
            'search'  => $search,
        ];

        // HANYA admin dapat list user
        if (session()->get('user_role') === 'admin') {
            $data['users'] = $this->userModel->findAll();
        }

        return view('classes/index', $data);
    }

    public function create()
    {
        $data = [];

        // Hanya admin yang membutuhkan list user
        if (session()->get('user_role') === 'admin') {
            $data['users'] = $this->userModel->findAll();
        }

        return view('classes/create', $data);
    }

    public function store()
    {
        $session = session();

        $userId = $session->get('user_id');
        if ($session->get('user_role') === 'admin') {
            $userId = $this->request->getPost('user_id');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // ===============================
        // INSERT CLASS
        // ===============================
        $this->classModel->insert([
            'name'    => $this->request->getPost('name'),
            'user_id' => $userId
        ]);

        $classId = $this->classModel->getInsertID();

        // ===============================
        // AUTO CREATE PAYMENT RULES
        // ===============================
        $categories = $this->paymentCategoryModel
            ->where('user_id', $userId)
            ->findAll();

        foreach ($categories as $category) {

            // CLASS RULE
            $this->paymentCategoryClassRuleModel->insert([
                'category_id'  => $category['id'],
                'class_id'     => $classId,
                'amount'       => $category['default_amount'],
                'is_mandatory' => 1,
                'user_id'      => $userId
            ]);

            // STUDENT RULE
            $students = $this->studentModel
                ->where('class', $classId)
                ->findAll();

            foreach ($students as $student) {
                $this->studentPaymentRuleModel->insert([
                    'student_id'   => $student['id'],
                    'category_id'  => $category['id'],
                    'amount'       => $category['default_amount'],
                    'is_mandatory' => 1,
                    'user_id'      => $userId,
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s')
                ]);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menambahkan kelas');
        }

        return redirect()->to('/classes')
            ->with('success', 'Kelas ditambahkan & tarif otomatis dibuat');
    }

    public function edit($id)
    {
        $class = $this->classModel->find($id);

        if (!$class || !$this->authorize($class['user_id'])) {
            return redirect()->to('/classes')->with('error', 'Akses ditolak');
        }

        $data = [
            'class' => $class
        ];

        // Hanya admin dapat list user
        if (session()->get('user_role') === 'admin') {
            $data['users'] = $this->userModel->findAll();
        }

        return view('classes/edit', $data);
    }

    public function update($id)
    {
        $class = $this->classModel->find($id);

        if (!$class) {
            return redirect()->to('/classes')->with('error', 'Data tidak ditemukan');
        }

        // Cek otorisasi
        if (session()->get('user_role') !== 'admin' && $class['user_id'] != session()->get('user_id')) {
            return redirect()->to('/classes')->with('error', 'Akses ditolak');
        }

        $data = [
            'name' => $this->request->getPost('name'),
        ];

        // Hanya admin yang bisa ubah pemilik kelas
        if (session()->get('user_role') === 'admin') {
            $data['user_id'] = $this->request->getPost('user_id');
        }

        $this->classModel->update($id, $data);

        return redirect()->to('/classes')->with('success', 'Kelas diperbarui');
    }

    public function delete($id)
    {
        $class = $this->classModel->find($id);

        if (!$class || !$this->authorize($class['user_id'])) {
            return redirect()->to('/classes')->with('error', 'Akses ditolak');
        }

        $this->classModel->delete($id);
        return redirect()->to('/classes')->with('success', 'Kelas dihapus');
    }
}
