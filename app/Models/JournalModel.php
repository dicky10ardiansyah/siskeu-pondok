<?php

namespace App\Models;

use CodeIgniter\Model;

class JournalModel extends Model
{
    protected $table      = 'journals';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'date',
        'description',
        'user_id'
    ];

    // Tambahkan: Model Events
    protected $beforeFind   = ['filterByUser'];
    protected $beforeInsert = ['addUserId'];

    // -------------------------------------------------------------
    // 1. FILTER DATA SECARA OTOMATIS: hanya user terkait yang lihat
    // -------------------------------------------------------------
    protected function filterByUser(array $data)
    {
        // Jika builder tidak ada (misal query manual), skip
        if (!isset($data['builder'])) {
            return $data;
        }

        $builder = $data['builder'];

        $session = session();
        $role    = $session->get('user_role');
        $userId  = $session->get('user_id');

        // ADMIN → bisa melihat semua atau filter manual via GET ?user_id=
        if ($role === 'admin') {
            $reqUser = service('request')->getGet('user_id');
            if ($reqUser) {
                $builder->where('students.user_id', $reqUser);
            }
            return $data;
        }

        // USER BIASA → hanya lihat data miliknya
        $builder->where('students.user_id', $userId);

        return $data;
    }

    // -------------------------------------------------------------
    // 2. OTOMATIS MENAMBAHKAN user_id SAAT INSERT
    // -------------------------------------------------------------
    protected function addUserId(array $data)
    {
        $userId = session()->get('user_id');

        if (!isset($data['data']['user_id'])) {
            $data['data']['user_id'] = $userId;
        }

        return $data;
    }

    public function getWithEntries($id)
    {
        $builder = $this->db->table($this->table);
        $builder->select('journals.*, journal_entries.account_id, journal_entries.debit, journal_entries.credit');
        $builder->join('journal_entries', 'journal_entries.journal_id = journals.id', 'left');
        $builder->where('journals.id', $id);
        return $builder->get()->getResultArray();
    }
}
