<?php

namespace App\Controllers;

use App\Models\SubscribeModel;

class Home extends BaseController
{
    public function index(): string
    {
        $data = [
            'title' => 'Beranda' // atau judul lain sesuai kebutuhan
        ];

        return view('home/index', $data);
    }

    public function subscribe()
    {
        $validation = \Config\Services::validation();

        $validation->setRules([
            'email' => 'required|valid_email|is_unique[subscribes.email]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->to('/home')->with('error', $validation->getErrors());
        }

        $model = new SubscribeModel();
        $model->insert([
            'email' => $this->request->getPost('email'),
        ]);

        return redirect()->to('/home')->with('success', 'Berhasil berlangganan!');
    }

    public function develop()
    {
        $data = [
            'title' => 'develop' // atau judul lain sesuai kebutuhan
        ];

        return view('home/develop', $data);
    }

    public function about()
    {
        $data = [
            'title' => 'About'
        ];

        return view('home/about', $data);
    }
}
