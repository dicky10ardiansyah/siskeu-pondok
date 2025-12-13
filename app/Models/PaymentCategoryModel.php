<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentCategoryModel extends Model
{
    protected $table = 'payment_categories';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'default_amount', 'billing_type', 'duration_months', 'user_id'];
    protected $useTimestamps = true;

    protected $beforeFind   = ['filterByUser'];
    protected $beforeInsert = ['addUserId'];

    // ----------------------------------------------
    // Filter per user (otomatis)
    // ----------------------------------------------
    protected function filterByUser(array $data)
    {
        if (!isset($data['builder'])) {
            return $data;
        }

        $builder = $data['builder'];

        $session = session();
        $role    = $session->get('user_role');
        $userId  = $session->get('user_id');

        // ADMIN → bebas
        if ($role === 'admin') {
            $reqUser = service('request')->getGet('user_id');
            if ($reqUser) {
                $builder->where('payment_categories.user_id', $reqUser);
            }
            return $data;
        }

        // USER → hanya data miliknya
        $builder->where('payment_categories.user_id', $userId);

        return $data;
    }

    // ----------------------------------------------
    // Auto tambah user_id saat insert
    // ----------------------------------------------
    protected function addUserId(array $data)
    {
        $userId = session()->get('user_id');

        if (!isset($data['data']['user_id'])) {
            $data['data']['user_id'] = $userId;
        }

        return $data;
    }
}
