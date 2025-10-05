<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path) {
    return Response::url($path);
}

// Get old input data if available
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

$company = $data['company'];
$isEdit = $data['is_edit'];
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0"><?= $isEdit ? 'Edit' : 'Create' ?> Company</h2>
                <p class="text-muted mb-0">
                    <?= $isEdit ? 'Update company details' : 'Add a new company for transaction categorization' ?>
                </p>
            </div>
            <a href="<?= appUrl('companies') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Companies
            </a>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Company Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $isEdit ? appUrl('companies/edit') : appUrl('companies/create') ?>" id="companyForm">
                    <?= CSRF::field() ?>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= $company['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Company Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($oldInput['name'] ?? $company['name']) ?>" 
                                       required maxlength="100">
                                <div class="form-text">Enter a unique company name (2-100 characters)</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?= ($oldInput['is_active'] ?? $company['is_active']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">
                                        Active
                                    </label>
                                </div>
                                <div class="form-text">Active companies appear in transaction dropdowns</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="3" maxlength="255" 
                                  placeholder="Optional description of the company or business type"><?= htmlspecialchars($oldInput['description'] ?? $company['description'] ?? '') ?></textarea>
                        <div class="form-text">Optional description (max 255 characters)</div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-<?= $isEdit ? 'check' : 'plus' ?>-circle me-2"></i>
                            <?= $isEdit ? 'Update' : 'Create' ?> Company
                        </button>
                        <a href="<?= appUrl('companies') ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($isEdit && isset($company['id'])): ?>
        <!-- Usage Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">Usage Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Created:</span>
                            <span class="fw-semibold"><?= date('M j, Y g:i A', strtotime($company['created_at'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Created By:</span>
                            <span class="fw-semibold"><?= htmlspecialchars($company['created_by_name'] ?? 'System') ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <?php if ($company['updated_at'] && $company['updated_at'] !== $company['created_at']): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Last Updated:</span>
                            <span class="fw-semibold"><?= date('M j, Y g:i A', strtotime($company['updated_at'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Updated By:</span>
                            <span class="fw-semibold"><?= htmlspecialchars($company['updated_by_name'] ?? 'System') ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <hr>
                
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> This company is currently used in 
                    <span class="fw-bold" id="usageCount">loading...</span> transaction(s).
                    <?php if (Auth::can('delete_companies')): ?>
                    Companies with transactions cannot be deleted, but can be deactivated.
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('companyForm');
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        
        if (name.length < 2) {
            e.preventDefault();
            alert('Company name must be at least 2 characters long.');
            return false;
        }
        
        if (name.length > 100) {
            e.preventDefault();
            alert('Company name cannot exceed 100 characters.');
            return false;
        }
    });
    
    // Character counter for description
    const description = document.getElementById('description');
    const maxLength = 255;
    
    // Create character counter
    const counter = document.createElement('div');
    counter.className = 'form-text text-end';
    counter.id = 'descriptionCounter';
    description.parentNode.appendChild(counter);
    
    function updateCounter() {
        const remaining = maxLength - description.value.length;
        counter.textContent = `${description.value.length}/${maxLength} characters`;
        counter.className = remaining < 20 ? 'form-text text-end text-warning' : 'form-text text-end text-muted';
    }
    
    description.addEventListener('input', updateCounter);
    updateCounter(); // Initial count
    
    <?php if ($isEdit && isset($company['id'])): ?>
    // Load usage count for edit mode
    fetch('<?= appUrl('api/companies/' . $company['id'] . '/usage') ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('usageCount').textContent = data.data.usage_count || 0;
            }
        })
        .catch(error => {
            console.error('Error loading usage count:', error);
            document.getElementById('usageCount').textContent = 'unknown';
        });
    <?php endif; ?>
});
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
