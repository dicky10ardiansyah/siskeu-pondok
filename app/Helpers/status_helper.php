<?php

use App\Models\BoardingStatusModel;
use App\Models\BoardingHistoryModel;

if (!function_exists('update_student_status')) {
    /**
     * Update status santri + simpan riwayat
     *
     * @param int $student_id
     * @param string $newStatus
     * @param string|null $reason
     * @return bool
     */
    function update_student_status(int $student_id, string $newStatus, string $reason = null): bool
    {
        $statusModel  = new BoardingStatusModel();
        $historyModel = new BoardingHistoryModel();

        $status = $statusModel->where('student_id', $student_id)->first();

        if ($status) {
            $statusModel->update($status['id'], [
                'previous_status' => $status['status'],
                'status'          => $newStatus,
            ]);
        } else {
            $statusModel->insert([
                'student_id'      => $student_id,
                'status'          => $newStatus,
                'previous_status' => null,
            ]);
        }

        // Simpan ke boarding_histories
        $historyModel->insert([
            'student_id' => $student_id,
            'status'     => $newStatus,
            'reason'     => $reason,
            'changed_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }
}
