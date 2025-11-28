<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'account_id',
        'total_amount',
        'date',
        'method',
        'reference'
    ];
    protected $useTimestamps = true;
}
