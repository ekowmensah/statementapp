<?php
$flashMessages = Flash::all();

foreach ($flashMessages as $type => $message):
    $alertClass = match($type) {
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info',
        default => 'alert-info'
    };
    
    $icon = match($type) {
        'success' => 'bi-check-circle',
        'error' => 'bi-exclamation-triangle',
        'warning' => 'bi-exclamation-circle',
        'info' => 'bi-info-circle',
        default => 'bi-info-circle'
    };
?>
<div class="alert <?= $alertClass ?> alert-dismissible fade show" role="alert">
    <i class="<?= $icon ?> me-2"></i>
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-coreui-dismiss="alert" aria-label="Close"></button>
</div>
<?php endforeach; ?>

<?php
// Display validation errors if they exist
if (isset($_SESSION['validation_errors'])):
    $errors = $_SESSION['validation_errors'];
    unset($_SESSION['validation_errors']);
?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi-exclamation-triangle me-2"></i>
    <strong>Validation Errors:</strong>
    <ul class="mb-0 mt-2">
        <?php foreach ($errors as $field => $fieldErrors): ?>
            <?php foreach ($fieldErrors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-coreui-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
