<?php
/**
 * Rates Controller
 * Handles rate management operations
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Models/Rate.php';
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Helpers/csrf.php';
require_once __DIR__ . '/../Helpers/validate.php';
require_once __DIR__ . '/../Helpers/response.php';
require_once __DIR__ . '/../Helpers/money.php';

class RatesController
{
    private $rateModel;

    public function __construct()
    {
        $this->rateModel = new Rate();
    }

    /**
     * List all rates
     */
    public function index()
    {
        Auth::requirePermission('view_rates');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $rates = $this->rateModel->getAll($perPage, $offset);
        $totalCount = $this->rateModel->getCount();
        
        // Get current effective rate to mark it
        $currentRate = $this->rateModel->getEffectiveRate(date('Y-m-d'));
        $currentRateId = $currentRate ? $currentRate['id'] : null;
        
        // Add is_current flag to each rate
        foreach ($rates as &$rate) {
            $rate['is_current'] = ($rate['id'] == $currentRateId);
        }

        $data = [
            'title' => 'Rate Management - Daily Statement App',
            'rates' => $rates,
            'current_page' => $page,
            'total_pages' => ceil($totalCount / $perPage),
            'total_count' => $totalCount,
            'can_create' => Auth::can('create_rates'),
            'can_edit' => Auth::can('edit_rates'),
            'can_delete' => Auth::can('delete_rates')
        ];

        include __DIR__ . '/../Views/rates/index.php';
    }

    /**
     * Show create rate form
     */
    public function create()
    {
        Auth::requirePermission('create_rates');

        $data = [
            'title' => 'Create Rate - Daily Statement App',
            'rate' => [
                'effective_on' => date('Y-m-d'),
                'rate_ag1' => '0.2100',
                'rate_ag2' => '0.0400',
                'note' => ''
            ],
            'csrf_token' => CSRF::getToken(),
            'is_edit' => false
        ];

        include __DIR__ . '/../Views/rates/form.php';
    }

    /**
     * Store new rate
     */
    public function store()
    {
        Auth::requirePermission('create_rates');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('rates');
        }

        if (!CSRF::validateRequest()) {
            Flash::error('Invalid security token. Please try again.');
            Response::redirect('rates/create');
        }

        $data = [
            'effective_on' => trim($_POST['effective_on'] ?? ''),
            'rate_ag1' => trim($_POST['rate_ag1'] ?? ''),
            'rate_ag2' => trim($_POST['rate_ag2'] ?? ''),
            'note' => trim($_POST['note'] ?? '')
        ];

        $validator = $this->rateModel->validate($data);

        if ($validator->fails()) {
            Flash::error('Please correct the errors below.');
            $_SESSION['old_input'] = $data;
            $_SESSION['validation_errors'] = $validator->errors();
            Response::redirect('rates/create');
        }

        try {
            $data['created_by'] = Auth::id();
            $id = $this->rateModel->create($data);

            Flash::success('Rate created successfully.');
            Response::redirect('rates');

        } catch (Exception $e) {
            Flash::error('Failed to create rate. Please try again.');
            Response::redirect('rates/create');
        }
    }

    /**
     * Show edit rate form
     */
    public function edit()
    {
        Auth::requirePermission('edit_rates');

        $id = $_GET['id'] ?? null;
        if (!$id) {
            Flash::error('Rate not found.');
            Response::redirect('rates');
        }

        $rate = $this->rateModel->find($id);
        if (!$rate) {
            Flash::error('Rate not found.');
            Response::redirect('rates');
        }

        $data = [
            'title' => 'Edit Rate - Daily Statement App',
            'rate' => $rate,
            'csrf_token' => CSRF::getToken(),
            'is_edit' => true
        ];

        include __DIR__ . '/../Views/rates/form.php';
    }

    /**
     * Update rate
     */
    public function update()
    {
        Auth::requirePermission('edit_rates');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('rates');
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            Flash::error('Rate not found.');
            Response::redirect('rates');
        }

        if (!CSRF::validateRequest()) {
            Flash::error('Invalid security token. Please try again.');
            Response::redirect("rates/edit?id={$id}");
        }

        $rate = $this->rateModel->find($id);
        if (!$rate) {
            Flash::error('Rate not found.');
            Response::redirect('rates');
        }

        $data = [
            'effective_on' => trim($_POST['effective_on'] ?? ''),
            'rate_ag1' => trim($_POST['rate_ag1'] ?? ''),
            'rate_ag2' => trim($_POST['rate_ag2'] ?? ''),
            'note' => trim($_POST['note'] ?? '')
        ];

        $validator = $this->rateModel->validate($data, $id);

        if ($validator->fails()) {
            Flash::error('Please correct the errors below.');
            $_SESSION['old_input'] = $data;
            $_SESSION['validation_errors'] = $validator->errors();
            Response::redirect("rates/edit?id={$id}");
        }

        try {
            $this->rateModel->update($id, $data);

            Flash::success('Rate updated successfully.');
            Response::redirect('rates');

        } catch (Exception $e) {
            Flash::error('Failed to update rate. Please try again.');
            Response::redirect("rates/edit?id={$id}");
        }
    }

    /**
     * Delete rate
     */
    public function delete()
    {
        Auth::requirePermission('delete_rates');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('rates');
        }

        if (!CSRF::validateRequest()) {
            Flash::error('Invalid security token. Please try again.');
            Response::redirect('rates');
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            Flash::error('Rate not found.');
            Response::redirect('rates');
        }

        $rate = $this->rateModel->find($id);
        if (!$rate) {
            Flash::error('Rate not found.');
            Response::redirect('rates');
        }

        // Check if rate is in use
        if ($this->rateModel->isInUse($id)) {
            Flash::error('Cannot delete rate - it is being used by transactions.');
            Response::redirect('rates');
        }

        try {
            $this->rateModel->delete($id);

            Flash::success('Rate deleted successfully.');
            Response::redirect('rates');

        } catch (Exception $e) {
            Flash::error('Failed to delete rate. Please try again.');
            Response::redirect('rates');
        }
    }

    /**
     * Get effective rate for date (API)
     */
    public function getEffectiveRate()
    {
        Auth::requirePermission('view_rates');

        $date = $_GET['date'] ?? date('Y-m-d');

        $rate = $this->rateModel->getEffectiveRate($date);

        if (!$rate) {
            Response::error('No effective rate found for the specified date.');
        }

        Response::success($rate);
    }
}
