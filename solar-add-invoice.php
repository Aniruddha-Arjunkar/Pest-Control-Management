<?php
session_start();

if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header('Location: login.php');
    exit();
}

require_once 'db_connect.php';
mysqli_set_charset($conn, 'utf8mb4');

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$client_id = (int)($_GET['client_id'] ?? 0);
$group_id  = (int)($_GET['group_id'] ?? 0);
$edit_mode = isset($_GET['edit']) && $_GET['edit'] == '1';

if ($client_id <= 0) {
    die('Client ID missing');
}

/* ================= FETCH SOLAR CLIENT ================= */

$stmt = mysqli_prepare($conn, "
    SELECT c.*, v.vendor_name, v.phone AS vendor_phone,
           v.email AS vendor_email, v.address AS vendor_address
    FROM tbl_solar_client c
    LEFT JOIN tbl_solar_vendor v ON c.vendor_id = v.id
    WHERE c.id = ?
    LIMIT 1
");

if (!$stmt) {
    die(mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, 'i', $client_id);
mysqli_stmt_execute($stmt);
$clientResult = mysqli_stmt_get_result($stmt);
$client = mysqli_fetch_assoc($clientResult);
mysqli_stmt_close($stmt);

if (!$client) {
    die('Solar client not found');
}

$is_trader = strtolower(trim($client['added_by'] ?? '')) === 'trader';

$inquiry_id = (int)($client['inquiry_id'] ?? 0);

/* ================= EDIT INVOICE DATA ================= */

$invoice_items = [];
$gst_invoice_no = '';
$created_at = '';

if ($edit_mode) {

    $stmt = mysqli_prepare($conn, "
        SELECT *
        FROM tbl_solar_invoice
        WHERE client_id = ?
        ORDER BY created_at DESC, id DESC
        LIMIT 1
    ");

    mysqli_stmt_bind_param($stmt, 'i', $client_id);
    mysqli_stmt_execute($stmt);
    $invoiceResult = mysqli_stmt_get_result($stmt);
    $invoiceFirst = mysqli_fetch_assoc($invoiceResult);
    mysqli_stmt_close($stmt);

    if ($invoiceFirst) {

        $gst_invoice_no = $invoiceFirst['gst_invoice_no'];
        $created_at = $invoiceFirst['created_at'];

        $stmt = mysqli_prepare($conn, "
            SELECT *
            FROM tbl_solar_invoice
            WHERE client_id = ? AND created_at = ?
            ORDER BY id ASC
        ");

        mysqli_stmt_bind_param($stmt, 'is', $client_id, $created_at);
        mysqli_stmt_execute($stmt);
        $itemsResult = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($itemsResult)) {
            $invoice_items[] = $row;
        }

        mysqli_stmt_close($stmt);
    }
}

/* ================= TRADER ITEMS ================= */

$form_items = [];

if (!empty($invoice_items)) {

    foreach ($invoice_items as $item) {
        $form_items[] = [
            'item_name'      => $item['item_name'] ?? '',
            'hsn'            => $item['hsn'] ?? '',
            'qty'            => $item['qty'] ?? 1,
            'item_total'     => $item['rate'] ?? '',
            'service_charge' => $item['service_charge'] ?? '',
            'gst_mode'       => $item['gst_mode'] ?? 'inclusive',
            'rate'           => $item['cgst_per'] + $item['sgst_per']
        ];
    }

} elseif ($is_trader) {

    $stmt = mysqli_prepare($conn, "
        SELECT *
        FROM tbl_solar_trading_history
        WHERE client_id = ? AND group_id = ?
        ORDER BY id ASC
    ");

    mysqli_stmt_bind_param($stmt, 'ii', $client_id, $group_id);
    mysqli_stmt_execute($stmt);
    $tradingResult = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($tradingResult)) {

        $form_items[] = [
            'item_name'      => $row['item_name'] ?? '',
            'hsn'            => $row['hsn_code'] ?? '',
            'qty'            => $row['qty'] ?? 1,
            'item_total'     => $row['amount'] ?? '',
            'service_charge' => $row['amount'] ?? '',
            'gst_mode'       => $row['gst_type'] ?? 'inclusive',
            'rate'           => $row['gst_rate'] ?? 18
        ];
    }

    mysqli_stmt_close($stmt);
}

if (empty($form_items)) {
    $form_items[] = [
        'item_name' => '',
        'hsn' => '',
        'qty' => 1,
        'item_total' => '',
        'service_charge' => '',
        'gst_mode' => 'inclusive',
        'rate' => 18
    ];
}

/* ================= HEADER ================= */

include 'header.php';
?>

<section id="solar-main-content">
<section class="outer-wrapper">

<div class="solar-reg-w3">
<div class="solar-form-card">

<div class="form-header">
    <div class="form-card-title">
        ☀️ <?= $edit_mode ? 'Update' : 'Add' ?> Solar Invoice
    </div>
</div>

<div style="padding:20px;">

<form action="solar-save-invoice.php" method="POST" target="_blank">

<input type="hidden" name="client_id" value="<?= $client_id ?>">
<input type="hidden" name="group_id" value="<?= $group_id ?>">
<input type="hidden" name="inquiry_id" value="<?= $inquiry_id ?>">
<input type="hidden" name="gst_invoice_no" value="<?= e($gst_invoice_no) ?>">
<input type="hidden" name="created_at" value="<?= e($created_at) ?>">
<input type="hidden" name="edit_mode" value="<?= $edit_mode ? 1 : 0 ?>">
<input type="hidden" name="is_trader" value="<?= $is_trader ? 1 : 0 ?>">

<!-- CLIENT DETAILS -->

<h4 class="solar-section-title">Client Details</h4>

<div class="actual-form-row">

    <div class="actual-form-group">
        <label>Client Name</label>
        <input type="text" value="<?= e($client['cust_name'] ?? '') ?>" readonly>
    </div>

    <div class="actual-form-group">
        <label>Phone</label>
        <input type="text" value="<?= e($client['cust_phone'] ?? '') ?>" readonly>
    </div>

    <div class="actual-form-group">
        <label>Email</label>
        <input type="text" value="<?= e($client['cust_email'] ?? '') ?>" readonly>
    </div>

</div>

<div class="actual-form-row">

    <div class="actual-form-group">
        <label>Billing Address</label>
        <textarea name="billing_address" rows="3"><?= e($client['billing_address'] ?? $client['cust_address'] ?? '') ?></textarea>
    </div>

    <div class="actual-form-group">
        <label>Shipping Address</label>
        <textarea name="shipping_address" rows="3"><?= e($client['shipping_address'] ?? $client['cust_address'] ?? '') ?></textarea>
    </div>

</div>

<!-- VENDOR DETAILS -->

<?php if (!empty($client['vendor_name'])): ?>

<h4 class="solar-section-title">Vendor Details</h4>

<div class="actual-form-row">

    <div class="actual-form-group">
        <label>Vendor Name</label>
        <input type="text" value="<?= e($client['vendor_name']) ?>" readonly>
    </div>

    <div class="actual-form-group">
        <label>Vendor Phone</label>
        <input type="text" value="<?= e($client['vendor_phone']) ?>" readonly>
    </div>

    <div class="actual-form-group">
        <label>Vendor Email</label>
        <input type="text" value="<?= e($client['vendor_email']) ?>" readonly>
    </div>

</div>

<?php endif; ?>

<!-- INVOICE DETAILS -->

<h4 class="solar-section-title">Invoice Information</h4>

<div class="actual-form-row">

    <div class="actual-form-group">
        <label>Invoice Date</label>
        <input type="date" name="invoice_date"
               value="<?= e($invoice_items[0]['invoice_date'] ?? date('Y-m-d')) ?>"
               required>
    </div>

    <div class="actual-form-group">
        <label>Due Date</label>
        <input type="date" name="due_date"
               value="<?= e($invoice_items[0]['due_date'] ?? date('Y-m-d')) ?>"
               required>
    </div>

    <div class="actual-form-group">
        <label>GST Invoice Number</label>
        <input type="text" value="<?= e($gst_invoice_no ?: 'Auto Generate') ?>" readonly>
    </div>

</div>

<!-- ITEM DETAILS -->

<h4 class="solar-section-title">Invoice Items</h4>

<div id="invoiceItems">

<?php foreach ($form_items as $index => $item): ?>

<div class="invoice-item-row" style="border:1px solid #ddd;padding:15px;margin-bottom:15px;border-radius:8px;">

    <div class="actual-form-row">

        <div class="actual-form-group">
            <label>Item / Service Name</label>
            <input type="text" name="item_name[]" class="item-name"
                   value="<?= e($item['item_name']) ?>" required>
        </div>

        <div class="actual-form-group">
            <label>HSN / SAC</label>
            <input type="text" name="hsn[]"
                   value="<?= e($item['hsn']) ?>">
        </div>

        <div class="actual-form-group">
            <label>Quantity</label>
            <input type="number" name="qty[]" class="qty"
                   value="<?= e($item['qty']) ?>" min="1" step="1"
                   oninput="calculateInvoice(this)" required>
        </div>

    </div>

    <?php if ($is_trader): ?>

    <div class="actual-form-row">

        <div class="actual-form-group">
            <label>Rate / Unit</label>
            <input type="number" name="service_charge[]" class="service-charge"
                   value="<?= e($item['service_charge']) ?>"
                   min="0" step="0.01"
                   oninput="calculateInvoice(this)" required>
        </div>

        <div class="actual-form-group">
            <label>GST Mode</label>
            <select name="gst_mode[]" class="gst-mode"
                    onchange="calculateInvoice(this)">
                <option value="inclusive" <?= $item['gst_mode'] == 'inclusive' ? 'selected' : '' ?>>Inclusive</option>
                <option value="exclusive" <?= $item['gst_mode'] == 'exclusive' ? 'selected' : '' ?>>Exclusive</option>
            </select>
        </div>

    </div>

    <?php else: ?>

    <div class="actual-form-row">

        <div class="actual-form-group">
            <label>Item Amount</label>
            <input type="number" name="item_total[]" class="item-total"
                   value="<?= e($item['item_total']) ?>"
                   min="0" step="0.01"
                   oninput="calculateInvoice(this)" required>
        </div>

        <div class="actual-form-group">
            <label>GST Mode</label>
            <select name="gst_mode[]" class="gst-mode"
                    onchange="calculateInvoice(this)">
                <option value="inclusive" <?= $item['gst_mode'] == 'inclusive' ? 'selected' : '' ?>>Inclusive</option>
                <option value="exclusive" <?= $item['gst_mode'] == 'exclusive' ? 'selected' : '' ?>>Exclusive</option>
            </select>
        </div>

    </div>

    <?php endif; ?>

    <div class="actual-form-row">

        <div class="actual-form-group">
            <label>GST Rate</label>
            <select name="rate[]" class="gst-rate"
                    onchange="calculateInvoice(this)">
                <option value="5" <?= (float)$item['rate'] == 5 ? 'selected' : '' ?>>5%</option>
                <option value="18" <?= (float)$item['rate'] == 18 ? 'selected' : '' ?>>18%</option>
            </select>
        </div>

        <div class="actual-form-group">
            <label>Taxable Amount</label>
            <input type="text" class="taxable-amount" readonly>
        </div>

        <div class="actual-form-group">
            <label>GST Amount</label>
            <input type="text" class="gst-amount" readonly>
        </div>

        <div class="actual-form-group">
            <label>Total Amount</label>
            <input type="text" class="final-amount" readonly>
        </div>

    </div>

    <button type="button" class="remove-item"
            onclick="removeItem(this)"
            style="background:#dc3545;color:white;border:0;padding:8px 15px;border-radius:5px;">
        Remove Item
    </button>

</div>

<?php endforeach; ?>

</div>

<button type="button" onclick="addItem()"
        style="background:#198754;color:white;border:0;padding:10px 18px;border-radius:5px;margin-bottom:20px;">
    + Add More Item
</button>

<div class="actual-form-row">
    <div class="actual-form-group">
        <label>Grand Total</label>
        <input type="text" id="grandTotal" value="0.00" readonly>
    </div>
</div>

<div class="from-submit-btn">
    <button type="submit" name="save_invoice">
        <?= $edit_mode ? 'Update Invoice' : 'Generate Invoice' ?>
    </button>

    <a href="solar-view-client-detail.php?client_id=<?= $client_id ?>"
       style="margin-left:10px;">
        Cancel
    </a>
</div>

</form>
</div>
</div>
</div>

</section>
</section>

<style>
.solar-section-title {
    font-size:18px;
    font-weight:600;
    color:#245b46;
    border-bottom:1px solid #ddd;
    padding-bottom:10px;
    margin:25px 0 18px;
}
.actual-form-group {
    flex:1;
    min-width:180px;
    margin-bottom:15px;
}
.actual-form-group label {
    display:block;
    margin-bottom:7px;
    font-weight:600;
}
.actual-form-group input,
.actual-form-group select,
.actual-form-group textarea {
    width:100%;
    padding:10px;
    border:1px solid #ccc;
    border-radius:5px;
    box-sizing:border-box;
}
.actual-form-row {
    display:flex;
    gap:15px;
    flex-wrap:wrap;
}
.from-submit-btn button {
    background:#198754;
    color:white;
    padding:11px 25px;
    border:0;
    border-radius:5px;
    cursor:pointer;
}
@media(max-width:768px) {
    .actual-form-row {display:block;}
}
</style>

<script>
function calculateInvoice(element) {
    const row = element.closest('.invoice-item-row');
    if (!row) return;

    const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
    const gstRate = parseFloat(row.querySelector('.gst-rate')?.value) || 0;
    const mode = row.querySelector('.gst-mode')?.value || 'inclusive';

    let enteredAmount = 0;

    const serviceInput = row.querySelector('.service-charge');

    if (serviceInput) {
        enteredAmount = qty * (parseFloat(serviceInput.value) || 0);
    } else {
        enteredAmount = parseFloat(row.querySelector('.item-total')?.value) || 0;
    }

    let taxable = enteredAmount;

    if (mode === 'inclusive') {
        taxable = enteredAmount / (1 + gstRate / 100);
    }

    const gst = taxable * gstRate / 100;
    const total = taxable + gst;

    row.querySelector('.taxable-amount').value = taxable.toFixed(2);
    row.querySelector('.gst-amount').value = gst.toFixed(2);
    row.querySelector('.final-amount').value = total.toFixed(2);

    calculateGrandTotal();
}

function calculateGrandTotal() {
    let total = 0;

    document.querySelectorAll('.final-amount').forEach(input => {
        total += parseFloat(input.value) || 0;
    });

    document.getElementById('grandTotal').value = total.toFixed(2);
}

function addItem() {
    const container = document.getElementById('invoiceItems');
    const firstRow = container.querySelector('.invoice-item-row');
    const newRow = firstRow.cloneNode(true);

    newRow.querySelectorAll('input').forEach(input => {
        if (input.classList.contains('qty')) {
            input.value = 1;
        } else {
            input.value = '';
        }
    });

    newRow.querySelectorAll('select').forEach(select => {
        if (select.classList.contains('gst-mode')) {
            select.value = 'inclusive';
        } else if (select.classList.contains('gst-rate')) {
            select.value = '18';
        }
    });

    container.appendChild(newRow);
    calculateInvoice(newRow.querySelector('.qty'));
}

function removeItem(button) {
    const rows = document.querySelectorAll('.invoice-item-row');

    if (rows.length <= 1) {
        alert('At least one item is required');
        return;
    }

    button.closest('.invoice-item-row').remove();
    calculateGrandTotal();
}

document.querySelectorAll('.invoice-item-row').forEach(row => {
    calculateInvoice(row.querySelector('.qty'));
});
</script>

</body>
</html>
