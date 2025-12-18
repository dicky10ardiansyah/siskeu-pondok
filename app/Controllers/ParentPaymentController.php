<?php

namespace App\Controllers;

use App\Models\ClassModel;
use App\Models\StudentModel;
use App\Models\PrentPaymentModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class ParentPaymentController extends BaseController
{
    protected $paymentModel;
    protected $studentModel;
    protected $classModel;
    protected $helpers = ['form'];

    public function __construct()
    {
        $this->paymentModel = new PrentPaymentModel();
        $this->studentModel = new StudentModel();
        $this->classModel   = new ClassModel();
    }

    public function index()
    {
        $search       = $this->request->getGet('q');
        $filterStatus = $this->request->getGet('status');
        $filterClass  = $this->request->getGet('class_id');
        $perPage      = 10;

        $builder = $this->paymentModel
            ->select('
            parent_payments.*,
            students.name AS student_name,
            students.status AS student_status,
            classes.name AS class_name
        ')
            ->join('students', 'students.id = parent_payments.student_id')
            ->join('classes', 'classes.id = parent_payments.class_id');

        // FILTER STATUS
        if ($filterStatus === '0' || $filterStatus === '1') {
            $builder->where('students.status', $filterStatus);
        }

        // FILTER KELAS
        if (!empty($filterClass)) {
            $builder->where('parent_payments.class_id', $filterClass);
        }

        // SEARCH
        if (!empty($search)) {
            $builder->groupStart()
                ->like('students.name', $search)
                ->orLike('parent_payments.account_name', $search)
                ->groupEnd();
        }

        // ✅ SORTING FINAL (TIDAK LULUS → LULUS)
        $builder
            ->orderBy('classes.name', 'ASC')
            ->orderBy('students.name', 'ASC')
            ->orderBy('(CASE WHEN students.status = 0 THEN 0 ELSE 1 END)', 'ASC', false);

        $data['payments'] = $builder->paginate($perPage, 'parent_payments');
        $data['pager']    = $this->paymentModel->pager;

        $data['search']       = $search;
        $data['filterStatus'] = $filterStatus;
        $data['filterClass']  = $filterClass;
        $data['classes']      = $this->classModel->orderBy('name', 'ASC')->findAll();

        return view('parent_payments/index', $data);
    }

    public function parent()
    {
        $students = $this->studentModel
            ->select('
            students.id,
            students.name,
            students.nis,
            classes.name as class_name
        ')
            ->join('classes', 'classes.id = students.class', 'left')
            ->where('students.status !=', 1)
            ->orderBy('students.name', 'ASC')
            ->findAll();

        $classes = $this->classModel
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('parent_payments/parent', [
            'students' => $students,
            'classes'  => $classes,
        ]);
    }

    public function parentStore()
    {
        $validationRules = [
            'student_id'   => 'required',
            'class_id'     => 'required',
            'account_name' => 'required|min_length[3]',
            'amount'       => 'required',
            'photo'        => 'uploaded[photo]|max_size[photo,2048]|is_image[photo]|mime_in[photo,image/jpg,image/jpeg,image/png]',
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $amount = str_replace('.', '', $this->request->getPost('amount'));

        $data = [
            'student_id'   => $this->request->getPost('student_id'),
            'class_id'     => $this->request->getPost('class_id'),
            'account_name' => $this->request->getPost('account_name'),
            'amount'       => $amount,
        ];

        $file = $this->request->getFile('photo');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            if (!is_dir(FCPATH . 'uploads/payment_parents')) {
                mkdir(FCPATH . 'uploads/payment_parents', 0775, true);
            }
            $file->move(FCPATH . 'uploads/payment_parents', $newName);
            $data['photo'] = $newName;
        }

        $this->paymentModel->save($data);

        // Set session flashdata untuk SweetAlert2
        return redirect()->to('/parent-payments/parent')->with('success', 'Pembayaran berhasil dikirim!');
    }

    public function create()
    {
        $data['students'] = $this->studentModel
            ->select('
            students.id,
            students.name,
            students.nis,
            classes.name as class_name
        ')
            ->join('classes', 'classes.id = students.class', 'left')
            ->where('students.status !=', 1)
            ->orderBy('students.name', 'ASC')
            ->findAll();

        $data['classes'] = $this->classModel
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('parent_payments/create', $data);
    }

    public function store()
    {
        $validationRules = [
            'student_id'   => 'required',
            'class_id'     => 'required',
            'account_name' => 'required|min_length[3]',
            'amount'       => 'required',
            'photo'        => 'uploaded[photo]|max_size[photo,2048]|is_image[photo]|mime_in[photo,image/jpg,image/jpeg,image/png]',
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $amount = str_replace('.', '', $this->request->getPost('amount'));

        $data = [
            'student_id'   => $this->request->getPost('student_id'),
            'class_id'     => $this->request->getPost('class_id'),
            'account_name' => $this->request->getPost('account_name'),
            'amount'       => $amount,
        ];

        // Handle file upload
        $file = $this->request->getFile('photo');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/payment_parents', $newName);
            $data['photo'] = $newName;
        }

        $this->paymentModel->save($data);

        return redirect()->to('/parent-payments')->with('success', 'Pembayaran berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $payment = $this->paymentModel->find($id);

        if (!$payment) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Data pembayaran tidak ditemukan');
        }

        $students = $this->studentModel
            ->select('
            students.id,
            students.name,
            students.nis,
            classes.name as class_name
        ')
            ->join('classes', 'classes.id = students.class', 'left')
            ->groupStart()
            ->where('students.status !=', 1)                 // siswa aktif
            ->orWhere('students.id', $payment['student_id']) // siswa yg sedang diedit
            ->groupEnd()
            ->orderBy('students.name', 'ASC')
            ->findAll();

        $classes = $this->classModel
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('parent_payments/edit', [
            'students' => $students,
            'classes'  => $classes,
            'payment'  => $payment,
        ]);
    }

    public function update($id)
    {
        $payment = $this->paymentModel->find($id);
        if (!$payment) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Data pembayaran tidak ditemukan');
        }

        $validationRules = [
            'student_id'   => 'required',
            'class_id'     => 'required',
            'account_name' => 'required|min_length[3]',
            'amount'       => 'required',
            'photo'        => 'max_size[photo,2048]|is_image[photo]|mime_in[photo,image/jpg,image/jpeg,image/png]',
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $amount = str_replace('.', '', $this->request->getPost('amount'));

        $data = [
            'student_id'   => $this->request->getPost('student_id'),
            'class_id'     => $this->request->getPost('class_id'),
            'account_name' => $this->request->getPost('account_name'),
            'amount'       => $amount,
        ];

        // handle file
        $file = $this->request->getFile('photo');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            if (!is_dir(FCPATH . 'uploads/payment_parents')) {
                mkdir(FCPATH . 'uploads/payment_parents', 0775, true);
            }
            $file->move(FCPATH . 'uploads/payment_parents', $newName);
            $data['photo'] = $newName;

            // hapus file lama setelah file baru berhasil diupload
            if ($payment['photo'] && file_exists(FCPATH . 'uploads/payment_parents/' . $payment['photo'])) {
                unlink(FCPATH . 'uploads/payment_parents/' . $payment['photo']);
            }
        }

        $this->paymentModel->update($id, $data);

        return redirect()->to('/parent-payments')->with('success', 'Pembayaran berhasil diupdate!');
    }

    public function delete($id)
    {
        $payment = $this->paymentModel->find($id);
        if (!$payment) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Data pembayaran tidak ditemukan');
        }

        // hapus file photo jika ada
        if ($payment['photo'] && file_exists(FCPATH . 'uploads/payment_parents/' . $payment['photo'])) {
            unlink(FCPATH . 'uploads/payment_parents/' . $payment['photo']);
        }

        $this->paymentModel->delete($id);

        return redirect()->to('/parent-payments')->with('success', 'Pembayaran berhasil dihapus!');
    }
}
