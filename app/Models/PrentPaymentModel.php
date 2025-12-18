<?php

namespace App\Models;

use CodeIgniter\Model;

class PrentPaymentModel extends Model
{
    protected $table      = 'parent_payments';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'student_id',
        'class_id',
        'account_name',
        'amount',
        'photo'
    ];
}
