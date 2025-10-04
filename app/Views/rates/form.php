<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../../Helpers/url.php';

// Get old input data if available
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

$rate = $data['rate'];
$isEdit = $data['is_edit'];
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0"><?= $isEdit ? 'Edit' : 'Create' ?> Rate</h2>
                <p class="text-muted mb-0">
                    <?= $isEdit ? 'Update rate information' : 'Add a new AG1 and AG2 rate' ?>
                </p>
            </div>
            <a href="<?= url('rates') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Rates
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Form Column -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Rate Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $isEdit ? url('rates/edit') : url('rates/create') ?>" id="rateForm">
                    <?= CSRF::field() ?>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= $rate['id'] ?>">
                        <!-- DEBUG: Editing Rate ID <?= $rate['id'] ?>, Effective: <?= $rate['effective_on'] ?> -->
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="effective_on" class="form-label">Effective Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="effective_on" name="effective_on" 
                                       value="<?= htmlspecialchars($oldInput['effective_on'] ?? $rate['effective_on'] ?? date('Y-m-d')) ?>" 
                                       required>
                                <div class="form-text">Date when this rate becomes effective</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="note" class="form-label">Note</label>
                                <input type="text" class="form-control" id="note" name="note" 
                                       value="<?= htmlspecialchars($oldInput['note'] ?? $rate['note'] ?? '') ?>"
                                       placeholder="Optional note about this rate change">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rate_ag1" class="form-label">AG1 Rate (%) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="rate_ag1" name="rate_ag1" 
                                           value="<?= htmlspecialchars($oldInput['rate_ag1'] ?? ($rate['rate_ag1'] ? $rate['rate_ag1'] * 100 : '')) ?>" 
                                           step="0.01" min="0" max="100" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">First tier percentage rate (0-100%)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="rate_ag2" class="form-label">AG2 Rate (%) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="rate_ag2" name="rate_ag2" 
                                           value="<?= htmlspecialchars($oldInput['rate_ag2'] ?? ($rate['rate_ag2'] ? $rate['rate_ag2'] * 100 : '')) ?>" 
                                           step="0.01" min="0" max="100" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">Second tier percentage rate (0-100%)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-<?= $isEdit ? 'check' : 'plus' ?>-circle me-2"></i>
                            <?= $isEdit ? 'Update' : 'Create' ?> Rate
                        </button>
                        <a href="<?= url('rates') ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Preview Column -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calculator me-2"></i>Rate Preview
                </h5>
            </div>
            <div class="card-body" id="previewContent">
                <div class="text-center text-muted">
                    <i class="bi bi-percent fs-1 d-block mb-2"></i>
                    <p>Enter rates to see calculation preview</p>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Rate Guidelines</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading">Rate Information</h6>
                    <ul class="mb-0 small">
                        <li><strong>AG1:</strong> First tier rate applied to CA amount</li>
                        <li><strong>AG2:</strong> Second tier rate applied to AV1 amount</li>
                        <li><strong>Effective Date:</strong> Must be unique</li>
                        <li><strong>Range:</strong> Both rates must be between 0-100%</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h6 class="alert-heading">Important Notes</h6>
                    <ul class="mb-0 small">
                        <li>Rates affect all transactions on or after the effective date</li>
                        <li>Historical transactions will use the rate that was effective on their date</li>
                        <li>Cannot delete rates that are currently in use</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('rateForm');
    const previewContent = document.getElementById('previewContent');
    
    // Preview update function
    function updatePreview() {
        const ag1 = parseFloat(document.getElementById('rate_ag1').value) || 0;
        const ag2 = parseFloat(document.getElementById('rate_ag2').value) || 0;
        const effectiveDate = document.getElementById('effective_on').value;
        
        if (!ag1 && !ag2) {
            previewContent.innerHTML = `
                <div class="text-center text-muted">
                    <i class="bi bi-percent fs-1 d-block mb-2"></i>
                    <p>Enter rates to see calculation preview</p>
                </div>
            `;
            return;
        }
        
        // Sample calculation with $1000 CA and $50 GA
        const sampleCA = 1000;
        const sampleGA = 50;
        const sampleJE = 25;
        
        const ag1Amount = sampleCA * (ag1 / 100);
        const av1 = sampleCA - ag1Amount;
        const ag2Amount = av1 * (ag2 / 100);
        const av2 = av1 - ag2Amount;
        const re = av2 - sampleGA;
        const fi = re - sampleJE;
        
        previewContent.innerHTML = `
            <div class="mb-3">
                <h6 class="text-primary">Sample Calculation</h6>
                <small class="text-muted">CA: $1,000 | GA: $50 | JE: $25</small>
            </div>
            
            <div class="d-flex justify-content-between mb-2">
                <span>AG1 (${ag1.toFixed(2)}%):</span>
                <span class="fw-semibold">$${ag1Amount.toFixed(2)}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>AV1:</span>
                <span>$${av1.toFixed(2)}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>AG2 (${ag2.toFixed(2)}%):</span>
                <span class="fw-semibold">$${ag2Amount.toFixed(2)}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>AV2:</span>
                <span>$${av2.toFixed(2)}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span>RE:</span>
                <span>$${re.toFixed(2)}</span>
            </div>
            <div class="d-flex justify-content-between border-top pt-2">
                <span class="fw-bold">FI (Final):</span>
                <span class="fw-bold text-success">$${fi.toFixed(2)}</span>
            </div>
            
            ${effectiveDate ? `
            <div class="mt-3 pt-3 border-top">
                <small class="text-muted">
                    <i class="bi bi-calendar me-1"></i>
                    Effective: ${new Date(effectiveDate).toLocaleDateString()}
                </small>
            </div>
            ` : ''}
        `;
    }
    
    // Add event listeners for real-time preview
    ['rate_ag1', 'rate_ag2', 'effective_on'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        field.addEventListener('input', debounce(updatePreview, 300));
        field.addEventListener('change', updatePreview);
    });
    
    // Initial preview if editing
    <?php if ($isEdit): ?>
    setTimeout(updatePreview, 100);
    <?php endif; ?>
    
    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Form validation and conversion
    form.addEventListener('submit', function(e) {
        const ag1Input = document.getElementById('rate_ag1');
        const ag2Input = document.getElementById('rate_ag2');
        const ag1 = parseFloat(ag1Input.value) || 0;
        const ag2 = parseFloat(ag2Input.value) || 0;
        const effectiveDate = document.getElementById('effective_on').value;
        
        if (ag1 < 0 || ag1 > 100) {
            e.preventDefault();
            FormUtils.showError('AG1 rate must be between 0 and 100%.');
            return false;
        }
        
        if (ag2 < 0 || ag2 > 100) {
            e.preventDefault();
            FormUtils.showError('AG2 rate must be between 0 and 100%.');
            return false;
        }
        
        // Convert percentages to decimals before submission
        ag1Input.value = (ag1 / 100).toFixed(4);
        ag2Input.value = (ag2 / 100).toFixed(4);
        
        if (!effectiveDate) {
            e.preventDefault();
            FormUtils.showError('Please select an effective date.');
            return false;
        }
        
        // Check if effective date is not too far in the past
        const selectedDate = new Date(effectiveDate);
        const oneYearAgo = new Date();
        oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1);
        
        if (selectedDate < oneYearAgo) {
            if (!confirm('The effective date is more than a year ago. This may affect many historical transactions. Continue?')) {
                return false;
            }
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
