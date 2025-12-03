<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';

    // Sesuaikan allowedFields dengan kolom baru
    protected $allowedFields = [
        'student_id',
        'debit_account_id',
        'credit_account_id',
        'journal_id',
        'total_amount',
        'date',
        'method',
        'reference'
    ];

    protected $useTimestamps = true;
}
