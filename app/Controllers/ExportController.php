<?php
/**
 * Export Controller
 * Handles CSV and PDF exports
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Models/DailyTxn.php';
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Helpers/response.php';
require_once __DIR__ . '/../Helpers/money.php';

class ExportController
{
    private $dailyTxnModel;

    public function __construct()
    {
        $this->dailyTxnModel = new DailyTxn();
    }

    /**
     * Export to CSV
     */
    public function csv()
    {
        Auth::requirePermission('export_csv');

        $month = $_GET['month'] ?? date('n');
        $year = $_GET['year'] ?? date('Y');
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        // Determine date range
        if ($startDate && $endDate) {
            $transactions = $this->dailyTxnModel->getForExport($startDate, $endDate);
            $filename = "statement_{$startDate}_to_{$endDate}.csv";
        } else {
            $startDate = "{$year}-{$month}-01";
            $endDate = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
            $transactions = $this->dailyTxnModel->getForExport($startDate, $endDate);
            $monthName = date('F_Y', mktime(0, 0, 0, $month, 1, $year));
            $filename = "statement_{$monthName}.csv";
        }

        if (empty($transactions)) {
            Flash::error('No data available for export.');
            Response::redirect('/statement');
        }

        // Prepare CSV data
        $headers = array_keys($transactions[0]);
        $data = [];

        // Add headers
        $data[] = $headers;

        // Add data rows
        foreach ($transactions as $transaction) {
            $row = [];
            foreach ($transaction as $key => $value) {
                // Format money values
                if (in_array($key, ['CA', 'AG1', 'AV1', 'AG2', 'AV2', 'GA', 'RE', 'JE', 'FI'])) {
                    $row[] = Money::formatForInput($value);
                } else {
                    $row[] = $value;
                }
            }
            $data[] = $row;
        }

        Response::csv($data, $filename);
    }

    /**
     * Export to PDF
     */
    public function pdf()
    {
        Auth::requirePermission('export_pdf');

        $month = $_GET['month'] ?? date('n');
        $year = $_GET['year'] ?? date('Y');
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        // Determine date range
        if ($startDate && $endDate) {
            $transactions = $this->dailyTxnModel->getByDateRangeComputed($startDate, $endDate);
            $title = "Statement Report: {$startDate} to {$endDate}";
            $filename = "statement_{$startDate}_to_{$endDate}.pdf";
        } else {
            $transactions = $this->dailyTxnModel->getByMonthComputed($year, $month);
            $monthlyTotals = $this->dailyTxnModel->getMonthlyTotals($year, $month);
            $monthName = date('F Y', mktime(0, 0, 0, $month, 1, $year));
            $title = "Statement Report: {$monthName}";
            $filename = "statement_" . date('F_Y', mktime(0, 0, 0, $month, 1, $year)) . ".pdf";
        }

        if (empty($transactions)) {
            Flash::error('No data available for export.');
            Response::redirect('/statement');
        }

        // Generate PDF content
        $html = $this->generatePdfHtml($transactions, $title, $monthlyTotals ?? null);

        // For now, we'll use a simple HTML to PDF approach
        // In production, you might want to use a library like TCPDF or DomPDF
        $this->outputPdf($html, $filename);
    }

    /**
     * Generate HTML for PDF
     */
    private function generatePdfHtml($transactions, $title, $totals = null)
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title><?= htmlspecialchars($title) ?></title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h1 { margin: 0; color: #333; }
                .header p { margin: 5px 0; color: #666; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .text-left { text-align: left; }
                .totals { background-color: #e9ecef; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Daily Statement App</h1>
                <p><?= htmlspecialchars($title) ?></p>
                <p>Generated on <?= date('F j, Y \a\t g:i A') ?></p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="text-left">Date</th>
                        <th>CA</th>
                        <th>AG1</th>
                        <th>AV1</th>
                        <th>AG2</th>
                        <th>AV2</th>
                        <th>GA</th>
                        <th>RE</th>
                        <th>JE</th>
                        <th>FI</th>
                        <th class="text-left">Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): ?>
                    <tr>
                        <td class="text-left"><?= htmlspecialchars($txn['txn_date']) ?></td>
                        <td><?= Money::format($txn['ca']) ?></td>
                        <td><?= Money::format($txn['ag1']) ?></td>
                        <td><?= Money::format($txn['av1']) ?></td>
                        <td><?= Money::format($txn['ag2']) ?></td>
                        <td><?= Money::format($txn['av2']) ?></td>
                        <td><?= Money::format($txn['ga']) ?></td>
                        <td><?= Money::format($txn['re']) ?></td>
                        <td><?= Money::format($txn['je']) ?></td>
                        <td><?= Money::format($txn['fi']) ?></td>
                        <td class="text-left"><?= htmlspecialchars($txn['note'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if ($totals): ?>
                    <tr class="totals">
                        <td class="text-left">TOTALS</td>
                        <td><?= Money::format($totals['total_ca']) ?></td>
                        <td><?= Money::format($totals['total_ag1']) ?></td>
                        <td><?= Money::format($totals['total_av1']) ?></td>
                        <td><?= Money::format($totals['total_ag2']) ?></td>
                        <td><?= Money::format($totals['total_av2']) ?></td>
                        <td><?= Money::format($totals['total_ga']) ?></td>
                        <td><?= Money::format($totals['total_re']) ?></td>
                        <td><?= Money::format($totals['total_je']) ?></td>
                        <td><?= Money::format($totals['total_fi']) ?></td>
                        <td class="text-left"><?= $totals['days_count'] ?> days</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="footer">
                <p>Daily Statement App - Generated by <?= htmlspecialchars(Auth::user()['name']) ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Output PDF (simplified version)
     */
    private function outputPdf($html, $filename)
    {
        // For a production system, you would use a proper PDF library here
        // This is a simplified version that outputs HTML with PDF headers
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        // In a real implementation, you would convert HTML to PDF here
        // For now, we'll just output the HTML with PDF headers
        // You could integrate libraries like:
        // - TCPDF: https://tcpdf.org/
        // - DomPDF: https://github.com/dompdf/dompdf
        // - wkhtmltopdf: https://wkhtmltopdf.org/
        
        echo $html;
        exit;
    }
}
