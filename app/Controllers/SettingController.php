<?php

namespace App\Controllers;

use App\Models\SettingModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class SettingController extends BaseController
{
    protected $settingModel;

    public function __construct()
    {
        $this->settingModel = new SettingModel();
    }

    public function index()
    {
        $setting = $this->settingModel->where('key', 'register_status')->first();
        $status = $setting['value'] ?? 'on';

        $data = [
            'title'  => 'Setting',
            'status' => $status,
        ];

        return view('setting/setting_view', $data);
    }


    public function toggleRegister()
    {
        $status = $this->request->getPost('status'); // "on" atau "off"

        // Cari dulu, lalu update jika ada, insert jika tidak
        $existing = $this->settingModel->where('key', 'register_status')->first();

        if ($existing) {
            $this->settingModel->update($existing['id'], ['value' => $status]);
        } else {
            $this->settingModel->insert([
                'key'   => 'register_status',
                'value' => $status
            ]);
        }

        return redirect()->back()->with('message', 'Status register diperbarui!');
    }
}
