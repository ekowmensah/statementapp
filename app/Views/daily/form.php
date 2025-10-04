<?php
require_once __DIR__ . '/../layouts/header.php';

// Helper function for correct URLs
function appUrl($path) {
    return Response::url($path);
}

// Get old input data if available
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

$transaction = $data['transaction'];
$isEdit = $data['is_edit'];
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0"><?= $isEdit ? 'Edit' : 'Create' ?> Daily Transaction</h2>
                <p class="text-muted mb-0">
                    <?= $isEdit ? 'Update transaction details' : 'Add a new daily transaction' ?>
                </p>
            </div>
            <a href="<?= appUrl('daily') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to List
            </a>
        </div>
    </div>
</div>

<div class="row">
    <!-- Form Column -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Transaction Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $isEdit ? appUrl('daily/edit') : appUrl('daily/create') ?>" id="transactionForm">
                    <?= CSRF::field() ?>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= $transaction['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="txn_date" class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="txn_date" name="txn_date" 
                                       value="<?= htmlspecialchars($oldInput['txn_date'] ?? $transaction['txn_date']) ?>" 
                                       required>
                                <div class="form-text">Select the date for this transaction</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="note" class="form-label">Note</label>
                                <input type="text" class="form-control" id="note" name="note" 
                                       value="<?= htmlspecialchars($oldInput['note'] ?? $transaction['note'] ?? '') ?>"
                                       placeholder="Optional note or description">
                            </div>
                        </div>
                    </div>
                    
                    <!-- First Row: CA Amount, AG1 %, AG2 % -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="ca" class="form-label">CA Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control money-input" id="ca" name="ca" 
                                           value="<?= Money::formatForInput($oldInput['ca'] ?? $transaction['ca']) ?>" 
                                           step="0.01" min="0" required>
                                </div>
                                <div class="form-text">Gross inflow amount</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="rate_ag1" class="form-label">AG1 (%) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="rate_ag1" name="rate_ag1" 
                                           value="<?= htmlspecialchars($oldInput['rate_ag1'] ?? (isset($transaction['rate_ag1']) && $transaction['rate_ag1'] ? number_format($transaction['rate_ag1'] * 100, 2) : '21')) ?>" 
                                           step="0.01" min="0" max="100" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">First tier percentage rate</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="rate_ag2" class="form-label">AG2 (%) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="rate_ag2" name="rate_ag2" 
                                           value="<?= htmlspecialchars($oldInput['rate_ag2'] ?? (isset($transaction['rate_ag2']) && $transaction['rate_ag2'] ? number_format($transaction['rate_ag2'] * 100, 2) : '4')) ?>" 
                                           step="0.01" min="0" max="100" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">Second tier percentage rate</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Second Row: GA Amount, JE Amount -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ga" class="form-label">GA Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control money-input" id="ga" name="ga" 
                                           value="<?= Money::formatForInput($oldInput['ga'] ?? $transaction['ga']) ?>" 
                                           step="0.01" min="0" required>
                                </div>
                                <div class="form-text">Daily fixed deduction</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="je" class="form-label">JE Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control money-input" id="je" name="je" 
                                           value="<?= Money::formatForInput($oldInput['je'] ?? ($transaction['je'] ?? 0)) ?>" 
                                           step="0.01" min="0" required>
                                </div>
                                <div class="form-text">Journal entry/expense</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-<?= $isEdit ? 'check' : 'plus' ?>-circle me-2"></i>
                            <?= $isEdit ? 'Update' : 'Create' ?> Transaction
                        </button>
                        <a href="<?= appUrl('daily') ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Preview Column -->
    <div class="col-md-4">
        <div class="card preview-card">
            <div class="card-header text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calculator me-2"></i>Live Preview
                </h5>
            </div>
            <div class="card-body" id="previewContent">
                <div class="text-center text-white-50">
                    <i class="bi bi-hourglass-split fs-1 d-block mb-2"></i>
                    <p>Enter transaction details to see computed values</p>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Rate Information</h6>
            </div>
            <div class="card-body" id="rateInfo">
                <div class="text-center text-muted">
                    <i class="bi bi-percent fs-1 d-block mb-2"></i>
                    <p>Select a date to see applicable rates</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('transactionForm');
    const previewContent = document.getElementById('previewContent');
    const rateInfo = document.getElementById('rateInfo');
    
    // Preview update function
    function updatePreview() {
        const date = document.getElementById('txn_date').value;
        const ca = document.getElementById('ca').value;
        const ga = document.getElementById('ga').value;
        const je = document.getElementById('je').value;
        const rateAg1 = document.getElementById('rate_ag1').value;
        const rateAg2 = document.getElementById('rate_ag2').value;
        
        if (!date || !ca || !ga || je === '' || !rateAg1 || !rateAg2) {
            previewContent.innerHTML = `
                <div class="text-center text-white-50">
                    <i class="bi bi-hourglass-split fs-1 d-block mb-2"></i>
                    <p>Enter transaction details to see computed values</p>
                </div>
            `;
            return;
        }
        
        // Show loading
        previewContent.innerHTML = `
            <div class="text-center text-white-50">
                <div class="spinner-border spinner-border-sm me-2"></div>
                <span>Calculating...</span>
            </div>
        `;
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const headers = {
            'Content-Type': 'application/json'
        };
        
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
        }
        
        // Make API call
        fetch('<?= appUrl('api/preview') ?>', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({
                date: date,
                ca: parseFloat(ca) || 0,
                ga: parseFloat(ga) || 0,
                je: parseFloat(je) || 0,
                rate_ag1: parseFloat(rateAg1) / 100 || 0,
                rate_ag2: parseFloat(rateAg2) / 100 || 0
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const computed = data.data;
                
                // Simple money formatting function
                const formatMoney = (amount) => {
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'USD'
                    }).format(amount || 0);
                };
                
                previewContent.innerHTML = `
                    <div class="preview-item">
                        <span>CA:</span>
                        <span>${formatMoney(computed.ca)}</span>
                    </div>
                    <div class="preview-item">
                        <span>AG1 (${(computed.rate_ag1 * 100).toFixed(2)}%):</span>
                        <span>${formatMoney(computed.ag1)}</span>
                    </div>
                    <div class="preview-item">
                        <span>AV1:</span>
                        <span>${formatMoney(computed.av1)}</span>
                    </div>
                    <div class="preview-item">
                        <span>AG2 (${(computed.rate_ag2 * 100).toFixed(2)}%):</span>
                        <span>${formatMoney(computed.ag2)}</span>
                    </div>
                    <div class="preview-item">
                        <span>AV2:</span>
                        <span>${formatMoney(computed.av2)}</span>
                    </div>
                    <div class="preview-item">
                        <span>GA:</span>
                        <span>${formatMoney(computed.ga)}</span>
                    </div>
                    <div class="preview-item">
                        <span>RE:</span>
                        <span>${formatMoney(computed.re)}</span>
                    </div>
                    <div class="preview-item">
                        <span>JE:</span>
                        <span>${formatMoney(computed.je)}</span>
                    </div>
                    <div class="preview-item">
                        <span>FI:</span>
                        <span>${formatMoney(computed.fi)}</span>
                    </div>
                `;
                
                // Update rate info with form values
                rateInfo.innerHTML = `
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Transaction Date:</span>
                        <span class="fw-semibold">${date}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">AG1 Rate:</span>
                        <span class="fw-semibold">${(computed.rate_ag1 * 100).toFixed(2)}%</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">AG2 Rate:</span>
                        <span class="fw-semibold">${(computed.rate_ag2 * 100).toFixed(2)}%</span>
                    </div>
                `;
            } else {
                previewContent.innerHTML = `
                    <div class="text-center text-white-50">
                        <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                        <p>Error: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Preview error:', error);
            console.error('Request URL:', '<?= appUrl('api/preview') ?>');
            console.error('CSRF Token found:', !!csrfToken);
            console.error('Form data sent:', {
                date: date,
                ca: parseFloat(ca) || 0,
                ga: parseFloat(ga) || 0,
                je: parseFloat(je) || 0,
                rate_ag1: parseFloat(rateAg1) / 100 || 0,
                rate_ag2: parseFloat(rateAg2) / 100 || 0
            });
            previewContent.innerHTML = `
                <div class="text-center text-white-50">
                    <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                    <p>Failed to calculate preview</p>
                    <small>Check console for details</small>
                </div>
            `;
        });
    }
    
    // Add event listeners for real-time preview
    ['txn_date', 'ca', 'ga', 'je', 'rate_ag1', 'rate_ag2'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        field.addEventListener('input', debounce(updatePreview, 500));
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
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const ca = parseFloat(document.getElementById('ca').value) || 0;
        const ga = parseFloat(document.getElementById('ga').value) || 0;
        const je = parseFloat(document.getElementById('je').value) || 0;
        const ag1Rate = parseFloat(document.getElementById('rate_ag1').value) || 0;
        const ag2Rate = parseFloat(document.getElementById('rate_ag2').value) || 0;
        
        if (ca < 0 || ga < 0 || je < 0) {
            e.preventDefault();
            alert('All amounts must be greater than or equal to 0.');
            return false;
        }
        
        if (ag1Rate < 0 || ag1Rate > 100) {
            e.preventDefault();
            alert('AG1 rate must be between 0 and 100%.');
            return false;
        }
        
        if (ag2Rate < 0 || ag2Rate > 100) {
            e.preventDefault();
            alert('AG2 rate must be between 0 and 100%.');
            return false;
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
