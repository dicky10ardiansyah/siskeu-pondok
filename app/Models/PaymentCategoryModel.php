<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentCategoryModel extends Model
{
    protected $table = 'payment_categories';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'default_amount'];
    protected $useTimestamps = true;
}
