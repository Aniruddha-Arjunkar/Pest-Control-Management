<?php
session_start();

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    $_SESSION['msg'] = "You must log in first";
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';
mysqli_set_charset($conn, "utf8mb4");

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================
   GET CLIENT AND GROUP ID
========================= */
$client_id = (int)($_GET['client_id'] ?? 0);
$group_id  = (int)($_GET['group_id'] ?? 0);

if ($client_id <= 0) {
    die("Invalid Client ID");
}

/* =========================
   CLIENT DETAILS
========================= */
$stmt = mysqli_prepare($conn, "
    SELECT id, cust_name
    FROM tbl_solar_client
    WHERE id = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $client_id);
mysqli_stmt_execute($stmt);

$client = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$client) {
    die("Client Not Found");
}

$is_edit = $group_id > 0;

$items = [];
$trading_date = date('Y-m-d');

/* =========================
   EDIT COMPLETE GROUP
========================= */
if ($is_edit) {

    $groupStmt = mysqli_prepare($conn, "
        SELECT id, trading_date
        FROM tbl_solar_trading_groups
        WHERE id = ? AND client_id = ?
        LIMIT 1
    ");

    mysqli_stmt_bind_param($groupStmt, "ii", $group_id, $client_id);
    mysqli_stmt_execute($groupStmt);

    $group = mysqli_fetch_assoc(mysqli_stmt_get_result($groupStmt));

    if (!$group) {
        die("Trading Group Not Found");
    }

    $trading_date = $group['trading_date'];

    $itemStmt = mysqli_prepare($conn, "
        SELECT *
        FROM tbl_solar_trading_history
        WHERE group_id = ? AND client_id = ?
        ORDER BY id ASC
    ");

    mysqli_stmt_bind_param($itemStmt, "ii", $group_id, $client_id);
    mysqli_stmt_execute($itemStmt);

    $result = mysqli_stmt_get_result($itemStmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }

    if (!$items) {
        die("No Items Found In This Group");
    }

} else {

    /* =========================
       NEW TRADING
    ========================= */
    $items[] = [
        'item_name' => '',
        'qty' => '',
        'hsn_code' => '',
        'amount' => '',
        'gst_rate' => 5,
        'gst_type' => 'exclusive',
        'description' => ''
    ];
}

include('header.php');
?>

<div class="reg-w3">

    <div class="refollowup-card">

        <!-- HEADER -->
        <div class="refollowup-header">

            <h2 class="refollowup-title">
                <?= $is_edit ? 'Edit Complete Trading Group' : 'Add Trading Details'; ?>
            </h2>

        </div>

        <div class="card-body">

            <!-- CLIENT DETAILS -->
            <div class="form-row">

                <div class="form-group">

                    <label>Client Name</label>

                    <input type="text"
                           class="form-control"
                           value="<?= e($client['cust_name']); ?>"
                           readonly>

                </div>

                <?php if ($is_edit): ?>

                    <div class="form-group">

                        <label>Trading Group ID</label>

                        <input type="text"
                               class="form-control"
                               value="<?= $group_id; ?>"
                               readonly>

                    </div>

                <?php endif; ?>

            </div>

            <hr>

            <form action="solar-add-trading-code.php" method="POST"
                  class="refollowup-form">

                <input type="hidden"
                       name="client_id"
                       value="<?= $client_id; ?>">

                <?php if ($is_edit): ?>

                    <input type="hidden"
                           name="group_id"
                           value="<?= $group_id; ?>">

                    <input type="hidden"
                           name="update_trading_group"
                           value="1">

                <?php else: ?>

                    <input type="hidden"
                           name="add_trading"
                           value="1">

                <?php endif; ?>

                <!-- TRADING DATE -->
                <div class="form-row">

                    <div class="form-group">

                        <label>Trading Date <span class="text-danger">*</span></label>

                        <input type="date"
                               name="trading_date"
                               class="form-control"
                               value="<?= e($trading_date); ?>"
                               required>

                    </div>

                </div>

                <hr>

                <!-- ITEMS -->
                <h4>Trading Items</h4>

                <div id="items-container">

                    <?php foreach ($items as $item): ?>

                        <div class="item-row refollowup-card mb-3">

                            <div class="form-row">

                                <div class="form-group">

                                    <label>Item Name <span class="text-danger">*</span></label>

                                    <input type="text"
                                           name="item_name[]"
                                           class="form-control"
                                           value="<?= e($item['item_name']); ?>"
                                           required>

                                </div>

                                <div class="form-group">

                                    <label>Quantity <span class="text-danger">*</span></label>

                                    <input type="number"
                                           name="qty[]"
                                           class="form-control qty"
                                           step="0.01"
                                           min="0.01"
                                           value="<?= e($item['qty']); ?>"
                                           required>

                                </div>

                                <div class="form-group">

                                    <label>HSN Code</label>

                                    <input type="text"
                                           name="hsn_code[]"
                                           class="form-control"
                                           value="<?= e($item['hsn_code']); ?>">

                                </div>

                                <div class="form-group">

                                    <label>Amount Per Item <span class="text-danger">*</span></label>

                                    <input type="number"
                                           name="amount[]"
                                           class="form-control amount"
                                           step="0.01"
                                           min="0"
                                           value="<?= e($item['amount']); ?>"
                                           required>

                                </div>

                                <div class="form-group">

                                    <label>GST</label>

                                    <select name="gst_rate[]"
                                            class="form-control gst-rate">

                                        <option value="5"
                                            <?= (float)$item['gst_rate'] == 5 ? 'selected' : ''; ?>>
                                            5%
                                        </option>

                                        <option value="18"
                                            <?= (float)$item['gst_rate'] == 18 ? 'selected' : ''; ?>>
                                            18%
                                        </option>

                                    </select>

                                </div>

                                <div class="form-group">

                                    <label>GST Type</label>

                                    <select name="gst_type[]"
                                            class="form-control gst-type">

                                        <option value="exclusive"
                                            <?= $item['gst_type'] == 'exclusive' ? 'selected' : ''; ?>>
                                            Exclusive
                                        </option>

                                        <option value="inclusive"
                                            <?= $item['gst_type'] == 'inclusive' ? 'selected' : ''; ?>>
                                            Inclusive
                                        </option>

                                    </select>

                                </div>

                                <div class="form-group">

                                    <label>Description</label>

                                    <textarea name="description[]"
                                              class="form-control"
                                              rows="2"><?= e($item['description']); ?></textarea>

                                </div>

                            </div>

                            <!-- ITEM CALCULATION -->
                            <div class="text-right mb-3">

                                <strong>Base:</strong>
                                ₹<span class="item-base">0.00</span>

                                &nbsp; | &nbsp;

                                <strong>GST:</strong>
                                ₹<span class="item-gst">0.00</span>

                                &nbsp; | &nbsp;

                                <strong>Total:</strong>
                                ₹<span class="item-total">0.00</span>

                            </div>

                            <div class="text-right">

                                <button type="button"
                                        class="remove-item btn btn-danger btn-sm">

                                    <i class="bi bi-trash"></i>
                                    Remove Item

                                </button>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <!-- ADD ITEM BUTTON -->
                <button type="button"
                        id="add-item"
                        class="btn btn-success">

                    <i class="bi bi-plus-circle"></i>
                    Add More Item

                </button>

                <hr>

                <!-- TOTAL SUMMARY -->
                <div class="refollowup-card">

                    <h4>Amount Summary</h4>

                    <div class="form-row">

                        <div class="form-group">

                            <label>Base Amount</label>

                            <h4>₹<span id="base">0.00</span></h4>

                        </div>

                        <div class="form-group">

                            <label>GST Amount</label>

                            <h4>₹<span id="gst">0.00</span></h4>

                        </div>

                        <div class="form-group">

                            <label>Grand Total</label>

                            <h4>₹<span id="total">0.00</span></h4>

                        </div>

                    </div>

                </div>

                <!-- ACTION BUTTONS -->
                <div class="form-row mt-3">

                    <div class="form-group">

                        <button type="submit" class="submit-btn">

                            <i class="bi bi-save"></i>

                            <?= $is_edit ? 'Update Complete Group' : 'Save Trading'; ?>

                        </button>

                        <a href="solar-view-trader-details.php?client_id=<?= $client_id; ?>"
                           class="btn btn-secondary">

                            Cancel

                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

const container = document.getElementById('items-container');
const addButton = document.getElementById('add-item');

/* =========================
   ADD MORE ITEM
========================= */
addButton.addEventListener('click', function() {

    const first = container.querySelector('.item-row');
    const clone = first.cloneNode(true);

    clone.querySelectorAll('input, textarea').forEach(el => {
        el.value = '';
    });

    clone.querySelectorAll('select').forEach(el => {
        el.selectedIndex = 0;
    });

    clone.querySelector('.item-base').innerText = '0.00';
    clone.querySelector('.item-gst').innerText = '0.00';
    clone.querySelector('.item-total').innerText = '0.00';

    container.appendChild(clone);

    calculateTotal();

});

/* =========================
   REMOVE ITEM
========================= */
container.addEventListener('click', function(e) {

    const button = e.target.closest('.remove-item');

    if (!button) return;

    const rows = container.querySelectorAll('.item-row');

    if (rows.length > 1) {

        button.closest('.item-row').remove();

        calculateTotal();

    } else {

        alert('At least one item is required.');

    }

});

/* =========================
   CALCULATE TOTAL
========================= */
container.addEventListener('input', calculateTotal);
container.addEventListener('change', calculateTotal);

function calculateTotal() {

    let totalBase = 0;
    let totalGST = 0;
    let grandTotal = 0;

    container.querySelectorAll('.item-row').forEach(row => {

        let qty = parseFloat(row.querySelector('.qty').value) || 0;

        let amount = parseFloat(row.querySelector('.amount').value) || 0;

        let rate = parseFloat(row.querySelector('.gst-rate').value) || 0;

        let type = row.querySelector('.gst-type').value;

        let inputAmount = qty * amount;

        let base = 0;
        let gst = 0;
        let total = 0;

        if (type === 'inclusive') {

            total = inputAmount;

            base = total / (1 + rate / 100);

            gst = total - base;

        } else {

            base = inputAmount;

            gst = base * rate / 100;

            total = base + gst;

        }

        base = Math.round(base * 100) / 100;

        gst = Math.round(gst * 100) / 100;

        total = Math.round(total * 100) / 100;

        row.querySelector('.item-base').innerText = base.toFixed(2);

        row.querySelector('.item-gst').innerText = gst.toFixed(2);

        row.querySelector('.item-total').innerText = total.toFixed(2);

        totalBase += base;

        totalGST += gst;

        grandTotal += total;

    });

    document.getElementById('base').innerText = totalBase.toFixed(2);

    document.getElementById('gst').innerText = totalGST.toFixed(2);

    document.getElementById('total').innerText = grandTotal.toFixed(2);

}

calculateTotal();

</script>

<!-- FOOTER SCRIPTS -->
<script src="js/bootstrap.js"></script>
<script src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/scripts.js"></script>
<script src="js/jquery.slimscroll.js"></script>
<script src="js/jquery.nicescroll.js"></script>
<script src="js/jquery.scrollTo.js"></script>
<script src="js/search.js"></script>

</body>
</html>
