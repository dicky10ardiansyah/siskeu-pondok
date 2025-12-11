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
        'amount'
    ];
}
