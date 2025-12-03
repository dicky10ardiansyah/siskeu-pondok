<?php

namespace App\Controllers;

use Dompdf\Dompdf;
use App\Models\AccountModel;
use App\Models\JournalModel;
use App\Models\TransactionModel;
use App\Models\JournalEntryModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LedgerController extends BaseController
{
    protected $accountsModel;
    protected $journalEntriesModel;
    protected $journalModel;

    public function __construct()
    {
        $this->accountsModel = new AccountModel();
        $this->journalEntriesModel = new JournalEntryModel();
        $this->journalModel = new JournalModel();
    }

    /**
     * Ambil data ledger per akun
     */
    protected function getLedgerData($start = null, $end = null)
    {
        $accounts = $this->accountsModel->findAll();
        $ledger = [];

        foreach ($accounts as $account) {
            $entries = [];

            // Ambil journal entries untuk akun ini
            $journalEntriesQuery = $this->journalEntriesModel->where('account_id', $account['id']);
            if ($start) $journalEntriesQuery->where('created_at >=', $start . ' 00:00:00');
            if ($end) $journalEntriesQuery->where('created_at <=', $end . ' 23:59:59');

            $journalEntries = $journalEntriesQuery->orderBy('created_at', 'ASC')->findAll();

            foreach ($journalEntries as $je) {
                $journalData = $this->journalModel->find($je['journal_id']);
                $entries[] = [
                    'date' => $je['created_at'],
                    'description' => $journalData ? $journalData['description'] : 'Jurnal',
                    'debit' => $je['debit'],
                    'credit' => $je['credit'],
                ];
            }

            // Urutkan berdasarkan tanggal
            usort($entries, fn($a, $b) => strtotime($a['date']) - strtotime($b['date']));

            // Hitung saldo berjalan
            $balance = 0;
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($entries as &$entry) {
                $balance += $entry['debit'] - $entry['credit'];
                $entry['balance'] = $balance;
                $totalDebit += $entry['debit'];
                $totalCredit += $entry['credit'];
            }

            // Tambahkan totalBalance di sini
            $ledger[$account['name']] = [
                'entries' => $entries,
                'totalDebit' => $totalDebit,
                'totalCredit' => $totalCredit,
                'totalBalance' => $balance, // saldo akhir akun
            ];
        }

        return $ledger;
    }

    /**
     * Halaman index ledger
     */
    public function index()
    {
        $start = $this->request->getGet('start');
        $end = $this->request->getGet('end');

        $ledger = $this->getLedgerData($start, $end);

        return view('ledger/index', [
            'ledger' => $ledger,
            'start' => $start,
            'end' => $end,
        ]);
    }

    /**
     * Export ledger PDF
     */
    public function exportPDF()
    {
        $start = $this->request->getGet('start');
        $end = $this->request->getGet('end');

        $ledger = $this->getLedgerData($start, $end);

        $html = view('ledger/pdf', [
            'ledger' => $ledger,
            'start' => $start,
            'end' => $end
        ]);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('ledger.pdf', ['Attachment' => true]);
        exit;
    }

    /**
     * Export ledger Excel
     */
    public function exportExcel()
    {
        $start = $this->request->getGet('start');
        $end = $this->request->getGet('end');

        $ledger = $this->getLedgerData($start, $end);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $row = 1;

        foreach ($ledger as $accountName => $data) {
            // Nama akun
            $sheet->setCellValue('A' . $row, $accountName);
            $sheet->mergeCells("A{$row}:F{$row}");
            $row++;

            // Header tabel
            $sheet->setCellValue('A' . $row, '#');
            $sheet->setCellValue('B' . $row, 'Tanggal');
            $sheet->setCellValue('C' . $row, 'Deskripsi');
            $sheet->setCellValue('D' . $row, 'Debit');
            $sheet->setCellValue('E' . $row, 'Kredit');
            $sheet->setCellValue('F' . $row, 'Saldo');
            $row++;

            $no = 1;
            foreach ($data['entries'] as $entry) {
                $sheet->setCellValue('A' . $row, $no++);
                $sheet->setCellValue('B' . $row, date('d M Y H:i', strtotime($entry['date'])));
                $sheet->setCellValue('C' . $row, $entry['description']);
                $sheet->setCellValue('D' . $row, $entry['debit']);
                $sheet->setCellValue('E' . $row, $entry['credit']);
                $sheet->setCellValue('F' . $row, $entry['balance']);
                $row++;
            }

            // Baris total
            $sheet->setCellValue('C' . $row, 'Total');
            $sheet->setCellValue('D' . $row, $data['totalDebit']);
            $sheet->setCellValue('E' . $row, $data['totalCredit']);
            $sheet->setCellValue('F' . $row, $data['totalBalance']); // saldo akhir
            $row += 2;
        }

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="ledger.xlsx"');
        $writer->save('php://output');
        exit;
    }
}
