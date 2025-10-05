<?php
/**
 * Company Controller
 * Handles company CRUD operations
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Models/Company.php';
require_once __DIR__ . '/../Helpers/auth.php';
require_once __DIR__ . '/../Helpers/csrf.php';
require_once __DIR__ . '/../Helpers/validate.php';
require_once __DIR__ . '/../Helpers/response.php';

class CompanyController
{
    private $companyModel;

    public function __construct()
    {
        $this->companyModel = new Company();
    }

    /**
     * List all companies with pagination and search
     */
    public function index()
    {
        Auth::requirePermission('view_companies');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 25);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 25;
        $offset = ($page - 1) * $perPage;
        $search = trim($_GET['search'] ?? '');

        // Get companies with pagination
        $result = $this->companyModel->getPaginated($perPage, $offset, $search);
        $companies = $result['companies'];
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

        $data = [
            'title' => 'Companies - Daily Statement App',
            'companies' => $companies,
            'pagination' => $pagination,
            'can_create' => Auth::can('create_companies'),
            'can_edit' => Auth::can('edit_companies'),
            'can_delete' => Auth::can('delete_companies'),
            'search' => $search,
            'per_page' => $perPage
        ];

        include __DIR__ . '/../Views/companies/index.php';
    }

    /**
     * Show create company form
     */
    public function create()
    {
        Auth::requirePermission('create_companies');

        $data = [
            'title' => 'Create Company - Daily Statement App',
            'company' => [
                'name' => '',
                'description' => '',
                'is_active' => 1
            ],
            'csrf_token' => CSRF::getToken(),
            'is_edit' => false
        ];

        include __DIR__ . '/../Views/companies/form.php';
    }

    /**
     * Store new company
     */
    public function store()
    {
        Auth::requirePermission('create_companies');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('companies');
        }

        // Validate CSRF token
        if (!CSRF::validateRequest()) {
            Flash::error('Invalid security token. Please try again.');
            Response::redirect('companies/create');
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Validate input
        $validator = $this->companyModel->validate($data);

        if ($validator->fails()) {
            if (Response::expectsJson()) {
                Response::validationError($validator->errors());
            }

            Flash::error('Please correct the errors below.');
            $_SESSION['old_input'] = $data;
            $_SESSION['validation_errors'] = $validator->errors();
            Response::redirect('companies/create');
        }

        try {
            $data['created_by'] = Auth::id();
            $id = $this->companyModel->create($data);

            if (Response::expectsJson()) {
                $company = $this->companyModel->find($id);
                Response::success($company, 'Company created successfully.');
            }

            Flash::success('Company created successfully.');
            Response::redirect('companies');

        } catch (Exception $e) {
            if (Response::expectsJson()) {
                Response::error('Failed to create company: ' . $e->getMessage());
            }

            Flash::error('Failed to create company. Please try again.');
            Response::redirect('companies/create');
        }
    }

    /**
     * Show edit company form
     */
    public function edit()
    {
        Auth::requirePermission('edit_companies');

        $id = $_GET['id'] ?? null;
        if (!$id) {
            Flash::error('Company not found.');
            Response::redirect('companies');
        }

        $company = $this->companyModel->find($id);
        if (!$company) {
            Flash::error('Company not found.');
            Response::redirect('companies');
        }

        $data = [
            'title' => 'Edit Company - Daily Statement App',
            'company' => $company,
            'csrf_token' => CSRF::getToken(),
            'is_edit' => true
        ];

        include __DIR__ . '/../Views/companies/form.php';
    }

    /**
     * Update company
     */
    public function update()
    {
        Auth::requirePermission('edit_companies');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('companies');
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            Flash::error('Company not found.');
            Response::redirect('companies');
        }

        // Validate CSRF token
        if (!CSRF::validateRequest()) {
            Flash::error('Invalid security token. Please try again.');
            Response::redirect("companies/edit?id={$id}");
        }

        $company = $this->companyModel->find($id);
        if (!$company) {
            Flash::error('Company not found.');
            Response::redirect('companies');
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Validate input
        $validator = $this->companyModel->validate($data, $id);

        if ($validator->fails()) {
            if (Response::expectsJson()) {
                Response::validationError($validator->errors());
            }

            Flash::error('Please correct the errors below.');
            $_SESSION['old_input'] = $data;
            $_SESSION['validation_errors'] = $validator->errors();
            Response::redirect("companies/edit?id={$id}");
        }

        try {
            $data['updated_by'] = Auth::id();
            $this->companyModel->update($id, $data);

            if (Response::expectsJson()) {
                $company = $this->companyModel->find($id);
                Response::success($company, 'Company updated successfully.');
            }

            Flash::success('Company updated successfully.');
            Response::redirect('companies');

        } catch (Exception $e) {
            if (Response::expectsJson()) {
                Response::error('Failed to update company: ' . $e->getMessage());
            }

            Flash::error('Failed to update company. Please try again.');
            Response::redirect("companies/edit?id={$id}");
        }
    }

    /**
     * Show company details
     */
    public function show()
    {
        Auth::requirePermission('view_companies');

        $id = $_GET['id'] ?? null;
        if (!$id) {
            if (Response::expectsJson()) {
                Response::error('Company not found.', null, 404);
            }
            Flash::error('Company not found.');
            Response::redirect('companies');
        }

        $company = $this->companyModel->find($id);
        if (!$company) {
            if (Response::expectsJson()) {
                Response::error('Company not found.', null, 404);
            }
            Flash::error('Company not found.');
            Response::redirect('companies');
        }

        // Get usage count
        $usageCount = $this->companyModel->getUsageCount($id);

        if (Response::expectsJson()) {
            $company['usage_count'] = $usageCount;
            Response::success($company);
        }

        $data = [
            'title' => 'Company Details - Daily Statement App',
            'company' => $company,
            'usage_count' => $usageCount,
            'can_edit' => Auth::can('edit_companies'),
            'can_delete' => Auth::can('delete_companies')
        ];

        include __DIR__ . '/../Views/companies/show.php';
    }

    /**
     * Delete company
     */
    public function delete()
    {
        Auth::requirePermission('delete_companies');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('companies');
        }

        // Validate CSRF token
        if (!CSRF::validateRequest()) {
            Flash::error('Invalid security token. Please try again.');
            Response::redirect('companies');
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            Flash::error('Company not found.');
            Response::redirect('companies');
        }

        $company = $this->companyModel->find($id);
        if (!$company) {
            Flash::error('Company not found.');
            Response::redirect('companies');
        }

        try {
            $this->companyModel->delete($id);

            if (Response::expectsJson()) {
                Response::success(null, 'Company deleted successfully.');
            }

            Flash::success('Company deleted successfully.');
            Response::redirect('companies');

        } catch (Exception $e) {
            if (Response::expectsJson()) {
                Response::error('Failed to delete company: ' . $e->getMessage());
            }

            Flash::error($e->getMessage());
            Response::redirect('companies');
        }
    }

    /**
     * Toggle company active status
     */
    public function toggleActive()
    {
        Auth::requirePermission('edit_companies');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirect('companies');
        }

        // Validate CSRF token
        if (!CSRF::validateRequest()) {
            Flash::error('Invalid security token. Please try again.');
            Response::redirect('companies');
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            Flash::error('Company not found.');
            Response::redirect('companies');
        }

        $company = $this->companyModel->find($id);
        if (!$company) {
            Flash::error('Company not found.');
            Response::redirect('companies');
        }

        try {
            $this->companyModel->toggleActive($id);

            if (Response::expectsJson()) {
                $updatedCompany = $this->companyModel->find($id);
                Response::success($updatedCompany, 'Company status updated successfully.');
            }

            $status = $company['is_active'] ? 'deactivated' : 'activated';
            Flash::success("Company {$status} successfully.");
            Response::redirect('companies');

        } catch (Exception $e) {
            if (Response::expectsJson()) {
                Response::error('Failed to update company status: ' . $e->getMessage());
            }

            Flash::error('Failed to update company status. Please try again.');
            Response::redirect('companies');
        }
    }

    /**
     * Get companies for API (used in dropdowns)
     */
    public function api()
    {
        Auth::requirePermission('view_companies');

        $search = $_GET['search'] ?? '';
        $activeOnly = isset($_GET['active_only']) ? (bool)$_GET['active_only'] : true;

        if ($search) {
            $companies = $this->companyModel->search($search);
        } else {
            $companies = $activeOnly ? $this->companyModel->getActive() : $this->companyModel->getAll();
        }

        Response::success($companies);
    }
}
