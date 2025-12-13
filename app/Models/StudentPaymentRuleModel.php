<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentPaymentRuleModel extends Model
{
    protected $table = 'student_payment_rules';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'category_id', // payment category
        'amount',
        'is_mandatory',
        'created_at',
        'updated_at',
        'user_id'
    ];
    protected $useTimestamps = true;

    protected $beforeFind   = ['filterByUser'];
    protected $beforeInsert = ['addUserId'];

    // -------------------------------------------------
    // FILTER DATA PER USER (otomatis untuk setiap query)
    // -------------------------------------------------
    protected function filterByUser(array $data)
    {
        // Jika builder tidak ada (misal query manual), skip
        if (!isset($data['builder'])) {
            return $data;
        }

        $builder = $data['builder'];

        $session = session();
        $role    = $session->get('user_role');
        $userId  = $session->get('user_id');

        // ADMIN → bisa melihat semua atau filter manual via GET ?user_id=
        if ($role === 'admin') {
            $reqUser = service('request')->getGet('user_id');
            if ($reqUser) {
                $builder->where('students.user_id', $reqUser);
            }
            return $data;
        }

        // USER BIASA → hanya lihat data miliknya
        $builder->where('students.user_id', $userId);

        return $data;
    }

    // -------------------------------------------------
    // AUTO TAMBAHKAN user_id KETIKA INSERT
    // -------------------------------------------------
    protected function addUserId(array $data)
    {
        if (!isset($data['data']['user_id'])) {
            $data['data']['user_id'] = session()->get('user_id');
        }

        return $data;
    }
}
