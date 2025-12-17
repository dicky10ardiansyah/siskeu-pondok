<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table      = 'students';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name',
        'nis',
        'class',
        'status',
        'school_year',
        'overpaid',
        'user_id',
        'address',
        'parent_name',
        'phone'
    ];

    protected $useTimestamps = true;
}
