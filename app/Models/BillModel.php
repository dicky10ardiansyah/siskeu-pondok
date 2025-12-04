<?php

namespace App\Models;

use CodeIgniter\Model;

class BillModel extends Model
{
    protected $table      = 'bills';
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

    protected $useTimestamps = false;

    public function getBillPayments($bill_id)
    {
        return $this->db->table('bill_payments')
            ->select('bill_payments.amount, payments.date')
            ->join('payments', 'payments.id = bill_payments.payment_id')
            ->where('bill_payments.bill_id', $bill_id)
            ->get()
            ->getResultArray();
    }
}
