<?php

namespace App\Models;

use CodeIgniter\Model;

class BillPaymentModel extends Model
{
    protected $table = 'bill_payments';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'bill_id',
        'payment_id',
        'amount'
    ];
    protected $useTimestamps = true;
}
