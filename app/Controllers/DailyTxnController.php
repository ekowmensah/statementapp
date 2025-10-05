<?php
/**
 * Daily Transaction Controller
 * Handles daily transaction CRUD operations and preview functionality
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Models/DailyTxn.php';
require_once __DIR__ . '/../Models/Rate.php';
require_once __DIR__ . '/../Models/MonthLock.php';
require_once __DIR__ . '/../Models/Company.php';
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Helpers/csrf.php';
require_once __DIR__ . '/../Helpers/validate.php';
require_once __DIR__ . '/../Helpers/response.php';
require_once __DIR__ . '/../Helpers/money.php';
require_once __DIR__ . '/../Helpers/rate_picker.php';

class DailyTxnController
{
    private $dailyTxnModel;
    private $rateModel;
    private $monthLockModel;
    private $companyModel;

    public function __construct()
    {
        $this->dailyTxnModel = new DailyTxn();
        $this->rateModel = new Rate();
        $this->monthLockModel = new MonthLock();
        $this->companyModel = new Company();
    }

    /**
     * List all daily transactions with enhanced filtering and pagination
     */
    public function index()
    {
        Auth::requirePermission('view_daily');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        $offset = ($page - 1) * $perPage;

        // Get filter parameters
        $filterType = $_GET['filter'] ?? 'month';
        $month = $_GET['month'] ?? date('n');
        $year = $_GET['year'] ?? date('Y');
        $search = trim($_GET['search'] ?? '');
        $companyId = $_GET['company_id'] ?? '';

        // Build date range based on filter type
        $dateRange = $this->buildDateRange($filterType, $year, $month);
        
        // Get transactions with enhanced filtering
        $result = $this->getFilteredTransactions($dateRange, $search, $page, $perPage, $companyId);
        $transactions = $result['transactions'];
        $totalCount = $result['total_count'];

        // Calculate pagination info
        $totalPages = ceil($totalCount / $perPage);
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $totalCount,
            'per_page' => $perPage,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => $page > 1 ? $page - 1 : null,
            'next_page' => $page < $totalPages ? $page + 1 : null
        ];

        // Check if any month in the range is locked
        $isLocked = $this->checkLockStatus($dateRange);
        $lockInfo = $isLocked ? $this->monthLockModel->getLockInfo($year, $month) : null;

        // Calculate totals for the filtered period
        $totals = $this->calculateFilteredTotals($dateRange, $search, $companyId);
        
        // Get available year range from database
        $yearRange = $this->getAvailableYearRange();
        
        // Get companies for filter dropdown
        $companies = $this->companyModel->getActive();

        $data = [
            'title' => 'Daily Transactions - Daily Statement App',
            'transactions' => $transactions,
            'pagination' => $pagination,
            'totals' => $totals,
            'total_pages' => $pagination['total_pages'],
            'can_create' => Auth::can('create_daily'),
            'can_edit' => Auth::can('edit_daily'),
            'can_delete' => Auth::can('delete_daily'),
            'page' => $page,
            'per_page' => $perPage,
            'filter_type' => $filterType,
            'selected_month' => $month,
            'selected_year' => $year,
            'year_range' => $yearRange,
            'search' => $search,
            'company_id' => $companyId,
            'companies' => $companies,
            'date_range' => $dateRange,
            'is_locked' => $isLocked,
            'lock_info' => $lockInfo
        ];

        include __DIR__ . '/../Views/daily/index.php';
    }

    /**
     * Build date range based on filter type
     */
    private function buildDateRange($filterType, $year, $month)
    {
        $today = date('Y-m-d');
        
        switch ($filterType) {
            case 'day':
                return [
                    'start' => $today,
                    'end' => $today,
                    'label' => 'Today (' . date('M j, Y') . ')'
                ];
                
            case 'week':
                $startOfWeek = date('Y-m-d', strtotime('monday this week'));
                $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
                return [
                    'start' => $startOfWeek,
                    'end' => $endOfWeek,
                    'label' => 'This Week (' . date('M j', strtotime($startOfWeek)) . ' - ' . date('M j, Y', strtotime($endOfWeek)) . ')'
                ];
                
            case 'year':
                return [
                    'start' => $year . '-01-01',
                    'end' => $year . '-12-31',
                    'label' => 'Year ' . $year
                ];
                
            case 'month':
            default:
                $startOfMonth = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
                $endOfMonth = date('Y-m-t', strtotime($startOfMonth));
                return [
                    'start' => $startOfMonth,
                    'end' => $endOfMonth,
                    'label' => date('F Y', strtotime($startOfMonth))
                ];
        }
    }

    /**
     * Get filtered transactions with pagination
     */
    private function getFilteredTransactions($dateRange, $search, $page, $perPage, $companyId = '')
    {
        $db = Database::getInstance();
        $offset = ($page - 1) * $perPage;
        
        $whereConditions = [];
        $params = [];
        
        // Date range filter
        $whereConditions[] = "txn_date BETWEEN ? AND ?";
        $params[] = $dateRange['start'];
        $params[] = $dateRange['end'];
        
        // Company filter
        if ($companyId) {
            $whereConditions[] = "company_id = ?";
            $params[] = $companyId;
        }
        
        // Search filter
        if ($search) {
            $whereConditions[] = "(txn_date LIKE ? OR note LIKE ? OR company_name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM v_daily_txn WHERE {$whereClause}";
        $countResult = $db->fetch($countSql, $params);
        $totalCount = $countResult['total'];
        
        // Get transactions
        $sql = "SELECT * FROM v_daily_txn WHERE {$whereClause} ORDER BY txn_date DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $transactions = $db->fetchAll($sql, $params);
        
        return [
            'transactions' => $transactions,
            'total_count' => $totalCount
        ];
    }

    /**
     * Calculate totals for filtered period
     */
    private function calculateFilteredTotals($dateRange, $search, $companyId = '')
    {
        $db = Database::getInstance();
        
        $whereConditions = [];
        $params = [];
        
        // Date range filter
        $whereConditions[] = "txn_date BETWEEN ? AND ?";
        $params[] = $dateRange['start'];
        $params[] = $dateRange['end'];
        
        // Company filter
        if ($companyId) {
            $whereConditions[] = "company_id = ?";
            $params[] = $companyId;
        }
        
        // Search filter
        if ($search) {
            $whereConditions[] = "(txn_date LIKE ? OR note LIKE ? OR company_name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    COUNT(*) as days_count,
                    SUM(ca) as total_ca,
                    SUM(ag1) as total_ag1,
                    SUM(av1) as total_av1,
                    SUM(ag2) as total_ag2,
                    SUM(av2) as total_av2,
                    SUM(ga) as total_ga,
                    SUM(re) as total_re,
                    SUM(je) as total_je,
                    SUM(fi) as total_fi,
                    AVG(rate_ag1) as avg_ag1_rate,
                    AVG(rate_ag2) as avg_ag2_rate
                FROM v_daily_txn 
                WHERE {$whereClause}";
        
        return $db->fetch($sql, $params);
    }

    /**
     * Check if any date in range is locked
     */
    private function checkLockStatus($dateRange)
    {
        $startDate = new DateTime($dateRange['start']);
        $endDate = new DateTime($dateRange['end']);
        
        while ($startDate <= $endDate) {
            $year = $startDate->format('Y');
            $month = $startDate->format('n');
            
            if ($this->monthLockModel->isLocked($year, $month)) {
                return true;
            }
            
            $startDate->modify('first day of next month');
        }
        
        return false;
    }

    /**
     * Show create transaction form
     */
    public function create()
    {
        Auth::requirePermission('create_daily');

        $data = [
            'title' => 'Create Daily Transaction - Daily Statement App',
            'transaction' => [
                'txn_date' => $_GET['date'] ?? '', // Don't auto-fill current date
                'ca' => '0.00',
                'ga' => '0.00',
                'je' => '0.00',
                'company_id' => '',
                'note' => ''
            ],
            'companies' => $this->companyModel->getActive(),
            'csrf_token' => CSRF::getToken(),
            'is_edit' => false
        ];

        include __DIR__ . '/../Views/daily/form.php';
    }

    /**
     * Store new transaction
     */
    public function store()
    {
        Auth::requirePermission('create_daily');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('daily');
        }

        // Validate CSRF token
        if (!CSRF::validateRequest()) {
            Flash::error('Invalid security token. Please try again.');
            Response::redirect('daily/create');
        }

        $data = [
            'txn_date' => trim($_POST['txn_date'] ?? ''),
            'ca' => trim($_POST['ca'] ?? '0'),
            'ga' => trim($_POST['ga'] ?? '0'),
            'je' => trim($_POST['je'] ?? '0'),
            'rate_ag1' => trim($_POST['rate_ag1'] ?? '21'),
            'rate_ag2' => trim($_POST['rate_ag2'] ?? '4'),
            'company_id' => trim($_POST['company_id'] ?? ''),
            'note' => trim($_POST['note'] ?? '')
        ];

        // Validate input
        $validator = $this->dailyTxnModel->validate($data);

        // Check if month is locked
        if ($data['txn_date']) {
            $validator->custom('txn_date', function($value) {
                return !$this->monthLockModel->isDateLocked($value);
            }, 'Cannot create transaction - month is locked.');
        }

        if ($validator->fails()) {
            if (Response::expectsJson()) {
                Response::validationError($validator->errors());
            }

            Flash::error('Please correct the errors below.');
            $_SESSION['old_input'] = $data;
            $_SESSION['validation_errors'] = $validator->errors();
            Response::redirect('daily/create');
        }

        try {
            // Parse money values
            $data['ca'] = Money::parse($data['ca']);
            $data['ga'] = Money::parse($data['ga']);
            $data['je'] = Money::parse($data['je']);
            
            // Convert percentage rates to decimals
            $data['rate_ag1'] = floatval($data['rate_ag1']) / 100;
            $data['rate_ag2'] = floatval($data['rate_ag2']) / 100;
            
            $data['created_by'] = Auth::id();

            $id = $this->dailyTxnModel->create($data);

            if (Response::expectsJson()) {
                $transaction = $this->dailyTxnModel->find($id);
                Response::success($transaction, 'Transaction created successfully.');
            }

            Flash::success('Daily transaction created successfully.');
            Response::redirect('daily');

        } catch (Exception $e) {
            if (Response::expectsJson()) {
                Response::error('Failed to create transaction: ' . $e->getMessage());
            }

            Flash::error('Failed to create transaction. Please try again.');
            Response::redirect('daily/create');
        }
    }

    /**
     * Show edit transaction form
     */
    public function edit()
    {
        Auth::requirePermission('edit_daily');

        $id = $_GET['id'] ?? null;
        if (!$id) {
            Flash::error('Transaction not found.');
            Response::redirect('daily');
        }

        $transaction = $this->dailyTxnModel->find($id);
        if (!$transaction) {
            Flash::error('Transaction not found.');
            Response::redirect('daily');
        }

        // Check if month is locked
        if ($this->monthLockModel->isDateLocked($transaction['txn_date'])) {
            Flash::error('Cannot edit transaction - month is locked.');
            Response::redirect('daily');
        }

        $data = [
            'title' => 'Edit Daily Transaction - Daily Statement App',
            'transaction' => $transaction,
            'companies' => $this->companyModel->getActive(),
            'csrf_token' => CSRF::getToken(),
            'is_edit' => true
        ];

        include __DIR__ . '/../Views/daily/form.php';
    }

    /**
     * Update transaction
     */
    public function update()
    {
        Auth::requirePermission('edit_daily');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('daily');
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            Flash::error('Transaction not found.');
            Response::redirect('daily');
        }

        // Validate CSRF token
        if (!CSRF::validateRequest()) {
            Flash::error('Invalid security token. Please try again.');
            Response::redirect("daily/edit?id={$id}");
        }

        $transaction = $this->dailyTxnModel->find($id);
        if (!$transaction) {
            Flash::error('Transaction not found.');
            Response::redirect('daily');
        }

        $data = [
            'txn_date' => trim($_POST['txn_date'] ?? ''),
            'ca' => trim($_POST['ca'] ?? '0'),
            'ga' => trim($_POST['ga'] ?? '0'),
            'je' => trim($_POST['je'] ?? '0'),
            'rate_ag1' => trim($_POST['rate_ag1'] ?? '21'),
            'rate_ag2' => trim($_POST['rate_ag2'] ?? '4'),
            'company_id' => trim($_POST['company_id'] ?? ''),
            'note' => trim($_POST['note'] ?? '')
        ];

        // Validate input
        $validator = $this->dailyTxnModel->validate($data, $id);

        // Check if month is locked
        if ($data['txn_date']) {
            $validator->custom('txn_date', function($value) {
                return !$this->monthLockModel->isDateLocked($value);
            }, 'Cannot update transaction - month is locked.');
        }

        if ($validator->fails()) {
            if (Response::expectsJson()) {
                Response::validationError($validator->errors());
            }

            Flash::error('Please correct the errors below.');
            $_SESSION['old_input'] = $data;
            $_SESSION['validation_errors'] = $validator->errors();
            Response::redirect("daily/edit?id={$id}");
        }

        try {
            // Parse money values
            $data['ca'] = Money::parse($data['ca']);
            $data['ga'] = Money::parse($data['ga']);
            $data['je'] = Money::parse($data['je']);
            
            // Convert percentage rates to decimals
            $data['rate_ag1'] = floatval($data['rate_ag1']) / 100;
            $data['rate_ag2'] = floatval($data['rate_ag2']) / 100;
            
            $data['updated_by'] = Auth::id();

            $this->dailyTxnModel->update($id, $data);

            if (Response::expectsJson()) {
                $transaction = $this->dailyTxnModel->find($id);
                Response::success($transaction, 'Transaction updated successfully.');
            }

            Flash::success('Daily transaction updated successfully.');
            Response::redirect('daily');

        } catch (Exception $e) {
            if (Response::expectsJson()) {
                Response::error('Failed to update transaction: ' . $e->getMessage());
            }

            Flash::error('Failed to update transaction. Please try again.');
            Response::redirect("daily/edit?id={$id}");
        }
    }

    /**
     * Show transaction details
     */
    public function show()
    {
        Auth::requirePermission('view_daily');

        $id = $_GET['id'] ?? null;
        if (!$id) {
            if (Response::expectsJson()) {
                Response::error('Transaction not found.', null, 404);
            }
            Flash::error('Transaction not found.');
            Response::redirect('daily');
        }

        // Get transaction with computed values
        $transaction = $this->dailyTxnModel->find($id);
        if (!$transaction) {
            if (Response::expectsJson()) {
                Response::error('Transaction not found.', null, 404);
            }
            Flash::error('Transaction not found.');
            Response::redirect('daily');
        }

        // Get computed values from view
        $computedTxn = $this->dailyTxnModel->getAllComputed();
        $computedTxn = array_filter($computedTxn, function($txn) use ($id) {
            return $txn['id'] == $id;
        });
        $computedTxn = reset($computedTxn);

        if (Response::expectsJson()) {
            Response::success($computedTxn);
        }

        $data = [
            'title' => 'Transaction Details - Daily Statement App',
            'transaction' => $computedTxn ?: $transaction,
            'can_edit' => Auth::can('edit_daily') && !$this->monthLockModel->isDateLocked($transaction['txn_date']),
            'can_delete' => Auth::can('delete_daily') && !$this->monthLockModel->isDateLocked($transaction['txn_date'])
        ];

        include __DIR__ . '/../Views/daily/show.php';
    }

    /**
     * Delete transaction
     */
    public function delete()
    {
        Auth::requirePermission('delete_daily');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('daily');
        }

        // Validate CSRF token
        if (!CSRF::validateRequest()) {
            Flash::error('Invalid security token. Please try again.');
            Response::redirect('daily');
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            Flash::error('Transaction not found.');
            Response::redirect('daily');
        }

        $transaction = $this->dailyTxnModel->find($id);
        if (!$transaction) {
            Flash::error('Transaction not found.');
            Response::redirect('daily');
        }

        // Check if month is locked
        if ($this->monthLockModel->isDateLocked($transaction['txn_date'])) {
            Flash::error('Cannot delete transaction - month is locked.');
            Response::redirect('daily');
        }

        try {
            $this->dailyTxnModel->delete($id);

            if (Response::expectsJson()) {
                Response::success(null, 'Transaction deleted successfully.');
            }

            Flash::success('Daily transaction deleted successfully.');
            Response::redirect('daily');

        } catch (Exception $e) {
            if (Response::expectsJson()) {
                Response::error('Failed to delete transaction: ' . $e->getMessage());
            }

            Flash::error('Failed to delete transaction. Please try again.');
            Response::redirect('daily');
        }
    }

    /**
     * Preview computed values (API endpoint)
     */
    public function preview()
    {
        Auth::requirePermission('view_daily');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Method not allowed.', null, 405);
        }

        // Validate CSRF token for API requests
        if (!CSRF::validateRequest()) {
            Response::error('Invalid security token.', null, 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $date = $input['date'] ?? '';
        $ca = $input['ca'] ?? 0;
        $ga = $input['ga'] ?? 0;
        $je = $input['je'] ?? 0;
        $rateAg1 = $input['rate_ag1'] ?? 0.21;
        $rateAg2 = $input['rate_ag2'] ?? 0.04;

        // Validate inputs
        if (empty($date)) {
            Response::error('Date is required.');
        }

        if (!DateTime::createFromFormat('Y-m-d', $date)) {
            Response::error('Invalid date format.');
        }

        try {
            // Calculate values directly with provided rates
            $ag1 = $ca * $rateAg1;
            $av1 = $ca - $ag1;
            $ag2 = $av1 * $rateAg2;
            $av2 = $av1 - $ag2;
            $re = $av2 - $ga;
            $fi = $re - $je;
            
            $computed = [
                'ca' => $ca,
                'ag1' => $ag1,
                'av1' => $av1,
                'ag2' => $ag2,
                'av2' => $av2,
                'ga' => $ga,
                're' => $re,
                'je' => $je,
                'fi' => $fi,
                'rate_ag1' => $rateAg1,
                'rate_ag2' => $rateAg2
            ];
            
            Response::success($computed);

        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    /**
     * Bulk operations (for future implementation)
     */
    public function bulk()
    {
        Auth::requirePermission('edit_daily');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('daily');
        }

        $action = $_POST['action'] ?? '';
        $ids = $_POST['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            Flash::error('No transactions selected.');
            Response::redirect('daily');
        }

        switch ($action) {
            case 'delete':
                return $this->bulkDelete($ids);
            case 'export':
                return $this->bulkExport($ids);
            default:
                Flash::error('Invalid action.');
                Response::redirect('daily');
        }
    }

    /**
     * Bulk delete transactions
     */
    private function bulkDelete($ids)
    {
        Auth::requirePermission('delete_daily');

        $deleted = 0;
        $errors = [];

        foreach ($ids as $id) {
            try {
                $transaction = $this->dailyTxnModel->find($id);
                if (!$transaction) {
                    continue;
                }

                if ($this->monthLockModel->isDateLocked($transaction['txn_date'])) {
                    $errors[] = "Transaction {$transaction['txn_date']} is in a locked month";
                    continue;
                }

                $this->dailyTxnModel->delete($id);
                $deleted++;

            } catch (Exception $e) {
                $errors[] = "Failed to delete transaction ID {$id}";
            }
        }

        if ($deleted > 0) {
            Flash::success("Successfully deleted {$deleted} transaction(s).");
        }

        if (!empty($errors)) {
            Flash::warning('Some transactions could not be deleted: ' . implode(', ', $errors));
        }

        Response::redirect('/daily');
    }

    /**
     * Get transactions for date range (API)
     */
    public function getByDateRange()
    {
        Auth::requirePermission('view_daily');

        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        if (empty($startDate) || empty($endDate)) {
            Response::error('Start date and end date are required.');
        }

        $transactions = $this->dailyTxnModel->getByDateRangeComputed($startDate, $endDate);

        Response::success($transactions);
    }

    /**
     * Get available year range from database
     */
    private function getAvailableYearRange()
    {
        $db = Database::getInstance();
        
        // Query directly from daily_txn table to avoid issues with view joins
        $result = $db->fetch(
            "SELECT 
                MIN(YEAR(txn_date)) as min_year,
                MAX(YEAR(txn_date)) as max_year
             FROM daily_txn 
             WHERE txn_date IS NOT NULL"
        );
        
        // If no data exists, provide a reasonable range
        if (empty($result['min_year']) || empty($result['max_year'])) {
            $minYear = date('Y') - 2; // 2 years back
            $maxYear = date('Y') + 1; // 1 year forward
        } else {
            $minYear = $result['min_year'];
            $maxYear = $result['max_year'];
            
            // Extend range to include at least current year
            $minYear = min($minYear, date('Y'));
            $maxYear = max($maxYear, date('Y'));
        }
        
        return [
            'min' => (int)$minYear,
            'max' => (int)$maxYear
        ];
    }
}
