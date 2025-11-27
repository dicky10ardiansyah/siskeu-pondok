<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    public function __construct()
    {
        helper('form');
    }

    public function register()
    {
        if (!$this->isRegisterEnabled()) {
            return redirect()->to('/unauthorized');
        }

        return view('auth/register', [
            'title' => 'Register'
        ]);
    }

    public function save_register()
    {
        if (!$this->isRegisterEnabled()) {
            return redirect()->to('/unauthorized');
        }

        if (!$this->validate([
            'name' => 'required',
            'email' => 'required|is_unique[users.email]',
            'password' => 'required',
            'password_confirmation' => 'required|matches[password]'
        ])) {
            session()->setFlashdata('errors', $this->validator->getErrors());
            return redirect()->back()->withInput();
        }

        $userModel = new \App\Models\UserModel();
        $password = $this->request->getPost('password');

        $userModel->save([
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user'
        ]);

        return redirect()->to('/login')->with('success', 'Registrasi berhasil, silahkan login.');
    }

    public function login()
    {
        return view('auth/login', [
            'title' => 'Login'
        ]);
    }

    public function login_process()
    {
        if (!$this->validate([
            'login' => 'required',
            'password' => 'required'
        ])) {
            session()->setFlashdata('errors', $this->validator->getErrors());
            return redirect()->back()->withInput();
        }

        $login = $this->request->getPost('login');
        $password = $this->request->getPost('password');

        $userModel = new UserModel();

        $user = $userModel
            ->where('LOWER(email)', strtolower($login))
            ->orWhere("BINARY name = " . $userModel->db->escape($login), null, false)
            ->first();

        if ($user && password_verify($password, $user['password'])) {
            session()->set([
                'user_id' => $user['id'],
                'user_name' => $user['name'],
                'user_email' => $user['email'],
                'user_role' => $user['role'],
                'isLoggedIn' => true
            ]);
            return redirect()->to('/home');
        } else {
            session()->setFlashdata('login_error', 'Email/Name atau password salah.');
            return redirect()->back()->withInput();
        }
    }

    public function profile()
    {
        $data = [
            'title' => 'Profile'
        ];

        return view('auth/profile', $data);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }

    private function isRegisterEnabled()
    {
        $settingModel = new \App\Models\SettingModel();
        $setting = $settingModel->where('key', 'register_status')->first();
        $status = $setting['value'] ?? 'on';
        return $status === 'on';
    }

    public function unauthorized()
    {
        return view('errors/unauthorized', [
            'title' => '403 - Unauthorized'
        ]);
    }
}
