<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentCategoryClassRuleModel extends Model
{
    protected $table      = 'payment_category_class_rules';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'class_id',
        'category_id',
        'amount',
        'user_id'
    ];

    // Hook otomatis
    protected $beforeInsert = ['addUserId'];
    protected $beforeUpdate = ['syncUserIdWithCategory'];

    /**
     * Tambahkan user_id otomatis saat insert
     */
    protected function addUserId(array $data)
    {
        $session = session();

        if (isset($data['data']['category_id']) && !isset($data['data']['user_id'])) {
            $categoryId = $data['data']['category_id'];
            $categoryModel = new PaymentCategoryModel();
            $category = $categoryModel->find($categoryId);

            if ($category && isset($category['user_id'])) {
                $data['data']['user_id'] = $category['user_id'];
            } else {
                $data['data']['user_id'] = $session->get('user_id');
            }
        } elseif (!isset($data['data']['user_id'])) {
            $data['data']['user_id'] = $session->get('user_id');
        }

        return $data;
    }

    /**
     * Update user_id saat category_id diupdate (misal pindah owner)
     */
    protected function syncUserIdWithCategory(array $data)
    {
        if (isset($data['data']['category_id'])) {
            $categoryId = $data['data']['category_id'];
            $categoryModel = new PaymentCategoryModel();
            $category = $categoryModel->find($categoryId);

            if ($category && isset($category['user_id'])) {
                $data['data']['user_id'] = $category['user_id'];
            }
        }

        return $data;
    }
}
