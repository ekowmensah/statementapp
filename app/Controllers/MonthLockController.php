<?php
/**
 * Month Lock Controller
 * Handles month locking operations
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Models/MonthLock.php';
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Helpers/csrf.php';
require_once __DIR__ . '/../Helpers/response.php';

class MonthLockController
{
    private $monthLockModel;

    public function __construct()
    {
        $this->monthLockModel = new MonthLock();
    }

    /**
     * Show month locks page
     */
    public function index()
    {
        Auth::requirePermission('view_locks');

        $locks = $this->monthLockModel->getAll();
        
        $data = [
            'title' => 'Month Locks - Daily Statement App',
            'locks' => $locks,
            'can_manage' => Auth::can('manage_locks'),
            'can_view' => Auth::can('view_locks'),
            'csrf_token' => CSRF::getToken()
        ];

        include __DIR__ . '/../Views/locks/index.php';
    }

    /**
     * Lock a month
     */
    public function lock()
    {
        Auth::requirePermission('manage_locks');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('locks');
        }

        if (!CSRF::validateRequest()) {
            Flash::error('Invalid security token. Please try again.');
            Response::redirect('locks');
        }

        $year = (int)($_POST['year'] ?? 0);
        $month = (int)($_POST['month'] ?? 0);
        $note = trim($_POST['note'] ?? '');

        $errors = $this->monthLockModel->validateLock($year, $month);

        if (!empty($errors)) {
            Flash::error(implode(' ', $errors));
            Response::redirect('locks');
        }

        try {
            $this->monthLockModel->lock($year, $month, Auth::id(), $note);

            $monthName = date('F Y', mktime(0, 0, 0, $month, 1, $year));
            Flash::success("Successfully locked {$monthName}.");

            if (Response::expectsJson()) {
                Response::success(null, "Month locked successfully.");
            }

            Response::redirect('locks');

        } catch (Exception $e) {
            Flash::error('Failed to lock month: ' . $e->getMessage());

            if (Response::expectsJson()) {
                Response::error('Failed to lock month: ' . $e->getMessage());
            }

            Response::redirect('locks');
        }
    }

    /**
     * Unlock a month
     */
    public function unlock()
    {
        Auth::requirePermission('manage_locks');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('locks');
        }

        if (!CSRF::validateRequest()) {
            Flash::error('Invalid security token. Please try again.');
            Response::redirect('locks');
        }

        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if (!$id) {
            Flash::error('Invalid lock ID.');
            Response::redirect('locks');
        }

        // Get the lock record to find year and month
        $lock = $this->monthLockModel->find($id);
        if (!$lock) {
            Flash::error('Lock record not found.');
            Response::redirect('locks');
        }

        try {
            $this->monthLockModel->unlockById($id, Auth::id(), $reason);

            $monthName = date('F Y', mktime(0, 0, 0, $lock['month_num'], 1, $lock['year_num']));
            Flash::success("Successfully unlocked {$monthName}.");

            if (Response::expectsJson()) {
                Response::success(null, "Month unlocked successfully.");
            }

            Response::redirect('locks');

        } catch (Exception $e) {
            Flash::error('Failed to unlock month: ' . $e->getMessage());

            if (Response::expectsJson()) {
                Response::error('Failed to unlock month: ' . $e->getMessage());
            }

            Response::redirect('locks');
        }
    }
}
