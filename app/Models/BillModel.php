<?php

namespace App\Models;

use CodeIgniter\Model;

class BillModel extends Model
{
    protected $table = 'bills';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'category_id',
        'month',
        'year',
        'amount',
        'paid_amount',
        'status'
    ];
    protected $useTimestamps = true;
}
