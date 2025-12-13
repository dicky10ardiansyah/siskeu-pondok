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
}
