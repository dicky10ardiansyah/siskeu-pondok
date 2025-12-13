<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassModel extends Model
{
    protected $table      = 'classes';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = ['name', 'user_id'];

    public function getWithUser()
    {
        return $this->select('classes.*, users.name as user_name')
            ->join('users', 'users.id = classes.user_id', 'left');
    }
}
