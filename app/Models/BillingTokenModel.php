<?php

namespace App\Models;

use CodeIgniter\Model;

class BillingTokenModel extends Model
{
    protected $table      = 'billing_tokens';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'student_id',
        'token',
        'expired_at'
    ];
}
