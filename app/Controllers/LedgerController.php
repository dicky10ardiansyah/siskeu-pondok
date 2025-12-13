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

    protected function getLedgerData($start = null, $end = null, $selectedUser = null)
    {
        $userId   = session()->get('user_id');
        $userRole = session()->get('user_role'); // admin | user

        // Tentukan user transaksi
        if ($userRole === 'admin' && $selectedUser) {
            $journalUserId = $selectedUser;
        } elseif ($userRole === 'admin') {
            $journalUserId = null; // semua user
        } else {
            $journalUserId = $userId; // user biasa hanya datanya sendiri
        }

        // Ambil SEMUA akun (akun adalah milik sistem)
        $accounts = $this->accountsModel->findAll();

        $ledger = [];

        foreach ($accounts as $account) {

            $jeQuery = $this->journalEntriesModel
                ->select('journal_entries.*, journals.date, journals.description')
                ->join('journals', 'journals.id = journal_entries.journal_id')
                ->where('journal_entries.account_id', $account['id']);

            // Filter pemilik transaksi (BEST PRACTICE)
            if ($journalUserId !== null) {
                $jeQuery->where('journals.user_id', $journalUserId);
            }

            // Filter tanggal
            if ($start) {
                $jeQuery->where('journals.date >=', $start);
            }
            if ($end) {
                $jeQuery->where('journals.date <=', $end);
            }

            $journalEntries = $jeQuery->orderBy('journals.date', 'ASC')->findAll();

            $entries = [];
            $balance = $totalDebit = $totalCredit = 0;

            foreach ($journalEntries as $je) {
                $balance += $je['debit'] - $je['credit'];

                $entries[] = [
                    'date'        => $je['date'],
                    'description' => $je['description'] ?? 'Jurnal',
                    'debit'       => (float) $je['debit'],
                    'credit'      => (float) $je['credit'],
                    'balance'     => $balance,
                ];

                $totalDebit  += $je['debit'];
                $totalCredit += $je['credit'];
            }

            // Akun tetap ditampilkan walau tanpa transaksi
            $ledger[$account['name']] = [
                'entries'      => $entries,
                'totalDebit'   => $totalDebit,
                'totalCredit'  => $totalCredit,
                'totalBalance' => $balance,
            ];
        }

        return $ledger;
    }

    public function index()
    {
        $start  = $this->request->getGet('start');
        $end    = $this->request->getGet('end');
        $selectedUser = $this->request->getGet('user_id');

        $ledger = $this->getLedgerData($start, $end, $selectedUser);

        $users = [];
        if (session()->get('user_role') === 'admin') {
            $users = (new \App\Models\UserModel())->findAll();
        }

        return view('ledger/index', [
            'ledger'        => $ledger,
            'start'         => $start,
            'end'           => $end,
            'role'          => session()->get('user_role'),
            'users'         => $users,
            'selected_user' => $selectedUser
        ]);
    }

    public function exportPDF()
    {
        $start  = $this->request->getGet('start');
        $end    = $this->request->getGet('end');
        $ledger = $this->getLedgerData($start, $end);

        $html = view('ledger/pdf', [
            'ledger' => $ledger,
            'start'  => $start,
            'end'    => $end
        ]);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('ledger.pdf', ['Attachment' => false]);
        exit;
    }

    public function exportExcel()
    {
        $start  = $this->request->getGet('start');
        $end    = $this->request->getGet('end');
        $ledger = $this->getLedgerData($start, $end);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $row         = 1;

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
            $sheet->setCellValue('F' . $row, $data['totalBalance']);
            $row += 2;
        }

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="ledger.xlsx"');
        $writer->save('php://output');
        exit;
    }
}
